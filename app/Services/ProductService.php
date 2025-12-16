<?php

namespace App\Services;

use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\StockMovement;
use App\Models\SaleItem;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * ProductService
 * 
 * Handles all product-related business logic including:
 * - Stock management
 * - Cost calculations
 * - Sales analytics
 * - Production requirements
 */
class ProductService
{
    /**
     * Get product with full analytics
     */
    public function getProductWithAnalytics(Product $product): array
    {
        $product->load(['category', 'rawMaterials', 'orderItems', 'productionPlanItems']);

        return [
            'product' => $product,
            'stock_status' => $this->getStockStatus($product),
            'cost_breakdown' => $this->getCostBreakdown($product),
            'sales_analytics' => $this->getSalesAnalytics($product),
            'production_info' => $this->getProductionInfo($product),
            'profit_analysis' => $this->getProfitAnalysis($product),
        ];
    }

    /**
     * Get stock status for a product
     */
    public function getStockStatus(Product $product): array
    {
        $minimumStock = $product->minimum_stock_level ?? 10;
        $currentStock = $product->quantity;

        $status = 'normal';
        if ($currentStock <= 0) {
            $status = 'out_of_stock';
        } elseif ($currentStock <= $minimumStock) {
            $status = 'low_stock';
        } elseif ($currentStock <= $minimumStock * 1.5) {
            $status = 'warning';
        }

        // Calculate days of stock based on average daily sales
        $avgDailySales = $this->getAverageDailySales($product->id, 30);
        $daysOfStock = $avgDailySales > 0 ? round($currentStock / $avgDailySales, 1) : null;

        return [
            'current_stock' => $currentStock,
            'minimum_stock' => $minimumStock,
            'status' => $status,
            'status_label' => $this->getStatusLabel($status),
            'status_class' => $this->getStatusClass($status),
            'days_of_stock' => $daysOfStock,
            'avg_daily_sales' => round($avgDailySales, 2),
            'reorder_needed' => $currentStock <= $minimumStock,
            'suggested_production' => $this->getSuggestedProductionQuantity($product),
        ];
    }

    /**
     * Get cost breakdown for a product
     */
    public function getCostBreakdown(Product $product): array
    {
        $rawMaterialCost = $product->getTotalRawMaterialCost();
        $directCost = $product->cost ?? 0;
        
        // Calculate labor cost (estimated as percentage of material cost)
        $laborCostPercentage = 0.15; // 15% of material cost
        $laborCost = $rawMaterialCost * $laborCostPercentage;
        
        // Overhead (estimated)
        $overheadPercentage = 0.10; // 10% of material cost
        $overheadCost = $rawMaterialCost * $overheadPercentage;
        
        $totalCost = $rawMaterialCost + $laborCost + $overheadCost;
        
        return [
            'raw_material_cost' => round($rawMaterialCost, 2),
            'labor_cost' => round($laborCost, 2),
            'overhead_cost' => round($overheadCost, 2),
            'total_cost' => round($totalCost, 2),
            'recorded_cost' => $directCost,
            'cost_difference' => round($totalCost - $directCost, 2),
            'materials' => $product->rawMaterials->map(function ($material) {
                $costPerUnit = $material->pivot->cost_per_unit ?? $material->cost_per_unit;
                $qtyWithWaste = $material->pivot->quantity_required * (1 + ($material->pivot->waste_percentage / 100));
                return [
                    'name' => $material->name,
                    'quantity' => $material->pivot->quantity_required,
                    'unit' => $material->pivot->unit,
                    'waste_percentage' => $material->pivot->waste_percentage,
                    'cost_per_unit' => $costPerUnit,
                    'total_cost' => round($qtyWithWaste * $costPerUnit, 2),
                ];
            }),
        ];
    }

    /**
     * Get sales analytics for a product
     */
    public function getSalesAnalytics(Product $product, int $months = 6): array
    {
        $startDate = Carbon::now()->subMonths($months)->startOfMonth();
        
        // Monthly sales data - use correct date function based on database driver
        $dateFormat = DB::connection()->getDriverName() === 'sqlite' 
            ? 'strftime("%Y-%m", sales.sale_date)' 
            : 'DATE_FORMAT(sales.sale_date, "%Y-%m")';
        
        $monthlySales = SaleItem::where('product_id', $product->id)
            ->whereHas('sale', function ($q) use ($startDate) {
                $q->where('sale_date', '>=', $startDate);
            })
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->selectRaw("{$dateFormat} as month, SUM(sale_items.quantity) as qty, SUM(sale_items.quantity * sale_items.unit_price) as revenue")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Total stats
        $totalSold = SaleItem::where('product_id', $product->id)->sum('quantity');
        $totalRevenue = SaleItem::where('product_id', $product->id)
            ->selectRaw('SUM(quantity * unit_price) as total')
            ->value('total') ?? 0;

        // This month vs last month
        $thisMonthSales = SaleItem::where('product_id', $product->id)
            ->whereHas('sale', function ($q) {
                $q->whereMonth('sale_date', now()->month)
                  ->whereYear('sale_date', now()->year);
            })
            ->sum('quantity');

        $lastMonthSales = SaleItem::where('product_id', $product->id)
            ->whereHas('sale', function ($q) {
                $q->whereMonth('sale_date', now()->subMonth()->month)
                  ->whereYear('sale_date', now()->subMonth()->year);
            })
            ->sum('quantity');

        $salesChange = $lastMonthSales > 0 
            ? (($thisMonthSales - $lastMonthSales) / $lastMonthSales) * 100 
            : 0;

        return [
            'total_sold' => $totalSold,
            'total_revenue' => round($totalRevenue, 2),
            'this_month_sales' => $thisMonthSales,
            'last_month_sales' => $lastMonthSales,
            'sales_change_percentage' => round($salesChange, 1),
            'monthly_data' => $monthlySales,
            'avg_monthly_sales' => $months > 0 ? round($totalSold / $months, 1) : 0,
        ];
    }

    /**
     * Get production information
     */
    public function getProductionInfo(Product $product): array
    {
        // Check raw material availability
        $canProduce = true;
        $maxProducible = PHP_INT_MAX;
        $materialShortages = [];

        foreach ($product->rawMaterials as $material) {
            $requiredPerUnit = $material->pivot->quantity_required * (1 + ($material->pivot->waste_percentage / 100));
            $available = $material->quantity;
            
            if ($requiredPerUnit > 0) {
                $possibleUnits = floor($available / $requiredPerUnit);
                $maxProducible = min($maxProducible, $possibleUnits);
                
                if ($possibleUnits <= 0) {
                    $canProduce = false;
                    $materialShortages[] = [
                        'material' => $material->name,
                        'available' => $available,
                        'required_per_unit' => round($requiredPerUnit, 3),
                        'shortage' => round($requiredPerUnit - $available, 3),
                    ];
                }
            }
        }

        if ($maxProducible === PHP_INT_MAX) {
            $maxProducible = 0;
        }

        return [
            'can_produce' => $canProduce,
            'max_producible' => $maxProducible,
            'material_shortages' => $materialShortages,
            'has_recipe' => $product->rawMaterials->count() > 0,
            'raw_materials_count' => $product->rawMaterials->count(),
        ];
    }

    /**
     * Get profit analysis
     */
    public function getProfitAnalysis(Product $product): array
    {
        $sellingPrice = $product->price;
        $cost = $product->cost ?? $product->getTotalRawMaterialCost();
        $profit = $sellingPrice - $cost;
        $margin = $sellingPrice > 0 ? ($profit / $sellingPrice) * 100 : 0;
        $markup = $cost > 0 ? ($profit / $cost) * 100 : 0;

        return [
            'selling_price' => $sellingPrice,
            'cost' => round($cost, 2),
            'profit_per_unit' => round($profit, 2),
            'profit_margin' => round($margin, 1),
            'markup_percentage' => round($markup, 1),
            'total_inventory_value' => round($product->quantity * $cost, 2),
            'potential_revenue' => round($product->quantity * $sellingPrice, 2),
            'potential_profit' => round($product->quantity * $profit, 2),
        ];
    }

    /**
     * Get average daily sales
     */
    protected function getAverageDailySales(int $productId, int $days = 30): float
    {
        $startDate = Carbon::now()->subDays($days);
        
        $totalSold = SaleItem::where('product_id', $productId)
            ->whereHas('sale', function ($q) use ($startDate) {
                $q->where('sale_date', '>=', $startDate);
            })
            ->sum('quantity');

        return $totalSold / $days;
    }

    /**
     * Get suggested production quantity
     */
    protected function getSuggestedProductionQuantity(Product $product): int
    {
        $avgDailySales = $this->getAverageDailySales($product->id, 30);
        $targetDaysStock = 14; // 2 weeks of stock
        $targetStock = $avgDailySales * $targetDaysStock;
        $currentStock = $product->quantity;
        $minimumStock = $product->minimum_stock_level ?? 10;

        $suggested = max(0, $targetStock - $currentStock);
        $suggested = max($suggested, $minimumStock * 2 - $currentStock);

        return (int) ceil($suggested);
    }

    /**
     * Get status label
     */
    protected function getStatusLabel(string $status): string
    {
        return match ($status) {
            'out_of_stock' => __('Out of Stock'),
            'low_stock' => __('Low Stock'),
            'warning' => __('Warning'),
            'normal' => __('In Stock'),
            default => __('Unknown'),
        };
    }

    /**
     * Get status CSS class
     */
    protected function getStatusClass(string $status): string
    {
        return match ($status) {
            'out_of_stock' => 'danger',
            'low_stock' => 'warning',
            'warning' => 'info',
            'normal' => 'success',
            default => 'secondary',
        };
    }

    /**
     * Adjust product stock
     */
    public function adjustStock(Product $product, float $quantity, string $type, ?string $notes = null): Product
    {
        return DB::transaction(function () use ($product, $quantity, $type, $notes) {
            $oldQuantity = $product->quantity;
            $product->quantity += $quantity;
            $product->save();

            // Record stock movement
            StockMovement::create([
                'product_id' => $product->id,
                'type' => $type,
                'quantity' => $quantity,
                'unit_price' => $product->cost,
                'notes' => $notes ?? "Stock adjustment: {$type}",
                'created_by' => Auth::id(),
            ]);

            Log::info("Product stock adjusted", [
                'product_id' => $product->id,
                'old_quantity' => $oldQuantity,
                'adjustment' => $quantity,
                'new_quantity' => $product->quantity,
                'type' => $type,
            ]);

            return $product->fresh();
        });
    }

    /**
     * Get products needing restock
     */
    public function getProductsNeedingRestock(): \Illuminate\Database\Eloquent\Collection
    {
        return Product::with('category')
            ->whereColumn('quantity', '<=', 'minimum_stock_level')
            ->orWhere('quantity', '<=', 0)
            ->orderBy('quantity')
            ->get();
    }

    /**
     * Get top selling products
     */
    public function getTopSellingProducts(int $limit = 10, ?Carbon $startDate = null): \Illuminate\Support\Collection
    {
        $startDate = $startDate ?? Carbon::now()->subMonths(3);

        return SaleItem::select('product_id')
            ->selectRaw('SUM(quantity) as total_qty')
            ->selectRaw('SUM(quantity * unit_price) as total_revenue')
            ->whereHas('sale', function ($q) use ($startDate) {
                $q->where('sale_date', '>=', $startDate);
            })
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->with('product')
            ->get()
            ->map(function ($item) {
                return [
                    'product' => $item->product,
                    'total_sold' => $item->total_qty,
                    'total_revenue' => $item->total_revenue,
                ];
            });
    }

    /**
     * Calculate production cost for quantity
     */
    public function calculateProductionCost(Product $product, int $quantity): array
    {
        $materials = $product->calculateRequiredRawMaterials($quantity);
        $totalMaterialCost = $materials->sum('total_cost');
        
        // Add labor and overhead
        $laborCost = $totalMaterialCost * 0.15;
        $overheadCost = $totalMaterialCost * 0.10;
        $totalCost = $totalMaterialCost + $laborCost + $overheadCost;

        return [
            'quantity' => $quantity,
            'material_cost' => round($totalMaterialCost, 2),
            'labor_cost' => round($laborCost, 2),
            'overhead_cost' => round($overheadCost, 2),
            'total_cost' => round($totalCost, 2),
            'cost_per_unit' => round($totalCost / max($quantity, 1), 2),
            'materials' => $materials,
        ];
    }
}
