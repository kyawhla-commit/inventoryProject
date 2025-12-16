<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\RawMaterial;
use App\Models\StockMovement;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Display a listing of products with filters
     */
    public function index(Request $request)
    {
        $query = Product::with('category');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Stock status filter
        if ($request->filled('stock_status')) {
            switch ($request->stock_status) {
                case 'in_stock':
                    $query->where('quantity', '>', 0)
                          ->whereColumn('quantity', '>', 'minimum_stock_level');
                    break;
                case 'low_stock':
                    $query->where('quantity', '>', 0)
                          ->whereColumn('quantity', '<=', 'minimum_stock_level');
                    break;
                case 'out_of_stock':
                    $query->where('quantity', '<=', 0);
                    break;
            }
        }

        // Sorting
        $sortBy = $request->input('sort', 'created_at');
        $sortDir = $request->input('dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $products = $query->paginate(15)->withQueryString();
        $categories = Category::orderBy('name')->get();

        // Statistics
        $stats = [
            'total' => Product::count(),
            'in_stock' => Product::where('quantity', '>', 0)->whereColumn('quantity', '>', 'minimum_stock_level')->count(),
            'low_stock' => Product::where('quantity', '>', 0)->whereColumn('quantity', '<=', 'minimum_stock_level')->count(),
            'out_of_stock' => Product::where('quantity', '<=', 0)->count(),
            'total_value' => Product::sum(DB::raw('quantity * COALESCE(cost, 0)')),
        ];

        return view('products.index', compact('products', 'categories', 'stats'));
    }

    /**
     * Show the form for creating a new product
     */
    public function create()
    {
        $categories = Category::orderBy('name')->get();
        $rawMaterials = RawMaterial::orderBy('name')->get();
        return view('products.create', compact('categories', 'rawMaterials'));
    }

    /**
     * Store a newly created product
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'barcode' => 'nullable|string|max:255|unique:products',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'quantity' => 'required|integer|min:0',
            'unit' => 'nullable|string|max:50',
            'price' => 'required|numeric|min:0',
            'cost' => 'required|numeric|min:0',
            'minimum_stock_level' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'raw_materials' => 'nullable|array',
            'raw_materials.*.raw_material_id' => 'required_with:raw_materials|exists:raw_materials,id',
            'raw_materials.*.quantity_required' => 'required_with:raw_materials|numeric|min:0.001',
            'raw_materials.*.unit' => 'required_with:raw_materials|string|max:50',
            'raw_materials.*.cost_per_unit' => 'nullable|numeric|min:0',
            'raw_materials.*.waste_percentage' => 'nullable|numeric|min:0|max:100',
            'raw_materials.*.is_primary' => 'boolean',
            'raw_materials.*.notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $input = $validated;

            // Handle image upload
            if ($image = $request->file('image')) {
                $profileImage = uniqid() . '.' . $image->getClientOriginalExtension();
                $image->storeAs('public/images', $profileImage);
                $input['image'] = 'storage/images/' . $profileImage;
            }

            // Create the product
            $product = Product::create($input);

            // Handle raw material relationships
            if ($request->has('raw_materials') && is_array($request->raw_materials)) {
                $this->attachRawMaterials($product, $request->raw_materials);
            }

            // Record initial stock if quantity > 0
            if ($product->quantity > 0) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'initial',
                    'quantity' => $product->quantity,
                    'unit_price' => $product->cost,
                    'notes' => 'Initial stock on product creation',
                    'created_by' => auth()->id(),
                ]);
            }

            DB::commit();

            return redirect()
                ->route('products.show', $product)
                ->with('success', __('Product created successfully.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', __('Failed to create product: ') . $e->getMessage());
        }
    }

    /**
     * Display the specified product with analytics
     */
    public function show(Product $product)
    {
        $analytics = $this->productService->getProductWithAnalytics($product);
        
        // Get recent stock movements
        $stockMovements = StockMovement::where('product_id', $product->id)
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('products.show', array_merge($analytics, [
            'stockMovements' => $stockMovements,
        ]));
    }

    /**
     * Show the form for editing the specified product
     */
    public function edit(Product $product)
    {
        $categories = Category::orderBy('name')->get();
        $rawMaterials = RawMaterial::orderBy('name')->get();
        $product->load('rawMaterials');
        return view('products.edit', compact('product', 'categories', 'rawMaterials'));
    }

    /**
     * Update the specified product
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'barcode' => 'nullable|string|max:255|unique:products,barcode,' . $product->id,
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'quantity' => 'required|integer|min:0',
            'unit' => 'nullable|string|max:50',
            'price' => 'required|numeric|min:0',
            'cost' => 'required|numeric|min:0',
            'minimum_stock_level' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'raw_materials' => 'nullable|array',
            'raw_materials.*.raw_material_id' => 'required_with:raw_materials|exists:raw_materials,id',
            'raw_materials.*.quantity_required' => 'required_with:raw_materials|numeric|min:0.001',
            'raw_materials.*.unit' => 'required_with:raw_materials|string|max:50',
            'raw_materials.*.cost_per_unit' => 'nullable|numeric|min:0',
            'raw_materials.*.waste_percentage' => 'nullable|numeric|min:0|max:100',
            'raw_materials.*.is_primary' => 'boolean',
            'raw_materials.*.notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $oldQuantity = $product->quantity;
            $input = $validated;

            // Handle image upload
            if ($image = $request->file('image')) {
                $profileImage = uniqid() . '.' . $image->getClientOriginalExtension();
                $image->storeAs('public/images', $profileImage);
                $input['image'] = 'storage/images/' . $profileImage;
            } else {
                unset($input['image']);
            }

            $product->update($input);

            // Handle raw material relationships
            if ($request->has('raw_materials') && is_array($request->raw_materials)) {
                $product->rawMaterials()->detach();
                $this->attachRawMaterials($product, $request->raw_materials);
            }

            // Record stock change if quantity changed
            $quantityDiff = $product->quantity - $oldQuantity;
            if ($quantityDiff != 0) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'adjustment',
                    'quantity' => $quantityDiff,
                    'unit_price' => $product->cost,
                    'notes' => 'Stock adjusted during product update',
                    'created_by' => auth()->id(),
                ]);
            }

            DB::commit();

            return redirect()
                ->route('products.show', $product)
                ->with('success', __('Product updated successfully.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', __('Failed to update product: ') . $e->getMessage());
        }
    }

    /**
     * Remove the specified product
     */
    public function destroy(Product $product)
    {
        try {
            // Check if product has sales or orders
            if ($product->orderItems()->exists()) {
                return back()->with('error', __('Cannot delete product with existing orders.'));
            }

            $product->rawMaterials()->detach();
            $product->delete();

            return redirect()
                ->route('products.index')
                ->with('success', __('Product deleted successfully.'));
        } catch (\Exception $e) {
            return back()->with('error', __('Failed to delete product: ') . $e->getMessage());
        }
    }

    /**
     * Adjust product stock
     */
    public function adjustStock(Request $request, Product $product)
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric',
            'type' => 'required|in:add,deduct,adjustment',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $quantity = $validated['type'] === 'deduct' 
                ? -abs($validated['quantity']) 
                : $validated['quantity'];

            if ($validated['type'] === 'deduct' && $product->quantity < abs($quantity)) {
                return back()->with('error', __('Insufficient stock. Available: :qty', ['qty' => $product->quantity]));
            }

            $this->productService->adjustStock(
                $product,
                $quantity,
                $validated['type'],
                $validated['notes']
            );

            return back()->with('success', __('Stock adjusted successfully.'));
        } catch (\Exception $e) {
            return back()->with('error', __('Failed to adjust stock: ') . $e->getMessage());
        }
    }

    /**
     * Calculate production cost
     */
    public function calculateCost(Request $request, Product $product)
    {
        $quantity = $request->input('quantity', 1);
        $costData = $this->productService->calculateProductionCost($product, $quantity);

        if ($request->ajax()) {
            return response()->json($costData);
        }

        return view('products.cost-calculator', compact('product', 'costData'));
    }

    /**
     * Duplicate a product
     */
    public function duplicate(Product $product)
    {
        $product->load('rawMaterials');
        $categories = Category::orderBy('name')->get();
        $rawMaterials = RawMaterial::orderBy('name')->get();

        return view('products.create', [
            'categories' => $categories,
            'rawMaterials' => $rawMaterials,
            'duplicateFrom' => $product,
        ]);
    }

    /**
     * Attach raw materials to a product
     */
    private function attachRawMaterials(Product $product, array $rawMaterialsData)
    {
        foreach ($rawMaterialsData as $index => $materialData) {
            if (!empty($materialData['raw_material_id']) && !empty($materialData['quantity_required'])) {
                $rawMaterial = RawMaterial::find($materialData['raw_material_id']);
                
                if ($rawMaterial) {
                    $product->rawMaterials()->attach($materialData['raw_material_id'], [
                        'quantity_required' => $materialData['quantity_required'],
                        'unit' => $materialData['unit'],
                        'cost_per_unit' => $materialData['cost_per_unit'] ?? $rawMaterial->cost_per_unit,
                        'waste_percentage' => $materialData['waste_percentage'] ?? 0,
                        'notes' => $materialData['notes'] ?? null,
                        'is_primary' => $materialData['is_primary'] ?? false,
                        'sequence_order' => $index + 1,
                    ]);
                }
            }
        }
    }
}
