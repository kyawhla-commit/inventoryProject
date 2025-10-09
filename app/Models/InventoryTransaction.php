<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_number',
        'type',
        'source',
        'product_id',
        'raw_material_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'unit',
        'stock_before',
        'stock_after',
        'sale_id',
        'purchase_id',
        'production_plan_item_id',
        'order_id',
        'reason',
        'notes',
        'approved_by',
        'approved_at',
        'created_by',
        'transaction_date',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'stock_before' => 'decimal:3',
        'stock_after' => 'decimal:3',
        'approved_at' => 'datetime',
        'transaction_date' => 'datetime',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function productionPlanItem()
    {
        return $this->belongsTo(ProductionPlanItem::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeForRawMaterial($query, $rawMaterialId)
    {
        return $query->where('raw_material_id', $rawMaterialId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    // Helper methods
    public function getItemName()
    {
        if ($this->product) {
            return $this->product->name;
        } elseif ($this->rawMaterial) {
            return $this->rawMaterial->name;
        }
        return 'Unknown Item';
    }

    public function getItemType()
    {
        return $this->product_id ? 'product' : 'raw_material';
    }

    public function isInbound()
    {
        return $this->type === 'in';
    }

    public function isOutbound()
    {
        return $this->type === 'out';
    }

    public function isAdjustment()
    {
        return $this->type === 'adjustment';
    }

    // Auto-generate transaction number
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->transaction_number)) {
                $model->transaction_number = 'TXN-' . date('Y') . '-' . str_pad(static::whereYear('created_at', date('Y'))->count() + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }
}
