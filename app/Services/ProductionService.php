<?php

namespace App\Services;

use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\ProductionPlan;
use App\Models\ProductionPlanItem;
use App\Models\RawMaterialUsage;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * ProductionService
 * 
 * Handles all production business logic including:
 * - Production execution with material deduction
 * - Stock management (add/deduct)
 * - Production cost tracking
 * - Material requirement validation
 */
class ProductionService
{
    protected RawMaterialService $rawMaterialService;

    public function __construct(RawMaterialService $rawMaterialService)
    {
        $this->rawMaterialService = $rawMaterialService;
    }

    /**
     * Execute production - deduct raw materials and add finished products to stock
     */
    public function executeProduction(ProductionPlan $plan, array $options = []): array
    {
        if ($plan->status !== 'in_progress') {
            throw new \Exception('Production plan must be in progress to execute.');
        }

        return DB::transaction(function () use ($plan, $options) {
            $results = [
                'products_added' => [],
                'materials_deducted' => [],
                'total_material_cost' => 0,
                'errors' => [],
            ];

            $plan->load('productionPlanItems.product.rawMaterials');

            foreach ($plan->productionPlanItems as $planItem) {
                $product = $planItem->product;
                $quantity = $options['quantities'][$planItem->id] ?? $planItem->planned_quantity;

                // Calculate and deduct raw materials
                $materialResult = $this->deductMaterialsForProduct($product, $quantity, $plan);
                
                if (!empty($materialResult['errors'])) {
                    $results['errors'] = array_merge($results['errors'], $materialResult['errors']);
                    continue;
                }

                $results['materials_deducted'] = array_merge(
                    $results['materials_deducted'], 
                    $materialResult['deducted']
                );
                $results['total_material_cost'] += $materialResult['total_cost'];

                // Add finished product to stock
                $oldStock = $product->quantity;
                $product->increment('quantity', $quantity);
                
                // Record stock movement for product
                $this->recordProductStockMovement($product, [
                    'type' => 'production',
                    'quantity' => $quantity,
                    'reference_type' => ProductionPlan::class,
                    'reference_id' => $plan->id,
                    'notes' => "Production from plan: {$plan->plan_number}",
                ]);

                $results['products_added'][] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity_added' => $quantity,
                    'old_stock' => $oldStock,
                    'new_stock' => $product->fresh()->quantity,
                    'material_cost' => $materialResult['total_cost'],
                ];

                // Update plan item
                $planItem->update([
                    'actual_quantity' => $quantity,
                    'actual_material_cost' => $materialResult['total_cost'],
                    'status' => 'completed',
                    'actual_end_date' => now(),
                ]);
            }

            // Update production plan
            $plan->update([
                'total_actual_cost' => $results['total_material_cost'],
            ]);

            return $results;
        });
    }

    /**
     * Deduct raw materials for producing a product
     */
    public function deductMaterialsForProduct(Product $product, float $quantity, ?ProductionPlan $plan = null): array
    {
        $result = [
            'deducted' => [],
            'total_cost' => 0,
            'errors' => [],
        ];

        $rawMaterials = $product->rawMaterials;

        foreach ($rawMaterials as $rawMaterial) {
            $pivot = $rawMaterial->pivot;
            $requiredQty = $pivot->quantity_required * $quantity;
            $wasteQty = $requiredQty * ($pivot->waste_percentage / 100);
            $totalRequired = $requiredQty + $wasteQty;

            // Check stock availability
            if ($rawMaterial->quantity < $totalRequired) {
                $result['errors'][] = [
                    'material' => $rawMaterial->name,
                    'required' => $totalRequired,
                    'available' => $rawMaterial->quantity,
                    'shortage' => $totalRequired - $rawMaterial->quantity,
                ];
                continue;
            }

            // Deduct stock
            $costPerUnit = $pivot->cost_per_unit ?? $rawMaterial->cost_per_unit;
            $totalCost = $totalRequired * $costPerUnit;

            $rawMaterial->decrement('quantity', $totalRequired);

            // Record usage
            RawMaterialUsage::create([
                'raw_material_id' => $rawMaterial->id,
                'product_id' => $product->id,
                'quantity_used' => $totalRequired,
                'cost_per_unit' => $costPerUnit,
                'total_cost' => $totalCost,
                'usage_date' => now(),
                'usage_type' => 'production',
                'batch_number' => $plan?->plan_number,
                'notes' => "Production: {$quantity} x {$product->name}",
                'recorded_by' => Auth::id(),
            ]);

            // Record stock movement
            StockMovement::create([
                'raw_material_id' => $rawMaterial->id,
                'type' => 'usage',
                'quantity' => -$totalRequired,
                'unit_price' => $costPerUnit,
                'reference_type' => $plan ? ProductionPlan::class : null,
                'reference_id' => $plan?->id,
                'notes' => "Production usage for {$product->name}",
                'created_by' => Auth::id(),
            ]);

            $result['deducted'][] = [
                'material_id' => $rawMaterial->id,
                'material_name' => $rawMaterial->name,
                'quantity_deducted' => $totalRequired,
                'unit' => $rawMaterial->unit,
                'cost' => $totalCost,
                'remaining_stock' => $rawMaterial->fresh()->quantity,
            ];

            $result['total_cost'] += $totalCost;
        }

        return $result;
    }

    /**
     * Validate material availability for production
     */
    public function validateMaterialAvailability(ProductionPlan $plan): array
    {
        $requirements = [];
        $shortages = [];
        $canProduce = true;

        $plan->load('productionPlanItems.product.rawMaterials');

        foreach ($plan->productionPlanItems as $planItem) {
            $product = $planItem->product;
            $quantity = $planItem->planned_quantity;

            foreach ($product->rawMaterials as $rawMaterial) {
                $pivot = $rawMaterial->pivot;
                $requiredQty = $pivot->quantity_required * $quantity;
                $wasteQty = $requiredQty * ($pivot->waste_percentage / 100);
                $totalRequired = $requiredQty + $wasteQty;

                // Aggregate requirements
                if (!isset($requirements[$rawMaterial->id])) {
                    $requirements[$rawMaterial->id] = [
                        'material' => $rawMaterial,
                        'total_required' => 0,
                        'available' => $rawMaterial->quantity,
                    ];
                }
                $requirements[$rawMaterial->id]['total_required'] += $totalRequired;
            }
        }

        // Check for shortages
        foreach ($requirements as $materialId => $req) {
            $shortage = $req['total_required'] - $req['available'];
            if ($shortage > 0) {
                $canProduce = false;
                $shortages[] = [
                    'material_id' => $materialId,
                    'material_name' => $req['material']->name,
                    'required' => $req['total_required'],
                    'available' => $req['available'],
                    'shortage' => $shortage,
                    'unit' => $req['material']->unit,
                ];
            }
        }

        return [
            'can_produce' => $canProduce,
            'requirements' => array_values($requirements),
            'shortages' => $shortages,
        ];
    }

    /**
     * Add stock to raw material (purchase/adjustment)
     */
    public function addRawMaterialStock(RawMaterial $material, float $quantity, float $unitPrice, string $type = 'purchase', ?string $notes = null): RawMaterial
    {
        return DB::transaction(function () use ($material, $quantity, $unitPrice, $type, $notes) {
            // Calculate new weighted average cost
            $newCost = $this->rawMaterialService->calculateWeightedAverageCost($material, $quantity, $unitPrice);
            
            // Update stock and cost
            $material->quantity += $quantity;
            $material->cost_per_unit = $newCost;
            $material->save();

            // Record stock movement
            StockMovement::create([
                'raw_material_id' => $material->id,
                'type' => $type,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'notes' => $notes ?? "Stock added: {$type}",
                'created_by' => Auth::id(),
            ]);

            return $material->fresh();
        });
    }

    /**
     * Add stock to finished product
     */
    public function addProductStock(Product $product, float $quantity, ?float $cost = null, string $type = 'adjustment', ?string $notes = null): Product
    {
        return DB::transaction(function () use ($product, $quantity, $cost, $type, $notes) {
            $product->increment('quantity', $quantity);

            $this->recordProductStockMovement($product, [
                'type' => $type,
                'quantity' => $quantity,
                'unit_price' => $cost ?? $product->cost,
                'notes' => $notes ?? "Stock adjustment: +{$quantity}",
            ]);

            return $product->fresh();
        });
    }

    /**
     * Deduct stock from finished product (sale/adjustment)
     */
    public function deductProductStock(Product $product, float $quantity, string $type = 'sale', ?string $notes = null): Product
    {
        if ($product->quantity < $quantity) {
            throw new \Exception("Insufficient stock. Available: {$product->quantity}, Required: {$quantity}");
        }

        return DB::transaction(function () use ($product, $quantity, $type, $notes) {
            $product->decrement('quantity', $quantity);

            $this->recordProductStockMovement($product, [
                'type' => $type,
                'quantity' => -$quantity,
                'unit_price' => $product->cost,
                'notes' => $notes ?? "Stock deduction: -{$quantity}",
            ]);

            return $product->fresh();
        });
    }

    /**
     * Get production cost breakdown
     */
    public function getProductionCostBreakdown(ProductionPlan $plan): array
    {
        $plan->load('productionPlanItems.product.rawMaterials');
        
        $breakdown = [
            'items' => [],
            'total_material_cost' => 0,
            'total_estimated_cost' => 0,
            'variance' => 0,
        ];

        foreach ($plan->productionPlanItems as $planItem) {
            $product = $planItem->product;
            $quantity = $planItem->planned_quantity;
            $materialCost = 0;

            $materials = [];
            foreach ($product->rawMaterials as $rawMaterial) {
                $pivot = $rawMaterial->pivot;
                $requiredQty = $pivot->quantity_required * $quantity;
                $wasteQty = $requiredQty * ($pivot->waste_percentage / 100);
                $totalRequired = $requiredQty + $wasteQty;
                $costPerUnit = $pivot->cost_per_unit ?? $rawMaterial->cost_per_unit;
                $cost = $totalRequired * $costPerUnit;

                $materials[] = [
                    'name' => $rawMaterial->name,
                    'quantity' => $totalRequired,
                    'unit' => $rawMaterial->unit,
                    'cost_per_unit' => $costPerUnit,
                    'total_cost' => $cost,
                ];

                $materialCost += $cost;
            }

            $breakdown['items'][] = [
                'product' => $product->name,
                'quantity' => $quantity,
                'materials' => $materials,
                'material_cost' => $materialCost,
                'cost_per_unit' => $quantity > 0 ? $materialCost / $quantity : 0,
            ];

            $breakdown['total_material_cost'] += $materialCost;
        }

        $breakdown['total_estimated_cost'] = $plan->total_estimated_cost ?? 0;
        $breakdown['variance'] = $breakdown['total_material_cost'] - $breakdown['total_estimated_cost'];

        return $breakdown;
    }

    /**
     * Record product stock movement
     */
    protected function recordProductStockMovement(Product $product, array $data): void
    {
        try {
            StockMovement::create([
                'product_id' => $product->id,
                'type' => $data['type'] ?? 'adjustment',
                'quantity' => $data['quantity'],
                'unit_price' => $data['unit_price'] ?? $product->cost,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);
        } catch (\Exception $e) {
            Log::warning("Failed to record product stock movement: " . $e->getMessage());
        }
    }

    /**
     * Get production statistics
     */
    public function getProductionStats(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();

        $plans = ProductionPlan::whereBetween('created_at', [$startDate, $endDate])->get();
        $completedPlans = $plans->where('status', 'completed');

        $totalMaterialCost = RawMaterialUsage::whereBetween('usage_date', [$startDate, $endDate])
            ->where('usage_type', 'production')
            ->sum('total_cost');

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'total_plans' => $plans->count(),
            'completed_plans' => $completedPlans->count(),
            'in_progress_plans' => $plans->where('status', 'in_progress')->count(),
            'total_material_cost' => $totalMaterialCost,
            'average_cost_per_plan' => $completedPlans->count() > 0 
                ? $totalMaterialCost / $completedPlans->count() 
                : 0,
        ];
    }
}
