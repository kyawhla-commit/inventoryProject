<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductionPlanItem;
use App\Models\Product;
use App\Models\RawMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ProductionPlanItemController extends Controller
{
    public function show($id)
    {
        $item = ProductionPlanItem::with([
            'productionPlan', 
            'product', 
            'recipe.recipeItems.rawMaterial', 
            'order'
        ])->find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Production plan item not found'
            ], 404);
        }

        // Add calculated fields
        $item->variance_percentage = $item->getVariancePercentage();
        $item->cost_variance = $item->getCostVariance();
        $item->cost_variance_percentage = $item->getCostVariancePercentage();
        $item->efficiency_percentage = $item->getEfficiencyPercentage();

        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $item = ProductionPlanItem::find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Production plan item not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,in_progress,completed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $updateData = ['status' => $request->status];

        // Handle status-specific updates
        switch ($request->status) {
            case 'in_progress':
                if (!$item->actual_start_date) {
                    $updateData['actual_start_date'] = now();
                }
                break;
            case 'completed':
                if (!$item->actual_end_date) {
                    $updateData['actual_end_date'] = now();
                }
                break;
        }

        $item->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Production plan item status updated successfully',
            'data' => $item
        ]);
    }

    public function updateProgress(Request $request, $id)
    {
        $item = ProductionPlanItem::with(['recipe.recipeItems.rawMaterial', 'product'])->find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Production plan item not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'actual_quantity' => 'required|numeric|min:0',
            'actual_material_cost' => 'nullable|numeric|min:0',
            'actual_start_date' => 'nullable|date',
            'actual_end_date' => 'nullable|date|after_or_equal:actual_start_date',
            'notes' => 'nullable|string',
            'consume_materials' => 'boolean', // Whether to consume raw materials from stock
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Update production item
            $item->update([
                'actual_quantity' => $request->actual_quantity,
                'actual_material_cost' => $request->actual_material_cost ?? $item->estimated_material_cost,
                'actual_start_date' => $request->actual_start_date ?? $item->actual_start_date,
                'actual_end_date' => $request->actual_end_date ?? $item->actual_end_date,
                'notes' => $request->notes ?? $item->notes,
                'status' => $request->actual_end_date ? 'completed' : 'in_progress',
            ]);

            // If consuming materials and production is completed
            if ($request->boolean('consume_materials') && $request->actual_end_date) {
                if ($item->recipe) {
                    $requirements = $item->recipe->calculateMaterialRequirements($request->actual_quantity);
                    
                    // Check if we have enough materials
                    foreach ($requirements as $requirement) {
                        $rawMaterial = RawMaterial::find($requirement['raw_material_id']);
                        if (!$rawMaterial || $rawMaterial->current_stock < $requirement['total_required']) {
                            DB::rollback();
                            return response()->json([
                                'success' => false,
                                'message' => "Insufficient stock for raw material: " . ($rawMaterial->name ?? 'Unknown')
                            ], 422);
                        }
                    }

                    // Consume materials from stock
                    foreach ($requirements as $requirement) {
                        $rawMaterial = RawMaterial::find($requirement['raw_material_id']);
                        $rawMaterial->decrement('current_stock', $requirement['total_required']);
                    }

                    // Add produced quantity to product stock
                    if ($item->product) {
                        $item->product->increment('quantity', $request->actual_quantity);
                    }
                }
            }

            DB::commit();

            $item->load(['productionPlan', 'product', 'recipe']);

            return response()->json([
                'success' => true,
                'message' => 'Production progress updated successfully',
                'data' => $item
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update production progress: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getMaterialRequirements($id)
    {
        $item = ProductionPlanItem::with(['recipe.recipeItems.rawMaterial'])->find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Production plan item not found'
            ], 404);
        }

        if (!$item->recipe) {
            return response()->json([
                'success' => false,
                'message' => 'No recipe found for this production item'
            ], 404);
        }

        $requirements = $item->recipe->calculateMaterialRequirements($item->planned_quantity);

        // Add current stock information
        $requirements = $requirements->map(function ($requirement) {
            $rawMaterial = RawMaterial::find($requirement['raw_material_id']);
            if ($rawMaterial) {
                $requirement['current_stock'] = $rawMaterial->current_stock;
                $requirement['shortage'] = max(0, $requirement['total_required'] - $rawMaterial->current_stock);
                $requirement['sufficient_stock'] = $rawMaterial->current_stock >= $requirement['total_required'];
            }
            return $requirement;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'production_item' => $item,
                'material_requirements' => $requirements,
                'total_estimated_cost' => $requirements->sum('estimated_cost'),
                'can_produce' => $requirements->every('sufficient_stock'),
            ]
        ]);
    }

    public function getByPriority(Request $request)
    {
        $query = ProductionPlanItem::with(['productionPlan', 'product', 'recipe'])
            ->whereHas('productionPlan', function($q) {
                $q->whereIn('status', ['approved', 'in_progress']);
            })
            ->orderBy('priority');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        $items = $query->get();

        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }

    public function getOverdue()
    {
        $items = ProductionPlanItem::with(['productionPlan', 'product', 'recipe'])
            ->where('status', '!=', 'completed')
            ->where('planned_end_date', '<', now())
            ->orderBy('planned_end_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }
}