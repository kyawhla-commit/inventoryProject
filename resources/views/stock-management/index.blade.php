@extends('layouts.app')

@section('title', __('Stock Management'))

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">{{ __('Stock Management') }}</h1>
            <p class="text-muted mb-0">{{ __('Manage raw materials and product inventory') }}</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('stock-management.view-all') }}" class="btn btn-info">
                <i class="fas fa-warehouse me-1"></i> {{ __('View All Stock') }}
            </a>
            <a href="{{ route('stock-management.add-raw-material') }}" class="btn btn-success">
                <i class="fas fa-plus me-1"></i> {{ __('Add Raw Material Stock') }}
            </a>
            <a href="{{ route('stock-management.add-product') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> {{ __('Add Product Stock') }}
            </a>
            <a href="{{ route('stock-management.deduct') }}" class="btn btn-warning">
                <i class="fas fa-minus me-1"></i> {{ __('Deduct Stock') }}
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <!-- Raw Materials Stats -->
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-white-50">{{ __('Raw Materials') }}</h6>
                            <h3 class="mb-0">{{ $rawMaterialStats['total'] }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-boxes fa-2x opacity-50"></i>
                        </div>
                    </div>
                    <small>{{ __('Total Value') }}: {{ number_format($rawMaterialStats['total_value'], 0) }} Ks</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-white-50">{{ __('Products') }}</h6>
                            <h3 class="mb-0">{{ $productStats['total'] }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-box fa-2x opacity-50"></i>
                        </div>
                    </div>
                    <small>{{ __('Total Value') }}: {{ number_format($productStats['total_value'], 0) }} Ks</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-dark-50">{{ __('Low Stock Alerts') }}</h6>
                            <h3 class="mb-0">{{ $rawMaterialStats['low_stock'] + $productStats['low_stock'] }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
                        </div>
                    </div>
                    <small>{{ __('Materials') }}: {{ $rawMaterialStats['low_stock'] }} | {{ __('Products') }}: {{ $productStats['low_stock'] }}</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-white-50">{{ __('Out of Stock') }}</h6>
                            <h3 class="mb-0">{{ $rawMaterialStats['out_of_stock'] + $productStats['out_of_stock'] }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-times-circle fa-2x opacity-50"></i>
                        </div>
                    </div>
                    <small>{{ __('Materials') }}: {{ $rawMaterialStats['out_of_stock'] }} | {{ __('Products') }}: {{ $productStats['out_of_stock'] }}</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Low Stock Alerts -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>{{ __('Low Stock Raw Materials') }}</h5>
                </div>
                <div class="card-body p-0">
                    @if($lowStockMaterials->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('Material') }}</th>
                                        <th class="text-end">{{ __('Current') }}</th>
                                        <th class="text-end">{{ __('Minimum') }}</th>
                                        <th>{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($lowStockMaterials as $material)
                                        <tr>
                                            <td>
                                                <a href="{{ route('raw-materials.show', $material) }}">{{ $material->name }}</a>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge {{ $material->quantity <= 0 ? 'bg-danger' : 'bg-warning' }}">
                                                    {{ number_format($material->quantity, 2) }} {{ $material->unit }}
                                                </span>
                                            </td>
                                            <td class="text-end">{{ number_format($material->minimum_stock_level, 2) }} {{ $material->unit }}</td>
                                            <td>
                                                <a href="{{ route('stock-management.add-raw-material') }}?material_id={{ $material->id }}" 
                                                   class="btn btn-sm btn-success">
                                                    <i class="fas fa-plus"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-check-circle fa-2x mb-2"></i>
                            <p class="mb-0">{{ __('All raw materials are well stocked') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>{{ __('Low Stock Products') }}</h5>
                </div>
                <div class="card-body p-0">
                    @if($lowStockProducts->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('Product') }}</th>
                                        <th class="text-end">{{ __('Current') }}</th>
                                        <th class="text-end">{{ __('Minimum') }}</th>
                                        <th>{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($lowStockProducts as $product)
                                        <tr>
                                            <td>
                                                <a href="{{ route('products.show', $product) }}">{{ $product->name }}</a>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge {{ $product->quantity <= 0 ? 'bg-danger' : 'bg-warning' }}">
                                                    {{ number_format($product->quantity, 2) }} {{ $product->unit ?? 'pcs' }}
                                                </span>
                                            </td>
                                            <td class="text-end">{{ number_format($product->minimum_stock_level ?? 0, 2) }}</td>
                                            <td>
                                                <a href="{{ route('stock-management.add-product') }}?product_id={{ $product->id }}" 
                                                   class="btn btn-sm btn-success">
                                                    <i class="fas fa-plus"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-check-circle fa-2x mb-2"></i>
                            <p class="mb-0">{{ __('All products are well stocked') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Stock Movements -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-history me-2"></i>{{ __('Recent Stock Movements') }}</h5>
            <a href="{{ route('stock-management.movements') }}" class="btn btn-sm btn-outline-primary">
                {{ __('View All') }} <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Item') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th class="text-end">{{ __('Quantity') }}</th>
                            <th>{{ __('Notes') }}</th>
                            <th>{{ __('By') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentMovements as $movement)
                            <tr>
                                <td>{{ $movement->created_at->format('M d, Y H:i') }}</td>
                                <td>
                                    @if($movement->product)
                                        <span class="badge bg-info me-1">{{ __('Product') }}</span>
                                        {{ $movement->product->name }}
                                    @elseif($movement->rawMaterial)
                                        <span class="badge bg-secondary me-1">{{ __('Material') }}</span>
                                        {{ $movement->rawMaterial->name }}
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $typeColors = [
                                            'purchase' => 'success',
                                            'production' => 'primary',
                                            'usage' => 'warning',
                                            'sale' => 'info',
                                            'adjustment' => 'secondary',
                                            'waste' => 'danger',
                                            'return' => 'success',
                                            'damage' => 'danger',
                                        ];
                                    @endphp
                                    <span class="badge bg-{{ $typeColors[$movement->type] ?? 'secondary' }}">
                                        {{ ucfirst($movement->type) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <span class="{{ $movement->quantity >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $movement->quantity >= 0 ? '+' : '' }}{{ number_format($movement->quantity, 2) }}
                                    </span>
                                </td>
                                <td>{{ Str::limit($movement->notes, 30) }}</td>
                                <td>{{ $movement->creator?->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    {{ __('No stock movements recorded yet') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
