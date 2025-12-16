@extends('layouts.app')

@section('title', $product->name)

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
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1 class="h3 mb-1">{{ $product->name }}</h1>
            <p class="text-muted mb-0">
                {{ $product->category->name ?? 'Uncategorized' }}
                @if($product->barcode)
                    <span class="ms-2"><i class="fas fa-barcode"></i> {{ $product->barcode }}</span>
                @endif
            </p>
        </div>
        <div class="btn-group">
            <a href="{{ route('products.raw-materials.index', $product) }}" class="btn btn-outline-info">
                <i class="fas fa-industry"></i> {{ __('Raw Materials') }}
            </a>
            <a href="{{ route('products.edit', $product) }}" class="btn btn-outline-primary">
                <i class="fas fa-edit"></i> {{ __('Edit') }}
            </a>
            <a href="{{ route('products.duplicate', $product) }}" class="btn btn-outline-secondary">
                <i class="fas fa-copy"></i> {{ __('Duplicate') }}
            </a>
            <a href="{{ route('products.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> {{ __('Back') }}
            </a>
        </div>
    </div>

    <div class="row">
        {{-- Left Column --}}
        <div class="col-lg-8">
            {{-- Stock Status Card --}}
            <div class="card mb-4 border-{{ $stock_status['status_class'] }}">
                <div class="card-header bg-{{ $stock_status['status_class'] }} {{ in_array($stock_status['status'], ['out_of_stock', 'low_stock']) ? 'text-white' : '' }}">
                    <h5 class="mb-0">
                        <i class="fas fa-boxes me-2"></i>{{ __('Stock Status') }}
                        <span class="badge bg-light text-dark float-end">{{ $stock_status['status_label'] }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center border-end">
                            <h2 class="mb-0 text-{{ $stock_status['status_class'] }}">{{ $stock_status['current_stock'] }}</h2>
                            <small class="text-muted">{{ __('Current Stock') }}</small>
                        </div>
                        <div class="col-md-3 text-center border-end">
                            <h4 class="mb-0">{{ $stock_status['minimum_stock'] }}</h4>
                            <small class="text-muted">{{ __('Minimum Level') }}</small>
                        </div>
                        <div class="col-md-3 text-center border-end">
                            <h4 class="mb-0">{{ $stock_status['days_of_stock'] ?? '-' }}</h4>
                            <small class="text-muted">{{ __('Days of Stock') }}</small>
                        </div>
                        <div class="col-md-3 text-center">
                            <h4 class="mb-0">{{ $stock_status['avg_daily_sales'] }}</h4>
                            <small class="text-muted">{{ __('Avg Daily Sales') }}</small>
                        </div>
                    </div>
                    
                    @if($stock_status['reorder_needed'])
                        <div class="alert alert-warning mt-3 mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            {{ __('Reorder needed! Suggested production:') }} <strong>{{ $stock_status['suggested_production'] }}</strong> {{ __('units') }}
                        </div>
                    @endif
                </div>
                <div class="card-footer bg-light">
                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#adjustStockModal">
                        <i class="fas fa-plus-minus"></i> {{ __('Adjust Stock') }}
                    </button>
                    <a href="{{ route('production-plans.create', ['product_id' => $product->id]) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-industry"></i> {{ __('Create Production Plan') }}
                    </a>
                </div>
            </div>

            {{-- Profit Analysis --}}
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>{{ __('Profit Analysis') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center mb-3">
                                <small class="text-muted d-block">{{ __('Selling Price') }}</small>
                                <h4 class="text-primary mb-0">Ks {{ number_format($profit_analysis['selling_price'], 0) }}</h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center mb-3">
                                <small class="text-muted d-block">{{ __('Cost') }}</small>
                                <h4 class="text-danger mb-0">Ks {{ number_format($profit_analysis['cost'], 0) }}</h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center mb-3 bg-{{ $profit_analysis['profit_per_unit'] >= 0 ? 'success' : 'danger' }} bg-opacity-10">
                                <small class="text-muted d-block">{{ __('Profit/Unit') }}</small>
                                <h4 class="text-{{ $profit_analysis['profit_per_unit'] >= 0 ? 'success' : 'danger' }} mb-0">
                                    Ks {{ number_format($profit_analysis['profit_per_unit'], 0) }}
                                </h4>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <td>{{ __('Profit Margin') }}</td>
                                    <td class="text-end"><strong>{{ $profit_analysis['profit_margin'] }}%</strong></td>
                                </tr>
                                <tr>
                                    <td>{{ __('Markup') }}</td>
                                    <td class="text-end"><strong>{{ $profit_analysis['markup_percentage'] }}%</strong></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <td>{{ __('Inventory Value') }}</td>
                                    <td class="text-end">Ks {{ number_format($profit_analysis['total_inventory_value'], 0) }}</td>
                                </tr>
                                <tr>
                                    <td>{{ __('Potential Profit') }}</td>
                                    <td class="text-end text-success">Ks {{ number_format($profit_analysis['potential_profit'], 0) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Cost Breakdown --}}
            @if($cost_breakdown['materials']->count() > 0)
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-calculator me-2"></i>{{ __('Cost Breakdown') }}</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('Raw Material') }}</th>
                                <th class="text-end">{{ __('Qty Required') }}</th>
                                <th class="text-end">{{ __('Waste %') }}</th>
                                <th class="text-end">{{ __('Cost/Unit') }}</th>
                                <th class="text-end">{{ __('Total') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cost_breakdown['materials'] as $material)
                                <tr>
                                    <td>{{ $material['name'] }}</td>
                                    <td class="text-end">{{ $material['quantity'] }} {{ $material['unit'] }}</td>
                                    <td class="text-end">{{ $material['waste_percentage'] }}%</td>
                                    <td class="text-end">Ks {{ number_format($material['cost_per_unit'], 0) }}</td>
                                    <td class="text-end">Ks {{ number_format($material['total_cost'], 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="4" class="text-end">{{ __('Raw Material Cost') }}:</td>
                                <td class="text-end">Ks {{ number_format($cost_breakdown['raw_material_cost'], 0) }}</td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-end">{{ __('Labor Cost') }} (15%):</td>
                                <td class="text-end">Ks {{ number_format($cost_breakdown['labor_cost'], 0) }}</td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-end">{{ __('Overhead') }} (10%):</td>
                                <td class="text-end">Ks {{ number_format($cost_breakdown['overhead_cost'], 0) }}</td>
                            </tr>
                            <tr class="table-primary">
                                <td colspan="4" class="text-end"><strong>{{ __('Total Calculated Cost') }}:</strong></td>
                                <td class="text-end"><strong>Ks {{ number_format($cost_breakdown['total_cost'], 0) }}</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @endif

            {{-- Sales Analytics --}}
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>{{ __('Sales Analytics') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3 text-center">
                            <h4 class="mb-0">{{ $sales_analytics['total_sold'] }}</h4>
                            <small class="text-muted">{{ __('Total Sold') }}</small>
                        </div>
                        <div class="col-md-3 text-center">
                            <h4 class="mb-0">Ks {{ number_format($sales_analytics['total_revenue'], 0) }}</h4>
                            <small class="text-muted">{{ __('Total Revenue') }}</small>
                        </div>
                        <div class="col-md-3 text-center">
                            <h4 class="mb-0">{{ $sales_analytics['this_month_sales'] }}</h4>
                            <small class="text-muted">{{ __('This Month') }}</small>
                        </div>
                        <div class="col-md-3 text-center">
                            <h4 class="mb-0 {{ $sales_analytics['sales_change_percentage'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $sales_analytics['sales_change_percentage'] >= 0 ? '+' : '' }}{{ $sales_analytics['sales_change_percentage'] }}%
                            </h4>
                            <small class="text-muted">{{ __('vs Last Month') }}</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stock Movements --}}
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>{{ __('Recent Stock Movements') }}</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
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
                                    <td>{{ $movement->created_at->format('M d, H:i') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $movement->quantity >= 0 ? 'success' : 'danger' }}">
                                            {{ ucfirst($movement->type) }}
                                        </span>
                                    </td>
                                    <td class="text-end {{ $movement->quantity >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $movement->quantity >= 0 ? '+' : '' }}{{ $movement->quantity }}
                                    </td>
                                    <td>{{ Str::limit($movement->notes, 40) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">{{ __('No stock movements') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Right Column --}}
        <div class="col-lg-4">
            {{-- Product Image & Info --}}
            <div class="card mb-4">
                <div class="card-body text-center">
                    @if($product->image)
                        <img src="{{ asset($product->image) }}" class="img-fluid rounded mb-3" style="max-height: 200px;" alt="{{ $product->name }}">
                    @else
                        <div class="bg-light rounded d-flex align-items-center justify-content-center mb-3" style="height: 150px;">
                            <i class="fas fa-box fa-4x text-muted"></i>
                        </div>
                    @endif
                    <h5>{{ $product->name }}</h5>
                    <p class="text-muted">{{ $product->description ?? __('No description') }}</p>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>{{ __('Category') }}</span>
                        <strong>{{ $product->category->name ?? '-' }}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>{{ __('Unit') }}</span>
                        <strong>{{ $product->unit ?? 'pcs' }}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>{{ __('Barcode') }}</span>
                        <strong>{{ $product->barcode ?? '-' }}</strong>
                    </li>
                </ul>
            </div>

            {{-- Production Info --}}
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-industry me-2"></i>{{ __('Production Info') }}</h6>
                </div>
                <div class="card-body">
                    @if($production_info['has_recipe'])
                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ __('Raw Materials') }}</span>
                            <strong>{{ $production_info['raw_materials_count'] }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ __('Can Produce') }}</span>
                            <span class="badge bg-{{ $production_info['can_produce'] ? 'success' : 'danger' }}">
                                {{ $production_info['can_produce'] ? __('Yes') : __('No') }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>{{ __('Max Producible') }}</span>
                            <strong>{{ $production_info['max_producible'] }} {{ __('units') }}</strong>
                        </div>
                        
                        @if(count($production_info['material_shortages']) > 0)
                            <hr>
                            <p class="text-danger mb-2"><i class="fas fa-exclamation-triangle"></i> {{ __('Material Shortages') }}:</p>
                            @foreach($production_info['material_shortages'] as $shortage)
                                <small class="d-block text-muted">
                                    {{ $shortage['material'] }}: {{ __('need') }} {{ $shortage['required_per_unit'] }}, {{ __('have') }} {{ $shortage['available'] }}
                                </small>
                            @endforeach
                        @endif
                    @else
                        <p class="text-muted mb-0">{{ __('No recipe defined. Add raw materials to enable production tracking.') }}</p>
                        <a href="{{ route('products.raw-materials.index', $product) }}" class="btn btn-sm btn-outline-primary mt-2">
                            <i class="fas fa-plus"></i> {{ __('Add Raw Materials') }}
                        </a>
                    @endif
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-bolt me-2"></i>{{ __('Quick Actions') }}</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('orders.create') }}" class="btn btn-outline-primary">
                            <i class="fas fa-shopping-cart"></i> {{ __('Create Order') }}
                        </a>
                        <a href="{{ route('production-plans.create', ['product_id' => $product->id]) }}" class="btn btn-outline-success">
                            <i class="fas fa-industry"></i> {{ __('Plan Production') }}
                        </a>
                        <a href="{{ route('products.raw-materials.index', $product) }}" class="btn btn-outline-info">
                            <i class="fas fa-cogs"></i> {{ __('Manage Recipe') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Adjust Stock Modal --}}
<div class="modal fade" id="adjustStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('products.adjust-stock', $product) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Adjust Stock') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Adjustment Type') }}</label>
                        <select name="type" class="form-select" required>
                            <option value="add">{{ __('Add Stock') }}</option>
                            <option value="deduct">{{ __('Deduct Stock') }}</option>
                            <option value="adjustment">{{ __('Adjustment (Correction)') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Quantity') }}</label>
                        <input type="number" name="quantity" class="form-control" min="1" required>
                        <small class="text-muted">{{ __('Current stock:') }} {{ $product->quantity }}</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Notes') }}</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="{{ __('Reason for adjustment...') }}"></textarea>
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
