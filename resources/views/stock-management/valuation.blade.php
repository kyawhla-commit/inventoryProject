@extends('layouts.app')

@section('title', __('Stock Valuation'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">{{ __('Stock Valuation Report') }}</h1>
            <p class="text-muted mb-0">{{ __('Current inventory value breakdown') }}</p>
        </div>
        <a href="{{ route('stock-management.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> {{ __('Back') }}
        </a>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="text-white-50">{{ __('Raw Materials Value') }}</h6>
                    <h3 class="mb-0">{{ number_format($totalRawMaterialValue, 0) }} Ks</h3>
                    <small>{{ $rawMaterials->count() }} {{ __('items') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="text-white-50">{{ __('Products Value') }}</h6>
                    <h3 class="mb-0">{{ number_format($totalProductValue, 0) }} Ks</h3>
                    <small>{{ $products->count() }} {{ __('items') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="text-white-50">{{ __('Total Inventory Value') }}</h6>
                    <h3 class="mb-0">{{ number_format($totalRawMaterialValue + $totalProductValue, 0) }} Ks</h3>
                    <small>{{ $rawMaterials->count() + $products->count() }} {{ __('total items') }}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Raw Materials Table -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-boxes me-2"></i>{{ __('Raw Materials') }}</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Supplier') }}</th>
                            <th class="text-end">{{ __('Quantity') }}</th>
                            <th class="text-end">{{ __('Unit Cost') }}</th>
                            <th class="text-end">{{ __('Total Value') }}</th>
                            <th>{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rawMaterials as $item)
                            <tr>
                                <td>
                                    <a href="{{ route('raw-materials.show', $item['id']) }}">{{ $item['name'] }}</a>
                                </td>
                                <td>{{ $item['supplier'] ?? '-' }}</td>
                                <td class="text-end">{{ number_format($item['quantity'], 2) }} {{ $item['unit'] }}</td>
                                <td class="text-end">{{ number_format($item['cost_per_unit'], 0) }} Ks</td>
                                <td class="text-end fw-bold">{{ number_format($item['total_value'], 0) }} Ks</td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'normal' => 'success',
                                            'low' => 'warning',
                                            'critical' => 'danger',
                                            'out_of_stock' => 'danger',
                                        ];
                                    @endphp
                                    <span class="badge bg-{{ $statusColors[$item['status']] ?? 'secondary' }}">
                                        {{ ucfirst(str_replace('_', ' ', $item['status'])) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="4" class="text-end">{{ __('Total Raw Materials Value') }}:</th>
                            <th class="text-end">{{ number_format($totalRawMaterialValue, 0) }} Ks</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-box me-2"></i>{{ __('Products') }}</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Category') }}</th>
                            <th class="text-end">{{ __('Quantity') }}</th>
                            <th class="text-end">{{ __('Unit Cost') }}</th>
                            <th class="text-end">{{ __('Total Value') }}</th>
                            <th>{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $item)
                            <tr>
                                <td>
                                    <a href="{{ route('products.show', $item['id']) }}">{{ $item['name'] }}</a>
                                </td>
                                <td>{{ $item['category'] ?? '-' }}</td>
                                <td class="text-end">{{ number_format($item['quantity'], 2) }} {{ $item['unit'] }}</td>
                                <td class="text-end">{{ number_format($item['cost_per_unit'], 0) }} Ks</td>
                                <td class="text-end fw-bold">{{ number_format($item['total_value'], 0) }} Ks</td>
                                <td>
                                    <span class="badge bg-{{ $statusColors[$item['status']] ?? 'secondary' }}">
                                        {{ ucfirst(str_replace('_', ' ', $item['status'])) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="4" class="text-end">{{ __('Total Products Value') }}:</th>
                            <th class="text-end">{{ number_format($totalProductValue, 0) }} Ks</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
