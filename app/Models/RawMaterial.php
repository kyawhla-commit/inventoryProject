<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Supplier;

/**
 * RawMaterial Model
 * 
 * Represents raw materials used in production.
 * Raw materials are purchased from suppliers and consumed in production plans
 * to manufacture finished products.
 */
class RawMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'quantity',
        'unit',
        'cost_per_unit',
        'supplier_id',
        'minimum_stock_level',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'cost_per_unit' => 'decimal:4',
        'minimum_stock_level' => 'decimal:2',
    ];

    // ==================== RELATIONSHIPS ====================

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function usages()
    {
        return $this->hasMany(RawMaterialUsage::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_raw_material')
            ->withPivot([
                'quantity_required',
                'unit',
                'cost_per_unit',
                'waste_percentage',
                'notes',
                'is_primary',
                'sequence_order'
            ])
            ->withTimestamps()
            ->orderBy('product_raw_material.sequence_order');
    }

    public function primaryProducts()
    {
        return $this->products()->wherePivot('is_primary', true);
    }

    public function recipeItems()
    {
        return $this->hasMany(RecipeItem::class);
    }

    // ==================== STOCK CHECKS ====================

    /**
     * Check if stock is low (at or below minimum level)
     */
    public function isLowStock(): bool
    {
        return $this->quantity <= $this->minimum_stock_level;
    }

    /**
     * Check if out of stock
     */
    public function isOutOfStock(): bool
    {
        return $this->quantity <= 0;
    }

    /**
     * Check if stock is critical (below 50% of minimum)
     */
    public function isCriticalStock(): bool
    {
        return $this->quantity < ($this->minimum_stock_level * 0.5);
    }

    /**
     * Check if sufficient stock for quantity
     */
    public function hasSufficientStock(float $requiredQuantity): bool
    {
        return $this->quantity >= $requiredQuantity;
    }

    /**
     * Get stock status
     */
    public function getStockStatus(): string
    {
        if ($this->isOutOfStock()) return 'out_of_stock';
        if ($this->isCriticalStock()) return 'critical';
        if ($this->isLowStock()) return 'low';
        return 'normal';
    }

    /**
     * Get stock status badge class
     */
    public function getStockStatusBadgeAttribute(): string
    {
        $status = $this->getStockStatus();
        if ($status === 'out_of_stock' || $status === 'critical') return 'bg-danger';
        if ($status === 'low') return 'bg-warning';
        if ($status === 'normal') return 'bg-success';
        return 'bg-secondary';
    }

    /**
     * Get stock status label
     */
    public function getStockStatusLabelAttribute(): string
    {
        $status = $this->getStockStatus();
        if ($status === 'out_of_stock') return __('Out of Stock');
        if ($status === 'critical') return __('Critical');
        if ($status === 'low') return __('Low Stock');
        if ($status === 'normal') return __('In Stock');
        return __('Unknown');
    }

    // ==================== CALCULATIONS ====================

    /**
     * Get total stock value
     */
    public function getStockValueAttribute(): float
    {
        return $this->quantity * $this->cost_per_unit;
    }

    /**
     * Get total usage for a period
     */
    public function getTotalUsageForPeriod($startDate, $endDate): float
    {
        return $this->usages()
            ->whereBetween('usage_date', [$startDate, $endDate])
            ->sum('quantity_used');
    }

    /**
     * Get total cost for a period
     */
    public function getTotalCostForPeriod($startDate, $endDate): float
    {
        return $this->usages()
            ->whereBetween('usage_date', [$startDate, $endDate])
            ->sum('total_cost');
    }

    /**
     * Get average daily usage (last 30 days)
     */
    public function getAverageDailyUsageAttribute(): float
    {
        $totalUsage = $this->getTotalUsageForPeriod(now()->subDays(30), now());
        return round($totalUsage / 30, 2);
    }

    /**
     * Get days of stock remaining
     */
    public function getDaysOfStockAttribute(): ?float
    {
        $dailyUsage = $this->average_daily_usage;
        if ($dailyUsage <= 0) return null;
        return round($this->quantity / $dailyUsage, 1);
    }

    /**
     * Get products using this material
     */
    public function getProductsUsingThis(): \Illuminate\Support\Collection
    {
        return $this->products->map(function ($product) {
            return [
                'product' => $product,
                'quantity_required' => $product->pivot->quantity_required,
                'unit' => $product->pivot->unit,
                'is_primary' => $product->pivot->is_primary,
                'waste_percentage' => $product->pivot->waste_percentage,
            ];
        });
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity', '<=', 'minimum_stock_level');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('quantity', '<=', 0);
    }

    public function scopeForSupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeInCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('sku', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }
}
