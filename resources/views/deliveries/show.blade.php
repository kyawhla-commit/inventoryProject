@extends('layouts.app')

@section('title', __('Delivery') . ' ' . $delivery->delivery_number)

@section('content')
<div class="container-fluid">
    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="fas fa-truck me-2"></i>{{ $delivery->delivery_number }}
                <span class="badge bg-{{ $delivery->status_badge_class }} ms-2">{{ $delivery->status_label }}</span>
            </h1>
            <p class="text-muted mb-0">{{ __('Order') }} #{{ $delivery->order_id }} | {{ $delivery->customer->name ?? 'N/A' }}</p>
        </div>
        <div class="btn-group">
            @if($delivery->canUpdate())
                <a href="{{ route('deliveries.edit', $delivery) }}" class="btn btn-outline-primary">
                    <i class="fas fa-edit"></i> {{ __('Edit') }}
                </a>
            @endif
            <a href="{{ route('deliveries.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> {{ __('Back') }}
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            {{-- Status Update Card --}}
            @if($delivery->canUpdate())
            <div class="card mb-4 border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-sync-alt me-2"></i>{{ __('Update Status') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('deliveries.update-status', $delivery) }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('New Status') }}</label>
                                <select name="status" class="form-select" id="statusSelect" required>
                                    @foreach($statuses as $key => $label)
                                        <option value="{{ $key }}" {{ $delivery->status === $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('Location') }}</label>
                                <input type="text" name="location" class="form-control" 
                                       placeholder="{{ __('Current location...') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('Notes') }}</label>
                                <input type="text" name="notes" class="form-control" 
                                       placeholder="{{ __('Status notes...') }}">
                            </div>
                        </div>
                        
                        {{-- Delivered fields --}}
                        <div id="deliveredFields" style="display: none;">
                            <hr>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ __('Recipient Name') }}</label>
                                    <input type="text" name="recipient_name" class="form-control" 
                                           placeholder="{{ __('Who received the delivery?') }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ __('Actual Cost') }}</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Ks</span>
                                        <input type="number" name="actual_cost" class="form-control" 
                                               value="{{ $delivery->delivery_fee }}" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ __('Delivery Notes') }}</label>
                                    <input type="text" name="delivery_notes" class="form-control" 
                                           placeholder="{{ __('Notes from driver...') }}">
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check"></i> {{ __('Update Status') }}
                        </button>
                    </form>
                </div>
            </div>
            @endif

            {{-- Delivery Details --}}
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>{{ __('Delivery Details') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th class="text-muted" width="40%">{{ __('Contact Name') }}</th>
                                    <td>{{ $delivery->contact_name }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">{{ __('Contact Phone') }}</th>
                                    <td>
                                        <a href="tel:{{ $delivery->contact_phone }}">{{ $delivery->contact_phone }}</a>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-muted">{{ __('Delivery Address') }}</th>
                                    <td>{{ $delivery->delivery_address }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th class="text-muted" width="40%">{{ __('Scheduled Date') }}</th>
                                    <td>{{ $delivery->scheduled_date?->format('M d, Y') }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">{{ __('Scheduled Time') }}</th>
                                    <td>{{ $delivery->scheduled_time ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">{{ __('Delivery Fee') }}</th>
                                    <td>Ks {{ number_format($delivery->delivery_fee, 0) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    @if($delivery->notes)
                        <div class="alert alert-info mb-0">
                            <strong>{{ __('Notes') }}:</strong> {{ $delivery->notes }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- Order Items --}}
            <div class="card mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-box me-2"></i>{{ __('Order Items') }}</h5>
                    <a href="{{ route('orders.show', $delivery->order) }}" class="btn btn-sm btn-outline-primary">
                        {{ __('View Order') }}
                    </a>
                </div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('Product') }}</th>
                                <th class="text-center">{{ __('Quantity') }}</th>
                                <th class="text-end">{{ __('Price') }}</th>
                                <th class="text-end">{{ __('Total') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($delivery->order->items as $item)
                                <tr>
                                    <td>{{ $item->product->name ?? 'N/A' }}</td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-end">Ks {{ number_format($item->price, 0) }}</td>
                                    <td class="text-end">Ks {{ number_format($item->quantity * $item->price, 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="3" class="text-end"><strong>{{ __('Order Total') }}:</strong></td>
                                <td class="text-end"><strong>Ks {{ number_format($delivery->order->total_amount, 0) }}</strong></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end">{{ __('Delivery Fee') }}:</td>
                                <td class="text-end">Ks {{ number_format($delivery->delivery_fee, 0) }}</td>
                            </tr>
                            <tr class="table-primary">
                                <td colspan="3" class="text-end"><strong>{{ __('Grand Total') }}:</strong></td>
                                <td class="text-end"><strong>Ks {{ number_format($delivery->order->total_amount + $delivery->delivery_fee, 0) }}</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Status History --}}
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>{{ __('Status History') }}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="timeline p-3">
                        @foreach($delivery->statusHistories as $history)
                            <div class="timeline-item mb-3">
                                <div class="d-flex">
                                    <div class="me-3">
                                        <span class="badge bg-{{ \App\Models\Delivery::getStatusBadgeClass($history->status) }} rounded-pill">
                                            {{ $history->status_label }}
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="text-muted small">
                                            {{ $history->created_at->format('M d, Y H:i') }}
                                            @if($history->updater)
                                                - {{ $history->updater->name }}
                                            @endif
                                        </div>
                                        @if($history->notes)
                                            <div>{{ $history->notes }}</div>
                                        @endif
                                        @if($history->location)
                                            <small class="text-muted">
                                                <i class="fas fa-map-marker-alt"></i> {{ $history->location }}
                                            </small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            {{-- Driver Info --}}
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-user me-2"></i>{{ __('Driver Information') }}</h5>
                </div>
                <div class="card-body">
                    @if($delivery->driver_name)
                        <div class="text-center mb-3">
                            <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" 
                                 style="width: 60px; height: 60px;">
                                <i class="fas fa-user fa-2x"></i>
                            </div>
                        </div>
                        <h5 class="text-center">{{ $delivery->driver_name }}</h5>
                        <p class="text-center text-muted mb-3">
                            <a href="tel:{{ $delivery->driver_phone }}">{{ $delivery->driver_phone }}</a>
                        </p>
                        @if($delivery->vehicle_number)
                            <div class="text-center">
                                <span class="badge bg-secondary fs-6">
                                    <i class="fas fa-car me-1"></i>{{ $delivery->vehicle_number }}
                                </span>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-3">
                            <i class="fas fa-user-slash fa-3x text-muted mb-2"></i>
                            <p class="text-muted">{{ __('No driver assigned') }}</p>
                            @if($delivery->canUpdate())
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#assignDriverModal">
                                    <i class="fas fa-user-plus"></i> {{ __('Assign Driver') }}
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Delivery Completion Info --}}
            @if($delivery->status === 'delivered')
            <div class="card mb-4 border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>{{ __('Delivery Completed') }}</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <th>{{ __('Delivered At') }}</th>
                            <td>{{ $delivery->delivered_at?->format('M d, Y H:i') }}</td>
                        </tr>
                        @if($delivery->recipient_name)
                        <tr>
                            <th>{{ __('Received By') }}</th>
                            <td>{{ $delivery->recipient_name }}</td>
                        </tr>
                        @endif
                        @if($delivery->actual_cost)
                        <tr>
                            <th>{{ __('Actual Cost') }}</th>
                            <td>Ks {{ number_format($delivery->actual_cost, 0) }}</td>
                        </tr>
                        @endif
                        @if($delivery->delivery_notes)
                        <tr>
                            <th>{{ __('Notes') }}</th>
                            <td>{{ $delivery->delivery_notes }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
            @endif

            {{-- Actions --}}
            @if($delivery->canCancel())
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>{{ __('Danger Zone') }}</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small">{{ __('Cancel this delivery if it cannot be completed.') }}</p>
                    <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#cancelModal">
                        <i class="fas fa-times"></i> {{ __('Cancel Delivery') }}
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Assign Driver Modal --}}
<div class="modal fade" id="assignDriverModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('deliveries.assign-driver', $delivery) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Assign Driver') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Driver Name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="driver_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Driver Phone') }} <span class="text-danger">*</span></label>
                        <input type="text" name="driver_phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Vehicle Number') }}</label>
                        <input type="text" name="vehicle_number" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Assign') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Cancel Modal --}}
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('deliveries.cancel', $delivery) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">{{ __('Cancel Delivery') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        {{ __('Are you sure you want to cancel this delivery?') }}
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Reason for cancellation') }}</label>
                        <textarea name="reason" class="form-control" rows="3" 
                                  placeholder="{{ __('Enter reason...') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn-danger">{{ __('Cancel Delivery') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('statusSelect')?.addEventListener('change', function() {
    const deliveredFields = document.getElementById('deliveredFields');
    if (this.value === 'delivered') {
        deliveredFields.style.display = 'block';
    } else {
        deliveredFields.style.display = 'none';
    }
});
</script>
@endpush
@endsection
