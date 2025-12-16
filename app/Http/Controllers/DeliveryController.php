<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\Order;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeliveryController extends Controller
{
    /**
     * Display a listing of deliveries
     */
    public function index(Request $request)
    {
        $query = Delivery::with(['order', 'customer']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date
        if ($request->filled('date')) {
            $query->whereDate('scheduled_date', $request->date);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('scheduled_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('scheduled_date', '<=', $request->end_date);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('delivery_number', 'like', "%{$search}%")
                  ->orWhere('driver_name', 'like', "%{$search}%")
                  ->orWhere('contact_name', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $deliveries = $query->latest()->paginate(15)->withQueryString();

        // Stats
        $stats = [
            'total' => Delivery::count(),
            'pending' => Delivery::pending()->count(),
            'in_progress' => Delivery::inProgress()->count(),
            'today' => Delivery::today()->count(),
            'delivered_today' => Delivery::today()->completed()->count(),
        ];

        $statuses = Delivery::getStatuses();

        return view('deliveries.index', compact('deliveries', 'stats', 'statuses'));
    }

    /**
     * Show form to create delivery from order
     */
    public function create(Request $request)
    {
        $order = null;
        if ($request->filled('order_id')) {
            $order = Order::with('customer')->findOrFail($request->order_id);
        }

        $orders = Order::with('customer')
            ->whereDoesntHave('delivery')
            ->whereIn('status', ['confirmed', 'processing', 'shipped'])
            ->orderBy('order_date', 'desc')
            ->get();

        return view('deliveries.create', compact('orders', 'order'));
    }

    /**
     * Store a new delivery
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'delivery_address' => 'required|string|max:500',
            'contact_name' => 'required|string|max:255',
            'contact_phone' => 'required|string|max:50',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'nullable|date_format:H:i',
            'driver_name' => 'nullable|string|max:255',
            'driver_phone' => 'nullable|string|max:50',
            'vehicle_number' => 'nullable|string|max:50',
            'delivery_fee' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $order = Order::with('customer')->findOrFail($validated['order_id']);

        // Check if delivery already exists
        if ($order->delivery) {
            return back()->with('error', __('Delivery already exists for this order.'));
        }

        $delivery = Delivery::create([
            'delivery_number' => Delivery::generateDeliveryNumber(),
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'status' => $validated['driver_name'] ? Delivery::STATUS_ASSIGNED : Delivery::STATUS_PENDING,
            'delivery_address' => $validated['delivery_address'],
            'contact_name' => $validated['contact_name'],
            'contact_phone' => $validated['contact_phone'],
            'scheduled_date' => $validated['scheduled_date'],
            'scheduled_time' => $validated['scheduled_time'],
            'driver_name' => $validated['driver_name'],
            'driver_phone' => $validated['driver_phone'],
            'vehicle_number' => $validated['vehicle_number'],
            'delivery_fee' => $validated['delivery_fee'] ?? 0,
            'notes' => $validated['notes'],
            'created_by' => Auth::id(),
        ]);

        // Record initial status
        $delivery->statusHistories()->create([
            'status' => $delivery->status,
            'notes' => __('Delivery created'),
            'updated_by' => Auth::id(),
        ]);

        return redirect()
            ->route('deliveries.show', $delivery)
            ->with('success', __('Delivery :number created successfully.', ['number' => $delivery->delivery_number]));
    }

    /**
     * Display delivery details
     */
    public function show(Delivery $delivery)
    {
        $delivery->load(['order.items.product', 'customer', 'statusHistories.updater']);
        $statuses = Delivery::getStatuses();

        return view('deliveries.show', compact('delivery', 'statuses'));
    }

    /**
     * Show edit form
     */
    public function edit(Delivery $delivery)
    {
        if (!$delivery->canUpdate()) {
            return redirect()
                ->route('deliveries.show', $delivery)
                ->with('error', __('This delivery cannot be edited.'));
        }

        return view('deliveries.edit', compact('delivery'));
    }

    /**
     * Update delivery
     */
    public function update(Request $request, Delivery $delivery)
    {
        if (!$delivery->canUpdate()) {
            return redirect()
                ->route('deliveries.show', $delivery)
                ->with('error', __('This delivery cannot be edited.'));
        }

        $validated = $request->validate([
            'delivery_address' => 'required|string|max:500',
            'contact_name' => 'required|string|max:255',
            'contact_phone' => 'required|string|max:50',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'nullable|date_format:H:i',
            'driver_name' => 'nullable|string|max:255',
            'driver_phone' => 'nullable|string|max:50',
            'vehicle_number' => 'nullable|string|max:50',
            'delivery_fee' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $delivery->update(array_merge($validated, [
            'updated_by' => Auth::id(),
        ]));

        return redirect()
            ->route('deliveries.show', $delivery)
            ->with('success', __('Delivery updated successfully.'));
    }

    /**
     * Update delivery status
     */
    public function updateStatus(Request $request, Delivery $delivery)
    {
        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', array_keys(Delivery::getStatuses())),
            'notes' => 'nullable|string|max:500',
            'location' => 'nullable|string|max:255',
            'recipient_name' => 'nullable|string|max:255',
            'delivery_notes' => 'nullable|string|max:1000',
            'actual_cost' => 'nullable|numeric|min:0',
        ]);

        if (!$delivery->canUpdate() && $validated['status'] !== Delivery::STATUS_CANCELLED) {
            return back()->with('error', __('This delivery status cannot be changed.'));
        }

        // Update additional fields for delivered status
        if ($validated['status'] === Delivery::STATUS_DELIVERED) {
            $delivery->recipient_name = $validated['recipient_name'] ?? null;
            $delivery->delivery_notes = $validated['delivery_notes'] ?? null;
            $delivery->actual_cost = $validated['actual_cost'] ?? null;
        }

        $delivery->updateStatus(
            $validated['status'],
            $validated['notes'],
            $validated['location'] ?? null
        );

        // Update order status if delivered
        if ($validated['status'] === Delivery::STATUS_DELIVERED) {
            $delivery->order->update(['status' => 'completed']);
        }

        return back()->with('success', __('Delivery status updated to :status.', [
            'status' => Delivery::getStatuses()[$validated['status']],
        ]));
    }

    /**
     * Assign driver to delivery
     */
    public function assignDriver(Request $request, Delivery $delivery)
    {
        $validated = $request->validate([
            'driver_name' => 'required|string|max:255',
            'driver_phone' => 'required|string|max:50',
            'vehicle_number' => 'nullable|string|max:50',
        ]);

        $delivery->update([
            'driver_name' => $validated['driver_name'],
            'driver_phone' => $validated['driver_phone'],
            'vehicle_number' => $validated['vehicle_number'],
            'updated_by' => Auth::id(),
        ]);

        if ($delivery->status === Delivery::STATUS_PENDING) {
            $delivery->updateStatus(Delivery::STATUS_ASSIGNED, __('Driver assigned'));
        }

        return back()->with('success', __('Driver assigned successfully.'));
    }

    /**
     * Cancel delivery
     */
    public function cancel(Request $request, Delivery $delivery)
    {
        if (!$delivery->canCancel()) {
            return back()->with('error', __('This delivery cannot be cancelled.'));
        }

        $reason = $request->input('reason', __('Cancelled by user'));
        $delivery->updateStatus(Delivery::STATUS_CANCELLED, $reason);

        return redirect()
            ->route('deliveries.show', $delivery)
            ->with('success', __('Delivery cancelled.'));
    }

    /**
     * Dashboard view for today's deliveries
     */
    public function dashboard()
    {
        $todayDeliveries = Delivery::with(['order', 'customer'])
            ->today()
            ->orderBy('scheduled_time')
            ->get();

        $pendingDeliveries = Delivery::with(['order', 'customer'])
            ->pending()
            ->orderBy('scheduled_date')
            ->limit(10)
            ->get();

        $inProgressDeliveries = Delivery::with(['order', 'customer'])
            ->inProgress()
            ->orderBy('scheduled_date')
            ->get();

        $stats = [
            'today_total' => $todayDeliveries->count(),
            'today_pending' => $todayDeliveries->where('status', Delivery::STATUS_PENDING)->count(),
            'today_in_progress' => $todayDeliveries->whereIn('status', [
                Delivery::STATUS_ASSIGNED,
                Delivery::STATUS_PICKED_UP,
                Delivery::STATUS_IN_TRANSIT,
            ])->count(),
            'today_delivered' => $todayDeliveries->where('status', Delivery::STATUS_DELIVERED)->count(),
            'today_failed' => $todayDeliveries->where('status', Delivery::STATUS_FAILED)->count(),
        ];

        return view('deliveries.dashboard', compact(
            'todayDeliveries',
            'pendingDeliveries',
            'inProgressDeliveries',
            'stats'
        ));
    }
}
