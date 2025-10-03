<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Category;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'barcode',
        'image',
        'quantity',
        'unit',
        'price',
        'cost',
        'minimum_stock_level',
        'category_id',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function rawMaterialUsages()
    {
        return $this->hasMany(RawMaterialUsage::class);
    }

    public function recipes()
    {
        return $this->hasMany(Recipe::class);
    }

    public function activeRecipe()
    {
        return $this->hasOne(Recipe::class)->where('is_active', true)->latest();
    }

    public function productionPlanItems()
    {
        return $this->hasMany(ProductionPlanItem::class);
    }

    public function rawMaterials()
    {
        return $this->belongsToMany(RawMaterial::class, 'product_raw_material')
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

    public function primaryRawMaterials()
    {
        return $this->rawMaterials()->wherePivot('is_primary', true);
    }

    public function getTotalRawMaterialCost()
    {
        return $this->rawMaterials->sum(function ($rawMaterial) {
            $costPerUnit = $rawMaterial->pivot->cost_per_unit ?? $rawMaterial->cost_per_unit;
            $quantityWithWaste = $rawMaterial->pivot->quantity_required * (1 + ($rawMaterial->pivot->waste_percentage / 100));
            return $quantityWithWaste * $costPerUnit;
        });
    }

    public function calculateRequiredRawMaterials($productQuantity)
    {
        return $this->rawMaterials->map(function ($rawMaterial) use ($productQuantity) {
            $requiredQuantity = $rawMaterial->pivot->quantity_required * $productQuantity;
            $wasteQuantity = $requiredQuantity * ($rawMaterial->pivot->waste_percentage / 100);
            $totalRequired = $requiredQuantity + $wasteQuantity;
            $costPerUnit = $rawMaterial->pivot->cost_per_unit ?? $rawMaterial->cost_per_unit;
            
            return [
                'raw_material_id' => $rawMaterial->id,
                'raw_material' => $rawMaterial,
                'required_quantity' => $requiredQuantity,
                'waste_quantity' => $wasteQuantity,
                'total_required' => $totalRequired,
                'unit' => $rawMaterial->pivot->unit,
                'cost_per_unit' => $costPerUnit,
                'total_cost' => $totalRequired * $costPerUnit,
                'is_primary' => $rawMaterial->pivot->is_primary,
                'notes' => $rawMaterial->pivot->notes,
            ];
        });
    }

    // Add this method to the Product model class
    
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
