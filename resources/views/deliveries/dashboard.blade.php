@extends('layouts.app')

@section('title', __('Delivery Dashboard'))

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-tachometer-alt me-2"></i>{{ __('Delivery Dashboard') }}</h1>
            <p class="text-muted mb-0">{{ now()->format('l, F d, Y') }}</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('deliveries.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-list"></i> {{ __('All Deliveries') }}
            </a>
            <a href="{{ route('deliveries.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> {{ __('New Delivery') }}
            </a>
        </div>
    </div>

    {{-- Today's Stats --}}
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card bg-primary text-white h-100">
                <div class="card-body text-center">
                    <h2 class="mb-0">{{ $stats['today_total'] }}</h2>
                    <small>{{ __("Today's Deliveries") }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body text-center">
                    <h2 class="mb-0">{{ $stats['today_pending'] }}</h2>
                    <small>{{ __('Pending') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white h-100">
                <div class="card-body text-center">
                    <h2 class="mb-0">{{ $stats['today_in_progress'] }}</h2>
                    <small>{{ __('In Progress') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white h-100">
                <div class="card-body text-center">
                    <h2 class="mb-0">{{ $stats['today_delivered'] }}</h2>
                    <small>{{ __('Delivered') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-danger text-white h-100">
                <div class="card-body text-center">
                    <h2 class="mb-0">{{ $stats['today_failed'] }}</h2>
                    <small>{{ __('Failed') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-dark text-white h-100">
                <div class="card-body text-center">
                    @php
                        $successRate = $stats['today_total'] > 0 
                            ? round(($stats['today_delivered'] / $stats['today_total']) * 100) 
                            : 0;
                    @endphp
                    <h2 class="mb-0">{{ $successRate }}%</h2>
                    <small>{{ __('Success Rate') }}</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Today's Deliveries --}}
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-calendar-day me-2"></i>{{ __("Today's Deliveries") }}</h5>
                    <span class="badge bg-light text-primary">{{ $todayDeliveries->count() }}</span>
                </div>
                <div class="card-body p-0">
                    @if($todayDeliveries->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('Time') }}</th>
                                        <th>{{ __('Delivery') }}</th>
                                        <th>{{ __('Customer') }}</th>
                                        <th>{{ __('Driver') }}</th>
                                        <th class="text-center">{{ __('Status') }}</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($todayDeliveries as $delivery)
                                        <tr class="{{ $delivery->status === 'delivered' ? 'table-success' : ($delivery->status === 'failed' ? 'table-danger' : '') }}">
                                            <td>
                                                <strong>{{ $delivery->scheduled_time ?? '--:--' }}</strong>
                                            </td>
                                            <td>
                                                <a href="{{ route('deliveries.show', $delivery) }}" class="text-decoration-none">
                                                    {{ $delivery->delivery_number }}
                                                </a>
                                            </td>
                                            <td>
                                                <div>{{ $delivery->contact_name }}</div>
                                                <small class="text-muted">{{ Str::limit($delivery->delivery_address, 30) }}</small>
                                            </td>
                                            <td>
                                                @if($delivery->driver_name)
                                                    {{ $delivery->driver_name }}
                                                @else
                                                    <span class="text-danger">{{ __('Not assigned') }}</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ $delivery->status_badge_class }}">
                                                    {{ $delivery->status_label }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('deliveries.show', $delivery) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-calendar-check fa-3x mb-3"></i>
                            <p>{{ __('No deliveries scheduled for today') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- In Progress Deliveries --}}
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-shipping-fast me-2"></i>{{ __('In Progress') }}</h5>
                </div>
                <div class="card-body p-0">
                    @if($inProgressDeliveries->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($inProgressDeliveries as $delivery)
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">
                                                <a href="{{ route('deliveries.show', $delivery) }}" class="text-decoration-none">
                                                    {{ $delivery->delivery_number }}
                                                </a>
                                                <span class="badge bg-{{ $delivery->status_badge_class }} ms-2">
                                                    {{ $delivery->status_label }}
                                                </span>
                                            </h6>
                                            <small class="text-muted">
                                                {{ $delivery->contact_name }} - {{ $delivery->delivery_address }}
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <div>{{ $delivery->driver_name ?? __('No driver') }}</div>
                                            <small class="text-muted">{{ $delivery->scheduled_date?->format('M d') }}</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-check-circle fa-2x mb-2"></i>
                            <p class="mb-0">{{ __('No deliveries in progress') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Pending Deliveries --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i>{{ __('Pending Assignment') }}</h5>
                </div>
                <div class="card-body p-0">
                    @if($pendingDeliveries->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($pendingDeliveries as $delivery)
                                <a href="{{ route('deliveries.show', $delivery) }}" class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong>{{ $delivery->delivery_number }}</strong>
                                            <div class="small text-muted">{{ $delivery->contact_name }}</div>
                                        </div>
                                        <div class="text-end">
                                            <div class="small">{{ $delivery->scheduled_date?->format('M d') }}</div>
                                            <span class="badge bg-warning text-dark">{{ __('Pending') }}</span>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-check-circle fa-2x mb-2"></i>
                            <p class="mb-0">{{ __('All deliveries assigned') }}</p>
                        </div>
                    @endif
                </div>
                @if($pendingDeliveries->count() > 0)
                    <div class="card-footer text-center">
                        <a href="{{ route('deliveries.index', ['status' => 'pending']) }}" class="text-decoration-none">
                            {{ __('View all pending') }} <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
