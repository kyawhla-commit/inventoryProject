@extends('layouts.app')

@section('title', __('View All Stock'))

@section('content')
<div class="container-fluid">
    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-warehouse me-2"></i>{{ __('View All Stock') }}</h1>
            <p class="text-muted mb-0">{{ __('Complete inventory overview of raw materials and products') }}</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('stock-management.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> {{ __('Back') }}
            </a>
            <a href="{{ route('stock-management.export', request()->query()) }}" class="btn btn-success">
                <i class="fas fa-download me-1"></i> {{ __('Export CSV') }}
            </a>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card bg-primary text-white">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-white-50">{{ __('Raw Materials') }}</small>
                            <h4 class="mb-0">{{ $totals['raw_materials_count'] }}</h4>
                        </div>
                        <i class="fas fa-boxes fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-white-50">{{ __('Products') }}</small>
                            <h4 class="mb-0">{{ $totals['products_count'] }}</h4>
                        </div>
                        <i class="fas fa-box fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-white-50">{{ __('Materials Value') }}</small>
                            <h4 class="mb-0">{{ number_format($totals['raw_materials_value'], 0) }}</h4>
                        </div>
                        <span class="opacity-50">Ks</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-secondary text-white">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-white-50">{{ __('Products Value') }}</small>
                            <h4 class="mb-0">{{ number_format($totals['products_value'], 0) }}</h4>
                        </div>
                        <span class="opacity-50">Ks</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-dark">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small>{{ __('Low Stock') }}</small>
                            <h4 class="mb-0">{{ $totals['low_stock_count'] }}</h4>
                        </div>
                        <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-danger text-white">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-white-50">{{ __('Out of Stock') }}</small>
                            <h4 class="mb-0">{{ $totals['out_of_stock_count'] }}</h4>
                        </div>
                        <i class="fas fa-times-circle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('stock-management.view-all') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">{{ __('Search') }}</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control" 
                               placeholder="{{ __('Search by name...') }}" 
                               value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('Type') }}</label>
                    <select name="type" class="form-select">
                        <option value="all" {{ request('type', 'all') === 'all' ? 'selected' : '' }}>{{ __('All Items') }}</option>
                        <option value="raw_materials" {{ request('type') === 'raw_materials' ? 'selected' : '' }}>{{ __('Raw Materials') }}</option>
                        <option value="products" {{ request('type') === 'products' ? 'selected' : '' }}>{{ __('Products') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('Stock Status') }}</label>
                    <select name="stock_status" class="form-select">
                        <option value="">{{ __('All Status') }}</option>
                        <option value="in_stock" {{ request('stock_status') === 'in_stock' ? 'selected' : '' }}>{{ __('In Stock') }}</option>
                        <option value="low_stock" {{ request('stock_status') === 'low_stock' ? 'selected' : '' }}>{{ __('Low Stock') }}</option>
                        <option value="out_of_stock" {{ request('stock_status') === 'out_of_stock' ? 'selected' : '' }}>{{ __('Out of Stock') }}</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter"></i> {{ __('Filter') }}
                    </button>
                    <a href="{{ route('stock-management.view-all') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> {{ __('Clear') }}
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Stock Tables --}}
    <div class="row">
        {{-- Raw Materials --}}
        @if($type === 'all' || $type === 'raw_materials')
        <div class="{{ $type === 'all' ? 'col-lg-6' : 'col-12' }}">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-boxes me-2"></i>{{ __('Raw Materials') }} ({{ $rawMaterials->count() }})</h5>
                    <span class="badge bg-light text-primary">{{ __('Value') }}: Ks {{ number_format($totals['raw_materials_value'], 0) }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                        <table class="table table-hover table-sm mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Supplier') }}</th>
                                    <th class="text-end">{{ __('Quantity') }}</th>
                                    <th class="text-end">{{ __('Cost/Unit') }}</th>
                                    <th class="text-end">{{ __('Value') }}</th>
                                    <th class="text-center">{{ __('Status') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rawMaterials as $material)
                                    @php
                                        $status = $material->quantity <= 0 ? 'out' : ($material->isLowStock() ? 'low' : 'ok');
                                        $statusClass = $status === 'out' ? 'danger' : ($status === 'low' ? 'warning' : 'success');
                                        $statusText = $status === 'out' ? __('Out') : ($status === 'low' ? __('Low') : __('OK'));
                                    @endphp
                                    <tr class="{{ $status === 'out' ? 'table-danger' : ($status === 'low' ? 'table-warning' : '') }}">
                                        <td>
                                            <a href="{{ route('raw-materials.show', $material) }}" class="text-decoration-none">
                                                <strong>{{ $material->name }}</strong>
                                            </a>
                                        </td>
                                        <td><small class="text-muted">{{ $material->supplier?->name ?? '-' }}</small></td>
                                        <td class="text-end">
                                            <strong>{{ number_format($material->quantity, 2) }}</strong>
                                            <small class="text-muted">{{ $material->unit }}</small>
                                        </td>
                                        <td class="text-end">{{ number_format($material->cost_per_unit, 0) }}</td>
                                        <td class="text-end">{{ number_format($material->quantity * $material->cost_per_unit, 0) }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $statusClass }}">{{ $statusText }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('stock-management.add-raw-material') }}?material_id={{ $material->id }}" 
                                               class="btn btn-sm btn-outline-success" title="{{ __('Add Stock') }}">
                                                <i class="fas fa-plus"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            {{ __('No raw materials found') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Products --}}
        @if($type === 'all' || $type === 'products')
        <div class="{{ $type === 'all' ? 'col-lg-6' : 'col-12' }}">
            <div class="card mb-4">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-box me-2"></i>{{ __('Products') }} ({{ $products->count() }})</h5>
                    <span class="badge bg-light text-success">{{ __('Value') }}: Ks {{ number_format($totals['products_value'], 0) }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                        <table class="table table-hover table-sm mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Category') }}</th>
                                    <th class="text-end">{{ __('Quantity') }}</th>
                                    <th class="text-end">{{ __('Cost') }}</th>
                                    <th class="text-end">{{ __('Price') }}</th>
                                    <th class="text-center">{{ __('Status') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($products as $product)
                                    @php
                                        $status = $product->quantity <= 0 ? 'out' : 
                                                 (isset($product->minimum_stock_level) && $product->quantity <= $product->minimum_stock_level ? 'low' : 'ok');
                                        $statusClass = $status === 'out' ? 'danger' : ($status === 'low' ? 'warning' : 'success');
                                        $statusText = $status === 'out' ? __('Out') : ($status === 'low' ? __('Low') : __('OK'));
                                    @endphp
                                    <tr class="{{ $status === 'out' ? 'table-danger' : ($status === 'low' ? 'table-warning' : '') }}">
                                        <td>
                                            <a href="{{ route('products.show', $product) }}" class="text-decoration-none">
                                                <strong>{{ $product->name }}</strong>
                                            </a>
                                        </td>
                                        <td><small class="text-muted">{{ $product->category?->name ?? '-' }}</small></td>
                                        <td class="text-end">
                                            <strong>{{ number_format($product->quantity, 0) }}</strong>
                                            <small class="text-muted">{{ $product->unit ?? 'pcs' }}</small>
                                        </td>
                                        <td class="text-end">{{ number_format($product->cost ?? 0, 0) }}</td>
                                        <td class="text-end text-primary">{{ number_format($product->price, 0) }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $statusClass }}">{{ $statusText }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('stock-management.add-product') }}?product_id={{ $product->id }}" 
                                               class="btn btn-sm btn-outline-success" title="{{ __('Add Stock') }}">
                                                <i class="fas fa-plus"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            {{ __('No products found') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Total Inventory Value --}}
    <div class="card bg-dark text-white">
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-4">
                    <h6 class="text-white-50">{{ __('Total Raw Materials Value') }}</h6>
                    <h3>Ks {{ number_format($totals['raw_materials_value'], 0) }}</h3>
                </div>
                <div class="col-md-4">
                    <h6 class="text-white-50">{{ __('Total Products Value') }}</h6>
                    <h3>Ks {{ number_format($totals['products_value'], 0) }}</h3>
                </div>
                <div class="col-md-4">
                    <h6 class="text-white-50">{{ __('Total Inventory Value') }}</h6>
                    <h2 class="text-success">Ks {{ number_format($totals['raw_materials_value'] + $totals['products_value'], 0) }}</h2>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
