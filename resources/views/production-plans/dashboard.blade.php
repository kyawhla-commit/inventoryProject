@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-chart-line"></i> Production Dashboard</h2>
        <div>
            <a href="{{ route('production-plans.index') }}" class="btn btn-outline-primary">
                <i class="fas fa-list"></i> Production Plans
            </a>
            <a href="{{ route('production-reports.index') }}" class="btn btn-outline-info">
                <i class="fas fa-file-alt"></i> Reports
            </a>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('production-plans.dashboard') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $startDate }}">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="{{ $endDate }}">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filter
                    </button>
                    <a href="{{ route('production-plans.dashboard') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Total Produced</h6>
                            <h3 class="mb-0">{{ number_format($totalProduced ?? 0, 2) }}</h3>
                            <small>Units</small>
                        </div>
                        <div>
                            <i class="fas fa-industry fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Production Cost</h6>
                            <h3 class="mb-0">${{ number_format($totalProductionCost, 2) }}</h3>
                            <small>Actual Cost</small>
                        </div>
                        <div>
                            <i class="fas fa-dollar-sign fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card {{ $costVariance <= 0 ? 'bg-success' : 'bg-warning' }} text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Cost Variance</h6>
                            <h3 class="mb-0">{{ $costVariance <= 0 ? '-' : '+' }}${{ number_format(abs($costVariance), 2) }}</h3>
                            <small>({{ number_format($costVariancePercentage, 1) }}%)</small>
                        </div>
                        <div>
                            <i class="fas fa-chart-line fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Completed Plans</h6>
                            <h3 class="mb-0">{{ $efficiencyMetrics['total_plans_completed'] }}</h3>
                            <small>On-time: {{ $efficiencyMetrics['on_time_completion_rate'] }}%</small>
                        </div>
                        <div>
                            <i class="fas fa-check-circle fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Low Stock Alerts -->
    @if($lowStockProducts->count() > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-warning">
                <h5><i class="fas fa-exclamation-triangle"></i> Low Stock Alerts</h5>
                <p class="mb-0">
                    <strong>{{ $lowStockProducts->count() }}</strong> product(s) are running low on stock. 
                    <a href="#stock-movements" class="alert-link">View details</a>
                </p>
            </div>
        </div>
    </div>
    @endif

    <!-- Efficiency Metrics -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-tachometer-alt"></i> Production Efficiency Metrics</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <h4>{{ $efficiencyMetrics['avg_completion_time'] }}</h4>
                            <p class="text-muted">Avg Completion Time (days)</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <h4>{{ $efficiencyMetrics['on_time_completion_rate'] }}%</h4>
                            <p class="text-muted">On-Time Completion Rate</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <h4>{{ $efficiencyMetrics['quality_metrics']['avg_efficiency'] }}%</h4>
                            <p class="text-muted">Avg Production Efficiency</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <h4 class="{{ $efficiencyMetrics['quality_metrics']['variance_rate'] <= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($efficiencyMetrics['quality_metrics']['variance_rate'], 1) }}%
                            </h4>
                            <p class="text-muted">Cost Variance Rate</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Products and Orders -->
    <div class="row mb-4">
        <!-- Top Performing Products -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-trophy"></i> Top Performing Products</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-end">Produced</th>
                                    <th class="text-end">Stock</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topProducts as $item)
                                    <tr>
                                        <td>
                                            <strong>{{ $item['product']->name }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $item['production_count'] }} production(s)</small>
                                        </td>
                                        <td class="text-end">
                                            {{ number_format($item['total_produced'], 2) }} {{ $item['product']->unit }}
                                        </td>
                                        <td class="text-end">
                                            {{ number_format($item['current_stock'], 2) }}
                                        </td>
                                        <td>
                                            @if($item['stock_status'] === 'low')
                                                <span class="badge bg-warning">Low Stock</span>
                                            @else
                                                <span class="badge bg-success">Normal</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No production data available</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders Fulfilled -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-shopping-cart"></i> Orders Fulfilled</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th class="text-end">Items</th>
                                    <th class="text-end">Fulfillment</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ordersFulfilled as $orderData)
                                    <tr>
                                        <td>
                                            <a href="{{ route('orders.show', $orderData['order']) }}">
                                                #{{ $orderData['order']->id }}
                                            </a>
                                        </td>
                                        <td>{{ $orderData['customer']->name ?? 'N/A' }}</td>
                                        <td class="text-end">
                                            {{ $orderData['fulfilled_items'] }} / {{ $orderData['total_items'] }}
                                        </td>
                                        <td class="text-end">
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar {{ $orderData['fulfillment_percentage'] >= 100 ? 'bg-success' : 'bg-warning' }}" 
                                                     role="progressbar" 
                                                     style="width: {{ min($orderData['fulfillment_percentage'], 100) }}%"
                                                     aria-valuenow="{{ $orderData['fulfillment_percentage'] }}" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                    {{ number_format($orderData['fulfillment_percentage'], 0) }}%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No orders fulfilled in this period</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Movements -->
    <!-- After the Efficiency Metrics section and before the Top Products section -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-exchange-alt"></i> Real-Time Stock Movement</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-end">Initial Stock</th>
                                    <th class="text-end">Production (+)</th>
                                    <th class="text-end">Orders (-)</th>
                                    <th class="text-end">Current Stock</th>
                                    <th>Status</th>
                                    <th>Trend</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stockMovements as $movement)
                                    <tr>
                                        <td>
                                            <strong>{{ $movement['product']->name }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $movement['product']->unit }}</small>
                                        </td>
                                        <td class="text-end">
                                            {{ number_format($movement['initial_stock']?? 0, 2) }}
                                        </td>
                                        <td class="text-end text-success">
                                            +{{ number_format($movement['produced_quantity'], 2) }}
                                        </td>
                                        <td class="text-end text-danger">
                                            -{{ number_format($movement['ordered_quantity'] ?? 0, 2) }}
                                        </td>
                                        <td class="text-end fw-bold">
                                            {{ number_format($movement['current_stock'], 2) }}
                                        </td>
                                        <td>
                                            @switch($movement['stock_status'])
                                                @case('out_of_stock')
                                                    <span class="badge bg-danger">Out of Stock</span>
                                                    @break
                                                @case('critical')
                                                    <span class="badge bg-danger">Critical</span>
                                                    @break
                                                @case('low')
                                                    <span class="badge bg-warning">Low Stock</span>
                                                    @break
                                                @default
                                                    <span class="badge bg-success">Normal</span>
                                            @endswitch
                                        </td>
                                        <td>
    @if(($movement['stock_trend'] ?? 0) > 0)
        <span class="text-success"><i class="fas fa-arrow-up"></i> Increasing</span>
    @elseif(($movement['stock_trend'] ?? 0) < 0)
        <span class="text-danger"><i class="fas fa-arrow-down"></i> Decreasing</span>
    @else
        <span class="text-secondary"><i class="fas fa-minus"></i> Stable</span>
    @endif
</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No stock movements in this period</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add this after the Stock Movements section -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-balance-scale"></i> Production vs. Orders Analysis</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-end">Production Rate</th>
                                <th class="text-end">Order Rate</th>
                                <th class="text-end">Balance</th>
                                <th>Status</th>
                                <th>Recommendation</th>
                            </tr>
                        </thead>
                        <tbody>
    @forelse($stockMovements as $movement)
        @php
            $producedQuantity = $movement['produced_quantity'] ?? 0;
            $orderedQuantity = $movement['ordered_quantity'] ?? 0;
            
            $productionRate = $producedQuantity > 0 ? 
                $producedQuantity / (strtotime($endDate) - strtotime($startDate)) * 86400 : 0;
            
            $orderRate = $orderedQuantity > 0 ? 
                $orderedQuantity / (strtotime($endDate) - strtotime($startDate)) * 86400 : 0;
            
            $balance = $productionRate - $orderRate;
            
            if ($balance > 0.1) {
                $status = 'surplus';
                $statusClass = 'success';
                $recommendation = 'Consider reducing production or increasing marketing';
            } elseif ($balance < -0.1) {
                $status = 'deficit';
                $statusClass = 'danger';
                $recommendation = 'Increase production to meet demand';
            } else {
                $status = 'balanced';
                $statusClass = 'info';
                $recommendation = 'Production matches demand';
            }
        @endphp
        <tr>
            <td>
                <strong>{{ $movement['product']->name ?? 'N/A' }}</strong>
                <br>
                <small class="text-muted">{{ $movement['product']->unit ?? '' }}</small>
            </td>
            <td class="text-end">
                {{ number_format($productionRate, 2) }} / day
            </td>
            <td class="text-end">
                {{ number_format($orderRate, 2) }} / day
            </td>
            <td class="text-end {{ $balance >= 0 ? 'text-success' : 'text-danger' }}">
                {{ $balance >= 0 ? '+' : '' }}{{ number_format($balance, 2) }} / day
            </td>
            <td>
                <span class="badge bg-{{ $statusClass }}">
                    {{ ucfirst($status) }}
                </span>
            </td>
            <td>
                <small>{{ $recommendation }}</small>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="6" class="text-center text-muted">No data available for analysis</td>
        </tr>
    @endforelse
</tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- Products Produced Details -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-cubes"></i> Products Produced Details</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-end">Total Produced</th>
                                    <th class="text-end">Production Count</th>
                                    <th class="text-end">Total Cost</th>
                                    <th class="text-end">Avg Cost/Unit</th>
                                    <th class="text-end">Stock Value</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($productsProduced as $item)
                                    <tr>
                                        <td>
                                            <strong>{{ $item['product']->name }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $item['product']->unit }}</small>
                                        </td>
                                        <td class="text-end">
                                            {{ number_format($item['total_produced'], 2) }}
                                        </td>
                                        <td class="text-end">
                                            {{ $item['production_count'] }}
                                        </td>
                                        <td class="text-end">
                                            ${{ number_format($item['total_cost'], 2) }}
                                        </td>
                                        <td class="text-end">
                                            ${{ number_format($item['avg_cost_per_unit'], 2) }}
                                        </td>
                                        <td class="text-end">
                                            ${{ number_format($item['stock_value'], 2) }}
                                        </td>
                                        <td>
                                            @if($item['stock_status'] === 'low')
                                                <span class="badge bg-warning">Low Stock</span>
                                            @else
                                                <span class="badge bg-success">Normal</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No products produced in this period</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if($productsProduced->count() > 0)
                            <tfoot class="table-info">
                                <tr>
                                    <th>Total</th>
                                    <th class="text-end">{{ number_format($productsProduced->sum('total_produced'), 2) }}</th>
                                    <th class="text-end">{{ $productsProduced->sum('production_count') }}</th>
                                    <th class="text-end">${{ number_format($productsProduced->sum('total_cost'), 2) }}</th>
                                    <th colspan="3"></th>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Completed Plans -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history"></i> Recent Completed Production Plans</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Plan Number</th>
                                    <th>Name</th>
                                    <th>Completed Date</th>
                                    <th class="text-end">Items</th>
                                    <th class="text-end">Est. Cost</th>
                                    <th class="text-end">Actual Cost</th>
                                    <th class="text-end">Variance</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($completedPlans->take(10) as $plan)
                                    <tr>
                                        <td>{{ $plan->plan_number }}</td>
                                        <td>{{ $plan->name }}</td>
                                        <td>{{ $plan->actual_end_date->format('M d, Y') }}</td>
                                        <td class="text-end">{{ $plan->productionPlanItems->count() }}</td>
                                        <td class="text-end">${{ number_format($plan->total_estimated_cost, 2) }}</td>
                                        <td class="text-end">${{ number_format($plan->total_actual_cost, 2) }}</td>
                                        <td class="text-end">
                                            <span class="{{ ($plan->total_actual_cost - $plan->total_estimated_cost) <= 0 ? 'text-success' : 'text-danger' }}">
                                                ${{ number_format($plan->total_actual_cost - $plan->total_estimated_cost, 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('production-plans.show', $plan) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">No completed production plans in this period</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .opacity-50 {
        opacity: 0.5;
    }
    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    .progress {
        border-radius: 0.25rem;
    }
</style>
@endpush
