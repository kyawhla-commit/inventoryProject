@extends('layouts.app')

@section('title', __('Dashboard'))

@push('styles')
<style>
    .stat-card {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .quick-action-btn {
        transition: all 0.2s;
    }
    .quick-action-btn:hover {
        transform: scale(1.02);
    }
    .activity-item {
        border-left: 3px solid transparent;
        transition: all 0.2s;
    }
    .activity-item:hover {
        background-color: rgba(0,0,0,0.02);
        border-left-color: #0d6efd;
    }
    .metric-icon {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
    }
    .progress-thin {
        height: 6px;
    }
    
    /* Today's Performance Card - Light Mode */
    .bg-gradient-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 50%, #1e40af 100%) !important;
        color: #ffffff !important;
        border: none;
        box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
    }
    
    .bg-gradient-primary .text-white-50 {
        color: rgba(255, 255, 255, 0.75) !important;
    }
    
    .bg-gradient-primary h6,
    .bg-gradient-primary .h4,
    .bg-gradient-primary span,
    .bg-gradient-primary small {
        color: inherit !important;
    }
    
    .bg-gradient-primary i {
        color: rgba(255, 255, 255, 0.85) !important;
    }
    
    /* Dark Mode adjustments for Today's Performance */
    .dark-mode .bg-gradient-primary,
    [data-bs-theme="dark"] .bg-gradient-primary {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 50%, #1e3a8a 100%) !important;
        box-shadow: 0 4px 20px rgba(37, 99, 235, 0.5);
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">{{ __('Dashboard') }}</h1>
            <p class="text-muted mb-0">{{ now()->format('l, F d, Y') }}</p>
        </div>
        <div class="d-flex gap-2">
            <select id="monthSelector" class="form-select" style="width: auto;" onchange="window.location.href='?month='+this.value">
                @for ($i = 0; $i < 12; $i++)
                    @php
                        $monthDate = now()->subMonths($i);
                        $monthValue = $monthDate->format('Y-m');
                        $monthLabel = $monthDate->format('F Y');
                    @endphp
                    <option value="{{ $monthValue }}" {{ $selectedMonth === $monthValue ? 'selected' : '' }}>
                        {{ $monthLabel }}
                    </option>
                @endfor
            </select>
        </div>
    </div>

    {{-- Today's Quick Stats --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient-primary text-white">
                <div class="card-body py-3">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <i class="fas fa-sun fa-2x opacity-75"></i>
                        </div>
                        <div class="col">
                            <h6 class="text-white-50 mb-1">{{ __("Today's Performance") }}</h6>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="d-flex align-items-center">
                                        <span class="h4 mb-0 me-2">Ks {{ number_format($todayStats['sales'], 0) }}</span>
                                        <small class="text-white-50">{{ __('Sales') }}</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex align-items-center">
                                        <span class="h4 mb-0 me-2">{{ $todayStats['sales_count'] }}</span>
                                        <small class="text-white-50">{{ __('Transactions') }}</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex align-items-center">
                                        <span class="h4 mb-0 me-2">{{ $todayStats['orders'] }}</span>
                                        <small class="text-white-50">{{ __('New Orders') }}</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex align-items-center">
                                        <span class="h4 mb-0 me-2">{{ $todayStats['new_customers'] }}</span>
                                        <small class="text-white-50">{{ __('New Customers') }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Action Alerts --}}
    @if($quickActions['low_stock_count'] > 0 || $quickActions['pending_orders'] > 0 || $quickActions['pending_deliveries'] > 0)
    <div class="row mb-4">
        @if($quickActions['low_stock_count'] > 0)
        <div class="col-md-4">
            <div class="alert alert-warning d-flex align-items-center mb-0">
                <i class="fas fa-exclamation-triangle me-3 fa-lg"></i>
                <div class="flex-grow-1">
                    <strong>{{ $quickActions['low_stock_count'] }}</strong> {{ __('items low on stock') }}
                </div>
                <a href="{{ route('stock-management.view-all', ['stock_status' => 'low_stock']) }}" class="btn btn-sm btn-warning">
                    {{ __('View') }}
                </a>
            </div>
        </div>
        @endif
        @if($quickActions['pending_orders'] > 0)
        <div class="col-md-4">
            <div class="alert alert-info d-flex align-items-center mb-0">
                <i class="fas fa-shopping-cart me-3 fa-lg"></i>
                <div class="flex-grow-1">
                    <strong>{{ $quickActions['pending_orders'] }}</strong> {{ __('orders pending') }}
                </div>
                <a href="{{ route('orders.index', ['status' => 'pending']) }}" class="btn btn-sm btn-info">
                    {{ __('View') }}
                </a>
            </div>
        </div>
        @endif
        @if($quickActions['pending_deliveries'] > 0)
        <div class="col-md-4">
            <div class="alert alert-primary d-flex align-items-center mb-0">
                <i class="fas fa-truck me-3 fa-lg"></i>
                <div class="flex-grow-1">
                    <strong>{{ $quickActions['pending_deliveries'] }}</strong> {{ __('deliveries pending') }}
                </div>
                <a href="{{ route('deliveries.index', ['status' => 'pending']) }}" class="btn btn-sm btn-primary">
                    {{ __('View') }}
                </a>
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- Monthly Financial Overview --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card stat-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-1">{{ __('Monthly Revenue') }}</p>
                            <h3 class="mb-0">Ks {{ number_format($monthlyRevenue, 0) }}</h3>
                            <small class="{{ $monthlyRevenueChange >= 0 ? 'text-success' : 'text-danger' }}">
                                <i class="fas fa-arrow-{{ $monthlyRevenueChange >= 0 ? 'up' : 'down' }}"></i>
                                {{ number_format(abs($monthlyRevenueChange), 1) }}% {{ __('vs last month') }}
                            </small>
                        </div>
                        <div class="metric-icon bg-success bg-opacity-10 text-success">
                            <i class="fas fa-chart-line fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-1">{{ __('Monthly Expenses') }}</p>
                            <h3 class="mb-0">Ks {{ number_format($monthlyExpenses, 0) }}</h3>
                            <small class="{{ $monthlyExpenseChange <= 0 ? 'text-success' : 'text-danger' }}">
                                <i class="fas fa-arrow-{{ $monthlyExpenseChange >= 0 ? 'up' : 'down' }}"></i>
                                {{ number_format(abs($monthlyExpenseChange), 1) }}% {{ __('vs last month') }}
                            </small>
                        </div>
                        <div class="metric-icon bg-danger bg-opacity-10 text-danger">
                            <i class="fas fa-receipt fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card h-100 border-0 shadow-sm {{ $monthlyProfit >= 0 ? 'border-success' : 'border-danger' }}">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-1">{{ __('Net Profit') }}</p>
                            <h3 class="mb-0 {{ $monthlyProfit >= 0 ? 'text-success' : 'text-danger' }}">
                                Ks {{ number_format($monthlyProfit, 0) }}
                            </h3>
                            <small class="{{ $monthlyProfitChange >= 0 ? 'text-success' : 'text-danger' }}">
                                <i class="fas fa-arrow-{{ $monthlyProfitChange >= 0 ? 'up' : 'down' }}"></i>
                                {{ number_format(abs($monthlyProfitChange), 1) }}% {{ __('vs last month') }}
                            </small>
                        </div>
                        <div class="metric-icon {{ $monthlyProfit >= 0 ? 'bg-success' : 'bg-danger' }} bg-opacity-10 {{ $monthlyProfit >= 0 ? 'text-success' : 'text-danger' }}">
                            <i class="fas fa-{{ $monthlyProfit >= 0 ? 'trophy' : 'exclamation-triangle' }} fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Enhanced Monthly Business Overview --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-calendar-alt me-2 text-primary"></i>
                        {{ __('Monthly Business Overview') }} - {{ \Carbon\Carbon::parse($selectedMonth)->format('F Y') }}
                    </h6>
                    <span class="badge bg-primary">{{ $monthlyBusinessStats['sales_count'] }} {{ __('Transactions') }}</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        {{-- Orders This Month --}}
                        <div class="col-md-2 col-6">
                            <div class="text-center p-3 rounded bg-primary bg-opacity-10">
                                <div class="metric-icon bg-primary bg-opacity-25 text-primary mx-auto mb-2" style="width: 40px; height: 40px;">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <h4 class="mb-0">{{ $monthlyBusinessStats['orders_count'] }}</h4>
                                <small class="text-muted">{{ __('Orders') }}</small>
                                <div class="mt-1">
                                    <small class="{{ $monthlyBusinessStats['orders_change'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        <i class="fas fa-arrow-{{ $monthlyBusinessStats['orders_change'] >= 0 ? 'up' : 'down' }} fa-xs"></i>
                                        {{ number_format(abs($monthlyBusinessStats['orders_change']), 1) }}%
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        {{-- New Customers --}}
                        <div class="col-md-2 col-6">
                            <div class="text-center p-3 rounded bg-success bg-opacity-10">
                                <div class="metric-icon bg-success bg-opacity-25 text-success mx-auto mb-2" style="width: 40px; height: 40px;">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                                <h4 class="mb-0">{{ $monthlyBusinessStats['new_customers'] }}</h4>
                                <small class="text-muted">{{ __('New Customers') }}</small>
                                <div class="mt-1">
                                    <small class="{{ $monthlyBusinessStats['customers_change'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        <i class="fas fa-arrow-{{ $monthlyBusinessStats['customers_change'] >= 0 ? 'up' : 'down' }} fa-xs"></i>
                                        {{ number_format(abs($monthlyBusinessStats['customers_change']), 1) }}%
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Deliveries Completed --}}
                        <div class="col-md-2 col-6">
                            <div class="text-center p-3 rounded bg-info bg-opacity-10">
                                <div class="metric-icon bg-info bg-opacity-25 text-info mx-auto mb-2" style="width: 40px; height: 40px;">
                                    <i class="fas fa-truck-loading"></i>
                                </div>
                                <h4 class="mb-0">{{ $monthlyBusinessStats['deliveries_completed'] }}</h4>
                                <small class="text-muted">{{ __('Deliveries') }}</small>
                                <div class="mt-1">
                                    <small class="{{ $monthlyBusinessStats['deliveries_change'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        <i class="fas fa-arrow-{{ $monthlyBusinessStats['deliveries_change'] >= 0 ? 'up' : 'down' }} fa-xs"></i>
                                        {{ number_format(abs($monthlyBusinessStats['deliveries_change']), 1) }}%
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Production Completed --}}
                        <div class="col-md-2 col-6">
                            <div class="text-center p-3 rounded bg-warning bg-opacity-10">
                                <div class="metric-icon bg-warning bg-opacity-25 text-warning mx-auto mb-2" style="width: 40px; height: 40px;">
                                    <i class="fas fa-industry"></i>
                                </div>
                                <h4 class="mb-0">{{ $monthlyBusinessStats['production_completed'] }}</h4>
                                <small class="text-muted">{{ __('Productions') }}</small>
                                <div class="mt-1">
                                    <small class="{{ $monthlyBusinessStats['production_change'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        <i class="fas fa-arrow-{{ $monthlyBusinessStats['production_change'] >= 0 ? 'up' : 'down' }} fa-xs"></i>
                                        {{ number_format(abs($monthlyBusinessStats['production_change']), 1) }}%
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Average Order Value --}}
                        <div class="col-md-2 col-6">
                            <div class="text-center p-3 rounded bg-secondary bg-opacity-10">
                                <div class="metric-icon bg-secondary bg-opacity-25 text-secondary mx-auto mb-2" style="width: 40px; height: 40px;">
                                    <i class="fas fa-calculator"></i>
                                </div>
                                <h4 class="mb-0">Ks {{ number_format($monthlyBusinessStats['avg_order_value'], 0) }}</h4>
                                <small class="text-muted">{{ __('Avg Order') }}</small>
                                <div class="mt-1">
                                    <small class="{{ $monthlyBusinessStats['avg_order_change'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        <i class="fas fa-arrow-{{ $monthlyBusinessStats['avg_order_change'] >= 0 ? 'up' : 'down' }} fa-xs"></i>
                                        {{ number_format(abs($monthlyBusinessStats['avg_order_change']), 1) }}%
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Profit Margin --}}
                        <div class="col-md-2 col-6">
                            <div class="text-center p-3 rounded {{ $monthlyBusinessStats['profit_margin'] >= 0 ? 'bg-success' : 'bg-danger' }} bg-opacity-10">
                                <div class="metric-icon {{ $monthlyBusinessStats['profit_margin'] >= 0 ? 'bg-success' : 'bg-danger' }} bg-opacity-25 {{ $monthlyBusinessStats['profit_margin'] >= 0 ? 'text-success' : 'text-danger' }} mx-auto mb-2" style="width: 40px; height: 40px;">
                                    <i class="fas fa-percentage"></i>
                                </div>
                                <h4 class="mb-0 {{ $monthlyBusinessStats['profit_margin'] >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($monthlyBusinessStats['profit_margin'], 1) }}%</h4>
                                <small class="text-muted">{{ __('Profit Margin') }}</small>
                                <div class="mt-1">
                                    <small class="{{ $monthlyBusinessStats['profit_margin_change'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        <i class="fas fa-arrow-{{ $monthlyBusinessStats['profit_margin_change'] >= 0 ? 'up' : 'down' }} fa-xs"></i>
                                        {{ number_format(abs($monthlyBusinessStats['profit_margin_change']), 1) }}pp
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Expense Breakdown --}}
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6 class="text-muted mb-3"><i class="fas fa-pie-chart me-2"></i>{{ __('Expense Breakdown') }}</h6>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between align-items-center p-2 border rounded">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-danger bg-opacity-10 text-danger rounded p-2 me-3">
                                                <i class="fas fa-boxes"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block">{{ __('Purchase Costs') }}</small>
                                                <strong>Ks {{ number_format($monthlyBusinessStats['purchase_costs'], 0) }}</strong>
                                            </div>
                                        </div>
                                        @php
                                            $purchasePercent = $monthlyExpenses > 0 ? ($monthlyBusinessStats['purchase_costs'] / $monthlyExpenses) * 100 : 0;
                                        @endphp
                                        <span class="badge bg-danger">{{ number_format($purchasePercent, 0) }}%</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between align-items-center p-2 border rounded">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-warning bg-opacity-10 text-warning rounded p-2 me-3">
                                                <i class="fas fa-users"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block">{{ __('Staff Costs') }}</small>
                                                <strong>Ks {{ number_format($monthlyBusinessStats['staff_costs'], 0) }}</strong>
                                            </div>
                                        </div>
                                        @php
                                            $staffPercent = $monthlyExpenses > 0 ? ($monthlyBusinessStats['staff_costs'] / $monthlyExpenses) * 100 : 0;
                                        @endphp
                                        <span class="badge bg-warning text-dark">{{ number_format($staffPercent, 0) }}%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Business Metrics Row --}}
    <div class="row mb-4">
        {{-- Orders --}}
        <div class="col-md-3">
            <div class="card stat-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="metric-icon bg-primary bg-opacity-10 text-primary">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <span class="badge bg-primary">{{ $totalOrders }}</span>
                    </div>
                    <h6 class="text-muted mb-2">{{ __('Orders') }}</h6>
                    <div class="d-flex flex-wrap gap-1">
                        <span class="badge bg-warning">{{ $ordersByStatus['pending'] ?? 0 }} {{ __('Pending') }}</span>
                        <span class="badge bg-info">{{ $ordersByStatus['confirmed'] ?? 0 }} {{ __('Confirmed') }}</span>
                        <span class="badge bg-success">{{ $ordersByStatus['completed'] ?? 0 }} {{ __('Done') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Deliveries --}}
        <div class="col-md-3">
            <div class="card stat-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="metric-icon bg-info bg-opacity-10 text-info">
                            <i class="fas fa-truck"></i>
                        </div>
                        <span class="badge bg-info">{{ $deliveryStats['today_scheduled'] }} {{ __('Today') }}</span>
                    </div>
                    <h6 class="text-muted mb-2">{{ __('Deliveries') }}</h6>
                    <div class="d-flex flex-wrap gap-1">
                        <span class="badge bg-warning">{{ $deliveryStats['pending'] }} {{ __('Pending') }}</span>
                        <span class="badge bg-primary">{{ $deliveryStats['in_progress'] }} {{ __('In Transit') }}</span>
                        <span class="badge bg-success">{{ $deliveryStats['today_delivered'] }} {{ __('Delivered') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Production --}}
        <div class="col-md-3">
            <div class="card stat-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="metric-icon bg-warning bg-opacity-10 text-warning">
                            <i class="fas fa-industry"></i>
                        </div>
                        <span class="badge bg-warning text-dark">{{ $productionStats['active_plans'] }} {{ __('Active') }}</span>
                    </div>
                    <h6 class="text-muted mb-2">{{ __('Production') }}</h6>
                    <div class="d-flex flex-wrap gap-1">
                        <span class="badge bg-secondary">{{ $productionStats['pending_plans'] }} {{ __('Planned') }}</span>
                        <span class="badge bg-success">{{ $productionStats['completed_today'] }} {{ __('Done Today') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Inventory --}}
        <div class="col-md-3">
            <div class="card stat-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="metric-icon bg-secondary bg-opacity-10 text-secondary">
                            <i class="fas fa-warehouse"></i>
                        </div>
                        <a href="{{ route('stock-management.view-all') }}" class="btn btn-sm btn-outline-secondary">
                            {{ __('View') }}
                        </a>
                    </div>
                    <h6 class="text-muted mb-2">{{ __('Inventory Value') }}</h6>
                    <h5 class="mb-0">Ks {{ number_format($inventoryValue['total'], 0) }}</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Sales Chart --}}
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-chart-area me-2 text-primary"></i>{{ __('Sales vs Purchases Trend') }}</h6>
                </div>
                <div class="card-body">
                    <canvas id="salesChart" height="300"></canvas>
                </div>
            </div>
        </div>

        {{-- Sales Goal & Top Products --}}
        <div class="col-lg-4 mb-4">
            {{-- Sales Goal --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-0">
                    <h6 class="mb-0"><i class="fas fa-bullseye me-2 text-success"></i>{{ __('Monthly Sales Goal') }}</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Ks {{ number_format($currentMonthSales, 0) }}</span>
                        <span class="text-muted">Ks {{ number_format($monthlySalesGoal, 0) }}</span>
                    </div>
                    <div class="progress progress-thin mb-2">
                        <div class="progress-bar bg-success" style="width: {{ min($salesProgressPercentage, 100) }}%"></div>
                    </div>
                    <small class="text-muted">{{ number_format($salesProgressPercentage, 1) }}% {{ __('achieved') }}</small>
                    
                    @if(auth()->user()->role == 'admin')
                    <hr>
                    <form action="{{ route('dashboard.goal') }}" method="POST" class="d-flex gap-2">
                        @csrf
                        <input type="number" name="monthly_sales_goal" class="form-control form-control-sm" 
                               placeholder="{{ __('New goal') }}" value="{{ $monthlySalesGoal }}">
                        <button type="submit" class="btn btn-sm btn-primary">{{ __('Set') }}</button>
                    </form>
                    @endif
                </div>
            </div>

            {{-- Top Products --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0">
                    <h6 class="mb-0"><i class="fas fa-star me-2 text-warning"></i>{{ __('Top Selling Products') }}</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse($topProductLabels as $index => $name)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>
                                    <span class="badge bg-{{ $index == 0 ? 'warning' : 'secondary' }} me-2">{{ $index + 1 }}</span>
                                    {{ $name }}
                                </span>
                                <span class="badge bg-primary rounded-pill">{{ $topProductQuantities[$index] }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-center text-muted">{{ __('No sales data') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Recent Sales --}}
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-receipt me-2 text-success"></i>{{ __('Recent Sales') }}</h6>
                    <a href="{{ route('sales.index') }}" class="btn btn-sm btn-outline-success">{{ __('View All') }}</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('ID') }}</th>
                                    <th>{{ __('Customer') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th class="text-end">{{ __('Amount') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentSales as $sale)
                                    <tr class="activity-item">
                                        <td><a href="{{ route('sales.show', $sale) }}">#{{ $sale->id }}</a></td>
                                        <td>{{ $sale->customer->name ?? 'N/A' }}</td>
                                        <td>{{ \Carbon\Carbon::parse($sale->sale_date)->format('M d') }}</td>
                                        <td class="text-end">Ks {{ number_format($sale->total_amount, 0) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-3">{{ __('No recent sales') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Today's Deliveries --}}
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-truck me-2 text-info"></i>{{ __("Today's Deliveries") }}</h6>
                    <a href="{{ route('deliveries.dashboard') }}" class="btn btn-sm btn-outline-info">{{ __('Dashboard') }}</a>
                </div>
                <div class="card-body p-0">
                    @if($todayDeliveries->count() > 0)
                        <ul class="list-group list-group-flush">
                            @foreach($todayDeliveries as $delivery)
                                <li class="list-group-item activity-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <a href="{{ route('deliveries.show', $delivery) }}" class="fw-bold text-decoration-none">
                                                {{ $delivery->delivery_number }}
                                            </a>
                                            <div class="small text-muted">{{ $delivery->contact_name }} - {{ $delivery->scheduled_time ?? '--:--' }}</div>
                                        </div>
                                        <span class="badge bg-{{ $delivery->status_badge_class }}">{{ $delivery->status_label }}</span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-calendar-check fa-2x mb-2"></i>
                            <p class="mb-0">{{ __('No deliveries scheduled for today') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Low Stock Alerts --}}
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm border-start border-warning border-4">
                <div class="card-header bg-transparent border-0">
                    <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2 text-warning"></i>{{ __('Low Stock Products') }}</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 250px;">
                        <table class="table table-sm mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>{{ __('Product') }}</th>
                                    <th class="text-end">{{ __('Stock') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lowStockProducts->take(5) as $product)
                                    <tr>
                                        <td><a href="{{ route('products.show', $product) }}">{{ $product->name }}</a></td>
                                        <td class="text-end"><span class="badge bg-danger">{{ $product->quantity }} {{ $product->unit }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="text-center text-muted py-3">{{ __('All products well stocked') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm border-start border-danger border-4">
                <div class="card-header bg-transparent border-0">
                    <h6 class="mb-0"><i class="fas fa-boxes me-2 text-danger"></i>{{ __('Low Stock Raw Materials') }}</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 250px;">
                        <table class="table table-sm mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>{{ __('Material') }}</th>
                                    <th class="text-end">{{ __('Current') }}</th>
                                    <th class="text-end">{{ __('Minimum') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lowStockRawMaterials->take(5) as $material)
                                    <tr>
                                        <td><a href="{{ route('raw-materials.show', $material) }}">{{ $material->name }}</a></td>
                                        <td class="text-end"><span class="badge bg-danger">{{ number_format($material->quantity, 1) }}</span></td>
                                        <td class="text-end">{{ number_format($material->minimum_stock_level, 1) }} {{ $material->unit }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-3">{{ __('All materials well stocked') }}</td></tr>
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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sales vs Purchases Chart
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($months->map(fn($m) => \Carbon\Carbon::parse($m)->format('M Y'))->values()) !!},
            datasets: [{
                label: '{{ __("Sales") }}',
                data: {!! json_encode($salesTotals) !!},
                borderColor: 'rgb(40, 167, 69)',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                fill: true,
                tension: 0.4
            }, {
                label: '{{ __("Purchases") }}',
                data: {!! json_encode($purchaseTotals) !!},
                borderColor: 'rgb(220, 53, 69)',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Ks ' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush
