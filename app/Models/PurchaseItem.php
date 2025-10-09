<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id',
        'raw_material_id',
        'quantity',
        'unit_price',
        'total_amount'
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class);
    }
}
