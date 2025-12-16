<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\RawMaterial;
use App\Models\StockMovement;
use App\Models\Supplier;
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
 * 
 * Key Features:
 * - Create/Update purchase orders
 * - Receive stock with weighted average cost calculation
 * - Stock movement tracking for audit trail
 * - Low stock alerts and suggested orders
 * - Supplier performance tracking
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
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $lineTotal = floatval($item['quantity']) * floatval($item['unit_price']);
                
                $purchase->items()->create([
                    'raw_material_id' => $item['raw_material_id'],
                    'quantity' => floatval($item['quantity']),
                    'unit_price' => floatval($item['unit_price']),
                    'total_amount' => $lineTotal,
                ]);
            }

            // Reload items
            $purchase->load('items');

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
            Log::warning("Failed to record stock movement: " . $e->getMessage());
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
                        Log::warning("Failed to record stock reversal: " . $e->getMessage());
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

    /**
     * Approve a pending purchase order
     */
    public function approvePurchase(Purchase $purchase): Purchase
    {
        if ($purchase->status !== Purchase::STATUS_PENDING) {
            throw new \Exception('Only pending purchases can be approved.');
        }

        $purchase->update([
            'status' => Purchase::STATUS_APPROVED,
        ]);

        return $purchase->fresh();
    }

    /**
     * Confirm a purchase order (approve and mark ready for receiving)
     */
    public function confirmPurchase(Purchase $purchase): Purchase
    {
        if (!in_array($purchase->status, [Purchase::STATUS_PENDING, Purchase::STATUS_APPROVED])) {
            throw new \Exception('This purchase order cannot be confirmed.');
        }

        $purchase->update([
            'status' => Purchase::STATUS_APPROVED,
            'confirmed_at' => now(),
        ]);

        return $purchase->fresh();
    }

    /**
     * Partially receive stock from purchase
     */
    public function partialReceive(Purchase $purchase, array $receivedItems): Purchase
    {
        return DB::transaction(function () use ($purchase, $receivedItems) {
            $allReceived = true;
            
            foreach ($receivedItems as $itemId => $receivedQty) {
                $item = $purchase->items()->find($itemId);
                if (!$item) continue;
                
                $receivedQty = floatval($receivedQty);
                if ($receivedQty <= 0) continue;
                
                // Get raw material
                $rawMaterial = RawMaterial::find($item->raw_material_id);
                if (!$rawMaterial) continue;
                
                // Update raw material stock
                $rawMaterial->quantity += $receivedQty;
                $rawMaterial->cost_per_unit = $this->calculateWeightedAverageCost(
                    $rawMaterial,
                    $receivedQty,
                    $item->unit_price
                );
                $rawMaterial->save();
                
                // Record stock movement
                $this->recordStockMovementWithQty($rawMaterial, $item, $purchase, $receivedQty);
                
                // Update item received quantity
                $item->received_quantity = ($item->received_quantity ?? 0) + $receivedQty;
                $item->save();
                
                // Check if all items fully received
                if ($item->received_quantity < $item->quantity) {
                    $allReceived = false;
                }
            }
            
            // Update purchase status
            $purchase->update([
                'status' => $allReceived ? Purchase::STATUS_RECEIVED : Purchase::STATUS_PARTIAL,
            ]);
            
            return $purchase->fresh(['items.rawMaterial']);
        });
    }

    /**
     * Record stock movement with custom quantity
     */
    protected function recordStockMovementWithQty(RawMaterial $material, PurchaseItem $item, Purchase $purchase, float $quantity): void
    {
        try {
            StockMovement::create([
                'raw_material_id' => $material->id,
                'type' => 'purchase',
                'quantity' => $quantity,
                'unit_price' => $item->unit_price,
                'reference_type' => Purchase::class,
                'reference_id' => $purchase->id,
                'notes' => "Purchase #{$purchase->purchase_number} (Partial: {$quantity})",
                'created_by' => Auth::id(),
            ]);
        } catch (\Exception $e) {
            Log::warning("Failed to record stock movement: " . $e->getMessage());
        }
    }

    /**
     * Get supplier purchase history
     */
    public function getSupplierPurchaseHistory(int $supplierId, int $limit = 10): array
    {
        $purchases = Purchase::where('supplier_id', $supplierId)
            ->with(['items.rawMaterial'])
            ->orderBy('purchase_date', 'desc')
            ->limit($limit)
            ->get();

        return [
            'purchases' => $purchases,
            'total_orders' => Purchase::where('supplier_id', $supplierId)->count(),
            'total_amount' => Purchase::where('supplier_id', $supplierId)
                ->where('status', Purchase::STATUS_RECEIVED)
                ->sum('total_amount'),
            'average_order_value' => Purchase::where('supplier_id', $supplierId)
                ->where('status', Purchase::STATUS_RECEIVED)
                ->avg('total_amount') ?? 0,
        ];
    }

    /**
     * Get material purchase history
     */
    public function getMaterialPurchaseHistory(int $materialId, int $months = 6): array
    {
        $startDate = now()->subMonths($months);
        
        $items = PurchaseItem::where('raw_material_id', $materialId)
            ->whereHas('purchase', function ($q) use ($startDate) {
                $q->where('purchase_date', '>=', $startDate)
                  ->where('status', Purchase::STATUS_RECEIVED);
            })
            ->with(['purchase.supplier'])
            ->get();

        // Group by month for trend analysis
        $monthlyData = $items->groupBy(function ($item) {
            return $item->purchase->purchase_date->format('Y-m');
        })->map(function ($group) {
            return [
                'quantity' => $group->sum('quantity'),
                'total_cost' => $group->sum('total_amount'),
                'avg_price' => $group->avg('unit_price'),
                'orders' => $group->count(),
            ];
        });

        return [
            'items' => $items,
            'monthly_data' => $monthlyData,
            'total_quantity' => $items->sum('quantity'),
            'total_cost' => $items->sum('total_amount'),
            'average_price' => $items->avg('unit_price') ?? 0,
            'min_price' => $items->min('unit_price') ?? 0,
            'max_price' => $items->max('unit_price') ?? 0,
        ];
    }

    /**
     * Calculate reorder suggestions based on usage patterns
     */
    public function calculateReorderSuggestions(): array
    {
        $materials = RawMaterial::with('supplier')->get();
        $suggestions = [];

        foreach ($materials as $material) {
            // Get average daily usage from last 30 days
            $usageData = $this->getMaterialUsageRate($material->id, 30);
            $dailyUsage = $usageData['daily_average'];
            
            // Calculate days of stock remaining
            $daysOfStock = $dailyUsage > 0 ? $material->quantity / $dailyUsage : null;
            
            // Lead time assumption (7 days default)
            $leadTime = 7;
            $safetyStock = $dailyUsage * 3; // 3 days safety stock
            
            // Reorder point
            $reorderPoint = ($dailyUsage * $leadTime) + $safetyStock;
            
            // Check if needs reorder
            if ($material->quantity <= $reorderPoint || $material->quantity <= $material->minimum_stock_level) {
                $suggestedQty = max(
                    $dailyUsage * 14, // 2 weeks supply
                    $material->minimum_stock_level * 2 - $material->quantity,
                    $material->minimum_stock_level
                );

                $suggestions[] = [
                    'material' => $material,
                    'current_stock' => $material->quantity,
                    'minimum_stock' => $material->minimum_stock_level,
                    'reorder_point' => round($reorderPoint, 2),
                    'daily_usage' => round($dailyUsage, 2),
                    'days_of_stock' => $daysOfStock ? round($daysOfStock, 1) : null,
                    'suggested_quantity' => round($suggestedQty, 2),
                    'estimated_cost' => round($suggestedQty * $material->cost_per_unit, 2),
                    'urgency' => $this->calculateUrgency($material->quantity, $reorderPoint, $material->minimum_stock_level),
                ];
            }
        }

        // Sort by urgency
        usort($suggestions, function ($a, $b) {
            $urgencyOrder = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
            return ($urgencyOrder[$a['urgency']] ?? 4) <=> ($urgencyOrder[$b['urgency']] ?? 4);
        });

        return $suggestions;
    }

    /**
     * Get material usage rate
     */
    protected function getMaterialUsageRate(int $materialId, int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        $totalUsage = \App\Models\RawMaterialUsage::where('raw_material_id', $materialId)
            ->where('usage_date', '>=', $startDate)
            ->sum('quantity_used');

        return [
            'total_usage' => $totalUsage,
            'daily_average' => $totalUsage / $days,
            'period_days' => $days,
        ];
    }

    /**
     * Calculate urgency level for reorder
     */
    protected function calculateUrgency(float $currentStock, float $reorderPoint, float $minimumStock): string
    {
        if ($currentStock <= 0) {
            return 'critical';
        }
        if ($currentStock <= $minimumStock * 0.5) {
            return 'critical';
        }
        if ($currentStock <= $minimumStock) {
            return 'high';
        }
        if ($currentStock <= $reorderPoint) {
            return 'medium';
        }
        return 'low';
    }

    /**
     * Create purchase from production plan requirements
     */
    public function createFromProductionRequirements(array $requirements, int $supplierId): Purchase
    {
        $items = [];
        
        foreach ($requirements as $req) {
            $material = RawMaterial::find($req['raw_material_id']);
            if (!$material) continue;
            
            // Only include materials from this supplier
            if ($material->supplier_id !== $supplierId) continue;
            
            $shortage = ($req['total_required'] ?? 0) - $material->quantity;
            if ($shortage > 0) {
                $items[] = [
                    'raw_material_id' => $material->id,
                    'quantity' => $shortage,
                    'unit_price' => $material->cost_per_unit,
                ];
            }
        }

        if (empty($items)) {
            throw new \Exception('No items to purchase from this supplier.');
        }

        return $this->createRawMaterialPurchase([
            'supplier_id' => $supplierId,
            'purchase_date' => now(),
            'status' => 'pending',
            'items' => $items,
        ]);
    }

    /**
     * Get purchase dashboard data
     */
    public function getDashboardData(): array
    {
        $today = now();
        $startOfMonth = $today->copy()->startOfMonth();
        $startOfLastMonth = $today->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $today->copy()->subMonth()->endOfMonth();

        // This month stats
        $thisMonthPurchases = Purchase::where('purchase_date', '>=', $startOfMonth)->get();
        $lastMonthPurchases = Purchase::whereBetween('purchase_date', [$startOfLastMonth, $endOfLastMonth])->get();

        // Pending purchases
        $pendingPurchases = Purchase::whereIn('status', [Purchase::STATUS_PENDING, Purchase::STATUS_APPROVED])
            ->with(['supplier', 'items'])
            ->orderBy('purchase_date')
            ->get();

        // Low stock materials
        $lowStockMaterials = $this->getLowStockMaterials();

        // Top suppliers this month
        $topSuppliers = Purchase::where('purchase_date', '>=', $startOfMonth)
            ->where('status', Purchase::STATUS_RECEIVED)
            ->selectRaw('supplier_id, SUM(total_amount) as total, COUNT(*) as orders')
            ->groupBy('supplier_id')
            ->orderByDesc('total')
            ->limit(5)
            ->with('supplier')
            ->get();

        return [
            'this_month' => [
                'total_purchases' => $thisMonthPurchases->count(),
                'total_amount' => $thisMonthPurchases->sum('total_amount'),
                'received_count' => $thisMonthPurchases->where('status', Purchase::STATUS_RECEIVED)->count(),
            ],
            'last_month' => [
                'total_purchases' => $lastMonthPurchases->count(),
                'total_amount' => $lastMonthPurchases->sum('total_amount'),
            ],
            'pending_purchases' => $pendingPurchases,
            'low_stock_materials' => $lowStockMaterials,
            'top_suppliers' => $topSuppliers,
            'reorder_suggestions' => $this->calculateReorderSuggestions(),
        ];
    }
}
