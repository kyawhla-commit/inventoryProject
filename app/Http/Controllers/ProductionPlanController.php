<?php

namespace App\Http\Controllers;

use App\Models\ProductionPlan;
use App\Models\ProductionPlanItem;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\Order;
use App\Models\RawMaterial;
use App\Models\RawMaterialUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductionPlanController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductionPlan::with(['createdBy', 'approvedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('planned_start_date', [$request->start_date, $request->end_date]);
        }

        $plans = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('production-plans.index', compact('plans'));
    }

    public function create()
    {
        $products = Product::with('activeRecipe')->get();
        $orders = Order::whereIn('status', ['pending', 'confirmed'])->with('customer')->get();

        return view('production-plans.create', compact('products', 'orders'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'planned_start_date' => 'required|date',
            'planned_end_date' => 'required|date|after_or_equal:planned_start_date',
            'notes' => 'nullable|string',
            'plan_items' => 'required|array|min:1',
            'plan_items.*.product_id' => 'required|exists:products,id',
            'plan_items.*.recipe_id' => 'nullable|exists:recipes,id',
            'plan_items.*.order_id' => 'nullable|exists:orders,id',
            'plan_items.*.planned_quantity' => 'required|numeric|min:0.001',
            'plan_items.*.unit' => 'required|string|max:50',
            'plan_items.*.planned_start_date' => 'required|date',
            'plan_items.*.planned_end_date' => 'required|date|after_or_equal:plan_items.*.planned_start_date',
            'plan_items.*.priority' => 'required|integer|min:1',
            'plan_items.*.notes' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($request) {
            $plan = ProductionPlan::create([
                'name' => $request->name,
                'description' => $request->description,
                'planned_start_date' => $request->planned_start_date,
                'planned_end_date' => $request->planned_end_date,
                'status' => 'draft',
                'created_by' => Auth::id(),
                'notes' => $request->notes,
            ]);

            $totalEstimatedCost = 0;

            foreach ($request->plan_items as $item) {
                if (!empty($item['product_id']) && !empty($item['planned_quantity'])) {
                    $recipe = null;
                    $estimatedCost = 0;

                    if (!empty($item['recipe_id'])) {
                        $recipe = Recipe::find($item['recipe_id']);
                        if ($recipe) {
                            $requirements = $recipe->calculateMaterialRequirements($item['planned_quantity']);
                            $estimatedCost = $requirements->sum('estimated_cost');
                        }
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
                        'priority' => $item['priority'],
                        'notes' => $item['notes'],
                    ]);

                    $totalEstimatedCost += $estimatedCost;
                }
            }

            $plan->update(['total_estimated_cost' => $totalEstimatedCost]);
        });

        return redirect()->route('production-plans.index')
            ->with('success', 'Production plan created successfully.');
    }

    public function show(ProductionPlan $productionPlan)
    {
        $productionPlan->load([
            'productionPlanItems.product',
            'productionPlanItems.recipe',
            'productionPlanItems.order.customer',
            'createdBy',
            'approvedBy'
        ]);

        $materialRequirements = $productionPlan->calculateMaterialRequirements();
        
        // Get material usage records for this production plan
        $materialUsages = RawMaterialUsage::where('batch_number', $productionPlan->plan_number)
            ->with(['rawMaterial', 'product', 'recordedBy'])
            ->orderBy('usage_date', 'desc')
            ->get();

        return view('production-plans.show', compact('productionPlan', 'materialRequirements', 'materialUsages'));
    }

    public function edit(ProductionPlan $productionPlan)
    {
        if ($productionPlan->status === 'completed') {
            return redirect()->route('production-plans.show', $productionPlan)
                ->with('error', 'Cannot edit completed production plan.');
        }

        $productionPlan->load('productionPlanItems');
        $products = Product::with('activeRecipe')->get();
        $orders = Order::whereIn('status', ['pending', 'confirmed'])->with('customer')->get();

        return view('production-plans.edit', compact('productionPlan', 'products', 'orders'));
    }

    public function update(Request $request, ProductionPlan $productionPlan)
    {
        if ($productionPlan->status === 'completed') {
            return redirect()->route('production-plans.show', $productionPlan)
                ->with('error', 'Cannot update completed production plan.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'planned_start_date' => 'required|date',
            'planned_end_date' => 'required|date|after_or_equal:planned_start_date',
            'notes' => 'nullable|string',
            'plan_items' => 'required|array|min:1',
            'plan_items.*.product_id' => 'required|exists:products,id',
            'plan_items.*.recipe_id' => 'nullable|exists:recipes,id',
            'plan_items.*.order_id' => 'nullable|exists:orders,id',
            'plan_items.*.planned_quantity' => 'required|numeric|min:0.001',
            'plan_items.*.unit' => 'required|string|max:50',
            'plan_items.*.planned_start_date' => 'required|date',
            'plan_items.*.planned_end_date' => 'required|date|after_or_equal:plan_items.*.planned_start_date',
            'plan_items.*.priority' => 'required|integer|min:1',
            'plan_items.*.notes' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($request, $productionPlan) {
            $productionPlan->update([
                'name' => $request->name,
                'description' => $request->description,
                'planned_start_date' => $request->planned_start_date,
                'planned_end_date' => $request->planned_end_date,
                'notes' => $request->notes,
            ]);

            // Delete existing items
            $productionPlan->productionPlanItems()->delete();

            $totalEstimatedCost = 0;

            foreach ($request->plan_items as $item) {
                if (!empty($item['product_id']) && !empty($item['planned_quantity'])) {
                    $recipe = null;
                    $estimatedCost = 0;

                    if (!empty($item['recipe_id'])) {
                        $recipe = Recipe::find($item['recipe_id']);
                        if ($recipe) {
                            $requirements = $recipe->calculateMaterialRequirements($item['planned_quantity']);
                            $estimatedCost = $requirements->sum('estimated_cost');
                        }
                    }

                    ProductionPlanItem::create([
                        'production_plan_id' => $productionPlan->id,
                        'product_id' => $item['product_id'],
                        'recipe_id' => $item['recipe_id'],
                        'order_id' => $item['order_id'] ?? null, 
                        'planned_quantity' => $item['planned_quantity'],
                        'unit' => $item['unit'],
                        'estimated_material_cost' => $estimatedCost,
                        'planned_start_date' => $item['planned_start_date'],
                        'planned_end_date' => $item['planned_end_date'],
                        'priority' => $item['priority'],
                        'notes' => $item['notes'],
                    ]);

                    $totalEstimatedCost += $estimatedCost;
                }
            }

            $productionPlan->update(['total_estimated_cost' => $totalEstimatedCost]);
        });

        return redirect()->route('production-plans.show', $productionPlan)
            ->with('success', 'Production plan updated successfully.');
    }

    public function destroy(ProductionPlan $productionPlan)
    {
        if (in_array($productionPlan->status, ['in_progress', 'completed'])) {
            return redirect()->route('production-plans.index')
                ->with('error', 'Cannot delete production plan that is in progress or completed.');
        }

        $productionPlan->delete();

        return redirect()->route('production-plans.index')
            ->with('success', 'Production plan deleted successfully.');
    }

    public function approve(ProductionPlan $productionPlan)
    {
        if ($productionPlan->status !== 'draft') {
            return redirect()->route('production-plans.show', $productionPlan)
                ->with('error', 'Only draft plans can be approved.');
        }

        $productionPlan->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return redirect()->route('production-plans.show', $productionPlan)
            ->with('success', 'Production plan approved successfully.');
    }

    public function start(ProductionPlan $productionPlan)
    {
        if ($productionPlan->status !== 'approved') {
            return redirect()->route('production-plans.show', $productionPlan)
                ->with('error', 'Only approved plans can be started.');
        }

        $productionPlan->update([
            'status' => 'in_progress',
            'actual_start_date' => now(),
        ]);

        return redirect()->route('production-plans.show', $productionPlan)
            ->with('success', 'Production plan started successfully.');
    }


    public function complete(ProductionPlan $productionPlan)
    {
        if ($productionPlan->status !== 'in_progress') {
            return redirect()->back()
                ->with('error', 'Only in-progress plans can be completed.');
        }

        $updatedProducts = [];

        try {
            DB::beginTransaction();
    
            // Reload to get fresh data
            $productionPlan->load('productionPlanItems.product');
    
            // Update production plan status
            $productionPlan->status = 'completed';
            $productionPlan->actual_end_date = now();
            $productionPlan->save();
    
            // Add produced quantities to products
            foreach ($productionPlan->productionPlanItems as $planItem) {
                // Always use planned_quantity as actual_quantity when completing
                $quantityToAdd = $planItem->planned_quantity;
                
                if ($quantityToAdd > 0 && $planItem->product) {
                    // Get current stock before update
                    $oldStock = $planItem->product->quantity;
                    
                    // Update stock
                    $planItem->product->increment('quantity', $quantityToAdd);
                    
                    // Refresh to get new stock
                    $planItem->product->refresh();
                    $newStock = $planItem->product->quantity;
                    
                    // Track updated products for confirmation message
                    $updatedProducts[] = [
                        'name' => $planItem->product->name,
                        'added' => $quantityToAdd,
                        'old_stock' => $oldStock,
                        'new_stock' => $newStock,
                    ];
                    
                    // Update plan item status and set actual_quantity equal to planned_quantity
                    $planItem->status = 'completed';
                    $planItem->actual_quantity = $quantityToAdd;
                    $planItem->actual_end_date = now();
                    $planItem->save();
                }
            }
    
            DB::commit();
    
            // Create detailed success message
            $message = 'Production plan completed successfully! Stock updated for ' . count($updatedProducts) . ' product(s):';
            foreach ($updatedProducts as $product) {
                $message .= sprintf(
                    " %s (+%s, now %s)",
                    $product['name'],
                    number_format($product['added'], 2),
                    number_format($product['new_stock'], 2)
                );
            }
    
            return redirect()->route('production-plans.show', $productionPlan)
                ->with('success', $message);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to complete production plan: ' . $e->getMessage());
        }
    }
    

    public function materialRequirements(ProductionPlan $productionPlan)
    {
        $requirements = $productionPlan->calculateMaterialRequirements();
        
        // Check availability
        $requirements = collect($requirements)->map(function ($requirement) {
            $rawMaterial = RawMaterial::find($requirement['raw_material_id']);
            
            // Add default value if key doesn't exist
            $totalRequired = $requirement['total_required'] ?? 0;
            
            $requirement['available_quantity'] = $rawMaterial->quantity;
            $requirement['shortage'] = max(0, $totalRequired - $rawMaterial->quantity);
            $requirement['is_sufficient'] = $rawMaterial->quantity >= $totalRequired;
            return $requirement;
        });
        // Get all available raw materials for additional usage
        $availableRawMaterials = RawMaterial::where('quantity', '>', 0)->get();

        return view('production-plans.material-requirements', compact('productionPlan', 'requirements', 'availableRawMaterials'));
    }

    public function recordActualUsage(ProductionPlan $productionPlan, Request $request)
    {
        $request->validate([
            'plan_item_id' => 'required|exists:production_plan_items,id',
            'actual_quantity' => 'required|numeric|min:0',
            'material_usages' => 'required|array',
            'material_usages.*.raw_material_id' => 'required|exists:raw_materials,id',
            'material_usages.*.quantity_used' => 'required|numeric|min:0.001',
            'material_usages.*.usage_type' => 'required|string',
        ]);

        DB::transaction(function () use ($request, $productionPlan) {
            $planItem = ProductionPlanItem::findOrFail($request->plan_item_id);
            
            // Update actual quantity
            $planItem->update([
                'actual_quantity' => $request->actual_quantity,
                'status' => 'completed',
                'actual_end_date' => now(),
            ]);

            $totalActualCost = 0;

            // Record material usages
            foreach ($request->material_usages as $usage) {
                if ($usage['quantity_used'] > 0) {
                    $rawMaterial = RawMaterial::findOrFail($usage['raw_material_id']);
                    
                    $materialUsage = RawMaterialUsage::create([
                        'raw_material_id' => $usage['raw_material_id'],
                        'product_id' => $planItem->product_id,
                        'order_id' => $planItem->order_id,
                        'quantity_used' => $usage['quantity_used'],
                        'cost_per_unit' => $rawMaterial->cost_per_unit,
                        'total_cost' => $usage['quantity_used'] * $rawMaterial->cost_per_unit,
                        'usage_date' => now(),
                        'usage_type' => $usage['usage_type'],
                        'notes' => "Production Plan: {$productionPlan->plan_number}",
                        'recorded_by' => Auth::id(),
                    ]);

                    $materialUsage->updateRawMaterialStock();
                    $totalActualCost += $materialUsage->total_cost;
                }
            }

            // Update actual material cost
            $planItem->update(['actual_material_cost' => $totalActualCost]);

            // Update production plan total actual cost
            $productionPlan->update([
                'total_actual_cost' => $productionPlan->productionPlanItems->sum('actual_material_cost')
            ]);
        });

        return redirect()->route('production-plans.show', $productionPlan)
            ->with('success', 'Actual usage recorded successfully.');
    }
}
