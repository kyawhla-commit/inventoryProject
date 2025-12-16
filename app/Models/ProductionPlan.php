<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_number',
        'name',
        'description',
        'planned_start_date',
        'planned_end_date',
        'actual_start_date',
        'actual_end_date',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'total_estimated_cost',
        'total_actual_cost',
        'notes',
    ];

    protected $casts = [
        'planned_start_date' => 'date',
        'planned_end_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date',
        'approved_at' => 'datetime',
        'total_estimated_cost' => 'decimal:2',
        'total_actual_cost' => 'decimal:2',
    ];

    public function productionPlanItems()
    {
        return $this->hasMany(ProductionPlanItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rawMaterialUsages()
    {
        return $this->hasMany(RawMaterialUsage::class, 'production_plan_id');
    }

    /**
     * Calculate the raw material requirements for this production plan
     *
     * @return array
     */
    public function calculateMaterialRequirements()
    {
        $requirements = [];
        
        foreach ($this->productionPlanItems as $item) {
            $product = $item->product;
            if (!$product) continue;
            
            $quantity = $item->planned_quantity ?? $item->quantity ?? 0;
            
            // Get raw materials for this product
            $productRawMaterials = $product->rawMaterials;
            
            foreach ($productRawMaterials as $rawMaterial) {
                $pivot = $rawMaterial->pivot;
                $requiredQuantity = ($pivot->quantity_required ?? 0) * $quantity;
                $wasteQuantity = $requiredQuantity * (($pivot->waste_percentage ?? 0) / 100);
                $totalRequired = $requiredQuantity + $wasteQuantity;
                $costPerUnit = $pivot->cost_per_unit ?? $rawMaterial->cost_per_unit ?? 0;
                $estimatedCost = $totalRequired * $costPerUnit;
                
                // If this raw material is already in requirements, add to it
                $found = false;
                foreach ($requirements as &$req) {
                    if ($req['raw_material_id'] == $rawMaterial->id) {
                        $req['quantity_required'] += $requiredQuantity;
                        $req['total_required'] += $totalRequired;
                        $req['estimated_cost'] += $estimatedCost;
                        $found = true;
                        break;
                    }
                }
                
                // If not found, add new requirement
                if (!$found) {
                    $requirements[] = [
                        'raw_material_id' => $rawMaterial->id,
                        'raw_material' => $rawMaterial, // Include the full object for view access
                        'raw_material_name' => $rawMaterial->name,
                        'quantity_required' => $requiredQuantity,
                        'total_required' => $totalRequired,
                        'unit' => $rawMaterial->unit,
                        'available' => $rawMaterial->quantity,
                        'cost_per_unit' => $costPerUnit,
                        'estimated_cost' => $estimatedCost,
                        'is_sufficient' => $rawMaterial->quantity >= $totalRequired,
                    ];
                }
            }
        }
        
        return $requirements;
    }

    public function getStatusBadgeClass()
    {
        return match($this->status) {
            'draft' => 'bg-secondary',
            'approved' => 'bg-info',
            'in_progress' => 'bg-warning',
            'completed' => 'bg-success',
            'cancelled' => 'bg-danger',
            default => 'bg-primary'
        };
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['approved', 'in_progress']);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->plan_number)) {
                $model->plan_number = 'PP-' . date('Y') . '-' . str_pad(static::whereYear('created_at', date('Y'))->count() + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
