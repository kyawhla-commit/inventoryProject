@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Production Plans</h4>

                    <a href="{{ route('production-plans.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Production Plan
                    </a>
                    <div>
                        <a href="{{ route('production-plans.dashboard') }}" class="btn btn-info">
                            <i class="fas fa-chart-line"></i> Dashboard
                        </a>
                        <a href="{{ route('production-plans.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> New Plan
                        </a>
                        <a href="{{ route('raw-material-usages.index') }}" class="btn btn-success">
                            <i class="fas fa-boxes"></i> Material Usage
                        </a>
                        <a href="{{ route('raw-material-usages.efficiency') }}" class="btn btn-info">
                            <i class="fas fa-chart-line"></i> Material Efficiency
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <select name="status" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="date" name="start_date" class="form-control" 
                                       value="{{ request('start_date') }}" placeholder="Start Date">
                            </div>
                            <div class="col-md-3">
                                <input type="date" name="end_date" class="form-control" 
                                       value="{{ request('end_date') }}" placeholder="End Date">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-secondary">Filter</button>
                                <a href="{{ route('production-plans.index') }}" class="btn btn-outline-secondary">Clear</a>
                            </div>
                        </div>
                    </form>

                    <!-- Production Plans Table -->
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Plan Number</th>
                                    <th>Name</th>
                                    <th>Planned Dates</th>
                                    <th>Status</th>
                                    <th>Items</th>
                                    <th>Est. Cost</th>
                                    <th>Actual Cost</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                    <th>Material Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($plans as $plan)
                                    <tr>
                                        <td>{{ $plan->plan_number }}</td>
                                        <td>{{ $plan->name }}</td>
                                        <td>
                                            {{ $plan->planned_start_date->format('M d') }} - 
                                            {{ $plan->planned_end_date->format('M d, Y') }}
                                        </td>
                                        <td>
                                            <span class="badge {{ $plan->getStatusBadgeClass() }}">
                                                {{ ucfirst(str_replace('_', ' ', $plan->status)) }}
                                            </span>
                                        </td>
                                        <td>{{ $plan->productionPlanItems->count() }}</td>
                                        <td>${{ number_format($plan->total_estimated_cost, 2) }}</td>
                                        <td>${{ number_format($plan->total_actual_cost, 2) }}</td>
                                        <td>{{ $plan->createdBy->name }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('production-plans.show', $plan) }}" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @if($plan->status === 'draft')
                                                    <a href="{{ route('production-plans.edit', $plan) }}" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                @endif
                                                @if($plan->status === 'draft')
                                                    <form action="{{ route('production-plans.approve', $plan) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn-sm btn-success" title="Approve">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                @if($plan->status === 'approved')
                                                    <form action="{{ route('production-plans.start', $plan) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn-sm btn-primary" title="Start">
                                                            <i class="fas fa-play"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                @if($plan->status === 'in_progress')
                                                    <form action="{{ route('production-plans.complete', $plan) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn-sm btn-success" title="Complete">
                                                            <i class="fas fa-flag-checkered"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('raw-material-usages.create', ['batch_number' => $plan->plan_number]) }}" 
                                                   class="btn btn-sm btn-primary" title="Record Material Usage">
                                                    <i class="fas fa-clipboard-list"></i>
                                                </a>
                                                <a href="{{ route('raw-material-usages.bulk-create', ['batch_number' => $plan->plan_number]) }}" 
                                                   class="btn btn-sm btn-warning" title="Bulk Record Materials">
                                                    <i class="fas fa-layer-group"></i>
                                                </a>
                                                <a href="{{ route('production-plans.material-requirements', $plan) }}" 
                                                   class="btn btn-sm btn-info" title="View Material Requirements">
                                                    <i class="fas fa-list-check"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center">No production plans found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $plans->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection