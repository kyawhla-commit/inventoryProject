@extends('layouts.app')

@section('title', __('Deliveries'))

@section('content')
<div class="container-fluid">
    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-truck me-2"></i>{{ __('Deliveries') }}</h1>
            <p class="text-muted mb-0">{{ __('Manage order deliveries and track status') }}</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('deliveries.dashboard') }}" class="btn btn-info">
                <i class="fas fa-tachometer-alt me-1"></i> {{ __('Dashboard') }}
            </a>
            <a href="{{ route('deliveries.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> {{ __('New Delivery') }}
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card bg-primary text-white">
                <div class="card-body py-3 text-center">
                    <h4 class="mb-0">{{ $stats['total'] }}</h4>
                    <small>{{ __('Total') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-dark">
                <div class="card-body py-3 text-center">
                    <h4 class="mb-0">{{ $stats['pending'] }}</h4>
                    <small>{{ __('Pending') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white">
                <div class="card-body py-3 text-center">
                    <h4 class="mb-0">{{ $stats['in_progress'] }}</h4>
                    <small>{{ __('In Progress') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-secondary text-white">
                <div class="card-body py-3 text-center">
                    <h4 class="mb-0">{{ $stats['today'] }}</h4>
                    <small>{{ __('Today') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body py-3 text-center">
                    <h4 class="mb-0">{{ $stats['delivered_today'] }}</h4>
                    <small>{{ __('Delivered Today') }}</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control" 
                               placeholder="{{ __('Search...') }}" value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">{{ __('All Status') }}</option>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="start_date" class="form-control" 
                           value="{{ request('start_date') }}" placeholder="{{ __('From') }}">
                </div>
                <div class="col-md-2">
                    <input type="date" name="end_date" class="form-control" 
                           value="{{ request('end_date') }}" placeholder="{{ __('To') }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> {{ __('Filter') }}
                    </button>
                    <a href="{{ route('deliveries.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> {{ __('Clear') }}
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Deliveries Table --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Delivery #') }}</th>
                            <th>{{ __('Order') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Driver') }}</th>
                            <th>{{ __('Scheduled') }}</th>
                            <th class="text-center">{{ __('Status') }}</th>
                            <th class="text-end">{{ __('Fee') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($deliveries as $delivery)
                            <tr>
                                <td>
                                    <a href="{{ route('deliveries.show', $delivery) }}" class="fw-bold text-decoration-none">
                                        {{ $delivery->delivery_number }}
                                    </a>
                                </td>
                                <td>
                                    <a href="{{ route('orders.show', $delivery->order) }}" class="text-decoration-none">
                                        #{{ $delivery->order_id }}
                                    </a>
                                </td>
                                <td>
                                    <div>{{ $delivery->customer->name ?? '-' }}</div>
                                    <small class="text-muted">{{ $delivery->contact_phone }}</small>
                                </td>
                                <td>
                                    @if($delivery->driver_name)
                                        <div>{{ $delivery->driver_name }}</div>
                                        <small class="text-muted">{{ $delivery->vehicle_number }}</small>
                                    @else
                                        <span class="text-muted">{{ __('Not assigned') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $delivery->scheduled_date?->format('M d, Y') }}</div>
                                    @if($delivery->scheduled_time)
                                        <small class="text-muted">{{ $delivery->scheduled_time }}</small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $delivery->status_badge_class }}">
                                        {{ $delivery->status_label }}
                                    </span>
                                </td>
                                <td class="text-end">Ks {{ number_format($delivery->delivery_fee, 0) }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('deliveries.show', $delivery) }}" class="btn btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($delivery->canUpdate())
                                            <a href="{{ route('deliveries.edit', $delivery) }}" class="btn btn-outline-secondary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-truck fa-2x mb-2 d-block"></i>
                                    {{ __('No deliveries found') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($deliveries->hasPages())
            <div class="card-footer">
                {{ $deliveries->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
