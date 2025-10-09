<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductionPlan;
use App\Models\ProductionPlanItem;
use App\Models\Recipe;
use App\Models\RawMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProductionPlanController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductionPlan::with(['productionPlanItems.product', 'productionPlanItems.recipe', 'createdBy']);
        
        // Search by plan number or name
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('plan_number', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('planned_start_date', '>=', $request->get('date_from'));
        }
        if ($request->has('date_to')) {
            $query->whereDate('planned_end_date', '<=', $request->get('date_to'));
        }

        $perPage = $request->get('per_page', 15);
        $plans = $query->latest('created_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $plans
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'planned_start_date' => 'required|date',
            'planned_end_date' => 'required|date|after_or_equal:planned_start_date',
            'status' => 'required|in:draft,approved,in_progress,completed,cancelled',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.recipe_id' => 'required|exists:recipes,id',
            'items.*.order_id' => 'nullable|exists:orders,id',
            'items.*.planned_quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string|max:50',
            'items.*.planned_start_date' => 'required|date',
            'items.*.planned_end_date' => 'required|date|after_or_equal:items.*.planned_start_date',
            'items.*.priority' => 'nullable|integer|min:1|max:10',
            'items.*.notes' => 'nullable|string',
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
            // Calculate total estimated cost
            $totalEstimatedCost = 0;
            foreach ($request->items as $item) {
                $recipe = Recipe::find($item['recipe_id']);
                if ($recipe) {
                    $requirements = $recipe->calculateMaterialRequirements($item['planned_quantity']);
                    $totalEstimatedCost += $requirements->sum('estimated_cost');
                }
            }

            // Create production plan
            $planData = [
                'name' => $request->name,
                'description' => $request->description,
                'planned_start_date' => $request->planned_start_date,
                'planned_end_date' => $request->planned_end_date,
                'status' => $request->status,
                'total_estimated_cost' => $totalEstimatedCost,
                'notes' => $request->notes,
            ];

            // Add created_by if user is authenticated
            if (Auth::check()) {
                $planData['created_by'] = Auth::id();
            }

            $plan = ProductionPlan::create($planData);

            // Create production plan items
            foreach ($request->items as $item) {
                $recipe = Recipe::find($item['recipe_id']);
                $estimatedCost = 0;
                
                if ($recipe) {
                    $requirements = $recipe->calculateMaterialRequirements($item['planned_quantity']);
                    $estimatedCost = $requirements->sum('estimated_cost');
                }

                ProductionPlanItem::create([
                    'production_plan_id' => $plan->id,
                    'product_id' => $item['product_id'],
                    'recipe_id' => $item['recipe_id'],
                    'order_id' => $item['order_id'] ?? null,
                    'planned_quantity' => $item['planned_quantity'],
                    'unit' => $item['unit'],
                    'estimated_material_cost' => $estimatedCost,
                    'planned_start_date' => $item['planned_start_date'],
                    'planned_end_date' => $item['planned_end_date'],
                    'status' => 'pending',
                    'priority' => $item['priority'] ?? 5,
                    'notes' => $item['notes'],
                ]);
            }

            DB::commit();

            $plan->load(['productionPlanItems.product', 'productionPlanItems.recipe']);

            return response()->json([
                'success' => true,
                'message' => 'Production plan created successfully',
                'data' => $plan
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create production plan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $plan = ProductionPlan::with([
            'productionPlanItems.product', 
            'productionPlanItems.recipe', 
            'productionPlanItems.order',
            'createdBy',
            'approvedBy'
        ])->find($id);

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'Production plan not found'
            ], 404);
        }

        // Add calculated fields
        $plan->material_requirements = $plan->calculateMaterialRequirements();
        $plan->completion_percentage = $this->calculateCompletionPercentage($plan);

        return response()->json([
            'success' => true,
            'data' => $plan
        ]);
    }

    public function update(Request $request, $id)
    {
        $plan = ProductionPlan::find($id);

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'Production plan not found'
            ], 404);
        }

        // Check if plan can be updated
        if (in_array($plan->status, ['completed', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update completed or cancelled production plan'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'planned_start_date' => 'sometimes|required|date',
            'planned_end_date' => 'sometimes|required|date|after_or_equal:planned_start_date',
            'status' => 'sometimes|required|in:draft,approved,in_progress,completed,cancelled',
            'notes' => 'nullable|string',
            'items' => 'sometimes|required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.recipe_id' => 'required|exists:recipes,id',
            'items.*.order_id' => 'nullable|exists:orders,id',
            'items.*.planned_quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string|max:50',
            'items.*.planned_start_date' => 'required|date',
            'items.*.planned_end_date' => 'required|date|after_or_equal:items.*.planned_start_date',
            'items.*.priority' => 'nullable|integer|min:1|max:10',
            'items.*.notes' => 'nullable|string',
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
            // Update production plan
            $plan->update($request->only([
                'name', 'description', 'planned_start_date', 'planned_end_date', 
                'status', 'notes'
            ]));

            // If updating items
            if ($request->has('items')) {
                // Delete old items
                $plan->productionPlanItems()->delete();

                // Calculate new total estimated cost
                $totalEstimatedCost = 0;
                foreach ($request->items as $item) {
                    $recipe = Recipe::find($item['recipe_id']);
                    if ($recipe) {
                        $requirements = $recipe->calculateMaterialRequirements($item['planned_quantity']);
                        $totalEstimatedCost += $requirements->sum('estimated_cost');
                    }
                }

                // Create new items
                foreach ($request->items as $item) {
                    $recipe = Recipe::find($item['recipe_id']);
                    $estimatedCost = 0;
                    
                    if ($recipe) {
                        $requirements = $recipe->calculateMaterialRequirements($item['planned_quantity']);
                        $estimatedCost = $requirements->sum('estimated_cost');
                    }

                    ProductionPlanItem::create([
                        'production_plan_id' => $plan->id,
                        'product_id' => $item['product_id'],
                        'recipe_id' => $item['recipe_id'],
                        'order_id' => $item['order_id'] ?? null,
                        'planned_quantity' => $item['planned_quantity'],
                        'unit' => $item['unit'],
                        'estimated_material_cost' => $estimatedCost,
                        'planned_start_date' => $item['planned_start_date'],
                        'planned_end_date' => $item['planned_end_date'],
                        'status' => 'pending',
                        'priority' => $item['priority'] ?? 5,
                        'notes' => $item['notes'],
                    ]);
                }

                $plan->update(['total_estimated_cost' => $totalEstimatedCost]);
            }

            DB::commit();

            $plan->load(['productionPlanItems.product', 'productionPlanItems.recipe']);

            return response()->json([
                'success' => true,
                'message' => 'Production plan updated successfully',
                'data' => $plan
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update production plan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $plan = ProductionPlan::with('productionPlanItems')->find($id);

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'Production plan not found'
            ], 404);
        }

        // Check if plan can be deleted
        if ($plan->status === 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete production plan that is in progress'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $plan->productionPlanItems()->delete();
            $plan->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Production plan deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete production plan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $plan = ProductionPlan::find($id);

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'Production plan not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:draft,approved,in_progress,completed,cancelled',
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
            case 'approved':
                if (Auth::check()) {
                    $updateData['approved_by'] = Auth::id();
                    $updateData['approved_at'] = now();
                }
                break;
            case 'in_progress':
                if (!$plan->actual_start_date) {
                    $updateData['actual_start_date'] = now();
                }
                break;
            case 'completed':
                if (!$plan->actual_end_date) {
                    $updateData['actual_end_date'] = now();
                }
                break;
        }

        $plan->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Production plan status updated successfully',
            'data' => $plan
        ]);
    }

    public function getMaterialRequirements($id)
    {
        $plan = ProductionPlan::with(['productionPlanItems.recipe.recipeItems.rawMaterial'])->find($id);

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'Production plan not found'
            ], 404);
        }

        $requirements = $plan->calculateMaterialRequirements();

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
                'production_plan' => $plan,
                'material_requirements' => $requirements,
                'total_estimated_cost' => $requirements->sum('estimated_cost'),
                'materials_with_shortage' => $requirements->where('sufficient_stock', false)->count(),
            ]
        ]);
    }

    public function getByStatus($status)
    {
        $validStatuses = ['draft', 'approved', 'in_progress', 'completed', 'cancelled'];
        
        if (!in_array($status, $validStatuses)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status'
            ], 422);
        }

        $plans = ProductionPlan::with(['productionPlanItems.product', 'createdBy'])
            ->where('status', $status)
            ->latest('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $plans
        ]);
    }

    private function calculateCompletionPercentage($plan)
    {
        $totalItems = $plan->productionPlanItems->count();
        if ($totalItems === 0) return 0;

        $completedItems = $plan->productionPlanItems->where('status', 'completed')->count();
        return round(($completedItems / $totalItems) * 100, 2);
    }
}