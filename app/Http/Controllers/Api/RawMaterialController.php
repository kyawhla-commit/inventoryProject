<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RawMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RawMaterialController extends Controller
{
    public function index(Request $request)
    {
        $query = RawMaterial::query();
        
        // Search functionality
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('unit', 'like', "%{$search}%");
            });
        }

        // Filter by unit
        if ($request->has('unit')) {
            $query->where('unit', $request->get('unit'));
        }

        $perPage = $request->get('per_page', 15);
        $rawMaterials = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $rawMaterials
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'cost_per_unit' => 'required|numeric|min:0',
            'current_stock' => 'required|numeric|min:0',
            'min_stock_level' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $rawMaterial = RawMaterial::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Raw material created successfully',
            'data' => $rawMaterial
        ], 201);
    }

    public function show($id)
    {
        $rawMaterial = RawMaterial::find($id);

        if (!$rawMaterial) {
            return response()->json([
                'success' => false,
                'message' => 'Raw material not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $rawMaterial
        ]);
    }

    public function update(Request $request, $id)
    {
        $rawMaterial = RawMaterial::find($id);

        if (!$rawMaterial) {
            return response()->json([
                'success' => false,
                'message' => 'Raw material not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'unit' => 'sometimes|required|string|max:50',
            'cost_per_unit' => 'sometimes|required|numeric|min:0',
            'current_stock' => 'sometimes|required|numeric|min:0',
            'min_stock_level' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $rawMaterial->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Raw material updated successfully',
            'data' => $rawMaterial
        ]);
    }

    public function destroy($id)
    {
        $rawMaterial = RawMaterial::find($id);

        if (!$rawMaterial) {
            return response()->json([
                'success' => false,
                'message' => 'Raw material not found'
            ], 404);
        }

        $rawMaterial->delete();

        return response()->json([
            'success' => true,
            'message' => 'Raw material deleted successfully'
        ]);
    }

    public function lowStock(Request $request)
    {
        $rawMaterials = RawMaterial::whereColumn('current_stock', '<=', 'min_stock_level')
            ->orWhere('current_stock', '<=', 10) // Default threshold
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rawMaterials
        ]);
    }

    public function updateStock(Request $request, $id)
    {
        $rawMaterial = RawMaterial::find($id);

        if (!$rawMaterial) {
            return response()->json([
                'success' => false,
                'message' => 'Raw material not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'quantity' => 'required|numeric',
            'type' => 'required|in:add,subtract,set',
            'reason' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $quantity = $request->get('quantity');
        $type = $request->get('type');

        switch ($type) {
            case 'add':
                $rawMaterial->current_stock += $quantity;
                break;
            case 'subtract':
                if ($rawMaterial->current_stock < $quantity) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient stock'
                    ], 422);
                }
                $rawMaterial->current_stock -= $quantity;
                break;
            case 'set':
                $rawMaterial->current_stock = $quantity;
                break;
        }

        $rawMaterial->save();

        return response()->json([
            'success' => true,
            'message' => 'Stock updated successfully',
            'data' => $rawMaterial
        ]);
    }
}