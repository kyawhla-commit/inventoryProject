@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-boxes"></i> Production Material Usage</h2>
        <div>
            <a href="{{ route('production-material-usage.efficiency') }}" class="btn btn-info">
                <i class="fas fa-chart-bar"></i> Efficiency Analysis
            </a>
            <a href="{{ route('production-material-usage.stock-impact') }}" class="btn btn-warning">
                <i class="fas fa-warehouse"></i> Stock Impact
            </a>
            <a href="{{ route('production-material-usage.waste-analysis') }}" class="btn btn-danger">
                <i class="fas fa-trash-alt"></i> Waste Analysis
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
                    <a href="{{ route('production-material-usage.index') }}" class="btn btn-outline-secondary">
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
                            <h6 class="mb-0">Total Records</h6>
                            <h3 class="mb-0">{{ number_format($summaryStats->total_records) }}</h3>
                            <small>Usage entries</small>
                        </div>
                        <div>
                            <i class="fas fa-list-ul fa-3x opacity-50"></i>
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
                            <h6 class="mb-0">Total Quantity Used</h6>
                            <h3 class="mb-0">{{ number_format($summaryStats->total_quantity, 2) }}</h3>
                            <small>Units</small>
                        </div>
                        <div>
                            <i class="fas fa-cubes fa-3x opacity-50"></i>
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
                            <h6 class="mb-0">Total Cost</h6>
                            <h3 class="mb-0">${{ number_format($summaryStats->total_cost, 2) }}</h3>
                            <small>Material costs</small>
                        </div>
                        <div>
                            <i class="fas fa-dollar-sign fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Waste Cost</h6>
                            <h3 class="mb-0">${{ number_format($wasteStats->waste_cost ?? 0, 2) }}</h3>
                            <small>{{ $wasteStats->waste_records ?? 0 }} records</small>
                        </div>
                        <div>
                            <i class="fas fa-exclamation-triangle fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Low Stock Alert -->
    @if($lowStockMaterials->count() > 0)
    <div class="alert alert-danger">
        <h5><i class="fas fa-exclamation-circle"></i> Critical Stock Alert</h5>
        <p class="mb-0">
            <strong>{{ $lowStockMaterials->count() }}</strong> raw material(s) are at or below minimum stock level after production usage.
            <a href="{{ route('production-material-usage.stock-impact') }}" class="alert-link">View Stock Impact Report</a>
        </p>
    </div>
    @endif

    <!-- Top Used Materials -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Top Used Materials (By Cost)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Material</th>
                                    <th class="text-end">Total Used</th>
                                    <th class="text-end">Usage Count</th>
                                    <th class="text-end">Total Cost</th>
                                    <th class="text-end">Current Stock</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topMaterials as $item)
                                    @php
                                        $material = $item->rawMaterial;
                                        $stockStatus = 'normal';
                                        if ($material->quantity <= 0) $stockStatus = 'out';
                                        elseif ($material->quantity <= $material->minimum_stock_level * 0.5) $stockStatus = 'critical';
                                        elseif ($material->quantity <= $material->minimum_stock_level) $stockStatus = 'low';
                                    @endphp
                                    <tr>
                                        <td>
                                            <strong>{{ $material->name }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $material->unit }}</small>
                                        </td>
                                        <td class="text-end">{{ number_format($item->total_used, 2) }} {{ $material->unit }}</td>
                                        <td class="text-end">{{ $item->usage_count }}</td>
                                        <td class="text-end">${{ number_format($item->total_cost, 2) }}</td>
                                        <td class="text-end">{{ number_format($material->quantity, 2) }}</td>
                                        <td>
                                            @switch($stockStatus)
                                                @case('out')
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
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No material usage data available</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Material Usage -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history"></i> Recent Material Usage</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Material</th>
                                    <th>Product</th>
                                    <th class="text-end">Quantity Used</th>
                                    <th class="text-end">Cost</th>
                                    <th>Recorded By</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($usages as $usage)
                                    <tr>
                                        <td>{{ $usage->usage_date->format('M d, Y') }}</td>
                                        <td>
                                            <strong>{{ $usage->rawMaterial->name }}</strong>
                                            @if($usage->batch_number)
                                                <br><small class="text-muted">Batch: {{ $usage->batch_number }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $usage->product->name ?? 'N/A' }}</td>
                                        <td class="text-end">
                                            {{ number_format($usage->quantity_used, 2) }} {{ $usage->rawMaterial->unit }}
                                        </td>
                                        <td class="text-end">${{ number_format($usage->total_cost, 2) }}</td>
                                        <td>{{ $usage->recordedBy->name ?? 'System' }}</td>
                                        <td>
                                            @if($usage->notes)
                                                <small>{{ Str::limit($usage->notes, 50) }}</small>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No usage records found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $usages->links() }}
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
</style>
@endpush
