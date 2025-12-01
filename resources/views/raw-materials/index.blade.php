@extends('layouts.app')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">{{ __('Raw Materials') }}</h1>
            <p class="text-muted mb-0">{{ __('Manage inventory of raw materials for production') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('raw-materials.low-stock') }}" class="btn btn-outline-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>{{ __('Low Stock') }}
                @if($stats['low_stock_count'] > 0)
                    <span class="badge bg-danger ms-1">{{ $stats['low_stock_count'] }}</span>
                @endif
            </a>
            <a href="{{ route('raw-materials.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>{{ __('Add Material') }}
            </a>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-primary bg-opacity-10 rounded-3 p-3">
                            <i class="fas fa-boxes text-primary fa-lg"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">{{ __('Total Materials') }}</h6>
                            <h4 class="mb-0">{{ number_format($stats['total_materials']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-success bg-opacity-10 rounded-3 p-3">
                            <i class="fas fa-dollar-sign text-success fa-lg"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">{{ __('Stock Value') }}</h6>
                            <h4 class="mb-0">{{ number_format($stats['total_stock_value'], 0) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-warning bg-opacity-10 rounded-3 p-3">
                            <i class="fas fa-exclamation-triangle text-warning fa-lg"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">{{ __('Low Stock') }}</h6>
                            <h4 class="mb-0">{{ number_format($stats['low_stock_count']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-info bg-opacity-10 rounded-3 p-3">
                            <i class="fas fa-chart-line text-info fa-lg"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">{{ __('Month Usage') }}</h6>
                            <h4 class="mb-0">{{ number_format($stats['month_usage_cost'], 0) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('raw-materials.index') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('Search') }}</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="{{ __('Name...') }}" 
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
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
                <div class="col-md-3">
                    <label class="form-label">{{ __('Stock') }}</label>
                    <select name="low_stock" class="form-select">
                        <option value="">{{ __('All') }}</option>
                        <option value="1" {{ request('low_stock') ? 'selected' : '' }}>{{ __('Low Stock Only') }}</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i>{{ __('Search') }}
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

    {{-- Materials Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Material') }}</th>
                            <th>{{ __('Supplier') }}</th>
                            <th class="text-center">{{ __('Stock') }}</th>
                            <th class="text-end">{{ __('Cost/Unit') }}</th>
                            <th class="text-end">{{ __('Value') }}</th>
                            <th class="text-center">{{ __('Status') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rawMaterials as $material)
                            <tr>
                                <td>
                                    <a href="{{ route('raw-materials.show', $material) }}" class="fw-semibold text-decoration-none">
                                        {{ $material->name }}
                                    </a>
                                </td>
                                <td>{{ $material->supplier->name ?? '-' }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $material->stock_status_badge }}">
                                        {{ number_format($material->quantity, 2) }} {{ $material->unit }}
                                    </span>
                                    @if($material->isLowStock())
                                        <br><small class="text-danger">
                                            <i class="fas fa-exclamation-triangle"></i> {{ __('Min:') }} {{ $material->minimum_stock_level }}
                                        </small>
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format($material->cost_per_unit, 2) }}</td>
                                <td class="text-end fw-semibold">{{ number_format($material->stock_value, 0) }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $material->stock_status_badge }}">
                                        {{ $material->stock_status_label }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('raw-materials.show', $material) }}" 
                                           class="btn btn-outline-primary" title="{{ __('View') }}">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('raw-materials.edit', $material) }}" 
                                           class="btn btn-outline-secondary" title="{{ __('Edit') }}">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="{{ route('purchases.create', ['supplier_id' => $material->supplier_id]) }}" 
                                           class="btn btn-outline-success" title="{{ __('Order') }}">
                                            <i class="fas fa-shopping-cart"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3"></i>
                                        <p class="mb-0">{{ __('No raw materials found') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($rawMaterials->hasPages())
            <div class="card-footer bg-transparent">
                {{ $rawMaterials->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
