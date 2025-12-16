<?php

namespace App\Services;

use App\Models\RawMaterial;
use App\Models\RawMaterialUsage;
use App\Models\StockMovement;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * RawMaterialService
 * 
 * Handles all raw material business logic including:
 * - Stock management (adjustments, transfers)
 * - Cost calculations (weighted average, FIFO)
 * - Usage tracking and forecasting
 * - Reorder point calculations
 * - Supplier performance tracking
 */
class RawMaterialService
{
    /**
     * Create a new raw material with initial stock
     */
    public function create(array $data): RawMaterial
    {
        return DB::transaction(function () use ($data) {
            $material = RawMaterial::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'sku' => $data['sku'] ?? $this->generateSku($data['name']),
                'quantity' => $data['quantity'] ?? 0,
                'unit' => $data['unit'],
                'cost_per_unit' => $data['cost_per_unit'] ?? 0,
                'supplier_id' => $data['supplier_id'] ?? null,
                'minimum_stock_level' => $data['minimum_stock_level'] ?? 0,
                'reorder_quantity' => $data['reorder_quantity'] ?? null,
                'location' => $data['location'] ?? null,
                'category' => $data['category'] ?? null,
            ]);

            // Record initial stock if quantity > 0
            if (($data['quantity'] ?? 0) > 0) {
                $this->recordStockMovement($material, [
                    'type' => 'initial',
                    'quantity' => $data['quantity'],
                    'unit_price' => $data['cost_per_unit'] ?? 0,
                    'notes' => 'Initial stock entry',
                ]);
            }

            return $material;
        });
    }

    /**
     * Update raw material
     */
    public function update(RawMaterial $material, array $data): RawMaterial
    {
        return DB::transaction(function () use ($material, $data) {
            $oldQuantity = $material->quantity;
            $oldCost = $material->cost_per_unit;

            $material->update($data);

            // Record stock adjustment if quantity changed manually
            if (isset($data['quantity']) && $data['quantity'] != $oldQuantity) {
                $difference = $data['quantity'] - $oldQuantity;
                $this->recordStockMovement($material, [
                    'type' => 'adjustment',
                    'quantity' => $difference,
                    'unit_price' => $material->cost_per_unit,
                    'notes' => $data['adjustment_reason'] ?? 'Manual stock adjustment',
                ]);
            }

            return $material->fresh();
        });
    }

    /**
     * Adjust stock quantity with reason
     */
    public function adjustStock(RawMaterial $material, float $quantity, string $reason, string $type = 'adjustment'): RawMaterial
    {
        return DB::transaction(function () use ($material, $quantity, $reason, $type) {
            $material->quantity += $quantity;
            $material->save();

            $this->recordStockMovement($material, [
                'type' => $type,
                'quantity' => $quantity,
                'unit_price' => $material->cost_per_unit,
                'notes' => $reason,
            ]);

            return $material->fresh();
        });
    }

    /**
     * Deduct stock for production usage
     */
    public function deductForProduction(RawMaterial $material, float $quantity, ?int $productionPlanId = null, ?string $notes = null): bool
    {
        if ($material->quantity < $quantity) {
            throw new \Exception("Insufficient stock. Available: {$material->quantity}, Required: {$quantity}");
        }

        return DB::transaction(function () use ($material, $quantity, $productionPlanId, $notes) {
            $material->quantity -= $quantity;
            $material->save();

            // Record usage
            RawMaterialUsage::create([
                'raw_material_id' => $material->id,
                'production_plan_id' => $productionPlanId,
                'quantity_used' => $quantity,
                'unit' => $material->unit,
                'cost_per_unit' => $material->cost_per_unit,
                'total_cost' => $quantity * $material->cost_per_unit,
                'usage_date' => now(),
                'notes' => $notes,
            ]);

            $this->recordStockMovement($material, [
                'type' => 'usage',
                'quantity' => -$quantity,
                'unit_price' => $material->cost_per_unit,
                'reference_type' => $productionPlanId ? 'App\Models\ProductionPlan' : null,
                'reference_id' => $productionPlanId,
                'notes' => $notes ?? 'Production usage',
            ]);

            return true;
        });
    }

    /**
     * Calculate weighted average cost after purchase
     */
    public function calculateWeightedAverageCost(RawMaterial $material, float $newQuantity, float $newPrice): float
    {
        $existingValue = $material->quantity * $material->cost_per_unit;
        $newValue = $newQuantity * $newPrice;
        $totalQuantity = $material->quantity + $newQuantity;

        if ($totalQuantity <= 0) {
            return $newPrice;
        }

        return round(($existingValue + $newValue) / $totalQuantity, 4);
    }

    /**
     * Get stock valuation
     */
    public function getStockValuation(RawMaterial $material): array
    {
        return [
            'quantity' => $material->quantity,
            'unit_cost' => $material->cost_per_unit,
            'total_value' => $material->quantity * $material->cost_per_unit,
            'unit' => $material->unit,
        ];
    }

    /**
     * Get all materials stock valuation
     */
    public function getTotalStockValuation(): array
    {
        $materials = RawMaterial::all();
        
        $totalValue = $materials->sum(fn($m) => $m->quantity * $m->cost_per_unit);
        $totalItems = $materials->count();
        $lowStockCount = $materials->filter(fn($m) => $m->isLowStock())->count();

        return [
            'total_value' => $totalValue,
            'total_items' => $totalItems,
            'low_stock_count' => $lowStockCount,
            'by_category' => $materials->groupBy('category')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'value' => $group->sum(fn($m) => $m->quantity * $m->cost_per_unit),
                ];
            }),
        ];
    }

    /**
     * Get usage statistics for a material
     */
    public function getUsageStatistics(RawMaterial $material, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $usages = $material->usages()
            ->whereBetween('usage_date', [$startDate, $endDate])
            ->get();

        $totalUsed = $usages->sum('quantity_used');
        $totalCost = $usages->sum('total_cost');
        $usageCount = $usages->count();
        $days = $startDate->diffInDays($endDate) ?: 1;

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'days' => $days,
            ],
            'total_used' => $totalUsed,
            'total_cost' => $totalCost,
            'usage_count' => $usageCount,
            'daily_average' => round($totalUsed / $days, 2),
            'average_cost_per_use' => $usageCount > 0 ? round($totalCost / $usageCount, 2) : 0,
        ];
    }

    /**
     * Calculate reorder point based on usage history
     */
    public function calculateReorderPoint(RawMaterial $material, int $leadTimeDays = 7, float $safetyStockDays = 3): array
    {
        // Get average daily usage from last 30 days
        $stats = $this->getUsageStatistics($material, now()->subDays(30), now());
        $dailyUsage = $stats['daily_average'];

        $reorderPoint = ($dailyUsage * $leadTimeDays) + ($dailyUsage * $safetyStockDays);
        $suggestedReorderQty = $dailyUsage * 14; // 2 weeks supply

        return [
            'daily_usage' => $dailyUsage,
            'lead_time_days' => $leadTimeDays,
            'safety_stock_days' => $safetyStockDays,
            'reorder_point' => round($reorderPoint, 2),
            'suggested_reorder_quantity' => round($suggestedReorderQty, 2),
            'current_stock' => $material->quantity,
            'days_of_stock' => $dailyUsage > 0 ? round($material->quantity / $dailyUsage, 1) : null,
            'needs_reorder' => $material->quantity <= $reorderPoint,
        ];
    }

    /**
     * Get materials that need reordering
     */
    public function getMaterialsNeedingReorder(): \Illuminate\Database\Eloquent\Collection
    {
        return RawMaterial::whereColumn('quantity', '<=', 'minimum_stock_level')
            ->with('supplier')
            ->orderBy('quantity')
            ->get();
    }

    /**
     * Get purchase history for a material
     */
    public function getPurchaseHistory(RawMaterial $material, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return PurchaseItem::where('raw_material_id', $material->id)
            ->with(['purchase.supplier'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get stock movement history
     */
    public function getStockMovements(RawMaterial $material, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return StockMovement::where('raw_material_id', $material->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get price trend for a material
     */
    public function getPriceTrend(RawMaterial $material, int $months = 6): array
    {
        $purchases = PurchaseItem::where('raw_material_id', $material->id)
            ->whereHas('purchase', function ($q) use ($months) {
                $q->where('purchase_date', '>=', now()->subMonths($months));
            })
            ->with('purchase')
            ->get()
            ->groupBy(fn($item) => $item->purchase->purchase_date->format('Y-m'));

        return $purchases->map(function ($items, $month) {
            $avgPrice = $items->avg('unit_price');
            $totalQty = $items->sum('quantity');
            return [
                'month' => $month,
                'average_price' => round($avgPrice, 2),
                'total_quantity' => $totalQty,
                'purchase_count' => $items->count(),
            ];
        })->values()->toArray();
    }

    /**
     * Record stock movement for audit trail
     */
    protected function recordStockMovement(RawMaterial $material, array $data): void
    {
        try {
            StockMovement::create([
                'raw_material_id' => $material->id,
                'type' => $data['type'] ?? 'adjustment',
                'quantity' => $data['quantity'],
                'unit_price' => $data['unit_price'] ?? $material->cost_per_unit,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);
        } catch (\Exception $e) {
            Log::warning("Failed to record stock movement: " . $e->getMessage());
        }
    }

    /**
     * Generate SKU for material
     */
    protected function generateSku(string $name): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $name), 0, 3));
        $random = strtoupper(substr(uniqid(), -4));
        return "RM-{$prefix}-{$random}";
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(): array
    {
        $materials = RawMaterial::all();
        $lowStock = $materials->filter(fn($m) => $m->isLowStock());
        
        // This month's usage
        $monthUsage = 0;
        try {
            $monthUsage = RawMaterialUsage::whereMonth('usage_date', now()->month)
                ->whereYear('usage_date', now()->year)
                ->sum('total_cost');
        } catch (\Exception $e) {
            // Table might not exist
        }

        // This month's purchases
        $monthPurchases = 0;
        try {
            $monthPurchases = PurchaseItem::whereHas('purchase', function ($q) {
                $q->whereMonth('purchase_date', now()->month)
                  ->whereYear('purchase_date', now()->year)
                  ->where('status', 'received');
            })->sum('total_amount');
        } catch (\Exception $e) {
            // Column might not exist
        }

        return [
            'total_materials' => $materials->count(),
            'total_stock_value' => $materials->sum(fn($m) => $m->quantity * $m->cost_per_unit),
            'low_stock_count' => $lowStock->count(),
            'out_of_stock_count' => $materials->filter(fn($m) => $m->quantity <= 0)->count(),
            'month_usage_cost' => $monthUsage,
            'month_purchase_cost' => $monthPurchases,
            'low_stock_materials' => $lowStock->take(5)->map(fn($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'quantity' => $m->quantity,
                'minimum' => $m->minimum_stock_level,
                'unit' => $m->unit,
            ])->values(),
        ];
    }

    /**
     * Search materials
     */
    public function search(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = RawMaterial::with('supplier');

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (!empty($filters['low_stock'])) {
            $query->whereColumn('quantity', '<=', 'minimum_stock_level');
        }

        $sortBy = $filters['sort_by'] ?? 'name';
        $sortDir = $filters['sort_dir'] ?? 'asc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($filters['per_page'] ?? 15)->withQueryString();
    }
}
