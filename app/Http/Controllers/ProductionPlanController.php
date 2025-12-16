<?php

namespace App\Http\Controllers;

use App\Models\ProductionPlan;
use App\Models\ProductionPlanItem;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\Order;
use App\Models\RawMaterial;
use App\Models\RawMaterialUsage;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        // Load relationships for validation
        $productionPlan->load('productionPlanItems.product.rawMaterials');
        
        // Validate material availability before starting
        $validation = $this->validateMaterialsForCompletion($productionPlan);
        
        if (!$validation['can_complete']) {
            return redirect()->route('production-plans.show', $productionPlan)
                ->with('warning', 'Warning: ' . $validation['message'] . '. Production started but may not complete successfully.');
        }

        $productionPlan->update([
            'status' => 'in_progress',
            'actual_start_date' => now(),
        ]);

        return redirect()->route('production-plans.show', $productionPlan)
            ->with('success', 'Production plan started successfully. All materials are available.');
    }


    public function complete(ProductionPlan $productionPlan)
    {
        if ($productionPlan->status !== 'in_progress') {
            return redirect()->back()
                ->with('error', 'Only in-progress plans can be completed.');
        }

        try {
            DB::beginTransaction();
    
            // Reload to get fresh data with all relationships
            $productionPlan->load('productionPlanItems.product.rawMaterials');
    
            // First, validate all materials are available
            $validationResult = $this->validateMaterialsForCompletion($productionPlan);
            if (!$validationResult['can_complete']) {
                DB::rollBack();
                return redirect()->back()
                    ->with('error', 'Cannot complete production: ' . $validationResult['message']);
            }

            $updatedProducts = [];
            $deductedMaterials = [];
            $totalMaterialCost = 0;
            $itemMaterialCosts = [];
    
            // Process each production item
            foreach ($productionPlan->productionPlanItems as $planItem) {
                $quantityToAdd = $planItem->planned_quantity ?? 0;
                
                if ($quantityToAdd <= 0 || !$planItem->product) {
                    continue;
                }

                $product = $planItem->product;
                $itemCost = 0;
                
                // Deduct raw materials for this product
                foreach ($product->rawMaterials as $rawMaterial) {
                    $pivot = $rawMaterial->pivot;
                    $requiredQty = ($pivot->quantity_required ?? 0) * $quantityToAdd;
                    $wasteQty = $requiredQty * (($pivot->waste_percentage ?? 0) / 100);
                    $totalRequired = $requiredQty + $wasteQty;
                    
                    if ($totalRequired <= 0) {
                        continue;
                    }
                    
                    // Final check for stock availability
                    if ($rawMaterial->quantity < $totalRequired) {
                        throw new \Exception(
                            "Insufficient stock for {$rawMaterial->name}. " .
                            "Required: " . number_format($totalRequired, 2) . " {$rawMaterial->unit}, " .
                            "Available: " . number_format($rawMaterial->quantity, 2) . " {$rawMaterial->unit}"
                        );
                    }
                    
                    // Calculate cost
                    $costPerUnit = $pivot->cost_per_unit ?? $rawMaterial->cost_per_unit ?? 0;
                    $materialCost = $totalRequired * $costPerUnit;
                    
                    // Deduct stock
                    $rawMaterial->decrement('quantity', $totalRequired);
                    
                    // Record material usage
                    RawMaterialUsage::create([
                        'raw_material_id' => $rawMaterial->id,
                        'product_id' => $product->id,
                        'quantity_used' => $totalRequired,
                        'cost_per_unit' => $costPerUnit,
                        'total_cost' => $materialCost,
                        'usage_date' => now(),
                        'usage_type' => 'production',
                        'batch_number' => $productionPlan->plan_number,
                        'notes' => "Production Plan #{$productionPlan->plan_number}: {$quantityToAdd} x {$product->name}",
                        'recorded_by' => Auth::id(),
                    ]);
                    
                    // Record stock movement for audit trail
                    StockMovement::create([
                        'raw_material_id' => $rawMaterial->id,
                        'type' => 'usage',
                        'quantity' => -$totalRequired,
                        'unit_price' => $costPerUnit,
                        'reference_type' => ProductionPlan::class,
                        'reference_id' => $productionPlan->id,
                        'notes' => "Production: {$product->name} (Plan: {$productionPlan->plan_number})",
                        'created_by' => Auth::id(),
                    ]);
                    
                    $deductedMaterials[] = [
                        'name' => $rawMaterial->name,
                        'quantity' => $totalRequired,
                        'unit' => $rawMaterial->unit,
                        'cost' => $materialCost,
                    ];
                    
                    $itemCost += $materialCost;
                    $totalMaterialCost += $materialCost;
                }
                
                // Store item cost for later update
                $itemMaterialCosts[$planItem->id] = $itemCost;
                
                // Get current stock before update
                $oldStock = $product->quantity;
                
                // Add finished product to stock
                $product->increment('quantity', $quantityToAdd);
                
                // Record stock movement for finished product
                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'production',
                    'quantity' => $quantityToAdd,
                    'unit_price' => $product->cost ?? 0,
                    'reference_type' => ProductionPlan::class,
                    'reference_id' => $productionPlan->id,
                    'notes' => "Production output (Plan: {$productionPlan->plan_number})",
                    'created_by' => Auth::id(),
                ]);
                
                // Refresh to get new stock
                $product->refresh();
                
                // Track updated products
                $updatedProducts[] = [
                    'name' => $product->name,
                    'added' => $quantityToAdd,
                    'old_stock' => $oldStock,
                    'new_stock' => $product->quantity,
                    'material_cost' => $itemCost,
                ];
                
                // Update plan item
                $planItem->update([
                    'status' => 'completed',
                    'actual_quantity' => $quantityToAdd,
                    'actual_material_cost' => $itemCost,
                    'actual_end_date' => now(),
                ]);
            }

            // Update production plan status and costs
            $productionPlan->update([
                'status' => 'completed',
                'actual_end_date' => now(),
                'total_actual_cost' => $totalMaterialCost,
            ]);
    
            DB::commit();
    
            // Create detailed success message
            $message = '✓ Production completed successfully! ';
            $message .= count($updatedProducts) . ' product(s) added to stock. ';
            $message .= count($deductedMaterials) . ' material(s) deducted. ';
            $message .= 'Total cost: ' . number_format($totalMaterialCost, 0) . ' Ks';
    
            return redirect()->route('production-plans.show', $productionPlan)
                ->with('success', $message);
    
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Production completion failed: ' . $e->getMessage(), [
                'plan_id' => $productionPlan->id,
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->with('error', 'Failed to complete production: ' . $e->getMessage());
        }
    }

    /**
     * Validate all materials are available before completing production
     */
    protected function validateMaterialsForCompletion(ProductionPlan $plan): array
    {
        $requirements = [];
        $shortages = [];
        
        foreach ($plan->productionPlanItems as $planItem) {
            if (!$planItem->product) continue;
            
            $quantity = $planItem->planned_quantity ?? 0;
            
            foreach ($planItem->product->rawMaterials as $rawMaterial) {
                $pivot = $rawMaterial->pivot;
                $requiredQty = ($pivot->quantity_required ?? 0) * $quantity;
                $wasteQty = $requiredQty * (($pivot->waste_percentage ?? 0) / 100);
                $totalRequired = $requiredQty + $wasteQty;
                
                // Aggregate requirements by material
                if (!isset($requirements[$rawMaterial->id])) {
                    $requirements[$rawMaterial->id] = [
                        'material' => $rawMaterial,
                        'total_required' => 0,
                    ];
                }
                $requirements[$rawMaterial->id]['total_required'] += $totalRequired;
            }
        }
        
        // Check for shortages
        foreach ($requirements as $materialId => $req) {
            $material = $req['material'];
            $shortage = $req['total_required'] - $material->quantity;
            
            if ($shortage > 0) {
                $shortages[] = "{$material->name}: need " . number_format($req['total_required'], 2) . 
                              " {$material->unit}, have " . number_format($material->quantity, 2);
            }
        }
        
        if (!empty($shortages)) {
            return [
                'can_complete' => false,
                'message' => 'Insufficient materials: ' . implode('; ', $shortages),
                'shortages' => $shortages,
            ];
        }
        
        return [
            'can_complete' => true,
            'message' => 'All materials available',
            'shortages' => [],
        ];
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
