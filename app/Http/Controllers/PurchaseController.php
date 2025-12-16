<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\RawMaterial;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PurchaseController extends Controller
{
    protected PurchaseService $purchaseService;

    public function __construct(PurchaseService $purchaseService)
    {
        $this->purchaseService = $purchaseService;
    }

    /**
     * Display a listing of purchases
     */
    public function index(Request $request)
    {
        $query = Purchase::with(['supplier', 'items.rawMaterial']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by supplier
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('purchase_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('purchase_date', '<=', $request->end_date);
        }

        // Search by purchase number
        if ($request->filled('search')) {
            $query->where('purchase_number', 'like', '%' . $request->search . '%');
        }

        $purchases = $query->latest()->paginate(15)->withQueryString();
        $suppliers = Supplier::orderBy('name')->get();
        $statuses = Purchase::getStatuses();

        // Get statistics
        $stats = [
            'total_purchases' => Purchase::count(),
            'pending_count' => Purchase::pending()->count(),
            'received_count' => Purchase::received()->count(),
            'total_amount' => Purchase::whereMonth('purchase_date', now()->month)->sum('total_amount'),
        ];

        return view('purchases.index', compact('purchases', 'suppliers', 'statuses', 'stats'));
    }

    /**
     * Show the form for creating a new purchase
     * 
     * Note: Purchases are for RAW MATERIALS only.
     * Products are manufactured through Production Plans, not purchased.
     */
    public function create(Request $request)
    {
        $suppliers = Supplier::orderBy('name')->get();
        $rawMaterials = RawMaterial::orderBy('name')->get();
        
        // Get low stock materials for suggestions
        $lowStockMaterials = $this->purchaseService->getLowStockMaterials();
        
        // Pre-fill supplier if provided
        $selectedSupplier = $request->filled('supplier_id') 
            ? Supplier::find($request->supplier_id) 
            : null;

        // Get suggested order if supplier selected
        $suggestedItems = $selectedSupplier 
            ? $this->purchaseService->generateSuggestedOrder($selectedSupplier->id)
            : [];

        return view('purchases.create', compact(
            'suppliers', 
            'rawMaterials', 
            'lowStockMaterials',
            'selectedSupplier',
            'suggestedItems'
        ));
    }

    /**
     * Store a newly created purchase
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'status' => 'nullable|in:pending,approved,received',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.raw_material_id' => 'required|exists:raw_materials,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        try {
            // Filter out empty items
            $items = array_filter($validated['items'], function($item) {
                return !empty($item['raw_material_id']) && 
                       !empty($item['quantity']) && 
                       isset($item['unit_price']);
            });

            if (empty($items)) {
                return back()
                    ->withInput()
                    ->with('error', __('Please add at least one valid item.'));
            }

            $purchase = $this->purchaseService->createRawMaterialPurchase([
                'supplier_id' => $validated['supplier_id'],
                'purchase_date' => $validated['purchase_date'],
                'status' => $validated['status'] ?? 'pending',
                'notes' => $validated['notes'] ?? null,
                'items' => array_values($items), // Re-index array
            ]);

            $message = __('Purchase order :number created successfully.', ['number' => $purchase->purchase_number]);
            
            if ($purchase->status === 'received') {
                $message .= ' ' . __('Stock has been updated.');
            }

            return redirect()
                ->route('purchases.show', $purchase)
                ->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Purchase creation failed: ' . $e->getMessage(), [
                'data' => $validated,
                'trace' => $e->getTraceAsString()
            ]);
            return back()
                ->withInput()
                ->with('error', __('Failed to create purchase order: ') . $e->getMessage());
        }
    }

    /**
     * Display the specified purchase
     */
    public function show(Purchase $purchase)
    {
        $purchase->load(['supplier', 'items.rawMaterial', 'items.product']);
        
        return view('purchases.show', compact('purchase'));
    }

    /**
     * Show the form for editing the specified purchase
     */
    public function edit(Purchase $purchase)
    {
        if (!$purchase->canEdit()) {
            return redirect()
                ->route('purchases.show', $purchase)
                ->with('error', __('This purchase order cannot be edited.'));
        }

        $purchase->load(['items.rawMaterial']);
        $suppliers = Supplier::orderBy('name')->get();
        $rawMaterials = RawMaterial::orderBy('name')->get();

        return view('purchases.edit', compact('purchase', 'suppliers', 'rawMaterials'));
    }

    /**
     * Update the specified purchase
     */
    public function update(Request $request, Purchase $purchase)
    {
        if (!$purchase->canEdit()) {
            return redirect()
                ->route('purchases.show', $purchase)
                ->with('error', __('This purchase order cannot be edited.'));
        }

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'status' => 'nullable|in:pending,approved,received',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.raw_material_id' => 'required|exists:raw_materials,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        try {
            $purchase = $this->purchaseService->updatePurchase($purchase, [
                'supplier_id' => $validated['supplier_id'],
                'purchase_date' => $validated['purchase_date'],
                'status' => $validated['status'] ?? $purchase->status,
                'notes' => $validated['notes'] ?? null,
                'items' => $validated['items'],
            ]);

            return redirect()
                ->route('purchases.show', $purchase)
                ->with('success', __('Purchase order updated successfully.'));
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', __('Failed to update purchase order: ') . $e->getMessage());
        }
    }

    /**
     * Remove the specified purchase
     */
    public function destroy(Purchase $purchase)
    {
        if ($purchase->status === Purchase::STATUS_RECEIVED) {
            return redirect()
                ->route('purchases.index')
                ->with('error', __('Cannot delete a received purchase order.'));
        }

        $purchase->items()->delete();
        $purchase->delete();

        return redirect()
            ->route('purchases.index')
            ->with('success', __('Purchase order deleted successfully.'));
    }

    /**
     * Approve a pending purchase order
     */
    public function approve(Purchase $purchase)
    {
        if ($purchase->status !== Purchase::STATUS_PENDING) {
            return redirect()
                ->route('purchases.show', $purchase)
                ->with('error', __('Only pending purchases can be approved.'));
        }

        try {
            $this->purchaseService->approvePurchase($purchase);

            return redirect()
                ->route('purchases.show', $purchase)
                ->with('success', __('Purchase order approved successfully.'));
        } catch (\Exception $e) {
            return redirect()
                ->route('purchases.show', $purchase)
                ->with('error', __('Failed to approve purchase: ') . $e->getMessage());
        }
    }

    /**
     * Confirm a purchase order (approve and mark ready for receiving)
     */
    public function confirm(Purchase $purchase)
    {
        if (!in_array($purchase->status, [Purchase::STATUS_PENDING, Purchase::STATUS_APPROVED])) {
            return redirect()
                ->route('purchases.show', $purchase)
                ->with('error', __('This purchase order cannot be confirmed.'));
        }

        try {
            $this->purchaseService->confirmPurchase($purchase);

            return redirect()
                ->route('purchases.show', $purchase)
                ->with('success', __('Purchase order confirmed successfully.'));
        } catch (\Exception $e) {
            return redirect()
                ->route('purchases.show', $purchase)
                ->with('error', __('Failed to confirm purchase: ') . $e->getMessage());
        }
    }

    /**
     * Receive stock from purchase
     */
    public function receive(Request $request, Purchase $purchase)
    {
        if (!$purchase->canReceive()) {
            return redirect()
                ->route('purchases.show', $purchase)
                ->with('error', __('This purchase order cannot be received.'));
        }

        try {
            // Check if partial receive
            if ($request->has('partial') && $request->has('received_quantities')) {
                $this->purchaseService->partialReceive($purchase, $request->received_quantities);
                $message = __('Partial stock received successfully.');
            } else {
                $this->purchaseService->receiveStock($purchase);
                $message = __('Stock received successfully. Raw material quantities have been updated.');
            }

            return redirect()
                ->route('purchases.show', $purchase)
                ->with('success', $message);
        } catch (\Exception $e) {
            return redirect()
                ->route('purchases.show', $purchase)
                ->with('error', __('Failed to receive stock: ') . $e->getMessage());
        }
    }

    /**
     * Cancel a purchase order
     */
    public function cancel(Purchase $purchase)
    {
        if (!$purchase->canCancel()) {
            return redirect()
                ->route('purchases.show', $purchase)
                ->with('error', __('This purchase order cannot be cancelled.'));
        }

        try {
            $this->purchaseService->cancelPurchase($purchase);

            return redirect()
                ->route('purchases.show', $purchase)
                ->with('success', __('Purchase order cancelled successfully.'));
        } catch (\Exception $e) {
            return redirect()
                ->route('purchases.show', $purchase)
                ->with('error', __('Failed to cancel purchase order: ') . $e->getMessage());
        }
    }

    /**
     * Duplicate a purchase order
     */
    public function duplicate(Purchase $purchase)
    {
        $purchase->load(['items']);
        $suppliers = Supplier::orderBy('name')->get();
        $rawMaterials = RawMaterial::orderBy('name')->get();

        return view('purchases.create', [
            'suppliers' => $suppliers,
            'rawMaterials' => $rawMaterials,
            'duplicateFrom' => $purchase,
            'lowStockMaterials' => collect(),
            'selectedSupplier' => $purchase->supplier,
            'suggestedItems' => [],
        ]);
    }

    /**
     * Get raw materials for a supplier (AJAX)
     */
    public function getMaterialsBySupplier(Supplier $supplier)
    {
        $materials = RawMaterial::where('supplier_id', $supplier->id)
            ->orderBy('name')
            ->get(['id', 'name', 'unit', 'cost_per_unit', 'quantity', 'minimum_stock_level']);

        return response()->json($materials);
    }

    /**
     * Get suggested order for supplier (AJAX)
     */
    public function getSuggestedOrder(Request $request)
    {
        $supplierId = $request->input('supplier_id');
        $suggestions = $this->purchaseService->generateSuggestedOrder($supplierId);

        return response()->json($suggestions);
    }

    /**
     * Print purchase order
     */
    public function print(Purchase $purchase)
    {
        $purchase->load(['supplier', 'items.rawMaterial']);
        
        return view('purchases.print', compact('purchase'));
    }

    /**
     * Export purchases to CSV
     */
    public function export(Request $request)
    {
        $query = Purchase::with(['supplier', 'items.rawMaterial']);

        if ($request->filled('start_date')) {
            $query->whereDate('purchase_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('purchase_date', '<=', $request->end_date);
        }

        $purchases = $query->get();

        $filename = 'purchases_' . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($purchases) {
            $file = fopen('php://output', 'w');
            
            // Header row
            fputcsv($file, [
                'Purchase Number',
                'Date',
                'Supplier',
                'Status',
                'Total Amount',
                'Items Count',
            ]);

            foreach ($purchases as $purchase) {
                fputcsv($file, [
                    $purchase->purchase_number,
                    $purchase->purchase_date->format('Y-m-d'),
                    $purchase->supplier->name ?? 'N/A',
                    $purchase->status,
                    $purchase->total_amount,
                    $purchase->items->count(),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
