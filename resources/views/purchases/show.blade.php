@extends('layouts.app')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">{{ __('Purchase Order') }} #{{ $purchase->purchase_number }}</h1>
            <p class="text-muted mb-0">{{ __('Created on') }} {{ $purchase->created_at->format('M d, Y H:i') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>{{ __('Back') }}
            </a>
            @if($purchase->canEdit())
                <a href="{{ route('purchases.edit', $purchase) }}" class="btn btn-outline-primary">
                    <i class="fas fa-edit me-2"></i>{{ __('Edit') }}
                </a>
            @endif
            <a href="{{ route('purchases.duplicate', $purchase) }}" class="btn btn-outline-info">
                <i class="fas fa-copy me-2"></i>{{ __('Duplicate') }}
            </a>
            <a href="{{ route('purchases.print', $purchase) }}" class="btn btn-outline-dark" target="_blank">
                <i class="fas fa-print me-2"></i>{{ __('Print') }}
            </a>
        </div>
    </div>

    {{-- Alerts --}}
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

    <div class="row">
        {{-- Main Content --}}
        <div class="col-lg-8">
            {{-- Purchase Items --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0"><i class="fas fa-boxes me-2"></i>{{ __('Purchase Items') }}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('Material') }}</th>
                                    <th class="text-center">{{ __('Quantity') }}</th>
                                    <th class="text-center">{{ __('Unit') }}</th>
                                    <th class="text-end">{{ __('Unit Price') }}</th>
                                    <th class="text-end">{{ __('Total') }}</th>
                                    @if($purchase->status === 'received')
                                    <th class="text-center">{{ __('Stock Impact') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchase->items as $index => $item)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $item->rawMaterial->name ?? '-' }}</div>
                                            @if($item->rawMaterial)
                                                <small class="text-muted">
                                                    {{ __('Current Stock:') }} 
                                                    <span class="{{ $item->rawMaterial->quantity <= ($item->rawMaterial->minimum_stock_level ?? 0) ? 'text-danger' : 'text-success' }}">
                                                        {{ number_format($item->rawMaterial->quantity, 2) }} {{ $item->rawMaterial->unit }}
                                                    </span>
                                                </small>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                                        <td class="text-center">{{ $item->rawMaterial->unit ?? $item->unit ?? '-' }}</td>
                                        <td class="text-end">{{ number_format($item->unit_price, 0) }} Ks</td>
                                        <td class="text-end fw-semibold">{{ number_format($item->total_amount, 0) }} Ks</td>
                                        @if($purchase->status === 'received')
                                        <td class="text-center">
                                            <span class="badge bg-success">
                                                <i class="fas fa-plus me-1"></i>{{ number_format($item->quantity, 2) }}
                                            </span>
                                        </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="{{ $purchase->status === 'received' ? 5 : 5 }}" class="text-end fw-bold">{{ __('Grand Total:') }}</td>
                                    <td class="text-end fw-bold text-primary fs-5">{{ number_format($purchase->total_amount, 0) }} Ks</td>
                                    @if($purchase->status === 'received')
                                    <td></td>
                                    @endif
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Stock Impact Summary (for received purchases) --}}
            @if($purchase->status === 'received')
            <div class="card border-0 shadow-sm mb-4 border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>{{ __('Stock Updated') }}</h5>
                </div>
                <div class="card-body">
                    <p class="mb-3">{{ __('The following raw materials have been added to inventory:') }}</p>
                    <div class="row">
                        @foreach($purchase->items as $item)
                        @if($item->rawMaterial)
                        <div class="col-md-6 mb-2">
                            <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                                <span>{{ $item->rawMaterial->name }}</span>
                                <span class="badge bg-success">+{{ number_format($item->quantity, 2) }} {{ $item->rawMaterial->unit }}</span>
                            </div>
                        </div>
                        @endif
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- Notes --}}
            @if($purchase->notes)
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent">
                        <h5 class="mb-0"><i class="fas fa-sticky-note me-2"></i>{{ __('Notes') }}</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">{{ $purchase->notes }}</p>
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            {{-- Status Card --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>{{ __('Status & Actions') }}</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <span class="badge {{ $purchase->status_badge_class }} fs-6 px-4 py-2">
                            {{ \App\Models\Purchase::getStatuses()[$purchase->status] ?? ucfirst($purchase->status) }}
                        </span>
                    </div>
                    
                    {{-- Workflow Actions --}}
                    <div class="d-grid gap-2">
                        @if($purchase->status === 'pending')
                            <form action="{{ route('purchases.approve', $purchase) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-info w-100">
                                    <i class="fas fa-thumbs-up me-2"></i>{{ __('Approve Order') }}
                                </button>
                            </form>
                        @endif

                        @if($purchase->canReceive())
                            <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#receiveStockModal">
                                <i class="fas fa-check me-2"></i>{{ __('Receive Stock') }}
                            </button>
                        @endif
                        
                        @if($purchase->canCancel())
                            <form action="{{ route('purchases.cancel', $purchase) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger w-100"
                                        onclick="return confirm('{{ __('Are you sure you want to cancel this purchase order?') }}')">
                                    <i class="fas fa-times me-2"></i>{{ __('Cancel Order') }}
                                </button>
                            </form>
                        @endif
                    </div>

                    {{-- Status Timeline --}}
                    <hr>
                    <h6 class="text-muted mb-3">{{ __('Order Timeline') }}</h6>
                    <div class="timeline-simple">
                        <div class="timeline-item {{ in_array($purchase->status, ['pending', 'approved', 'received']) ? 'active' : '' }}">
                            <i class="fas fa-file-alt"></i>
                            <span>{{ __('Created') }}</span>
                            <small class="text-muted d-block">{{ $purchase->created_at->format('M d, H:i') }}</small>
                        </div>
                        <div class="timeline-item {{ in_array($purchase->status, ['approved', 'received']) ? 'active' : '' }}">
                            <i class="fas fa-thumbs-up"></i>
                            <span>{{ __('Approved') }}</span>
                        </div>
                        <div class="timeline-item {{ $purchase->status === 'received' ? 'active' : '' }}">
                            <i class="fas fa-check-circle"></i>
                            <span>{{ __('Received') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Purchase Details --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>{{ __('Details') }}</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted">{{ __('Purchase #') }}</td>
                            <td class="text-end fw-semibold">{{ $purchase->purchase_number }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Date') }}</td>
                            <td class="text-end">{{ $purchase->purchase_date->format('M d, Y') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Items') }}</td>
                            <td class="text-end">{{ $purchase->items->count() }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Total Qty') }}</td>
                            <td class="text-end">{{ number_format($purchase->items->sum('quantity'), 2) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Total Amount') }}</td>
                            <td class="text-end fw-bold text-primary">{{ number_format($purchase->total_amount, 0) }} Ks</td>
                        </tr>
                    </table>
                </div>
            </div>

            {{-- Supplier Info --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0"><i class="fas fa-truck me-2"></i>{{ __('Supplier') }}</h5>
                </div>
                <div class="card-body">
                    @if($purchase->supplier)
                        <h6 class="fw-bold mb-2">{{ $purchase->supplier->name }}</h6>
                        @if($purchase->supplier->email)
                            <p class="mb-1">
                                <i class="fas fa-envelope text-muted me-2"></i>{{ $purchase->supplier->email }}
                            </p>
                        @endif
                        @if($purchase->supplier->phone)
                            <p class="mb-1">
                                <i class="fas fa-phone text-muted me-2"></i>{{ $purchase->supplier->phone }}
                            </p>
                        @endif
                        @if($purchase->supplier->address)
                            <p class="mb-0">
                                <i class="fas fa-map-marker-alt text-muted me-2"></i>{{ $purchase->supplier->address }}
                            </p>
                        @endif
                    @else
                        <p class="text-muted mb-0">{{ __('No supplier information') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Receive Stock Modal --}}
@if($purchase->canReceive())
<div class="modal fade" id="receiveStockModal" tabindex="-1" aria-labelledby="receiveStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="receiveStockModalLabel">
                    <i class="fas fa-check-circle me-2"></i>{{ __('Receive Stock') }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('purchases.receive', $purchase) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        {{ __('Receiving stock will add the quantities to raw material inventory and update costs using weighted average.') }}
                    </div>

                    <h6 class="mb-3">{{ __('Items to Receive:') }}</h6>
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('Material') }}</th>
                                <th class="text-center">{{ __('Ordered') }}</th>
                                <th class="text-center">{{ __('Current Stock') }}</th>
                                <th class="text-center">{{ __('After Receive') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchase->items as $item)
                            @if($item->rawMaterial)
                            <tr>
                                <td>{{ $item->rawMaterial->name }}</td>
                                <td class="text-center">{{ number_format($item->quantity, 2) }} {{ $item->rawMaterial->unit }}</td>
                                <td class="text-center">{{ number_format($item->rawMaterial->quantity, 2) }} {{ $item->rawMaterial->unit }}</td>
                                <td class="text-center text-success fw-bold">
                                    {{ number_format($item->rawMaterial->quantity + $item->quantity, 2) }} {{ $item->rawMaterial->unit }}
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>

                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" id="confirmReceive" required>
                        <label class="form-check-label" for="confirmReceive">
                            {{ __('I confirm that I have physically received these items') }}
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>{{ __('Cancel') }}
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-1"></i>{{ __('Confirm & Receive Stock') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<style>
.timeline-simple {
    position: relative;
    padding-left: 30px;
}
.timeline-simple::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}
.timeline-item {
    position: relative;
    padding: 10px 0;
    color: #6c757d;
}
.timeline-item.active {
    color: #198754;
}
.timeline-item i {
    position: absolute;
    left: -25px;
    width: 20px;
    height: 20px;
    background: #fff;
    border: 2px solid currentColor;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
}
.timeline-item.active i {
    background: #198754;
    color: #fff;
    border-color: #198754;
}
</style>
@endsection
