@extends('layouts.app')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="fas fa-exclamation-triangle text-warning me-2"></i>{{ __('Low Stock Materials') }}
            </h1>
            <p class="text-muted mb-0">{{ __('Materials that need to be reordered') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('raw-materials.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>{{ __('Back') }}
            </a>
            <a href="{{ route('purchases.create') }}" class="btn btn-primary">
                <i class="fas fa-shopping-cart me-2"></i>{{ __('Create Purchase Order') }}
            </a>
        </div>
    </div>

    @if($lowStockMaterials->count() > 0)
        {{-- Summary --}}
        <div class="alert alert-warning mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-circle fa-2x me-3"></i>
                <div>
                    <strong>{{ $lowStockMaterials->count() }} {{ __('materials need attention') }}</strong>
                    <p class="mb-0">{{ __('These materials are at or below their minimum stock levels.') }}</p>
                </div>
            </div>
        </div>

        {{-- Group by Supplier --}}
        @foreach($bySupplier as $supplierId => $materials)
            @php
                $supplier = $materials->first()->supplier;
            @endphp
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-truck me-2"></i>
                        {{ $supplier->name ?? __('No Supplier') }}
                    </h5>
                    @if($supplier)
                        <a href="{{ route('purchases.create', ['supplier_id' => $supplier->id]) }}" class="btn btn-sm btn-success">
                            <i class="fas fa-plus me-1"></i>{{ __('Order from this Supplier') }}
                        </a>
                    @endif
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('Material') }}</th>
                                    <th class="text-center">{{ __('Current Stock') }}</th>
                                    <th class="text-center">{{ __('Minimum Level') }}</th>
                                    <th class="text-center">{{ __('Shortage') }}</th>
                                    <th class="text-end">{{ __('Est. Cost') }}</th>
                                    <th class="text-center">{{ __('Status') }}</th>
                                    <th class="text-end">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($materials as $material)
                                    @php
                                        $shortage = max(0, $material->minimum_stock_level - $material->quantity);
                                        $suggestedOrder = max($shortage, $material->reorder_quantity ?? $material->minimum_stock_level);
                                        $estimatedCost = $suggestedOrder * $material->cost_per_unit;
                                    @endphp
                                    <tr>
                                        <td>
                                            <a href="{{ route('raw-materials.show', $material) }}" class="fw-semibold text-decoration-none">
                                                {{ $material->name }}
                                            </a>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $material->stock_status_badge }}">
                                                {{ number_format($material->quantity, 2) }} {{ $material->unit }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            {{ number_format($material->minimum_stock_level, 2) }} {{ $material->unit }}
                                        </td>
                                        <td class="text-center text-danger fw-semibold">
                                            -{{ number_format($shortage, 2) }} {{ $material->unit }}
                                        </td>
                                        <td class="text-end">
                                            <small class="text-muted">{{ __('Order') }} {{ number_format($suggestedOrder, 0) }}:</small><br>
                                            <strong>{{ number_format($estimatedCost, 0) }}</strong>
                                        </td>
                                        <td class="text-center">
                                            @if($material->isOutOfStock())
                                                <span class="badge bg-danger">{{ __('Out of Stock') }}</span>
                                            @elseif($material->isCriticalStock())
                                                <span class="badge bg-danger">{{ __('Critical') }}</span>
                                            @else
                                                <span class="badge bg-warning">{{ __('Low') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('raw-materials.show', $material) }}" 
                                                   class="btn btn-outline-primary" title="{{ __('View') }}">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('purchases.create', ['supplier_id' => $material->supplier_id]) }}" 
                                                   class="btn btn-outline-success" title="{{ __('Order') }}">
                                                    <i class="fas fa-shopping-cart"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">{{ __('Supplier Total:') }}</td>
                                    <td class="text-end fw-bold text-primary">
                                        {{ number_format($materials->sum(function($m) {
                                            $shortage = max(0, $m->minimum_stock_level - $m->quantity);
                                            $suggestedOrder = max($shortage, $m->reorder_quantity ?? $m->minimum_stock_level);
                                            return $suggestedOrder * $m->cost_per_unit;
                                        }), 0) }}
                                    </td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Grand Total --}}
        <div class="card border-0 shadow-sm bg-primary text-white">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="mb-0">{{ __('Estimated Total Reorder Cost') }}</h5>
                        <small class="opacity-75">{{ __('Based on suggested order quantities') }}</small>
                    </div>
                    <div class="col-auto">
                        <h3 class="mb-0">
                            {{ number_format($lowStockMaterials->sum(function($m) {
                                $shortage = max(0, $m->minimum_stock_level - $m->quantity);
                                $suggestedOrder = max($shortage, $m->reorder_quantity ?? $m->minimum_stock_level);
                                return $suggestedOrder * $m->cost_per_unit;
                            }), 0) }}
                        </h3>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                <h4>{{ __('All Stock Levels OK') }}</h4>
                <p class="text-muted mb-0">{{ __('No materials are currently below their minimum stock levels.') }}</p>
            </div>
        </div>
    @endif
</div>
@endsection
