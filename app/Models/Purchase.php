<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Supplier;
use App\Models\PurchaseItem;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_number',
        'supplier_id',
        'order_id',
        'purchase_date',
        'total_amount',
        'status',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($purchase) {
            if (empty($purchase->purchase_number)) {
                $purchase->purchase_number = 'PO-' . date('Ymd') . '-' . strtoupper(uniqid());
            }
        });
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}