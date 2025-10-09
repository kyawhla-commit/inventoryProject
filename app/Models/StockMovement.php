<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'reference_type',
        'reference_id',
        'quantity',
        'movement_type',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    /**
     * Get the product associated with the stock movement.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user who recorded the stock movement.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the polymorphic relation.
     */
    public function reference()
    {
        return $this->morphTo();
    }
}