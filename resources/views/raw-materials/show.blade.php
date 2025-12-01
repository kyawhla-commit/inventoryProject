@extends('layouts.app')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">{{ $rawMaterial->name }}</h1>
            <p class="text-muted mb-0">{{ $rawMaterial->supplier->name ?? __('No Supplier') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('raw-materials.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>{{ __('Back') }}
            </a>
            <a href="{{ route('raw-materials.edit', $rawMaterial) }}" class="btn btn-outline-primary">
                <i class="fas fa-edit me-2"></i>{{ __('Edit') }}
            </a>
            <a href="{{ route('purchases.create', ['supplier_id' => $rawMaterial->supplier_id]) }}" class="btn btn-success">
                <i class="fas fa-shopping-cart me-2"></i>{{ __('Order More') }}
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

    @if($rawMaterial->isLowStock())
        <div class="alert alert-warning" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>{{ __('Low Stock Alert!') }}</strong> 
            {{ __('Current stock is at or below minimum level.') }}
            <a href="{{ route('purchases.create', ['supplier_id' => $rawMaterial->supplier_id]) }}" class="alert-link ms-2">
                {{ __('Create Purchase Order') }} →
            </a>
        </div>
    @endif

    <div class="row">
        {{-- Main Content --}}
        <div class="col-lg-8">
            {{-- Stock Overview --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0"><i class="fas fa-warehouse me-2"></i>{{ __('Stock Overview') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="border-end">
                                <h3 class="mb-1 {{ $rawMaterial->isLowStock() ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($rawMaterial->quantity, 2) }}
                                </h3>
                                <small class="text-muted">{{ __('Current Stock') }} ({{ $rawMaterial->unit }})</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border-end">
                                <h3 class="mb-1">{{ number_format($rawMaterial->minimum_stock_level, 2) }}</h3>
                                <small class="text-muted">{{ __('Minimum Level') }}</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border-end">
                                <h3 class="mb-1">{{ number_format($rawMaterial->cost_per_unit, 2) }}</h3>
                                <small class="text-muted">{{ __('Cost per') }} {{ $rawMaterial->unit }}</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <h3 class="mb-1 text-primary">{{ number_format($rawMaterial->stock_value, 0) }}</h3>
                            <small class="text-muted">{{ __('Total Value') }}</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Usage Statistics --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>{{ __('Usage Statistics') }} ({{ __('Last 30 Days') }})</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            <h4 class="text-info">{{ number_format($usageStats['total_used'], 2) }}</h4>
                            <small class="text-muted">{{ __('Total Used') }} ({{ $rawMaterial->unit }})</small>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <h4 class="text-warning">{{ number_format($usageStats['daily_average'], 2) }}</h4>
                            <small class="text-muted">{{ __('Daily Average') }}</small>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <h4 class="text-success">{{ number_format($usageStats['total_cost'], 0) }}</h4>
                            <small class="text-muted">{{ __('Total Cost') }}</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Reorder Analysis --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-calculator me-2"></i>{{ __('Reorder Analysis') }}</h5>
                    @if($reorderAnalysis['needs_reorder'])
                        <span class="badge bg-danger">{{ __('Reorder Needed') }}</span>
                    @else
                        <span class="badge bg-success">{{ __('Stock OK') }}</span>
                    @endif
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="text-muted">{{ __('Daily Usage (avg):') }}</td>
                                    <td class="text-end fw-semibold">{{ number_format($reorderAnalysis['daily_usage'], 2) }} {{ $rawMaterial->unit }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">{{ __('Lead Time:') }}</td>
                                    <td class="text-end fw-semibold">{{ $reorderAnalysis['lead_time_days'] }} {{ __('days') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">{{ __('Safety Stock:') }}</td>
                                    <td class="text-end fw-semibold">{{ $reorderAnalysis['safety_stock_days'] }} {{ __('days') }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="text-muted">{{ __('Reorder Point:') }}</td>
                                    <td class="text-end fw-semibold text-warning">{{ number_format($reorderAnalysis['reorder_point'], 2) }} {{ $rawMaterial->unit }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">{{ __('Suggested Order Qty:') }}</td>
                                    <td class="text-end fw-semibold text-primary">{{ number_format($reorderAnalysis['suggested_reorder_quantity'], 2) }} {{ $rawMaterial->unit }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">{{ __('Days of Stock:') }}</td>
                                    <td class="text-end fw-semibold">
                                        @if($reorderAnalysis['days_of_stock'])
                                            {{ number_format($reorderAnalysis['days_of_stock'], 1) }} {{ __('days') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stock Movements --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>{{ __('Recent Stock Movements') }}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th class="text-end">{{ __('Quantity') }}</th>
                                    <th>{{ __('Notes') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stockMovements as $movement)
                                    <tr>
                                        <td>{{ $movement->created_at->format('M d, Y H:i') }}</td>
                                        <td>
                                            <span class="badge bg-{{ $movement->quantity > 0 ? 'success' : 'danger' }}">
                                                {{ ucfirst($movement->type ?? 'adjustment') }}
                                            </span>
                                        </td>
                                        <td class="text-end {{ $movement->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $movement->quantity > 0 ? '+' : '' }}{{ number_format($movement->quantity, 2) }}
                                        </td>
                                        <td>{{ $movement->notes ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            {{ __('No stock movements recorded') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Purchase History --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>{{ __('Purchase History') }}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('PO #') }}</th>
                                    <th>{{ __('Supplier') }}</th>
                                    <th class="text-end">{{ __('Qty') }}</th>
                                    <th class="text-end">{{ __('Price') }}</th>
                                    <th class="text-end">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($purchaseHistory as $item)
                                    <tr>
                                        <td>{{ $item->purchase->purchase_date->format('M d, Y') }}</td>
                                        <td>
                                            <a href="{{ route('purchases.show', $item->purchase) }}">
                                                {{ $item->purchase->purchase_number }}
                                            </a>
                                        </td>
                                        <td>{{ $item->purchase->supplier->name ?? '-' }}</td>
                                        <td class="text-end">{{ number_format($item->quantity, 2) }}</td>
                                        <td class="text-end">{{ number_format($item->unit_price, 2) }}</td>
                                        <td class="text-end fw-semibold">{{ number_format($item->total_amount, 0) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            {{ __('No purchase history') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            {{-- Quick Actions --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0"><i class="fas fa-bolt me-2"></i>{{ __('Quick Actions') }}</h6>
                </div>
                <div class="card-body">
                    <button type="button" class="btn btn-outline-primary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#adjustStockModal">
                        <i class="fas fa-sliders-h me-2"></i>{{ __('Adjust Stock') }}
                    </button>
                    <a href="{{ route('purchases.create', ['supplier_id' => $rawMaterial->supplier_id]) }}" class="btn btn-outline-success w-100 mb-2">
                        <i class="fas fa-shopping-cart me-2"></i>{{ __('Create Purchase Order') }}
                    </a>
                    <a href="{{ route('raw-materials.edit', $rawMaterial) }}" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-edit me-2"></i>{{ __('Edit Details') }}
                    </a>
                </div>
            </div>

            {{-- Material Details --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>{{ __('Details') }}</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted">{{ __('Stock Status') }}</td>
                            <td class="text-end">
                                <span class="badge {{ $rawMaterial->stock_status_badge }}">
                                    {{ $rawMaterial->stock_status_label }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Unit') }}</td>
                            <td class="text-end">{{ $rawMaterial->unit }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Created') }}</td>
                            <td class="text-end">{{ $rawMaterial->created_at->format('M d, Y') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            {{-- Supplier Info --}}
            @if($rawMaterial->supplier)
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent">
                        <h6 class="mb-0"><i class="fas fa-truck me-2"></i>{{ __('Supplier') }}</h6>
                    </div>
                    <div class="card-body">
                        <h6 class="fw-bold mb-2">{{ $rawMaterial->supplier->name }}</h6>
                        @if($rawMaterial->supplier->email)
                            <p class="mb-1 small">
                                <i class="fas fa-envelope text-muted me-2"></i>{{ $rawMaterial->supplier->email }}
                            </p>
                        @endif
                        @if($rawMaterial->supplier->phone)
                            <p class="mb-1 small">
                                <i class="fas fa-phone text-muted me-2"></i>{{ $rawMaterial->supplier->phone }}
                            </p>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Used In Products --}}
            @if($rawMaterial->products->count() > 0)
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent">
                        <h6 class="mb-0"><i class="fas fa-box me-2"></i>{{ __('Used In Products') }}</h6>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @foreach($rawMaterial->products->take(5) as $product)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>{{ $product->name }}</span>
                                    <span class="badge bg-secondary">
                                        {{ $product->pivot->quantity_required }} {{ $product->pivot->unit ?? $rawMaterial->unit }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Adjust Stock Modal --}}
<div class="modal fade" id="adjustStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('raw-materials.adjust-stock', $rawMaterial) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Adjust Stock') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">
                        {{ __('Current Stock:') }} <strong>{{ number_format($rawMaterial->quantity, 2) }} {{ $rawMaterial->unit }}</strong>
                    </p>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Adjustment Type') }}</label>
                        <select name="type" class="form-select" required>
                            <option value="adjustment">{{ __('General Adjustment') }}</option>
                            <option value="damage">{{ __('Damage/Loss') }}</option>
                            <option value="return">{{ __('Return to Supplier') }}</option>
                            <option value="correction">{{ __('Inventory Correction') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Quantity') }}</label>
                        <div class="input-group">
                            <input type="number" name="adjustment" class="form-control" step="0.01" required
                                   placeholder="{{ __('Use negative for reduction') }}">
                            <span class="input-group-text">{{ $rawMaterial->unit }}</span>
                        </div>
                        <small class="text-muted">{{ __('Use positive to add, negative to subtract') }}</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Reason') }}</label>
                        <textarea name="reason" class="form-control" rows="2" required 
                                  placeholder="{{ __('Explain the reason for adjustment...') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Adjust Stock') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
