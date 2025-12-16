<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_number',
        'order_id',
        'customer_id',
        'status',
        'delivery_address',
        'contact_phone',
        'contact_name',
        'driver_name',
        'driver_phone',
        'vehicle_number',
        'scheduled_date',
        'scheduled_time',
        'picked_up_at',
        'delivered_at',
        'delivery_fee',
        'actual_cost',
        'notes',
        'delivery_notes',
        'proof_of_delivery',
        'recipient_name',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
        'delivery_fee' => 'decimal:2',
        'actual_cost' => 'decimal:2',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_ASSIGNED = 'assigned';
    const STATUS_PICKED_UP = 'picked_up';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => __('Pending'),
            self::STATUS_ASSIGNED => __('Assigned'),
            self::STATUS_PICKED_UP => __('Picked Up'),
            self::STATUS_IN_TRANSIT => __('In Transit'),
            self::STATUS_DELIVERED => __('Delivered'),
            self::STATUS_FAILED => __('Failed'),
            self::STATUS_CANCELLED => __('Cancelled'),
        ];
    }

    public static function getStatusBadgeClass(string $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_ASSIGNED => 'info',
            self::STATUS_PICKED_UP => 'primary',
            self::STATUS_IN_TRANSIT => 'primary',
            self::STATUS_DELIVERED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_CANCELLED => 'secondary',
            default => 'secondary',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return self::getStatusBadgeClass($this->status);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function statusHistories()
    {
        return $this->hasMany(DeliveryStatusHistory::class)->orderBy('created_at', 'desc');
    }

    // Generate delivery number
    public static function generateDeliveryNumber(): string
    {
        $prefix = 'DLV';
        $date = now()->format('Ymd');
        $lastDelivery = self::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastDelivery
            ? ((int) substr($lastDelivery->delivery_number, -4)) + 1
            : 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    // Check if delivery can be updated
    public function canUpdate(): bool
    {
        return !in_array($this->status, [self::STATUS_DELIVERED, self::STATUS_CANCELLED]);
    }

    // Check if delivery can be cancelled
    public function canCancel(): bool
    {
        return !in_array($this->status, [self::STATUS_DELIVERED, self::STATUS_CANCELLED]);
    }

    // Update status with history
    public function updateStatus(string $status, ?string $notes = null, ?string $location = null): void
    {
        $this->status = $status;
        
        if ($status === self::STATUS_PICKED_UP) {
            $this->picked_up_at = now();
        } elseif ($status === self::STATUS_DELIVERED) {
            $this->delivered_at = now();
        }
        
        $this->updated_by = auth()->id();
        $this->save();

        // Record history
        $this->statusHistories()->create([
            'status' => $status,
            'notes' => $notes,
            'location' => $location,
            'updated_by' => auth()->id(),
        ]);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeInProgress($query)
    {
        return $query->whereIn('status', [
            self::STATUS_ASSIGNED,
            self::STATUS_PICKED_UP,
            self::STATUS_IN_TRANSIT,
        ]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_DELIVERED);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_date', today());
    }
}
