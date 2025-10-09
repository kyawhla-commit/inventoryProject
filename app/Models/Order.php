<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    /**
     * Map order statuses to Bootstrap badge color classes.
     */
    public const STATUS_BADGE_CLASSES = [
        'pending'    => 'warning',
        'processing' => 'primary',
        'shipped'    => 'info',
        'completed'  => 'success',
        'cancelled'  => 'danger',
    ];

    protected $fillable = [
        'customer_id',
        'order_date',
        'total_amount',
        'status',
    ];

    protected $casts = [
        'order_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

        /**
     * Get the Bootstrap color class for the current status.
     */
    public function getBadgeClassAttribute(): string
    {
        return self::STATUS_BADGE_CLASSES[$this->status] ?? 'secondary';
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function rawMaterialUsages()
    {
        return $this->hasMany(RawMaterialUsage::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * Convert order to sale
     */
    public function convertToSale()
    {
        // Check if sale already exists
        if ($this->sales()->exists()) {
            return $this->sales()->first();
        }

        $sale = Sale::create([
            'order_id' => $this->id,
            'customer_id' => $this->customer_id,
            'sale_date' => now(),
            'total_amount' => $this->total_amount,
        ]);

        // Create sale items from order items
        foreach ($this->items as $orderItem) {
            $sale->items()->create([
                'product_id' => $orderItem->product_id,
                'quantity' => $orderItem->quantity,
                'unit_price' => $orderItem->price,
            ]);

            // Update product stock
            // if ($orderItem->product) {
            //     $orderItem->product->decrement('quantity', $orderItem->quantity);
            // }
        }

        return $sale;
    }

    /**
     * Create purchase order for raw materials needed
     */
    public function createPurchaseOrder($supplierId, $items = [])
    {
        $totalAmount = collect($items)->sum(function ($item) {
            return $item['quantity'] * $item['price'];
        });

        $purchase = Purchase::create([
            'order_id' => $this->id,
            'supplier_id' => $supplierId,
            'purchase_date' => now(),
            'total_amount' => $totalAmount,
            'status' => 'pending',
        ]);

        // Create purchase items
        foreach ($items as $item) {
            $purchase->items()->create([
                'product_id' => $item['product_id'] ?? null,
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],
            ]);
        }

        return $purchase;
    }

    /**
     * Get order fulfillment status
     */
    public function getFulfillmentStatus()
    {
        $hasSales = $this->sales()->exists();
        $hasPurchases = $this->purchases()->exists();
        $hasInvoice = $this->invoice()->exists();

        return [
            'has_sales' => $hasSales,
            'has_purchases' => $hasPurchases,
            'has_invoice' => $hasInvoice,
            'is_fully_processed' => $hasSales && $hasInvoice,
        ];
    }
}
