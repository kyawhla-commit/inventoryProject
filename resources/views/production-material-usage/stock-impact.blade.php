@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-warehouse"></i> Stock Impact from Production</h2>
        <div>
            <a href="{{ route('production-material-usage.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
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
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="{{ route('production-material-usage.stock-impact') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6>Materials Tracked</h6>
                    <h3>{{ $stockImpactData->count() }}</h3>
                    <small>Used in production</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6>Reorder Needed</h6>
                    <h3>{{ $stockImpactData->where('reorder_needed', true)->count() }}</h3>
                    <small>Below minimum stock</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6>Total Used</h6>
                    <h3>{{ number_format($stockImpactData->sum('used_in_period'), 2) }}</h3>
                    <small>Units in period</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Total Waste</h6>
                    <h3>{{ number_format($stockImpactData->sum('waste_in_period'), 2) }}</h3>
                    <small>Units wasted</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Impact Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-chart-line"></i> Material Stock Impact Analysis</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Material</th>
                            <th class="text-end">Current Stock</th>
                            <th class="text-end">Min. Stock</th>
                            <th class="text-end">Used</th>
                            <th class="text-end">Waste</th>
                            <th class="text-end">Total Consumed</th>
                            <th class="text-end">Avg Daily Use</th>
                            <th class="text-end">Days Until Stockout</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stockImpactData as $data)
                            @php
                                $statusClass = '';
                                $statusText = '';
                                switch($data['stock_status']) {
                                    case 'out_of_stock':
                                        $statusClass = 'bg-danger';
                                        $statusText = 'Out of Stock';
                                        break;
                                    case 'critical':
                                        $statusClass = 'bg-danger';
                                        $statusText = 'Critical';
                                        break;
                                    case 'low':
                                        $statusClass = 'bg-warning';
                                        $statusText = 'Low Stock';
                                        break;
                                    default:
                                        $statusClass = 'bg-success';
                                        $statusText = 'Normal';
                                }
                            @endphp
                            <tr class="{{ $data['reorder_needed'] ? 'table-danger' : '' }}">
                                <td>
                                    <strong>{{ $data['material']->name }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $data['material']->unit }}</small>
                                    @if($data['material']->supplier)
                                        <br><small class="text-muted">Supplier: {{ $data['material']->supplier->name }}</small>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <strong>{{ number_format($data['current_stock'], 2) }}</strong>
                                </td>
                                <td class="text-end">{{ number_format($data['minimum_stock'], 2) }}</td>
                                <td class="text-end">{{ number_format($data['used_in_period'], 2) }}</td>
                                <td class="text-end">
                                    @if($data['waste_in_period'] > 0)
                                        <span class="text-danger">{{ number_format($data['waste_in_period'], 2) }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <strong>{{ number_format($data['total_consumed'], 2) }}</strong>
                                </td>
                                <td class="text-end">{{ number_format($data['avg_daily_usage'], 2) }}</td>
                                <td class="text-end">
                                    @if($data['days_until_stockout'])
                                        @if($data['days_until_stockout'] < 7)
                                            <span class="badge bg-danger">{{ number_format($data['days_until_stockout'], 1) }} days</span>
                                        @elseif($data['days_until_stockout'] < 30)
                                            <span class="badge bg-warning">{{ number_format($data['days_until_stockout'], 1) }} days</span>
                                        @else
                                            <span class="badge bg-success">{{ number_format($data['days_until_stockout'], 1) }} days</span>
                                        @endif
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                                </td>
                                <td>
                                    @if($data['reorder_needed'])
                                        <a href="{{ route('raw-materials.show', $data['material']) }}" 
                                           class="btn btn-sm btn-danger">
                                            <i class="fas fa-shopping-cart"></i> Reorder
                                        </a>
                                    @else
                                        <a href="{{ route('raw-materials.show', $data['material']) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted">No stock impact data available</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Legend -->
    <div class="card mt-4">
        <div class="card-body">
            <h6>Legend:</h6>
            <div class="row">
                <div class="col-md-3">
                    <span class="badge bg-success">Normal</span> - Stock above minimum
                </div>
                <div class="col-md-3">
                    <span class="badge bg-warning">Low Stock</span> - Stock at or below minimum
                </div>
                <div class="col-md-3">
                    <span class="badge bg-danger">Critical</span> - Stock at or below 50% of minimum
                </div>
                <div class="col-md-3">
                    <span class="badge bg-danger">Out of Stock</span> - No stock available
                </div>
            </div>
            <hr>
            <p class="mb-0">
                <strong>Days Until Stockout</strong> is calculated based on average daily usage over the last 30 days.
                <br>
                <span class="text-muted">Red highlight indicates materials that need reordering.</span>
            </p>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .table-danger {
        background-color: #f8d7da !important;
    }
</style>
@endpush
