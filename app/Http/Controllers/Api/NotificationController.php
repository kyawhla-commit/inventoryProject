<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Product;
use App\Models\RawMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Notification::with(['user', 'product', 'rawMaterial', 'order', 'sale']);
        
        // Filter by user (default to authenticated user)
        $userId = $request->get('user_id', Auth::id());
        if ($userId) {
            $query->where('user_id', $userId);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->get('type'));
        }

        // Filter by priority
        if ($request->has('priority')) {
            $query->where('priority', $request->get('priority'));
        }

        $perPage = $request->get('per_page', 20);
        $notifications = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $notifications
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|max:100',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'user_id' => 'nullable|exists:users,id',
            'product_id' => 'nullable|exists:products,id',
            'raw_material_id' => 'nullable|exists:raw_materials,id',
            'order_id' => 'nullable|exists:orders,id',
            'sale_id' => 'nullable|exists:sales,id',
            'production_plan_id' => 'nullable|exists:production_plans,id',
            'data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $notification = Notification::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Notification created successfully',
            'data' => $notification
        ], 201);
    }

    public function show($id)
    {
        $notification = Notification::with([
            'user', 'product', 'rawMaterial', 'order', 'sale', 'productionPlan'
        ])->find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $notification
        ]);
    }

    public function markAsRead($id)
    {
        $notification = Notification::find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
            'data' => $notification
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        $userId = $request->get('user_id', Auth::id());
        
        $updated = Notification::where('user_id', $userId)
            ->where('status', 'unread')
            ->update([
                'status' => 'read',
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => "Marked {$updated} notifications as read"
        ]);
    }

    public function dismiss($id)
    {
        $notification = Notification::find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        }

        $notification->dismiss();

        return response()->json([
            'success' => true,
            'message' => 'Notification dismissed',
            'data' => $notification
        ]);
    }

    public function getUnreadCount(Request $request)
    {
        $userId = $request->get('user_id', Auth::id());
        
        $count = Notification::where('user_id', $userId)
            ->where('status', 'unread')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $count
            ]
        ]);
    }

    public function checkLowStock()
    {
        // Check low stock products
        $lowStockProducts = Product::whereColumn('quantity', '<=', 'min_quantity')
            ->orWhere('quantity', '<=', 5)
            ->get();

        foreach ($lowStockProducts as $product) {
            // Check if notification already exists for this product
            $existingNotification = Notification::where('type', 'low_stock')
                ->where('product_id', $product->id)
                ->where('status', 'unread')
                ->first();

            if (!$existingNotification) {
                Notification::create([
                    'type' => 'low_stock',
                    'title' => 'Low Stock Alert',
                    'message' => "Product '{$product->name}' is running low. Current stock: {$product->quantity}, Minimum: {$product->min_quantity}",
                    'priority' => $product->quantity <= 0 ? 'urgent' : 'high',
                    'product_id' => $product->id,
                    'data' => [
                        'current_stock' => $product->quantity,
                        'min_stock' => $product->min_quantity,
                        'shortage' => max(0, $product->min_quantity - $product->quantity),
                    ],
                ]);
            }
        }

        // Check low stock raw materials
        $lowStockRawMaterials = RawMaterial::whereColumn('current_stock', '<=', 'min_stock_level')
            ->orWhere('current_stock', '<=', 5)
            ->get();

        foreach ($lowStockRawMaterials as $rawMaterial) {
            $existingNotification = Notification::where('type', 'low_stock')
                ->where('raw_material_id', $rawMaterial->id)
                ->where('status', 'unread')
                ->first();

            if (!$existingNotification) {
                Notification::create([
                    'type' => 'low_stock',
                    'title' => 'Low Stock Alert - Raw Material',
                    'message' => "Raw material '{$rawMaterial->name}' is running low. Current stock: {$rawMaterial->current_stock}, Minimum: {$rawMaterial->min_stock_level}",
                    'priority' => $rawMaterial->current_stock <= 0 ? 'urgent' : 'high',
                    'raw_material_id' => $rawMaterial->id,
                    'data' => [
                        'current_stock' => $rawMaterial->current_stock,
                        'min_stock' => $rawMaterial->min_stock_level,
                        'shortage' => max(0, $rawMaterial->min_stock_level - $rawMaterial->current_stock),
                    ],
                ]);
            }
        }

        $totalNotifications = $lowStockProducts->count() + $lowStockRawMaterials->count();

        return response()->json([
            'success' => true,
            'message' => "Checked stock levels and created {$totalNotifications} notifications",
            'data' => [
                'low_stock_products' => $lowStockProducts->count(),
                'low_stock_raw_materials' => $lowStockRawMaterials->count(),
                'total_notifications' => $totalNotifications,
            ]
        ]);
    }

    public function createOrderNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'status' => 'required|string',
            'message' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $orderId = $request->order_id;
        $status = $request->status;
        $customMessage = $request->message;

        $message = $customMessage ?: "Order #{$orderId} status updated to: {$status}";

        $notification = Notification::create([
            'type' => 'order_update',
            'title' => 'Order Status Update',
            'message' => $message,
            'priority' => 'medium',
            'order_id' => $orderId,
            'data' => [
                'order_id' => $orderId,
                'new_status' => $status,
                'updated_at' => now()->toISOString(),
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order notification created successfully',
            'data' => $notification
        ], 201);
    }

    public function getNotificationTypes()
    {
        $types = [
            'low_stock' => 'Low Stock Alert',
            'order_update' => 'Order Status Update',
            'production_complete' => 'Production Completed',
            'purchase_received' => 'Purchase Received',
            'system_alert' => 'System Alert',
            'user_action' => 'User Action Required',
        ];

        return response()->json([
            'success' => true,
            'data' => $types
        ]);
    }

    public function getStats(Request $request)
    {
        $userId = $request->get('user_id', Auth::id());
        
        $query = Notification::query();
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $stats = [
            'total' => $query->count(),
            'unread' => $query->where('status', 'unread')->count(),
            'read' => $query->where('status', 'read')->count(),
            'dismissed' => $query->where('status', 'dismissed')->count(),
            'by_priority' => [
                'urgent' => $query->where('priority', 'urgent')->count(),
                'high' => $query->where('priority', 'high')->count(),
                'medium' => $query->where('priority', 'medium')->count(),
                'low' => $query->where('priority', 'low')->count(),
            ],
            'by_type' => $query->select('type')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}