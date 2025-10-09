<?php

namespace App\Http\Controllers;

use App\Models\ProductionPlan;
use App\Models\ProductionCost;
use App\Models\LaborCost;
use App\Models\OverheadCost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionCostController extends Controller
{
    public function show(ProductionPlan $productionPlan)
    {
        $productionCost = $productionPlan->productionCost;
        $laborCosts = $productionPlan->laborCosts;
        $overheadCosts = $productionPlan->overheadCosts;
        $varianceReasons = $productionCost->varianceReasons;

        return view('production-costs.show', compact(
            'productionPlan',
            'productionCost',
            'laborCosts',
            'overheadCosts',
            'varianceReasons'
        ));
    }

    public function dashboard()
    {
        $costSummary = ProductionCost::select(
            DB::raw('SUM(planned_material_cost) as total_planned_material'),
            DB::raw('SUM(actual_material_cost) as total_actual_material'),
            DB::raw('SUM(planned_labor_cost) as total_planned_labor'),
            DB::raw('SUM(actual_labor_cost) as total_actual_labor'),
            DB::raw('SUM(planned_overhead_cost) as total_planned_overhead'),
            DB::raw('SUM(actual_overhead_cost) as total_actual_overhead')
        )->first();

        $varianceAnalysis = ProductionCost::with('varianceReasons')
            ->orderBy('total_variance', 'desc')
            ->take(10)
            ->get();

        $monthlyTrend = ProductionCost::select(
            DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
            DB::raw('AVG(total_variance) as avg_variance'),
            DB::raw('SUM(total_actual_cost) as total_cost')
        )
        ->groupBy('month')
        ->orderBy('month')
        ->get();

        return view('production-costs.dashboard', compact(
            'costSummary',
            'varianceAnalysis',
            'monthlyTrend'
        ));
    }

    public function updateActualCosts(Request $request, ProductionPlan $productionPlan)
    {
        $validated = $request->validate([
            'actual_material_cost' => 'required|numeric',
            'actual_labor_cost' => 'required|numeric',
            'actual_overhead_cost' => 'required|numeric',
            'variance_reasons' => 'array'
        ]);

        DB::transaction(function () use ($productionPlan, $validated) {
            $productionCost = $productionPlan->productionCost;
            
            $productionCost->update([
                'actual_material_cost' => $validated['actual_material_cost'],
                'actual_labor_cost' => $validated['actual_labor_cost'],
                'actual_overhead_cost' => $validated['actual_overhead_cost'],
                'total_actual_cost' => array_sum([
                    $validated['actual_material_cost'],
                    $validated['actual_labor_cost'],
                    $validated['actual_overhead_cost']
                ])
            ]);

            $productionCost->calculateVariances();

            if (!empty($validated['variance_reasons'])) {
                foreach ($validated['variance_reasons'] as $reason) {
                    $productionCost->varianceReasons()->create([
                        'cost_type' => $reason['type'],
                        'variance_amount' => $reason['amount'],
                        'reason' => $reason['description'],
                        'recorded_by' => auth()->id()
                    ]);
                }
            }
        });

        return redirect()->back()->with('success', 'Production costs updated successfully');
    }
}