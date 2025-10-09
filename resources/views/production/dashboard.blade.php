@extends('layouts.app')

@section('content')
<div class="container-fluid">

<!-- Low Stock Alert with Details -->
@if($lowStockProducts->count() > 0)
<div class="card border-warning mb-4">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0">
            ⚠️ Low Stock Alert - {{ $lowStockProducts->count() }} product(s) need attention
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Current Stock</th>
                        <th>Minimum Required</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lowStockProducts as $product)
                    <tr>
                        <td>
                            <strong>{{ $product->name }}</strong>
                            @if($product->category)
                            <br><small class="text-muted">{{ $product->category->name }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="fw-bold text-danger">
                                {{ $product->quantity }} {{ $product->unit }}
                            </span>
                        </td>
                        <td>{{ $product->minimum_stock_level }} {{ $product->unit }}</td>
                        <td>
                            <span class="badge bg-{{ $product->stock_status_color }}">
                                {{ ucfirst($product->stock_status) }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('products.edit', $product->id) }}" 
                               class="btn btn-sm btn-outline-primary">
                                Restock
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($lowStockProducts->count() > 5)
        <div class="text-center">
            <small class="text-muted">
                ... and {{ $lowStockProducts->count() - 5 }} more products with low stock
            </small>
        </div>
        @endif
    </div>
</div>
@endif

<!-- Critical Stock Alert -->
@php
    $criticalProducts = $lowStockProducts->where('stock_status', 'out_of_stock');
@endphp

@if($criticalProducts->count() > 0)
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <h5>🚨 Critical Stock Alert!</h5>
    <strong>{{ $criticalProducts->count() }} product(s) are critically low on stock!</strong>
    <div class="mt-2">
        @foreach($criticalProducts as $product)
        <span class="badge bg-dark me-2">
            {{ $product->name }} (Only {{ $product->quantity }} {{ $product->unit }} left!)
        </span>
        @endforeach
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- Low Stock Alert -->
@php
    $lowStockOnly = $lowStockProducts->where('stock_status', 'low');
@endphp

@if($lowStockOnly->count() > 0)
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <h5>⚠️ Low Stock Alerts</h5>
    <strong>{{ $lowStockOnly->count() }} product(s) are running low on stock.</strong>
    
    <!-- Show specific products -->
    <div class="mt-2">
        @foreach($lowStockOnly as $product)
        <span class="badge bg-warning me-2">
            {{ $product->name }} ({{ $product->quantity }} {{ $product->unit }} left)
        </span>
        @endforeach
    </div>
    
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- Production Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">Total Plans</h5>
                <h2>{{ $productionStats['total_plans'] }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h5 class="card-title">In Progress</h5>
                <h2>{{ $productionStats['in_progress'] }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Completed</h5>
                <h2>{{ $productionStats['completed'] }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">Pending</h5>
                <h2>{{ $productionStats['pending'] }}</h2>
            </div>
        </div>
    </div>
</div>

<!-- Real-time Stock Levels -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Product Stock Levels</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Current Stock</th>
                                <th>In Production</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stockLevels as $product)
                            <tr>
                                <td>{{ $product->name }}</td>
                                <td>{{ $product->quantity }} {{ $product->unit }}</td>
                                <td>
                                    @php
                                        // Calculate in-production quantity from your controller data
                                        $inProduction = $productsProduced->where('product.id', $product->id)->first() ? 
                                                       $productsProduced->where('product.id', $product->id)->first()['total_produced'] : 0;
                                    @endphp
                                    {{ $inProduction }} {{ $product->unit }}
                                </td>
                                <td>
                                    <span class="badge bg-{{ $product->stock_status_color }}">
                                        {{ ucfirst($product->stock_status) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Critical Raw Materials</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Material</th>
                                <th>Current Stock</th>
                                <th>Monthly Usage</th>
                                <th>Days Remaining</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($criticalMaterials as $material)
                            <tr>
                                <td>{{ $material->name }}</td>
                                <td>{{ $material->quantity }} {{ $material->unit }}</td>
                                <td>{{ number_format($material->monthly_usage, 2) }} {{ $material->unit }}</td>
                                <td>
                                    <span class="badge bg-{{ $material->days_remaining < 7 ? 'danger' : 'warning' }}">
                                        {{ $material->days_remaining ?? 'N/A' }} days
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @if($criticalMaterials->count() == 0)
                <div class="text-center text-muted py-3">
                    No critical raw materials at this time
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Production Efficiency Metrics -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5>Production Efficiency</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <h6>Avg Completion Time</h6>
                        <h3 class="text-primary">{{ $efficiencyMetrics['avg_completion_time'] }} days</h3>
                    </div>
                    <div class="col-md-3 text-center">
                        <h6>On-Time Completion</h6>
                        <h3 class="text-success">{{ $efficiencyMetrics['on_time_completion_rate'] }}%</h3>
                    </div>
                    <div class="col-md-3 text-center">
                        <h6>Production Efficiency</h6>
                        <h3 class="text-info">{{ $efficiencyMetrics['avg_efficiency'] }}%</h3>
                    </div>
                    <div class="col-md-3 text-center">
                        <h6>Cost Variance</h6>
                        <h3 class="text-{{ $costVariancePercentage > 0 ? 'danger' : 'success' }}">
                            {{ number_format($costVariancePercentage, 1) }}%
                        </h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Completed Production Summary -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5>Recent Production Summary ({{ $startDate }} to {{ $endDate }})</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 text-center">
                        <h6>Total Produced</h6>
                        <h3 class="text-success">{{ $totalProduced }} units</h3>
                    </div>
                    <div class="col-md-4 text-center">
                        <h6>Total Production Cost</h6>
                        <h3 class="text-warning">${{ number_format($totalProductionCost, 2) }}</h3>
                    </div>
                    <div class="col-md-4 text-center">
                        <h6>Completed Plans</h6>
                        <h3 class="text-info">{{ $completedPlans->count() }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top Products Produced -->
@if($topProducts->count() > 0)
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5>Top Produced Products</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Total Produced</th>
                                <th>Current Stock</th>
                                <th>Total Cost</th>
                                <th>Avg Cost/Unit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topProducts as $productData)
                            <tr>
                                <td>{{ $productData['product']->name }}</td>
                                <td>{{ $productData['total_produced'] }} {{ $productData['product']->unit }}</td>
                                <td>{{ $productData['current_stock'] }} {{ $productData['product']->unit }}</td>
                                <td>${{ number_format($productData['total_cost'], 2) }}</td>
                                <td>${{ number_format($productData['avg_cost_per_unit'], 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Orders Fulfilled -->
@if($ordersFulfilled->count() > 0)
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5>Recent Orders Fulfilled</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Order Number</th>
                                <th>Customer</th>
                                <th>Items Fulfilled</th>
                                <th>Fulfillment Rate</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ordersFulfilled as $orderData)
                            <tr>
                                <td>{{ $orderData['order']->order_number ?? 'N/A' }}</td>
                                <td>{{ $orderData['customer']->name ?? 'N/A' }}</td>
                                <td>{{ $orderData['fulfilled_items'] }}/{{ $orderData['total_items'] }}</td>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar" role="progressbar" 
                                            style="width: {{ $orderData['fulfillment_percentage'] }}%"
                                            aria-valuenow="{{ $orderData['fulfillment_percentage'] }}" 
                                            aria-valuemin="0" 
                                            aria-valuemax="100">
                                            {{ round($orderData['fulfillment_percentage']) }}%
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $orderData['fulfillment_percentage'] == 100 ? 'success' : 'warning' }}">
                                        {{ $orderData['fulfillment_percentage'] == 100 ? 'Completed' : 'Partial' }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

</div>
@endsection