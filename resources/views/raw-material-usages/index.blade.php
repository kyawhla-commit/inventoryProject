@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>{{ __('Raw Material Usage Tracking') }}</h2>
                <div>
<<<<<<< Updated upstream
=======
                <a href="{{ route('production-plans.dashboard') }}" class="btn btn-info">
                            <i class="fas fa-chart-line"></i> Dashboard
                        </a>
>>>>>>> Stashed changes
                    <a href="{{ route('raw-material-usages.bulk-create-test') }}" class="btn btn-info me-2">
                        <i class="fas fa-layer-group"></i> {{ __('Bulk Record Usage') }}
                    </a>
                    <a href="{{ route('raw-material-usages.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> {{ __('Record Usage') }}
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">{{ __('Total Usage Records') }}</h6>
                                    <h4>{{ $usages->total() }}</h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-clipboard-list fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">{{ __('Today\'s Usage') }}</h6>
                                    <h4>{{ $usages->where('usage_date', today())->count() }}</h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-calendar-day fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">{{ __('This Week') }}</h6>
                                    <h4>{{ $usages->where('usage_date', '>=', now()->startOfWeek())->count() }}</h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-calendar-week fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">{{ __('This Month') }}</h6>
                                    <h4>{{ $usages->where('usage_date', '>=', now()->startOfMonth())->count() }}</h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-calendar-alt fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">{{ __('Filter Usage Records') }}</h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('raw-material-usages.index') }}">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="raw_material_id" class="form-label">{{ __('Raw Material') }}</label>
                                <select class="form-control" id="raw_material_id" name="raw_material_id">
                                    <option value="">{{ __('All Raw Materials') }}</option>
                                    @foreach($rawMaterials as $rawMaterial)
                                        <option value="{{ $rawMaterial->id }}" {{ request('raw_material_id') == $rawMaterial->id ? 'selected' : '' }}>
                                            {{ $rawMaterial->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="usage_type" class="form-label">{{ __('Usage Type') }}</label>
                                <select class="form-control" id="usage_type" name="usage_type">
                                    <option value="">{{ __('All Types') }}</option>
                                    @foreach($usageTypes as $key => $label)
                                        <option value="{{ $key }}" {{ request('usage_type') == $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="product_id" class="form-label">{{ __('Product') }}</label>
                                <select class="form-control" id="product_id" name="product_id">
                                    <option value="">{{ __('All Products') }}</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                                            {{ $product->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="start_date" class="form-label">{{ __('Start Date') }}</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="{{ request('start_date') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="end_date" class="form-label">{{ __('End Date') }}</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="{{ request('end_date') }}">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-filter"></i> {{ __('Filter') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <a href="{{ route('raw-material-usages.index') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-times"></i> {{ __('Clear Filters') }}
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Usage Records Table -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">{{ __('Usage Records') }}</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Raw Material') }}</th>
                                    <th>{{ __('Usage Type') }}</th>
                                    <th>{{ __('Quantity Used') }}</th>
                                    <th>{{ __('Unit Cost') }}</th>
                                    <th>{{ __('Total Cost') }}</th>
                                    <th>{{ __('Product/Order') }}</th>
                                    <th>{{ __('Batch #') }}</th>
                                    <th>{{ __('Recorded By') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($usages as $usage)
                                    <tr>
                                        <td>
                                            <span class="fw-bold">{{ $usage->usage_date ? $usage->usage_date->format('M d, Y') : 'N/A' }}</span>
                                            <br><small class="text-muted">{{ $usage->usage_date ? $usage->usage_date->format('l') : '' }}</small>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="me-2">
                                                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                        <i class="fas fa-cube text-white fa-sm"></i>
                                                    </div>
                                                </div>
                                                <div>
                                                    <strong>{{ $usage->rawMaterial->name }}</strong>
                                                    <br><small class="text-muted">{{ $usage->rawMaterial->unit }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $usage->getUsageTypeBadgeClass() }}">
                                                {{ $usageTypes[$usage->usage_type] ?? $usage->usage_type }}
                                            </span>
                                        </td>
                                        <td>
                                            <strong>{{ number_format($usage->quantity_used, 2) }}</strong>
                                            <small class="text-muted">{{ $usage->rawMaterial->unit }}</small>
                                        </td>
                                        <td>${{ number_format($usage->cost_per_unit, 2) }}</td>
                                        <td>
                                            <strong class="text-success">${{ number_format($usage->total_cost, 2) }}</strong>
                                        </td>
                                        <td>
                                            @if($usage->product)
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-box text-info me-1"></i>
                                                    <span>{{ $usage->product->name }}</span>
                                                </div>
                                            @elseif($usage->order)
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-shopping-cart text-warning me-1"></i>
                                                    <span>Order #{{ $usage->order->id }}</span>
                                                    @if($usage->order->customer)
                                                        <br><small class="text-muted">{{ $usage->order->customer->name }}</small>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-muted">{{ __('General Usage') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($usage->batch_number)
                                                <span class="badge bg-secondary">{{ $usage->batch_number }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="me-2">
                                                    <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                        <i class="fas fa-user text-white fa-xs"></i>
                                                    </div>
                                                </div>
                                                <div>
                                                    <small>{{ $usage->recordedBy->name }}</small>
                                                    <br><small class="text-muted">{{ $usage->created_at->format('H:i') }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('raw-material-usages.show', $usage) }}" class="btn btn-sm btn-info" title="{{ __('View Details') }}">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('raw-material-usages.edit', $usage) }}" class="btn btn-sm btn-warning" title="{{ __('Edit') }}">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="POST" action="{{ route('raw-material-usages.destroy', $usage) }}" style="display: inline;" onsubmit="return confirm('{{ __('Are you sure? This will restore the raw material stock.') }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="{{ __('Delete') }}">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-clipboard-list fa-3x mb-3"></i>
                                                <h5>{{ __('No Usage Records Found') }}</h5>
                                                <p>{{ __('No raw material usage records match your current filters.') }}</p>
                                                <a href="{{ route('raw-material-usages.create') }}" class="btn btn-primary">
                                                    <i class="fas fa-plus"></i> {{ __('Record First Usage') }}
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($usages->hasPages())
                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted">
                                {{ __('Showing') }} {{ $usages->firstItem() }} {{ __('to') }} {{ $usages->lastItem() }} {{ __('of') }} {{ $usages->total() }} {{ __('results') }}
                            </div>
                            <div>
                                {{ $usages->links() }}
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Quick Stats -->
            @if($usages->count() > 0)
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">{{ __('Usage by Type') }}</h6>
                            </div>
                            <div class="card-body">
                                @php
                                    $usageByType = $usages->groupBy('usage_type');
                                @endphp
                                @foreach($usageByType as $type => $typeUsages)
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <span class="badge bg-{{ $typeUsages->first()->getUsageTypeBadgeClass() }} me-2">
                                                {{ $usageTypes[$type] ?? $type }}
                                            </span>
                                        </div>
                                        <div>
                                            <strong>{{ $typeUsages->count() }}</strong> {{ __('records') }}
                                            <small class="text-muted">
                                                (${{ number_format($typeUsages->sum('total_cost'), 2) }})
                                            </small>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">{{ __('Top Raw Materials') }}</h6>
                            </div>
                            <div class="card-body">
                                @php
                                    $topMaterials = $usages->groupBy('raw_material_id')->sortByDesc(function($group) {
                                        return $group->sum('total_cost');
                                    })->take(5);
                                @endphp
                                @foreach($topMaterials as $materialId => $materialUsages)
                                    @php $material = $materialUsages->first()->rawMaterial; @endphp
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <strong>{{ $material->name }}</strong>
                                            <br><small class="text-muted">{{ $materialUsages->sum('quantity_used') }} {{ $material->unit }}</small>
                                        </div>
                                        <div class="text-end">
                                            <strong class="text-success">${{ number_format($materialUsages->sum('total_cost'), 2) }}</strong>
                                            <br><small class="text-muted">{{ $materialUsages->count() }} {{ __('uses') }}</small>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
.table th {
    border-top: none;
    font-weight: 600;
    font-size: 0.875rem;
}

.table td {
    vertical-align: middle;
}

.card-header h6 {
    color: #495057;
    font-weight: 600;
}

.btn-group .btn {
    border-radius: 0.25rem;
    margin-right: 2px;
}

.badge {
    font-size: 0.75rem;
}

.table-responsive {
    border-radius: 0.375rem;
}

.table-dark th {
    background-color: #343a40;
    border-color: #454d55;
}
</style>
@endsection