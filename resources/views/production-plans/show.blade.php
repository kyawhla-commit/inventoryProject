@extends('layouts.app')

@section('title', 'Production Plan - ' . $productionPlan->name)

@section('content')
<div class="container-fluid">
    <!-- Alerts -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{!! session('success') !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{!! session('error') !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{!! session('warning') !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-0">Production Plan Details</h4>
                <div class="page-title-right">
                    <a href="{{ route('production-plans.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Plans
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Main Content -->
        <div class="col-xl-8">
            <!-- Plan Overview Card -->
            <div class="card">
                <div class="card-header bg-transparent border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{{ $productionPlan->name }}</h5>
                        <div class="d-flex align-items-center">
                            <span class="badge {{ $productionPlan->getStatusBadgeClass() }} fs-6">
                                {{ ucfirst(str_replace('_', ' ', $productionPlan->status)) }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Plan Information -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted small mb-1">PLAN NUMBER</label>
                                <p class="mb-0 fw-semibold">{{ $productionPlan->plan_number }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small mb-1">DESCRIPTION</label>
                                <p class="mb-0">{{ $productionPlan->description ?? 'No description provided' }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small mb-1">CREATED BY</label>
                                <p class="mb-0">{{ $productionPlan->createdBy->name ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted small mb-1">PLANNED START</label>
                                <p class="mb-0">
                                    <i class="fas fa-calendar-alt text-primary me-2"></i>
                                    {{ $productionPlan->planned_start_date->format('M d, Y') }}
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small mb-1">PLANNED END</label>
                                <p class="mb-0">
                                    <i class="fas fa-calendar-alt text-primary me-2"></i>
                                    {{ $productionPlan->planned_end_date->format('M d, Y') }}
                                </p>
                            </div>
                            @if($productionPlan->actual_start_date)
                            <div class="mb-3">
                                <label class="form-label text-muted small mb-1">ACTUAL START</label>
                                <p class="mb-0 text-success">
                                    <i class="fas fa-play-circle me-2"></i>
                                    {{ $productionPlan->actual_start_date->format('M d, Y') }}
                                </p>
                            </div>
                            @endif
                            @if($productionPlan->actual_end_date)
                            <div class="mb-3">
                                <label class="form-label text-muted small mb-1">ACTUAL END</label>
                                <p class="mb-0 text-success">
                                    <i class="fas fa-flag-checkered me-2"></i>
                                    {{ $productionPlan->actual_end_date->format('M d, Y') }}
                                </p>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card border-0 bg-primary bg-opacity-10">
                                <div class="card-body text-center">
                                    <h3 class="text-primary mb-1">{{ $productionPlan->productionPlanItems->count() }}</h3>
                                    <p class="text-muted mb-0 small">Total Items</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 bg-success bg-opacity-10">
                                <div class="card-body text-center">
                                    <h3 class="text-success mb-1">{{ $productionPlan->productionPlanItems->where('status', 'completed')->count() }}</h3>
                                    <p class="text-muted mb-0 small">Completed</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 bg-warning bg-opacity-10">
                                <div class="card-body text-center">
                                    <h3 class="text-warning mb-1">{{ $productionPlan->productionPlanItems->where('status', 'in_progress')->count() }}</h3>
                                    <p class="text-muted mb-0 small">In Progress</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 bg-info bg-opacity-10">
                                <div class="card-body text-center">
                                    <h3 class="text-info mb-1">{{ $productionPlan->productionPlanItems->where('status', 'pending')->count() }}</h3>
                                    <p class="text-muted mb-0 small">Pending</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cost Summary -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-transparent">
                                    <h6 class="mb-0">Cost Summary</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-md-4 border-end">
                                            <label class="text-muted small mb-1">Estimated Cost</label>
                                            <h4 class="text-primary">{{ number_format($productionPlan->total_estimated_cost ?? 0, 0) }} Ks</h4>
                                        </div>
                                        <div class="col-md-4 border-end">
                                            <label class="text-muted small mb-1">Actual Cost</label>
                                            <h4 class="{{ ($productionPlan->total_actual_cost ?? 0) <= ($productionPlan->total_estimated_cost ?? 0) ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($productionPlan->total_actual_cost ?? 0, 0) }} Ks
                                            </h4>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="text-muted small mb-1">Variance</label>
                                            @php
                                                $variance = ($productionPlan->total_actual_cost ?? 0) - ($productionPlan->total_estimated_cost ?? 0);
                                                $varianceClass = $variance <= 0 ? 'text-success' : 'text-danger';
                                                $varianceIcon = $variance <= 0 ? 'fa-arrow-down' : 'fa-arrow-up';
                                            @endphp
                                            <h4 class="{{ $varianceClass }}">
                                                <i class="fas {{ $varianceIcon }} me-1"></i>
                                                {{ number_format(abs($variance), 0) }} Ks
                                            </h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($productionPlan->notes)
                    <div class="mb-4">
                        <label class="form-label text-muted small mb-2">NOTES</label>
                        <div class="bg-light p-3 rounded border">
                            {!! nl2br(e($productionPlan->notes)) !!}
                        </div>
                    </div>
                    @endif

                    <!-- Production Items Table -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-bottom">
                            <h6 class="mb-0">Production Items</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Product</th>
                                            <th>Recipe</th>
                                            <th class="text-center">Planned Qty</th>
                                            <th class="text-center">Actual Qty</th>
                                            <th class="text-end">Est. Cost</th>
                                            <th class="text-end">Actual Cost</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Priority</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($productionPlan->productionPlanItems as $item)
                                        <tr>
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-shrink-0">
                                                        <i class="fas fa-cube text-primary"></i>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <h6 class="mb-0">{{ $item->product->name ?? 'N/A' }}</h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="text-muted">{{ $item->recipe->name ?? 'N/A' }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="fw-semibold">{{ $item->planned_quantity ?? 0 }}</span>
                                                <small class="text-muted"> {{ $item->unit }}</small>
                                            </td>
                                            <td class="text-center">
                                                <span class="fw-semibold {{ ($item->actual_quantity ?? 0) >= ($item->planned_quantity ?? 0) ? 'text-success' : 'text-warning' }}">
                                                    {{ $item->actual_quantity ?? 0 }}
                                                </span>
                                                <small class="text-muted"> {{ $item->unit }}</small>
                                            </td>
                                            <td class="text-end">
                                                <span class="text-muted">{{ number_format($item->estimated_material_cost ?? 0, 0) }} Ks</span>
                                            </td>
                                            <td class="text-end">
                                                <span class="{{ ($item->actual_material_cost ?? 0) <= ($item->estimated_material_cost ?? 0) ? 'text-success' : 'text-danger' }}">
                                                    {{ number_format($item->actual_material_cost ?? 0, 0) }} Ks
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                @php
                                                    $statusClass = match($item->status ?? 'pending') {
                                                        'completed' => 'bg-success',
                                                        'in_progress' => 'bg-warning',
                                                        'pending' => 'bg-secondary',
                                                        default => 'bg-secondary'
                                                    };
                                                @endphp
                                                <span class="badge {{ $statusClass }}">
                                                    {{ ucfirst($item->status ?? 'pending') }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-dark border">
                                                    {{ $item->priority ?? 1 }}
                                                </span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-4">
                        <div class="d-flex flex-wrap gap-2">
                            @if($productionPlan->status === 'draft')
                            <a href="{{ route('production-plans.edit', $productionPlan) }}" class="btn btn-warning">
                                <i class="fas fa-edit me-1"></i> Edit Plan
                            </a>
                            <form action="{{ route('production-plans.approve', $productionPlan) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check me-1"></i> Approve Plan
                                </button>
                            </form>
                            @endif

                            @if($productionPlan->status === 'approved')
                            <form action="{{ route('production-plans.start', $productionPlan) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-play me-1"></i> Start Production
                                </button>
                            </form>
                            @endif

                            @if($productionPlan->status === 'in_progress')
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#completeProductionModal">
                                <i class="fas fa-flag-checkered me-1"></i> Complete Production
                            </button>
                            @endif

                            <a href="{{ route('production-plans.material-requirements', $productionPlan) }}" class="btn btn-info">
                                <i class="fas fa-list me-1"></i> Material Requirements
                            </a>

                            <a href="{{ route('production-costs.show', $productionPlan) }}" class="btn btn-outline-info">
                                <i class="fas fa-calculator me-1"></i> View Costs
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Material Usage Records -->
            @if(isset($materialUsages) && $materialUsages->count() > 0)
            <div class="card mt-4">
                <div class="card-header bg-transparent border-bottom">
                    <h5 class="card-title mb-0">Material Usage Records</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Date</th>
                                    <th>Material</th>
                                    <th>Product</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-center">Type</th>
                                    <th class="text-end">Cost</th>
                                    <th>Recorded By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($materialUsages as $usage)
                                <tr>
                                    <td class="ps-4">
                                        <small class="text-muted">{{ $usage->usage_date->format('M d, Y') }}</small>
                                        <br>
                                        <small class="text-muted">{{ $usage->usage_date->format('H:i') }}</small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-box text-warning me-2"></i>
                                            {{ $usage->rawMaterial->name ?? 'N/A' }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ $usage->product->name ?? 'N/A' }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-semibold">{{ number_format($usage->quantity_used, 2) }}</span>
                                        <small class="text-muted"> {{ $usage->rawMaterial->unit ?? '' }}</small>
                                    </td>
                                    <td class="text-center">
                                        @if($usage->usage_type == 'production')
                                            <span class="badge bg-primary">Production</span>
                                        @elseif($usage->usage_type == 'waste')
                                            <span class="badge bg-warning">Waste</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $usage->usage_type }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-semibold">{{ number_format($usage->total_cost ?? 0, 0) }} Ks</span>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $usage->recordedBy->name ?? 'System' }}</small>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="col-xl-4">
            <!-- Material Requirements Summary -->
            <div class="card">
                <div class="card-header bg-transparent border-bottom">
                    <h5 class="card-title mb-0">Material Requirements</h5>
                </div>
                <div class="card-body">
                    @if(is_array($materialRequirements) && count($materialRequirements) > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless">
                                <thead>
                                    <tr>
                                        <th>Material</th>
                                        <th class="text-end">Required</th>
                                        <th class="text-end">Cost</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($materialRequirements as $requirement)
                                    @php
                                        $materialName = $requirement['raw_material_name'] ?? ($requirement['raw_material']->name ?? 'N/A');
                                        $totalRequired = $requirement['total_required'] ?? $requirement['quantity_required'] ?? 0;
                                        $estimatedCost = $requirement['estimated_cost'] ?? 0;
                                        $unit = $requirement['unit'] ?? '';
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-box text-primary me-2"></i>
                                                <small class="fw-semibold">{{ $materialName }}</small>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <small class="fw-semibold">{{ number_format($totalRequired, 2) }}</small>
                                            <br>
                                            <small class="text-muted">{{ $unit }}</small>
                                        </td>
                                        <td class="text-end">
                                            <small class="text-success">{{ number_format($estimatedCost, 0) }} Ks</small>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="border-top">
                                        <th class="pt-3">Total</th>
                                        <th colspan="2" class="text-end pt-3 text-primary">
                                            {{ number_format(array_sum(array_column($materialRequirements, 'estimated_cost')), 0) }} Ks
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-box-open fa-2x text-muted mb-3"></i>
                            <p class="text-muted mb-0">No material requirements calculated</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Stock Availability -->
            <div class="card mt-4">
                <div class="card-header bg-transparent border-bottom">
                    <h5 class="card-title mb-0">Stock Availability</h5>
                </div>
                <div class="card-body">
                    @if(is_array($materialRequirements) && count($materialRequirements) > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless">
                                <thead>
                                    <tr>
                                        <th>Material</th>
                                        <th class="text-end">Required</th>
                                        <th class="text-end">Available</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($materialRequirements as $requirement)
                                    @php
                                        $materialName = $requirement['raw_material_name'] ?? ($requirement['raw_material']->name ?? 'N/A');
                                        $totalRequired = $requirement['total_required'] ?? $requirement['quantity_required'] ?? 0;
                                        $available = $requirement['available'] ?? (isset($requirement['raw_material']) ? $requirement['raw_material']->quantity : 0);
                                        $isSufficient = $requirement['is_sufficient'] ?? ($available >= $totalRequired);
                                    @endphp
                                    <tr>
                                        <td>
                                            <small class="fw-semibold">{{ $materialName }}</small>
                                        </td>
                                        <td class="text-end">
                                            <small>{{ number_format($totalRequired, 2) }}</small>
                                        </td>
                                        <td class="text-end">
                                            <small class="{{ $isSufficient ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($available, 2) }}
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            @if($isSufficient)
                                                <i class="fas fa-check-circle text-success" title="Sufficient"></i>
                                            @else
                                                <i class="fas fa-exclamation-triangle text-danger" title="Insufficient"></i>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-clipboard-check fa-2x text-muted mb-3"></i>
                            <p class="text-muted mb-0">No materials to check</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Material Management Actions -->
            <div class="card mt-4">
                <div class="card-header bg-transparent border-bottom">
                    <h5 class="card-title mb-0">Material Management</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('raw-material-usages.bulk-create') }}" class="btn btn-primary">
                            <i class="fas fa-clipboard-list me-2"></i> Record Material Usage
                        </a>
                        <a href="{{ route('stock-management.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-warehouse me-2"></i> Stock Management
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stock Impact Card -->
            <div class="card mt-4">
                <div class="card-header bg-transparent border-bottom">
                    <h5 class="card-title mb-0">Stock Impact</h5>
                </div>
                <div class="card-body">
                    @if($productionPlan->status === 'completed')
                        <div class="alert alert-success mb-3">
                            <i class="fas fa-check-circle me-2"></i> Stock updated upon completion
                        </div>
                    @endif
                    
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-end">Change</th>
                                    <th class="text-end">Current</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($productionPlan->productionPlanItems as $item)
                                <tr>
                                    <td>
                                        <small class="fw-semibold">{{ $item->product->name ?? 'N/A' }}</small>
                                    </td>
                                    <td class="text-end">
                                        <small class="text-success">+{{ number_format($item->actual_quantity ?? $item->planned_quantity ?? 0, 2) }}</small>
                                    </td>
                                    <td class="text-end">
                                        <small class="fw-semibold">{{ number_format($item->product->quantity ?? 0, 2) }}</small>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    @if($productionPlan->status !== 'completed')
                        <div class="alert alert-info mt-3 mb-0">
                            <i class="fas fa-info-circle me-2"></i> Stock will be updated when production is completed
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Complete Production Modal -->
@if($productionPlan->status === 'in_progress')
<div class="modal fade" id="completeProductionModal" tabindex="-1" aria-labelledby="completeProductionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="completeProductionModalLabel">
                    <i class="fas fa-flag-checkered me-2"></i>Complete Production
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>This action will:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Deduct raw materials from inventory based on product recipes</li>
                        <li>Add finished products to stock</li>
                        <li>Record all material usage and stock movements</li>
                        <li>Calculate actual production costs</li>
                    </ul>
                </div>

                <h6 class="mb-3">Products to be Added:</h6>
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th class="text-end">Quantity</th>
                            <th class="text-end">Current Stock</th>
                            <th class="text-end">After Production</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($productionPlan->productionPlanItems as $item)
                        <tr>
                            <td>{{ $item->product->name ?? 'N/A' }}</td>
                            <td class="text-end text-success">+{{ number_format($item->planned_quantity ?? 0, 2) }}</td>
                            <td class="text-end">{{ number_format($item->product->quantity ?? 0, 2) }}</td>
                            <td class="text-end fw-bold">{{ number_format(($item->product->quantity ?? 0) + ($item->planned_quantity ?? 0), 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                @if(is_array($materialRequirements) && count($materialRequirements) > 0)
                <h6 class="mb-3 mt-4">Materials to be Deducted:</h6>
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Material</th>
                            <th class="text-end">Required</th>
                            <th class="text-end">Available</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $hasShortage = false; @endphp
                        @foreach($materialRequirements as $req)
                        @php
                            $materialName = $req['raw_material_name'] ?? ($req['raw_material']->name ?? 'N/A');
                            $totalRequired = $req['total_required'] ?? $req['quantity_required'] ?? 0;
                            $available = $req['available'] ?? (isset($req['raw_material']) ? $req['raw_material']->quantity : 0);
                            $isSufficient = $available >= $totalRequired;
                            if (!$isSufficient) $hasShortage = true;
                        @endphp
                        <tr class="{{ !$isSufficient ? 'table-danger' : '' }}">
                            <td>{{ $materialName }}</td>
                            <td class="text-end text-danger">-{{ number_format($totalRequired, 2) }} {{ $req['unit'] ?? '' }}</td>
                            <td class="text-end">{{ number_format($available, 2) }}</td>
                            <td class="text-center">
                                @if($isSufficient)
                                    <i class="fas fa-check-circle text-success"></i>
                                @else
                                    <i class="fas fa-times-circle text-danger"></i>
                                    <small class="text-danger">Shortage: {{ number_format($totalRequired - $available, 2) }}</small>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                @if($hasShortage)
                <div class="alert alert-danger mt-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> Some materials have insufficient stock. Production cannot be completed until stock is replenished.
                </div>
                @endif
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancel
                </button>
                <form action="{{ route('production-plans.complete', $productionPlan) }}" method="POST" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-success" @if(isset($hasShortage) && $hasShortage) disabled @endif>
                        <i class="fas fa-check me-1"></i> Confirm & Complete Production
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

<style>
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.table th {
    border-top: none;
    font-weight: 600;
    font-size: 0.875rem;
}

.badge {
    font-size: 0.75rem;
    padding: 0.35em 0.65em;
}

.page-title-box {
    padding: 0;
    margin-bottom: 1.5rem;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.02);
}
</style>
@endsection
