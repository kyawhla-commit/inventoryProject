<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Supplier;
use App\Models\PurchaseItem;
use Illuminate\Support\Facades\Auth;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_number',
        'supplier_id',
        'order_id',
        'purchase_date',
        'total_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_RECEIVED = 'received';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_PARTIAL = 'partial';

    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => __('Pending'),
            self::STATUS_APPROVED => __('Approved'),
            self::STATUS_RECEIVED => __('Received'),
            self::STATUS_PARTIAL => __('Partially Received'),
            self::STATUS_CANCELLED => __('Cancelled'),
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($purchase) {
            if (empty($purchase->purchase_number)) {
                $purchase->purchase_number = self::generatePurchaseNumber();
            }
        });
    }

    /**
     * Generate unique purchase number
     */
    public static function generatePurchaseNumber(): string
    {
        $prefix = 'PO';
        $date = now()->format('Ymd');
        $lastPurchase = self::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastPurchase 
            ? ((int) preg_replace('/[^0-9]/', '', substr($lastPurchase->purchase_number, -4))) + 1 
            : 1;
        
        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Alias for items - all purchase items are raw materials
     * Products are manufactured through Production Plans, not purchased.
     */
    public function rawMaterialItems()
    {
        return $this->items();
    }

    /**
     * Check if purchase can be edited
     */
    public function canEdit(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_APPROVED]);
    }

    /**
     * Check if purchase can be cancelled
     */
    public function canCancel(): bool
    {
        return $this->status !== self::STATUS_CANCELLED;
    }

    /**
     * Check if purchase can be received
     */
    public function canReceive(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_PARTIAL]);
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'bg-warning',
            self::STATUS_APPROVED => 'bg-info',
            self::STATUS_RECEIVED => 'bg-success',
            self::STATUS_PARTIAL => 'bg-primary',
            self::STATUS_CANCELLED => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    /**
     * Calculate total from items
     */
    public function recalculateTotal(): void
    {
        $this->total_amount = $this->items->sum('total_amount');
        $this->saveQuietly();
    }

    /**
     * Scope for pending purchases
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for received purchases
     */
    public function scopeReceived($query)
    {
        return $query->where('status', self::STATUS_RECEIVED);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('purchase_date', [$startDate, $endDate]);
    }

    /**
     * Scope for supplier
     */
    public function scopeForSupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }
}