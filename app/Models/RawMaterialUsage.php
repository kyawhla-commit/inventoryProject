<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RawMaterialUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'raw_material_id',
        'product_id',
        'order_id',
        'quantity_used',
        'cost_per_unit',
        'total_cost',
        'usage_date',
        'usage_type',
        'notes',
        'batch_number',
        'recorded_by',
    ];

    protected $casts = [
        'usage_date' => 'date',
        'quantity_used' => 'decimal:3',
        'cost_per_unit' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function productionPlan(): BelongsTo
    {
        return $this->belongsTo(ProductionPlan::class, 'production_plan_id');
    }

    /**
     * Get the raw material that owns the usage.
     */


    public function calculateTotalCost()
    {
        $this->total_cost = $this->quantity_used * $this->cost_per_unit;
        return $this;
    }

    public function updateRawMaterialStock()
    {
        $rawMaterial = $this->rawMaterial;
        if ($rawMaterial) {
            $rawMaterial->decrement('quantity', $this->quantity_used);
        }
        return $this;
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('usage_date', [$startDate, $endDate]);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('usage_type', $type);
    }

    public function scopeByRawMaterial($query, $rawMaterialId)
    {
        return $query->where('raw_material_id', $rawMaterialId);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function getUsageTypeBadgeClass()
    {
        return match($this->usage_type) {
            'production' => 'bg-success',
            'waste' => 'bg-danger',
            'adjustment' => 'bg-warning',
            'testing' => 'bg-info',
            'maintenance' => 'bg-secondary',
            default => 'bg-primary'
        };
    }

    public static function getUsageTypes()
    {
        return [
            'production' => 'Production',
            'waste' => 'Waste/Loss',
            'adjustment' => 'Stock Adjustment',
            'testing' => 'Quality Testing',
            'maintenance' => 'Equipment Maintenance',
            'other' => 'Other'
        ];
    }
}