@extends('layouts.app')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">{{ __('Purchase Orders') }}</h1>
            <p class="text-muted mb-0">{{ __('Manage raw material purchases from suppliers') }}</p>
        </div>
        <a href="{{ route('purchases.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>{{ __('New Purchase') }}
        </a>
    </div>

    {{-- Statistics Cards --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-primary bg-opacity-10 rounded-3 p-3">
                            <i class="fas fa-shopping-cart text-primary fa-lg"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">{{ __('Total Orders') }}</h6>
                            <h4 class="mb-0">{{ number_format($stats['total_purchases']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-warning bg-opacity-10 rounded-3 p-3">
                            <i class="fas fa-clock text-warning fa-lg"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">{{ __('Pending') }}</h6>
                            <h4 class="mb-0">{{ number_format($stats['pending_count']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-success bg-opacity-10 rounded-3 p-3">
                            <i class="fas fa-check-circle text-success fa-lg"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">{{ __('Received') }}</h6>
                            <h4 class="mb-0">{{ number_format($stats['received_count']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-info bg-opacity-10 rounded-3 p-3">
                            <i class="fas fa-dollar-sign text-info fa-lg"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">{{ __('This Month') }}</h6>
                            <h4 class="mb-0">{{ number_format($stats['total_amount'], 0) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('purchases.index') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">{{ __('Search') }}</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="{{ __('Purchase number...') }}" 
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('Status') }}</label>
                    <select name="status" class="form-select">
                        <option value="">{{ __('All Status') }}</option>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('Supplier') }}</label>
                    <select name="supplier_id" class="form-select">
                        <option value="">{{ __('All Suppliers') }}</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('From Date') }}</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('To Date') }}</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
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

    {{-- Purchases Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Purchase #') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Supplier') }}</th>
                            <th>{{ __('Items') }}</th>
                            <th class="text-end">{{ __('Total') }}</th>
                            <th class="text-center">{{ __('Status') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchases as $purchase)
                            <tr>
                                <td>
                                    <a href="{{ route('purchases.show', $purchase) }}" class="fw-semibold text-decoration-none">
                                        {{ $purchase->purchase_number }}
                                    </a>
                                </td>
                                <td>{{ $purchase->purchase_date->format('M d, Y') }}</td>
                                <td>{{ $purchase->supplier->name ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-secondary">{{ $purchase->items->count() }} {{ __('items') }}</span>
                                </td>
                                <td class="text-end fw-semibold">{{ number_format($purchase->total_amount, 0) }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $purchase->status_badge_class }}">
                                        {{ $statuses[$purchase->status] ?? $purchase->status }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('purchases.show', $purchase) }}" 
                                           class="btn btn-outline-primary" title="{{ __('View') }}">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($purchase->canEdit())
                                            <a href="{{ route('purchases.edit', $purchase) }}" 
                                               class="btn btn-outline-secondary" title="{{ __('Edit') }}">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endif
                                        @if($purchase->canReceive())
                                            <form action="{{ route('purchases.receive', $purchase) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success" 
                                                        title="{{ __('Receive Stock') }}"
                                                        onclick="return confirm('{{ __('Receive all items and update stock?') }}')">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3"></i>
                                        <p class="mb-0">{{ __('No purchase orders found') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($purchases->hasPages())
            <div class="card-footer bg-transparent">
                {{ $purchases->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
