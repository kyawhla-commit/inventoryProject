<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Invoice;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * OrderService
 * 
 * Handles all order-related business logic including:
 * - Order creation with stock validation
 * - Order status management
 * - Stock deduction on order confirmation
 * - Order to sale conversion
 * - Invoice generation
 */
class OrderService
{
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Create a new order with stock validation
     */
    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            // Validate stock availability
            $stockValidation = $this->validateStockAvailability($data['items']);
            if (!$stockValidation['is_valid']) {
                throw new \Exception('Insufficient stock: ' . implode(', ', $stockValidation['errors']));
            }

            // Calculate total
            $totalAmount = $this->calculateTotal($data['items']);

            // Create order
            $order = Order::create([
                'customer_id' => $data['customer_id'],
                'order_date' => $data['order_date'] ?? now(),
                'total_amount' => $totalAmount,
                'status' => $data['status'] ?? self::STATUS_PENDING,
                'notes' => $data['notes'] ?? null,
            ]);

            // Create order items
            foreach ($data['items'] as $item) {
                $product = Product::find($item['product_id']);
                
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'] ?? $product->price,
                ]);
            }

            // If status is confirmed or beyond, deduct stock
            if (in_array($order->status, [self::STATUS_CONFIRMED, self::STATUS_PROCESSING, self::STATUS_SHIPPED, self::STATUS_COMPLETED])) {
                $this->deductStock($order);
            }

            // Auto-create invoice if configured
            if ($data['create_invoice'] ?? false) {
                $this->createInvoice($order);
            }

            return $order->load('items.product', 'customer');
        });
    }

    /**
     * Update order status with business logic
     */
    public function updateStatus(Order $order, string $newStatus): Order
    {
        $oldStatus = $order->status;

        // Validate status transition
        if (!$this->isValidStatusTransition($oldStatus, $newStatus)) {
            throw new \Exception("Invalid status transition from {$oldStatus} to {$newStatus}");
        }

        return DB::transaction(function () use ($order, $oldStatus, $newStatus) {
            // Handle stock based on status change
            if ($this->shouldDeductStock($oldStatus, $newStatus)) {
                $this->deductStock($order);
            } elseif ($this->shouldRestoreStock($oldStatus, $newStatus)) {
                $this->restoreStock($order);
            }

            // Update status
            $order->update(['status' => $newStatus]);

            // Auto-actions based on new status
            if ($newStatus === self::STATUS_COMPLETED && !$order->sales()->exists()) {
                $this->convertToSale($order);
            }

            return $order->fresh();
        });
    }

    /**
     * Confirm order - validates stock and deducts it
     */
    public function confirmOrder(Order $order): Order
    {
        if ($order->status !== self::STATUS_PENDING) {
            throw new \Exception('Only pending orders can be confirmed.');
        }

        // Re-validate stock
        $items = $order->items->map(fn($item) => [
            'product_id' => $item->product_id,
            'quantity' => $item->quantity,
        ])->toArray();

        $validation = $this->validateStockAvailability($items);
        if (!$validation['is_valid']) {
            throw new \Exception('Cannot confirm order. ' . implode(', ', $validation['errors']));
        }

        return $this->updateStatus($order, self::STATUS_CONFIRMED);
    }

    /**
     * Cancel order - restores stock if already deducted
     */
    public function cancelOrder(Order $order, ?string $reason = null): Order
    {
        if ($order->status === self::STATUS_COMPLETED) {
            throw new \Exception('Completed orders cannot be cancelled.');
        }

        return DB::transaction(function () use ($order, $reason) {
            // Restore stock if it was deducted
            if (in_array($order->status, [self::STATUS_CONFIRMED, self::STATUS_PROCESSING, self::STATUS_SHIPPED])) {
                $this->restoreStock($order);
            }

            $order->update([
                'status' => self::STATUS_CANCELLED,
                'notes' => $order->notes . ($reason ? "\nCancellation reason: {$reason}" : ''),
            ]);

            return $order->fresh();
        });
    }

    /**
     * Convert order to sale
     */
    public function convertToSale(Order $order): Sale
    {
        if ($order->sales()->exists()) {
            return $order->sales()->first();
        }

        return DB::transaction(function () use ($order) {
            $sale = Sale::create([
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'sale_date' => now(),
                'total_amount' => $order->total_amount,
            ]);

            foreach ($order->items as $orderItem) {
                $sale->items()->create([
                    'product_id' => $orderItem->product_id,
                    'quantity' => $orderItem->quantity,
                    'unit_price' => $orderItem->price,
                ]);
            }

            // Update order status
            $order->update(['status' => self::STATUS_COMPLETED]);

            return $sale;
        });
    }

    /**
     * Create invoice for order
     */
    public function createInvoice(Order $order): Invoice
    {
        if ($order->invoice) {
            return $order->invoice;
        }

        $invoice = Invoice::create([
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'subtotal' => $order->total_amount,
            'tax_amount' => 0,
            'total_amount' => $order->total_amount,
            'status' => 'pending',
        ]);

        foreach ($order->items as $item) {
            $invoice->items()->create([
                'product_id' => $item->product_id,
                'description' => $item->product->name ?? 'Product',
                'quantity' => $item->quantity,
                'unit_price' => $item->price,
                'total' => $item->quantity * $item->price,
            ]);
        }

        return $invoice;
    }

    /**
     * Validate stock availability for order items
     */
    public function validateStockAvailability(array $items): array
    {
        $errors = [];
        $isValid = true;

        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            if (!$product) {
                $errors[] = "Product ID {$item['product_id']} not found";
                $isValid = false;
                continue;
            }

            if ($product->quantity < $item['quantity']) {
                $errors[] = "{$product->name}: need {$item['quantity']}, have {$product->quantity}";
                $isValid = false;
            }
        }

        return [
            'is_valid' => $isValid,
            'errors' => $errors,
        ];
    }

    /**
     * Deduct stock for order items
     */
    protected function deductStock(Order $order): void
    {
        foreach ($order->items as $item) {
            if (!$item->product) continue;

            $item->product->decrement('quantity', $item->quantity);

            // Record stock movement
            StockMovement::create([
                'product_id' => $item->product_id,
                'type' => 'sale',
                'quantity' => -$item->quantity,
                'unit_price' => $item->price,
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'notes' => "Order #{$order->id} - Stock reserved",
                'created_by' => Auth::id(),
            ]);
        }
    }

    /**
     * Restore stock for cancelled/modified orders
     */
    protected function restoreStock(Order $order): void
    {
        foreach ($order->items as $item) {
            if (!$item->product) continue;

            $item->product->increment('quantity', $item->quantity);

            // Record stock movement
            StockMovement::create([
                'product_id' => $item->product_id,
                'type' => 'return',
                'quantity' => $item->quantity,
                'unit_price' => $item->price,
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'notes' => "Order #{$order->id} - Stock restored",
                'created_by' => Auth::id(),
            ]);
        }
    }

    /**
     * Calculate order total
     */
    protected function calculateTotal(array $items): float
    {
        return collect($items)->sum(function ($item) {
            $price = $item['price'] ?? Product::find($item['product_id'])?->price ?? 0;
            return $item['quantity'] * $price;
        });
    }

    /**
     * Check if status transition is valid
     */
    protected function isValidStatusTransition(string $from, string $to): bool
    {
        $validTransitions = [
            self::STATUS_PENDING => [self::STATUS_CONFIRMED, self::STATUS_CANCELLED],
            self::STATUS_CONFIRMED => [self::STATUS_PROCESSING, self::STATUS_CANCELLED],
            self::STATUS_PROCESSING => [self::STATUS_SHIPPED, self::STATUS_CANCELLED],
            self::STATUS_SHIPPED => [self::STATUS_COMPLETED, self::STATUS_CANCELLED],
            self::STATUS_COMPLETED => [], // No transitions from completed
            self::STATUS_CANCELLED => [], // No transitions from cancelled
        ];

        return in_array($to, $validTransitions[$from] ?? []);
    }

    /**
     * Check if stock should be deducted
     */
    protected function shouldDeductStock(string $oldStatus, string $newStatus): bool
    {
        $nonDeductedStatuses = [self::STATUS_PENDING];
        $deductedStatuses = [self::STATUS_CONFIRMED, self::STATUS_PROCESSING, self::STATUS_SHIPPED, self::STATUS_COMPLETED];

        return in_array($oldStatus, $nonDeductedStatuses) && in_array($newStatus, $deductedStatuses);
    }

    /**
     * Check if stock should be restored
     */
    protected function shouldRestoreStock(string $oldStatus, string $newStatus): bool
    {
        $deductedStatuses = [self::STATUS_CONFIRMED, self::STATUS_PROCESSING, self::STATUS_SHIPPED];
        return in_array($oldStatus, $deductedStatuses) && $newStatus === self::STATUS_CANCELLED;
    }

    /**
     * Get order statistics
     */
    public function getStatistics(?string $period = 'month'): array
    {
        $startDate = match($period) {
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        $orders = Order::where('created_at', '>=', $startDate)->get();

        return [
            'total_orders' => $orders->count(),
            'total_revenue' => $orders->where('status', self::STATUS_COMPLETED)->sum('total_amount'),
            'pending_orders' => $orders->where('status', self::STATUS_PENDING)->count(),
            'completed_orders' => $orders->where('status', self::STATUS_COMPLETED)->count(),
            'cancelled_orders' => $orders->where('status', self::STATUS_CANCELLED)->count(),
            'average_order_value' => $orders->avg('total_amount') ?? 0,
        ];
    }
}
