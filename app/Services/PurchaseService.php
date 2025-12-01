<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\RawMaterial;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * PurchaseService
 * 
 * Handles all purchase-related business logic for RAW MATERIALS.
 * 
 * Note: This service is for raw material purchases only.
 * Products are manufactured through Production Plans, not purchased.
 * 
 * Business Flow:
 * Raw Materials (Purchased) → Production Plans → Products (Manufactured) → Sales
 */

class PurchaseService
{
    /**
     * Create a new purchase order for raw materials
     */
    public function createRawMaterialPurchase(array $data): Purchase
    {
        return DB::transaction(function () use ($data) {
            $totalAmount = $this->calculateTotalAmount($data['items']);
            
            $purchase = Purchase::create([
                'purchase_number' => $this->generatePurchaseNumber(),
                'supplier_id' => $data['supplier_id'],
                'purchase_date' => $data['purchase_date'] ?? now(),
                'total_amount' => $totalAmount,
                'status' => $data['status'] ?? 'pending',
            ]);

            foreach ($data['items'] as $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];
                
                $purchase->items()->create([
                    'raw_material_id' => $item['raw_material_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_amount' => $lineTotal,
                ]);
            }

            // If status is received, update stock immediately
            if (($data['status'] ?? 'pending') === 'received') {
                $this->receiveStock($purchase);
            }

            return $purchase;
        });
    }

    /**
     * Update an existing purchase
     */
    public function updatePurchase(Purchase $purchase, array $data): Purchase
    {
        return DB::transaction(function () use ($purchase, $data) {
            $oldStatus = $purchase->status;
            
            // Delete existing items
            $purchase->items()->delete();
            
            $totalAmount = $this->calculateTotalAmount($data['items']);
            
            $purchase->update([
                'supplier_id' => $data['supplier_id'],
                'purchase_date' => $data['purchase_date'] ?? $purchase->purchase_date,
                'total_amount' => $totalAmount,
                'status' => $data['status'] ?? $purchase->status,
            ]);

            foreach ($data['items'] as $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];
                
                $purchase->items()->create([
                    'raw_material_id' => $item['raw_material_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_amount' => $lineTotal,
                ]);
            }

            // Handle status change to received
            if ($oldStatus !== 'received' && ($data['status'] ?? $purchase->status) === 'received') {
                $this->receiveStock($purchase);
            }

            return $purchase->fresh(['items.rawMaterial', 'supplier']);
        });
    }

    /**
     * Receive stock from purchase - updates raw material quantities
     */
    public function receiveStock(Purchase $purchase): void
    {
        foreach ($purchase->items as $item) {
            if ($item->raw_material_id) {
                $rawMaterial = RawMaterial::find($item->raw_material_id);
                if ($rawMaterial) {
                    // Update quantity
                    $rawMaterial->quantity += $item->quantity;
                    
                    // Update cost per unit using weighted average
                    $rawMaterial->cost_per_unit = $this->calculateWeightedAverageCost(
                        $rawMaterial,
                        $item->quantity,
                        $item->unit_price
                    );
                    
                    $rawMaterial->save();

                    // Record stock movement
                    $this->recordStockMovement($rawMaterial, $item, $purchase);
                }
            }
        }

        // Update purchase status
        $purchase->update(['status' => 'received']);
    }

    /**
     * Calculate weighted average cost for raw material
     */
    protected function calculateWeightedAverageCost(RawMaterial $material, float $newQty, float $newPrice): float
    {
        $existingValue = $material->quantity * $material->cost_per_unit;
        $newValue = $newQty * $newPrice;
        $totalQty = $material->quantity + $newQty;
        
        if ($totalQty <= 0) {
            return $newPrice;
        }
        
        return ($existingValue + $newValue) / $totalQty;
    }

    /**
     * Record stock movement for audit trail
     */
    protected function recordStockMovement(RawMaterial $material, PurchaseItem $item, Purchase $purchase): void
    {
        try {
            StockMovement::create([
                'raw_material_id' => $material->id,
                'type' => 'purchase',
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'reference_type' => Purchase::class,
                'reference_id' => $purchase->id,
                'notes' => "Purchase #{$purchase->purchase_number}",
                'created_by' => Auth::id(),
            ]);
        } catch (\Exception $e) {
            // Log error but don't fail the purchase - stock movement is for audit only
            \Log::warning("Failed to record stock movement: " . $e->getMessage());
        }
    }

    /**
     * Calculate total amount from items
     */
    protected function calculateTotalAmount(array $items): float
    {
        return collect($items)->sum(function ($item) {
            return $item['quantity'] * $item['unit_price'];
        });
    }

    /**
     * Generate unique purchase number
     */
    protected function generatePurchaseNumber(): string
    {
        $prefix = 'PO';
        $date = now()->format('Ymd');
        $lastPurchase = Purchase::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastPurchase ? ((int) substr($lastPurchase->purchase_number, -4)) + 1 : 1;
        
        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    /**
     * Cancel a purchase order
     */
    public function cancelPurchase(Purchase $purchase): bool
    {
        if ($purchase->status === 'received') {
            // Reverse stock if already received
            $this->reverseStock($purchase);
        }

        $purchase->update(['status' => 'cancelled']);
        return true;
    }

    /**
     * Reverse stock from a cancelled/returned purchase
     */
    protected function reverseStock(Purchase $purchase): void
    {
        foreach ($purchase->items as $item) {
            if ($item->raw_material_id) {
                $rawMaterial = RawMaterial::find($item->raw_material_id);
                if ($rawMaterial) {
                    $rawMaterial->quantity = max(0, $rawMaterial->quantity - $item->quantity);
                    $rawMaterial->save();

                    // Record reversal movement
                    try {
                        StockMovement::create([
                            'raw_material_id' => $rawMaterial->id,
                            'type' => 'purchase_reversal',
                            'quantity' => -$item->quantity,
                            'unit_price' => $item->unit_price,
                            'reference_type' => Purchase::class,
                            'reference_id' => $purchase->id,
                            'notes' => "Reversal of Purchase #{$purchase->purchase_number}",
                            'created_by' => Auth::id(),
                        ]);
                    } catch (\Exception $e) {
                        \Log::warning("Failed to record stock reversal: " . $e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * Get purchase statistics
     */
    public function getStatistics(string $period = 'month'): array
    {
        $startDate = match($period) {
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        $purchases = Purchase::where('created_at', '>=', $startDate)->get();

        return [
            'total_purchases' => $purchases->count(),
            'total_amount' => $purchases->sum('total_amount'),
            'pending_count' => $purchases->where('status', 'pending')->count(),
            'received_count' => $purchases->where('status', 'received')->count(),
            'cancelled_count' => $purchases->where('status', 'cancelled')->count(),
            'average_order_value' => $purchases->avg('total_amount') ?? 0,
        ];
    }

    /**
     * Get low stock raw materials that need reordering
     */
    public function getLowStockMaterials(): \Illuminate\Database\Eloquent\Collection
    {
        return RawMaterial::whereColumn('quantity', '<=', 'minimum_stock_level')
            ->orderBy('quantity')
            ->get();
    }

    /**
     * Generate suggested purchase order based on low stock
     */
    public function generateSuggestedOrder(?int $supplierId = null): array
    {
        $query = RawMaterial::whereColumn('quantity', '<=', 'minimum_stock_level');
        
        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        $lowStockMaterials = $query->get();

        return $lowStockMaterials->map(function ($material) {
            // Suggest ordering enough to reach 2x minimum stock level
            $suggestedQty = max(
                $material->minimum_stock_level * 2 - $material->quantity,
                $material->minimum_stock_level
            );

            return [
                'raw_material_id' => $material->id,
                'name' => $material->name,
                'current_stock' => $material->quantity,
                'minimum_stock' => $material->minimum_stock_level,
                'suggested_quantity' => $suggestedQty,
                'unit' => $material->unit,
                'unit_price' => $material->cost_per_unit,
                'estimated_cost' => $suggestedQty * $material->cost_per_unit,
                'supplier_id' => $material->supplier_id,
            ];
        })->toArray();
    }
}
