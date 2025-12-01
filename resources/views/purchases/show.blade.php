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
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchase->items as $index => $item)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $item->rawMaterial->name ?? $item->product->name ?? '-' }}</div>
                                            @if($item->rawMaterial)
                                                <small class="text-muted">
                                                    {{ __('Current Stock:') }} {{ number_format($item->rawMaterial->quantity, 2) }} {{ $item->rawMaterial->unit }}
                                                </small>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                                        <td class="text-center">{{ $item->rawMaterial->unit ?? $item->unit ?? '-' }}</td>
                                        <td class="text-end">{{ number_format($item->unit_price, 2) }}</td>
                                        <td class="text-end fw-semibold">{{ number_format($item->total_amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="5" class="text-end fw-bold">{{ __('Grand Total:') }}</td>
                                    <td class="text-end fw-bold text-primary fs-5">{{ number_format($purchase->total_amount, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

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
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>{{ __('Status') }}</h5>
                </div>
                <div class="card-body text-center">
                    <span class="badge {{ $purchase->status_badge_class }} fs-6 px-4 py-2">
                        {{ \App\Models\Purchase::getStatuses()[$purchase->status] ?? $purchase->status }}
                    </span>
                    
                    @if($purchase->canReceive())
                        <form action="{{ route('purchases.receive', $purchase) }}" method="POST" class="mt-3">
                            @csrf
                            <button type="submit" class="btn btn-success w-100"
                                    onclick="return confirm('{{ __('This will update raw material stock. Continue?') }}')">
                                <i class="fas fa-check me-2"></i>{{ __('Receive Stock') }}
                            </button>
                        </form>
                    @endif
                    
                    @if($purchase->canCancel())
                        <form action="{{ route('purchases.cancel', $purchase) }}" method="POST" class="mt-2">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger w-100"
                                    onclick="return confirm('{{ __('Are you sure you want to cancel this purchase order?') }}')">
                                <i class="fas fa-times me-2"></i>{{ __('Cancel Order') }}
                            </button>
                        </form>
                    @endif
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
@endsection
