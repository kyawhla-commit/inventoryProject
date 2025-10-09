<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\RecipeItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class RecipeController extends Controller
{
    public function index(Request $request)
    {
        $query = Recipe::with(['product', 'recipeItems.rawMaterial']);

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($productQuery) use ($search) {
                        $productQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->has('product_id')) {
            $query->where('product_id', $request->get('product_id'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $perPage = $request->get('per_page', 15);
        $recipes = $query->latest()->paginate($perPage);

        $recipes->getCollection()->transform(function ($recipe) {
            $recipe->total_material_cost = $recipe->getTotalMaterialCost();
            $recipe->estimated_cost_per_unit = $recipe->getEstimatedCostPerUnit();
            return $recipe;
        });

        return response()->json([
            'success' => true,
            'data' => $recipes
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'batch_size' => 'required|numeric|min:0.01',
            'unit' => 'required|string|max:50',
            'yield_percentage' => 'nullable|numeric|min:0|max:100',
            'preparation_time' => 'nullable|integer|min:0',
            'production_time' => 'nullable|integer|min:0',
            'instructions' => 'nullable|string',
            'is_active' => 'boolean',
            'version' => 'nullable|string|max:50',
            'items' => 'required|array|min:1',
            'items.*.raw_material_id' => 'required|exists:raw_materials,id',
            'items.*.quantity_required' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string|max:50',
            'items.*.cost_per_unit' => 'nullable|numeric|min:0',
            'items.*.waste_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.notes' => 'nullable|string',
            'items.*.sequence_order' => 'nullable|integer|min:1',
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
            $recipeData = [
                'product_id' => $request->product_id,
                'name' => $request->name,
                'description' => $request->description,
                'batch_size' => $request->batch_size,
                'unit' => $request->unit,
                'yield_percentage' => $request->yield_percentage ?? 100,
                'preparation_time' => $request->preparation_time,
                'production_time' => $request->production_time,
                'instructions' => $request->instructions,
                'is_active' => $request->is_active ?? true,
                'version' => $request->version ?? '1.0',
            ];

            // Skip created_by for now to avoid auth issues

            $recipe = Recipe::create($recipeData);

            foreach ($request->items as $index => $item) {
                RecipeItem::create([
                    'recipe_id' => $recipe->id,
                    'raw_material_id' => $item['raw_material_id'],
                    'quantity_required' => $item['quantity_required'],
                    'unit' => $item['unit'],
                    'cost_per_unit' => $item['cost_per_unit'],
                    'waste_percentage' => $item['waste_percentage'] ?? 0,
                    'notes' => $item['notes'],
                    'sequence_order' => $item['sequence_order'] ?? ($index + 1),
                ]);
            }

            DB::commit();

            $recipe->load(['product', 'recipeItems.rawMaterial']);
            $recipe->total_material_cost = $recipe->getTotalMaterialCost();
            $recipe->estimated_cost_per_unit = $recipe->getEstimatedCostPerUnit();

            return response()->json([
                'success' => true,
                'message' => 'Recipe created successfully',
                'data' => $recipe
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create recipe: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $recipe = Recipe::with(['product', 'recipeItems.rawMaterial', 'createdBy'])->find($id);

        if (!$recipe) {
            return response()->json([
                'success' => false,
                'message' => 'Recipe not found'
            ], 404);
        }

        $recipe->total_material_cost = $recipe->getTotalMaterialCost();
        $recipe->estimated_cost_per_unit = $recipe->getEstimatedCostPerUnit();

        return response()->json([
            'success' => true,
            'data' => $recipe
        ]);
    }

    public function update(Request $request, $id)
    {
        $recipe = Recipe::find($id);

        if (!$recipe) {
            return response()->json([
                'success' => false,
                'message' => 'Recipe not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'product_id' => 'sometimes|required|exists:products,id',
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'batch_size' => 'sometimes|required|numeric|min:0.01',
            'unit' => 'sometimes|required|string|max:50',
            'yield_percentage' => 'nullable|numeric|min:0|max:100',
            'preparation_time' => 'nullable|integer|min:0',
            'production_time' => 'nullable|integer|min:0',
            'instructions' => 'nullable|string',
            'is_active' => 'boolean',
            'version' => 'nullable|string|max:50',
            'items' => 'sometimes|required|array|min:1',
            'items.*.raw_material_id' => 'required|exists:raw_materials,id',
            'items.*.quantity_required' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string|max:50',
            'items.*.cost_per_unit' => 'nullable|numeric|min:0',
            'items.*.waste_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.notes' => 'nullable|string',
            'items.*.sequence_order' => 'nullable|integer|min:1',
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
            $recipe->update($request->only([
                'product_id',
                'name',
                'description',
                'batch_size',
                'unit',
                'yield_percentage',
                'preparation_time',
                'production_time',
                'instructions',
                'is_active',
                'version'
            ]));

            if ($request->has('items')) {
                $recipe->recipeItems()->delete();

                foreach ($request->items as $index => $item) {
                    RecipeItem::create([
                        'recipe_id' => $recipe->id,
                        'raw_material_id' => $item['raw_material_id'],
                        'quantity_required' => $item['quantity_required'],
                        'unit' => $item['unit'],
                        'cost_per_unit' => $item['cost_per_unit'],
                        'waste_percentage' => $item['waste_percentage'] ?? 0,
                        'notes' => $item['notes'],
                        'sequence_order' => $item['sequence_order'] ?? ($index + 1),
                    ]);
                }
            }

            DB::commit();

            $recipe->load(['product', 'recipeItems.rawMaterial']);
            $recipe->total_material_cost = $recipe->getTotalMaterialCost();
            $recipe->estimated_cost_per_unit = $recipe->getEstimatedCostPerUnit();

            return response()->json([
                'success' => true,
                'message' => 'Recipe updated successfully',
                'data' => $recipe
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update recipe: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $recipe = Recipe::with('recipeItems')->find($id);

        if (!$recipe) {
            return response()->json([
                'success' => false,
                'message' => 'Recipe not found'
            ], 404);
        }

        if ($recipe->productionPlanItems()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete recipe that is used in production plans'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $recipe->recipeItems()->delete();
            $recipe->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Recipe deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete recipe: ' . $e->getMessage()
            ], 500);
        }
    }

    public function calculateMaterials(Request $request, $id)
    {
        $recipe = Recipe::with('recipeItems.rawMaterial')->find($id);

        if (!$recipe) {
            return response()->json([
                'success' => false,
                'message' => 'Recipe not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'quantity' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $requirements = $recipe->calculateMaterialRequirements($request->quantity);

        return response()->json([
            'success' => true,
            'data' => [
                'recipe' => $recipe,
                'requested_quantity' => $request->quantity,
                'batch_multiplier' => $request->quantity / $recipe->batch_size,
                'material_requirements' => $requirements,
                'total_estimated_cost' => $requirements->sum('estimated_cost'),
            ]
        ]);
    }

    public function duplicate($id)
    {
        $originalRecipe = Recipe::with('recipeItems')->find($id);

        if (!$originalRecipe) {
            return response()->json([
                'success' => false,
                'message' => 'Recipe not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            $newRecipeData = [
                'product_id' => $originalRecipe->product_id,
                'name' => $originalRecipe->name . ' (Copy)',
                'description' => $originalRecipe->description,
                'batch_size' => $originalRecipe->batch_size,
                'unit' => $originalRecipe->unit,
                'yield_percentage' => $originalRecipe->yield_percentage,
                'preparation_time' => $originalRecipe->preparation_time,
                'production_time' => $originalRecipe->production_time,
                'instructions' => $originalRecipe->instructions,
                'is_active' => false,
                'version' => '1.0',
            ];

            // Skip created_by for now to avoid auth issues

            $newRecipe = Recipe::create($newRecipeData);

            foreach ($originalRecipe->recipeItems as $item) {
                RecipeItem::create([
                    'recipe_id' => $newRecipe->id,
                    'raw_material_id' => $item->raw_material_id,
                    'quantity_required' => $item->quantity_required,
                    'unit' => $item->unit,
                    'cost_per_unit' => $item->cost_per_unit,
                    'waste_percentage' => $item->waste_percentage,
                    'notes' => $item->notes,
                    'sequence_order' => $item->sequence_order,
                ]);
            }

            DB::commit();

            $newRecipe->load(['product', 'recipeItems.rawMaterial']);

            return response()->json([
                'success' => true,
                'message' => 'Recipe duplicated successfully',
                'data' => $newRecipe
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate recipe: ' . $e->getMessage()
            ], 500);
        }
    }
}
