<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Supplier;

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

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
