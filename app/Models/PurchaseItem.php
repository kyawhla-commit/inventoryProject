<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * PurchaseItem Model
 * 
 * Represents items in a purchase order.
 * Note: Purchase items are for RAW MATERIALS only.
 * Products are manufactured through Production Plans, not purchased.
 */
class PurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'raw_material_id',
        'quantity',
        'unit_price',
        'unit_cost',
        'total_amount',
        'unit',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            // Auto-calculate total amount
            if ($item->quantity && $item->unit_price) {
                $item->total_amount = $item->quantity * $item->unit_price;
            }
        });
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class);
    }

    /**
     * Get the item name
     */
    public function getItemNameAttribute(): string
    {
        return $this->rawMaterial?->name ?? 'Unknown Item';
    }

    /**
     * Get the item unit
     */
    public function getItemUnitAttribute(): string
    {
        return $this->unit ?? $this->rawMaterial?->unit ?? '';
    }
}
