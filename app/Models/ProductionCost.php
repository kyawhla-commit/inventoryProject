<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionCost extends Model
{
    protected $fillable = [
        'production_plan_id',
        'planned_material_cost',
        'actual_material_cost',
        'material_variance',
        'planned_labor_cost',
        'actual_labor_cost',
        'labor_variance',
        'planned_overhead_cost',
        'actual_overhead_cost',
        'overhead_variance',
        'total_planned_cost',
        'total_actual_cost',
        'total_variance',
        'material_gl_code',
        'labor_gl_code',
        'overhead_gl_code',
    ];

    public function productionPlan()
    {
        return $this->belongsTo(ProductionPlan::class);
    }

    public function varianceReasons()
    {
        return $this->hasMany(CostVarianceReason::class);
    }

    public function laborCosts()
    {
        return $this->hasMany(LaborCost::class, 'production_plan_id', 'production_plan_id');
    }

    public function overheadCosts()
    {
        return $this->hasMany(OverheadCost::class, 'production_plan_id', 'production_plan_id');
    }

    public function calculateVariances()
    {
        $this->material_variance = $this->actual_material_cost - $this->planned_material_cost;
        $this->labor_variance = $this->actual_labor_cost - $this->planned_labor_cost;
        $this->overhead_variance = $this->actual_overhead_cost - $this->planned_overhead_cost;
        $this->total_variance = $this->material_variance + $this->labor_variance + $this->overhead_variance;
        $this->save();
    }
}