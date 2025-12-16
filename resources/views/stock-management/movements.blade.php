@extends('layouts.app')

@section('title', __('Stock Movements'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">{{ __('Stock Movements') }}</h1>
            <p class="text-muted mb-0">{{ __('Track all stock transactions') }}</p>
        </div>
        <a href="{{ route('stock-management.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> {{ __('Back to Dashboard') }}
        </a>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('stock-management.movements') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">{{ __('Movement Type') }}</label>
                    <select name="type" class="form-select">
                        <option value="">{{ __('All Types') }}</option>
                        @foreach($movementTypes as $key => $label)
                            <option value="{{ $key }}" {{ request('type') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('Item Type') }}</label>
                    <select name="item_type" class="form-select">
                        <option value="">{{ __('All Items') }}</option>
                        <option value="raw_material" {{ request('item_type') == 'raw_material' ? 'selected' : '' }}>{{ __('Raw Materials') }}</option>
                        <option value="product" {{ request('item_type') == 'product' ? 'selected' : '' }}>{{ __('Products') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('Start Date') }}</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('End Date') }}</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter me-1"></i> {{ __('Filter') }}
                    </button>
                    <a href="{{ route('stock-management.movements') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i> {{ __('Clear') }}
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Movements Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Date/Time') }}</th>
                            <th>{{ __('Item') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th class="text-end">{{ __('Quantity') }}</th>
                            <th class="text-end">{{ __('Unit Price') }}</th>
                            <th>{{ __('Reference') }}</th>
                            <th>{{ __('Notes') }}</th>
                            <th>{{ __('By') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movements as $movement)
                            <tr>
                                <td>
                                    <div>{{ $movement->created_at->format('M d, Y') }}</div>
                                    <small class="text-muted">{{ $movement->created_at->format('H:i:s') }}</small>
                                </td>
                                <td>
                                    @if($movement->product)
                                        <span class="badge bg-info me-1">{{ __('Product') }}</span>
                                        <a href="{{ route('products.show', $movement->product) }}">
                                            {{ $movement->product->name }}
                                        </a>
                                    @elseif($movement->rawMaterial)
                                        <span class="badge bg-secondary me-1">{{ __('Material') }}</span>
                                        <a href="{{ route('raw-materials.show', $movement->rawMaterial) }}">
                                            {{ $movement->rawMaterial->name }}
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $typeColors = [
                                            'purchase' => 'success',
                                            'production' => 'primary',
                                            'usage' => 'warning',
                                            'sale' => 'info',
                                            'adjustment' => 'secondary',
                                            'waste' => 'danger',
                                            'return' => 'success',
                                            'damage' => 'danger',
                                            'initial' => 'dark',
                                        ];
                                    @endphp
                                    <span class="badge bg-{{ $typeColors[$movement->type] ?? 'secondary' }}">
                                        {{ $movementTypes[$movement->type] ?? ucfirst($movement->type) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <span class="fw-bold {{ $movement->quantity >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $movement->quantity >= 0 ? '+' : '' }}{{ number_format($movement->quantity, 2) }}
                                    </span>
                                    <small class="text-muted">
                                        {{ $movement->product?->unit ?? $movement->rawMaterial?->unit ?? '' }}
                                    </small>
                                </td>
                                <td class="text-end">
                                    @if($movement->unit_price)
                                        {{ number_format($movement->unit_price, 0) }} Ks
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($movement->reference_type && $movement->reference_id)
                                        @php
                                            $refType = class_basename($movement->reference_type);
                                        @endphp
                                        <span class="badge bg-outline-secondary">{{ $refType }} #{{ $movement->reference_id }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <span title="{{ $movement->notes }}">
                                        {{ Str::limit($movement->notes, 25) }}
                                    </span>
                                </td>
                                <td>{{ $movement->creator?->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p class="mb-0">{{ __('No stock movements found') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($movements->hasPages())
            <div class="card-footer">
                {{ $movements->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
