<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'raw_material_id',
        'reference_type',
        'reference_id',
        'type',
        'quantity',
        'unit_price',
        'movement_type',
        'notes',
        'user_id',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    const TYPE_PURCHASE = 'purchase';
    const TYPE_PURCHASE_REVERSAL = 'purchase_reversal';
    const TYPE_USAGE = 'usage';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_RETURN = 'return';

    /**
     * Get the product associated with the stock movement.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the raw material associated with the stock movement.
     */
    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class);
    }

    /**
     * Get the user who recorded the stock movement.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the creator of the stock movement.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the polymorphic relation.
     */
    public function reference()
    {
        return $this->morphTo();
    }

    /**
     * Scope for raw material movements
     */
    public function scopeForRawMaterial($query, $rawMaterialId)
    {
        return $query->where('raw_material_id', $rawMaterialId);
    }

    /**
     * Scope for purchase movements
     */
    public function scopePurchases($query)
    {
        return $query->where('type', self::TYPE_PURCHASE);
    }
}