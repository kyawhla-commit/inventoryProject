<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Product;
use App\Models\RawMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $query = Purchase::with(['supplier', 'items.product']);
        
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function($supplierQuery) use ($search) {
                      $supplierQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->get('supplier_id'));
        }

        if ($request->has('date_from')) {
            $query->whereDate('purchase_date', '>=', $request->get('date_from'));
        }
        if ($request->has('date_to')) {
            $query->whereDate('purchase_date', '<=', $request->get('date_to'));
        }

        $perPage = $request->get('per_page', 15);
        $purchases = $query->latest('purchase_date')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $purchases
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'status' => 'required|in:pending,ordered,received,cancelled',
            'order_id' => 'nullable|exists:orders,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.raw_material_id' => 'nullable|exists:raw_materials,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        $validator->after(function ($validator) use ($request) {
            if ($request->has('items')) {
                foreach ($request->items as $index => $item) {
                    if (empty($item['product_id']) && empty($item['raw_material_id'])) {
                        $validator->errors()->add("items.{$index}", 'Each item must have either a product_id or raw_material_id');
                    }
                    if (!empty($item['product_id']) && !empty($item['raw_material_id'])) {
                        $validator->errors()->add("items.{$index}", 'Each item cannot have both product_id and raw_material_id');
                    }
                }
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
            $totalAmount = collect($request->items)->sum(function ($item) {
                return $item['quantity'] * $item['unit_cost'];
            });

            $purchase = Purchase::create([
                'supplier_id' => $request->supplier_id,
                'purchase_date' => $request->purchase_date,
                'total_amount' => $totalAmount,
                'status' => $request->status,
                'order_id' => $request->order_id,
            ]);

            foreach ($request->items as $item) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'] ?? null,
                    'raw_material_id' => $item['raw_material_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                ]);
            }

            DB::commit();

            $purchase->load(['supplier', 'items.product']);

            return response()->json([
                'success' => true,
                'message' => 'Purchase created successfully',
                'data' => $purchase
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create purchase: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $purchase = Purchase::with(['supplier', 'items.product', 'order'])->find($id);

        if (!$purchase) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $purchase
        ]);
    }

    public function update(Request $request, $id)
    {
        $purchase = Purchase::find($id);

        if (!$purchase) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'supplier_id' => 'sometimes|required|exists:suppliers,id',
            'purchase_date' => 'sometimes|required|date',
            'status' => 'sometimes|required|in:pending,ordered,received,cancelled',
            'items' => 'sometimes|required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.raw_material_id' => 'nullable|exists:raw_materials,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
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
            if ($request->has('items')) {
                $purchase->items()->delete();

                $totalAmount = collect($request->items)->sum(function ($item) {
                    return $item['quantity'] * $item['unit_cost'];
                });

                foreach ($request->items as $item) {
                    PurchaseItem::create([
                        'purchase_id' => $purchase->id,
                        'product_id' => $item['product_id'] ?? null,
                        'raw_material_id' => $item['raw_material_id'] ?? null,
                        'quantity' => $item['quantity'],
                        'unit_cost' => $item['unit_cost'],
                    ]);
                }

                $purchase->update(['total_amount' => $totalAmount]);
            }

            $purchase->update($request->only(['supplier_id', 'purchase_date', 'status']));

            DB::commit();

            $purchase->load(['supplier', 'items.product']);

            return response()->json([
                'success' => true,
                'message' => 'Purchase updated successfully',
                'data' => $purchase
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update purchase: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $purchase = Purchase::with('items')->find($id);

        if (!$purchase) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase not found'
            ], 404);
        }

        if ($purchase->status === 'received') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete received purchase. Stock has been updated.'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $purchase->items()->delete();
            $purchase->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete purchase: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $purchase = Purchase::with('items')->find($id);

        if (!$purchase) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,ordered,received,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $oldStatus = $purchase->status;
        $newStatus = $request->status;

        DB::beginTransaction();
        try {
            if ($newStatus === 'received' && $oldStatus !== 'received') {
                foreach ($purchase->items as $item) {
                    if ($item->product_id) {
                        $product = Product::find($item->product_id);
                        if ($product) {
                            $product->increment('quantity', $item->quantity);
                            if ($item->unit_cost > 0) {
                                $product->update(['cost' => $item->unit_cost]);
                            }
                        }
                    } elseif ($item->raw_material_id) {
                        $rawMaterial = RawMaterial::find($item->raw_material_id);
                        if ($rawMaterial) {
                            $rawMaterial->increment('current_stock', $item->quantity);
                            if ($item->unit_cost > 0) {
                                $rawMaterial->update(['cost_per_unit' => $item->unit_cost]);
                            }
                        }
                    }
                }
            }

            if ($oldStatus === 'received' && $newStatus !== 'received') {
                foreach ($purchase->items as $item) {
                    if ($item->product_id) {
                        $product = Product::find($item->product_id);
                        if ($product && $product->quantity >= $item->quantity) {
                            $product->decrement('quantity', $item->quantity);
                        }
                    } elseif ($item->raw_material_id) {
                        $rawMaterial = RawMaterial::find($item->raw_material_id);
                        if ($rawMaterial && $rawMaterial->current_stock >= $item->quantity) {
                            $rawMaterial->decrement('current_stock', $item->quantity);
                        }
                    }
                }
            }

            $purchase->update(['status' => $newStatus]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase status updated successfully',
                'data' => $purchase
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update purchase status: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getByStatus($status)
    {
        $validStatuses = ['pending', 'ordered', 'received', 'cancelled'];
        
        if (!in_array($status, $validStatuses)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status'
            ], 422);
        }

        $purchases = Purchase::with(['supplier', 'items.product'])
            ->where('status', $status)
            ->latest('purchase_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $purchases
        ]);
    }

    public function receive($id)
    {
        $purchase = Purchase::with('items')->find($id);

        if (!$purchase) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase not found'
            ], 404);
        }

        if ($purchase->status === 'received') {
            return response()->json([
                'success' => false,
                'message' => 'Purchase has already been received'
            ], 422);
        }

        return $this->updateStatus(new Request(['status' => 'received']), $id);
    }
}