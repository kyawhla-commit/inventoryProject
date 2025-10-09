<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\RawMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class InventoryTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryTransaction::with(['product', 'rawMaterial', 'sale', 'purchase', 'createdBy']);
        
        // Search by transaction number or item name
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                  ->orWhereHas('product', function($productQuery) use ($search) {
                      $productQuery->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('rawMaterial', function($rawMaterialQuery) use ($search) {
                      $rawMaterialQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->get('type'));
        }

        // Filter by source
        if ($request->has('source')) {
            $query->where('source', $request->get('source'));
        }

        // Filter by product
        if ($request->has('product_id')) {
            $query->where('product_id', $request->get('product_id'));
        }

        // Filter by raw material
        if ($request->has('raw_material_id')) {
            $query->where('raw_material_id', $request->get('raw_material_id'));
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->get('date_from'));
        }
        if ($request->has('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->get('date_to'));
        }

        $perPage = $request->get('per_page', 15);
        $transactions = $query->latest('transaction_date')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:in,out,adjustment',
            'source' => 'required|in:sale,purchase,production,adjustment,return,transfer,waste',
            'product_id' => 'nullable|exists:products,id',
            'raw_material_id' => 'nullable|exists:raw_materials,id',
            'quantity' => 'required|numeric',
            'unit_cost' => 'nullable|numeric|min:0',
            'unit' => 'required|string|max:50',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'transaction_date' => 'required|date',
        ]);

        // Custom validation: must have either product_id or raw_material_id
        $validator->after(function ($validator) use ($request) {
            if (empty($request->product_id) && empty($request->raw_material_id)) {
                $validator->errors()->add('item', 'Either product_id or raw_material_id is required');
            }
            if (!empty($request->product_id) && !empty($request->raw_material_id)) {
                $validator->errors()->add('item', 'Cannot have both product_id and raw_material_id');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Get current stock
            $currentStock = 0;
            if ($request->product_id) {
                $item = Product::find($request->product_id);
                $currentStock = $item->quantity;
            } elseif ($request->raw_material_id) {
                $item = RawMaterial::find($request->raw_material_id);
                $currentStock = $item->current_stock;
            }

            // Calculate new stock based on transaction type
            $quantity = $request->quantity;
            if ($request->type === 'out' || ($request->type === 'adjustment' && $quantity < 0)) {
                $quantity = abs($quantity) * -1; // Make negative for outbound
            } else {
                $quantity = abs($quantity); // Make positive for inbound
            }

            $newStock = $currentStock + $quantity;

            // Validate stock levels
            if ($newStock < 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction would result in negative stock'
                ], 422);
            }

            // Create transaction record
            $transactionData = [
                'type' => $request->type,
                'source' => $request->source,
                'product_id' => $request->product_id,
                'raw_material_id' => $request->raw_material_id,
                'quantity' => $quantity,
                'unit_cost' => $request->unit_cost,
                'total_cost' => $request->unit_cost ? abs($quantity) * $request->unit_cost : null,
                'unit' => $request->unit,
                'stock_before' => $currentStock,
                'stock_after' => $newStock,
                'reason' => $request->reason,
                'notes' => $request->notes,
                'transaction_date' => $request->transaction_date,
            ];

            // Add created_by if user is authenticated
            if (Auth::check()) {
                $transactionData['created_by'] = Auth::id();
            }

            $transaction = InventoryTransaction::create($transactionData);

            // Update actual stock
            if ($request->product_id) {
                $item->update(['quantity' => $newStock]);
            } elseif ($request->raw_material_id) {
                $item->update(['current_stock' => $newStock]);
            }

            DB::commit();

            $transaction->load(['product', 'rawMaterial', 'createdBy']);

            return response()->json([
                'success' => true,
                'message' => 'Inventory transaction created successfully',
                'data' => $transaction
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create inventory transaction: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $transaction = InventoryTransaction::with([
            'product', 
            'rawMaterial', 
            'sale', 
            'purchase', 
            'productionPlanItem',
            'order',
            'createdBy',
            'approvedBy'
        ])->find($id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Inventory transaction not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $transaction
        ]);
    }

    public function getItemHistory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'nullable|exists:products,id',
            'raw_material_id' => 'nullable|exists:raw_materials,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        // Custom validation: must have either product_id or raw_material_id
        $validator->after(function ($validator) use ($request) {
            if (empty($request->product_id) && empty($request->raw_material_id)) {
                $validator->errors()->add('item', 'Either product_id or raw_material_id is required');
            }
            if (!empty($request->product_id) && !empty($request->raw_material_id)) {
                $validator->errors()->add('item', 'Cannot have both product_id and raw_material_id');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = InventoryTransaction::with(['sale', 'purchase', 'productionPlanItem', 'createdBy']);

        if ($request->product_id) {
            $query->where('product_id', $request->product_id);
            $item = Product::find($request->product_id);
        } else {
            $query->where('raw_material_id', $request->raw_material_id);
            $item = RawMaterial::find($request->raw_material_id);
        }

        // Date range filter
        if ($request->date_from) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        $transactions = $query->orderBy('transaction_date', 'desc')->get();

        // Calculate running totals
        $runningStock = $item ? ($request->product_id ? $item->quantity : $item->current_stock) : 0;
        $transactions = $transactions->map(function ($transaction) use (&$runningStock) {
            $transaction->running_stock = $runningStock;
            $runningStock -= $transaction->quantity; // Go backwards in time
            return $transaction;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'item' => $item,
                'current_stock' => $request->product_id ? $item->quantity : $item->current_stock,
                'transactions' => $transactions,
                'summary' => [
                    'total_transactions' => $transactions->count(),
                    'total_in' => $transactions->where('type', 'in')->sum('quantity'),
                    'total_out' => abs($transactions->where('type', 'out')->sum('quantity')),
                    'total_adjustments' => $transactions->where('type', 'adjustment')->sum('quantity'),
                ]
            ]
        ]);
    }

    public function createAdjustment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'nullable|exists:products,id',
            'raw_material_id' => 'nullable|exists:raw_materials,id',
            'adjustment_type' => 'required|in:increase,decrease,set',
            'quantity' => 'required|numeric|min:0',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'unit_cost' => 'nullable|numeric|min:0',
        ]);

        // Custom validation
        $validator->after(function ($validator) use ($request) {
            if (empty($request->product_id) && empty($request->raw_material_id)) {
                $validator->errors()->add('item', 'Either product_id or raw_material_id is required');
            }
            if (!empty($request->product_id) && !empty($request->raw_material_id)) {
                $validator->errors()->add('item', 'Cannot have both product_id and raw_material_id');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Get current stock
            if ($request->product_id) {
                $item = Product::find($request->product_id);
                $currentStock = $item->quantity;
                $unit = $item->unit ?? 'pcs';
            } else {
                $item = RawMaterial::find($request->raw_material_id);
                $currentStock = $item->current_stock;
                $unit = $item->unit;
            }

            // Calculate adjustment quantity
            $adjustmentQuantity = 0;
            switch ($request->adjustment_type) {
                case 'increase':
                    $adjustmentQuantity = $request->quantity;
                    break;
                case 'decrease':
                    $adjustmentQuantity = -$request->quantity;
                    break;
                case 'set':
                    $adjustmentQuantity = $request->quantity - $currentStock;
                    break;
            }

            $newStock = $currentStock + $adjustmentQuantity;

            // Validate new stock
            if ($newStock < 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Adjustment would result in negative stock'
                ], 422);
            }

            // Create adjustment transaction
            $transactionData = [
                'type' => 'adjustment',
                'source' => 'adjustment',
                'product_id' => $request->product_id,
                'raw_material_id' => $request->raw_material_id,
                'quantity' => $adjustmentQuantity,
                'unit_cost' => $request->unit_cost,
                'total_cost' => $request->unit_cost ? abs($adjustmentQuantity) * $request->unit_cost : null,
                'unit' => $unit,
                'stock_before' => $currentStock,
                'stock_after' => $newStock,
                'reason' => $request->reason,
                'notes' => $request->notes,
                'transaction_date' => now(),
            ];

            // Add created_by if user is authenticated
            if (Auth::check()) {
                $transactionData['created_by'] = Auth::id();
            }

            $transaction = InventoryTransaction::create($transactionData);

            // Update actual stock
            if ($request->product_id) {
                $item->update(['quantity' => $newStock]);
            } else {
                $item->update(['current_stock' => $newStock]);
            }

            DB::commit();

            $transaction->load(['product', 'rawMaterial', 'createdBy']);

            return response()->json([
                'success' => true,
                'message' => 'Stock adjustment created successfully',
                'data' => $transaction
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create stock adjustment: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getStockMovements(Request $request)
    {
        $query = InventoryTransaction::with(['product', 'rawMaterial', 'createdBy']);

        // Filter by date range (default to last 30 days)
        $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));
        
        $query->whereBetween('transaction_date', [$dateFrom, $dateTo]);

        // Group by type and source
        $movements = $query->get()->groupBy(['type', 'source']);

        $summary = [
            'date_range' => ['from' => $dateFrom, 'to' => $dateTo],
            'total_transactions' => $query->count(),
            'movements' => $movements,
            'totals_by_type' => [
                'in' => $query->where('type', 'in')->sum('quantity'),
                'out' => abs($query->where('type', 'out')->sum('quantity')),
                'adjustments' => $query->where('type', 'adjustment')->sum('quantity'),
            ],
            'totals_by_source' => $query->get()->groupBy('source')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total_quantity' => $group->sum('quantity'),
                    'total_cost' => $group->sum('total_cost'),
                ];
            }),
        ];

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    public function getLowStockItems()
    {
        // Get products with low stock
        $lowStockProducts = Product::whereColumn('quantity', '<=', 'min_quantity')
            ->orWhere('quantity', '<=', 10)
            ->with(['category'])
            ->get()
            ->map(function ($product) {
                return [
                    'type' => 'product',
                    'id' => $product->id,
                    'name' => $product->name,
                    'current_stock' => $product->quantity,
                    'min_stock' => $product->min_quantity,
                    'unit' => $product->unit ?? 'pcs',
                    'category' => $product->category->name ?? null,
                ];
            });

        // Get raw materials with low stock
        $lowStockRawMaterials = RawMaterial::whereColumn('current_stock', '<=', 'min_stock_level')
            ->orWhere('current_stock', '<=', 10)
            ->get()
            ->map(function ($rawMaterial) {
                return [
                    'type' => 'raw_material',
                    'id' => $rawMaterial->id,
                    'name' => $rawMaterial->name,
                    'current_stock' => $rawMaterial->current_stock,
                    'min_stock' => $rawMaterial->min_stock_level,
                    'unit' => $rawMaterial->unit,
                    'category' => null,
                ];
            });

        $lowStockItems = $lowStockProducts->concat($lowStockRawMaterials);

        return response()->json([
            'success' => true,
            'data' => [
                'low_stock_items' => $lowStockItems,
                'summary' => [
                    'total_items' => $lowStockItems->count(),
                    'products' => $lowStockProducts->count(),
                    'raw_materials' => $lowStockRawMaterials->count(),
                ]
            ]
        ]);
    }
}