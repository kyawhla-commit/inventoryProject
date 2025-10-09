@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-boxes"></i> Material Requirements</h2>
        <div>
            <a href="{{ route('production-plans.show', $productionPlan) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Plan
            </a>
        </div>
    </div>

    <!-- Production Plan Info -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Production Plan: {{ $productionPlan->name }}</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <strong>Plan Number:</strong><br>
                    {{ $productionPlan->plan_number }}
                </div>
                <div class="col-md-3">
                    <strong>Status:</strong><br>
                    <span class="badge {{ $productionPlan->getStatusBadgeClass() }}">
                        {{ ucfirst(str_replace('_', ' ', $productionPlan->status)) }}
                    </span>
                </div>
                <div class="col-md-3">
                    <strong>Planned Start:</strong><br>
                    {{ $productionPlan->planned_start_date->format('M d, Y') }}
                </div>
                <div class="col-md-3">
                    <strong>Planned End:</strong><br>
                    {{ $productionPlan->planned_end_date->format('M d, Y') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Production Items Summary -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-list"></i> Production Items</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Recipe</th>
                            <th class="text-end">Planned Quantity</th>
                            <th class="text-end">Estimated Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($productionPlan->productionPlanItems as $item)
                            <tr>
                                <td>{{ $item->product->name }}</td>
                                <td>{{ $item->recipe->name ?? 'N/A' }}</td>
                                <td class="text-end">{{ number_format($item->planned_quantity, 2) }} {{ $item->unit }}</td>
                                <td class="text-end">${{ number_format($item->estimated_material_cost, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-info">
                        <tr>
                            <th colspan="3">Total Estimated Cost</th>
                            <th class="text-end">${{ number_format($productionPlan->total_estimated_cost, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Material Requirements -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-cubes"></i> Raw Material Requirements</h5>
            @if($requirements->count() > 0)
                <span class="badge bg-primary">{{ $requirements->count() }} Materials</span>
            @endif
        </div>
        <div class="card-body">
            @if($requirements->count() > 0)
                <!-- Summary Alerts -->
                @php
                    $insufficientMaterials = $requirements->filter(function($req) {
                        return !$req['is_sufficient'];
                    });
                @endphp

                @if($insufficientMaterials->count() > 0)
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-exclamation-triangle"></i> Insufficient Stock Alert</h6>
                        <p class="mb-0">
                            <strong>{{ $insufficientMaterials->count() }}</strong> material(s) have insufficient stock to complete this production plan.
                        </p>
                    </div>
                @else
                    <div class="alert alert-success">
                        <h6><i class="fas fa-check-circle"></i> All Materials Available</h6>
                        <p class="mb-0">Sufficient stock is available for all required materials.</p>
                    </div>
                @endif

                <!-- Requirements Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Raw Material</th>
                                <th class="text-end">Required Quantity</th>
                                <th class="text-end">Available Stock</th>
                                <th class="text-end">Shortage</th>
                                <th class="text-end">Estimated Cost</th>
                                <th class="text-center">Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
<<<<<<< Updated upstream
    @foreach($requirements as $requirement)
        @php
            // Get the raw material object - adjust based on your actual data structure
            $rawMaterial = $requirement['raw_material'] ?? \App\Models\RawMaterial::find($requirement['raw_material_id']);
            $isSufficient = $requirement['is_sufficient'] ?? false;
            $unit = $requirement['unit'] ?? $rawMaterial->unit ?? 'unit';
            $totalRequired = $requirement['total_required'] ?? 0;
            $availableQuantity = $requirement['available_quantity'] ?? 0;
            $shortage = $requirement['shortage'] ?? 0;
            $estimatedCost = $requirement['estimated_cost'] ?? 0;
        @endphp
        
        @if($rawMaterial) {{-- Only show if raw material exists --}}
            <tr class="{{ !$isSufficient ? 'table-danger' : '' }}">
                <td>
                    <strong>{{ $rawMaterial->name }}</strong>
                    <br>
                    <small class="text-muted">
                        Unit: {{ $unit }}
                        @if($rawMaterial->supplier)
                            | Supplier: {{ $rawMaterial->supplier->name }}
                        @endif
                    </small>
                </td>
                <td class="text-end">
                    <strong>{{ number_format($totalRequired, 2) }}</strong> {{ $unit }}
                </td>
                <td class="text-end">
                    {{ number_format($availableQuantity, 2) }} {{ $rawMaterial->unit }}
                </td>
                <td class="text-end">
                    @if($shortage > 0)
                        <span class="text-danger">
                            <strong>{{ number_format($shortage, 2) }}</strong> {{ $unit }}
                        </span>
                    @else
                        <span class="text-success">-</span>
                    @endif
                </td>
                <td class="text-end">
                    ${{ number_format($estimatedCost, 2) }}
                </td>
                <td class="text-center">
                    @if($isSufficient)
                        <span class="badge bg-success">
                            <i class="fas fa-check"></i> Sufficient
                        </span>
                    @else
                        <span class="badge bg-danger">
                            <i class="fas fa-times"></i> Insufficient
                        </span>
                    @endif
                </td>
                <td>
                    <a href="{{ route('raw-materials.show', $rawMaterial) }}" 
                       class="btn btn-sm btn-outline-primary" 
                       target="_blank">
                        <i class="fas fa-eye"></i> View
                    </a>
                    @if(!$isSufficient)
                        <a href="{{ route('raw-materials.edit', $rawMaterial) }}" 
                           class="btn btn-sm btn-warning" 
                           target="_blank">
                            <i class="fas fa-shopping-cart"></i> Restock
                        </a>
                    @endif
                </td>
            </tr>
        @endif
    @endforeach
</tbody>
=======
                            @foreach($requirements as $requirement)
                                @php
                                    $rawMaterial = $requirement['raw_material'];
                                    $isSufficient = $requirement['is_sufficient'];
                                @endphp
                                <tr class="{{ !$isSufficient ? 'table-danger' : '' }}">
                                    <td>
                                        <strong>{{ $rawMaterial->name }}</strong>
                                        <br>
                                        <small class="text-muted">
                                            Unit: {{ $requirement['unit'] }}
                                            @if($rawMaterial->supplier)
                                                | Supplier: {{ $rawMaterial->supplier->name }}
                                            @endif
                                        </small>
                                    </td>
                                    <td class="text-end">
                                        <strong>{{ number_format($requirement['total_required']?? 0, 2) }}</strong> {{ $requirement['unit'] }}
                                    </td>
                                    <td class="text-end">
                                        {{ number_format($requirement['available_quantity'], 2) }} {{ $rawMaterial->unit }}
                                    </td>
                                    <td class="text-end">
                                        @if($requirement['shortage'] > 0)
                                            <span class="text-danger">
                                                <strong>{{ number_format($requirement['shortage'], 2) }}</strong> {{ $requirement['unit'] }}
                                            </span>
                                        @else
                                            <span class="text-success">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        ${{ number_format($requirement['estimated_cost'] ?? 0, 2) }}
                                    </td>
                                    <td class="text-center">
                                        @if($isSufficient)
                                            <span class="badge bg-success">
                                                <i class="fas fa-check"></i> Sufficient
                                            </span>
                                        @else
                                            <span class="badge bg-danger">
                                                <i class="fas fa-times"></i> Insufficient
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('raw-materials.show', $rawMaterial) }}" 
                                           class="btn btn-sm btn-outline-primary" 
                                           target="_blank">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        @if(!$isSufficient)
                                            <a href="{{ route('raw-materials.edit', $rawMaterial) }}" 
                                               class="btn btn-sm btn-warning" 
                                               target="_blank">
                                                <i class="fas fa-shopping-cart"></i> Restock
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
>>>>>>> Stashed changes
                        <tfoot class="table-info">
                            <tr>
                                <th colspan="4">Total Estimated Material Cost</th>
                                <th class="text-end" colspan="3">
                                    ${{ number_format($requirements->sum('estimated_cost'), 2) }}
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Material Breakdown by Product -->
                <div class="mt-4">
                    <h6><i class="fas fa-list-alt"></i> Breakdown by Production Item</h6>
                    
                    @foreach($productionPlan->productionPlanItems as $planItem)
                        @if($planItem->recipe)
                            <div class="card mb-3">
                                <div class="card-header">
                                    <strong>{{ $planItem->product->name }}</strong>
                                    <span class="text-muted">
                                        - {{ number_format($planItem->planned_quantity, 2) }} {{ $planItem->unit }}
                                    </span>
                                </div>
                                <div class="card-body">
                                    @php
                                        $itemRequirements = $planItem->recipe->calculateMaterialRequirements($planItem->planned_quantity);
                                    @endphp
                                    
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Material</th>
                                                <th class="text-end">Required</th>
                                                <th class="text-end">Cost</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($itemRequirements as $itemReq)
                                                <tr>
                                                    <td>{{ $itemReq['raw_material']->name }}</td>
                                                    <td class="text-end">
                                                        {{ number_format($itemReq['total_required'], 2) }} {{ $itemReq['unit'] }}
                                                    </td>
                                                    <td class="text-end">
                                                        ${{ number_format($itemReq['estimated_cost'], 2) }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="table-light">
                                                <th colspan="2">Subtotal</th>
                                                <th class="text-end">
                                                    ${{ number_format($itemRequirements->sum('estimated_cost'), 2) }}
                                                </th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-warning">
                                <strong>{{ $planItem->product->name }}</strong>: No recipe assigned
                            </div>
                        @endif
                    @endforeach
                </div>

                <!-- Action Buttons -->
                <div class="mt-4">
                    @if($productionPlan->status === 'draft')
                        @if($insufficientMaterials->count() > 0)
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> 
                                Please restock insufficient materials before approving this production plan.
                            </div>
                        @else
                            <form action="{{ route('production-plans.approve', $productionPlan) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check"></i> Materials Ready - Approve Plan
                                </button>
                            </form>
                        @endif
                    @endif

                    <a href="{{ route('production-plans.show', $productionPlan) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Plan
                    </a>
                </div>

            @else
                <div class="alert alert-warning">
                    <h6><i class="fas fa-exclamation-triangle"></i> No Material Requirements</h6>
                    <p class="mb-0">
                        No material requirements could be calculated. This may be because:
                    </p>
                    <ul class="mb-0 mt-2">
                        <li>Production plan items don't have recipes assigned</li>
                        <li>Recipes don't have raw materials defined</li>
                    </ul>
                </div>

                <div class="mt-3">
                    <a href="{{ route('production-plans.edit', $productionPlan) }}" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Edit Plan to Add Recipes
                    </a>
                    <a href="{{ route('production-plans.show', $productionPlan) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Plan
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .table-danger {
        background-color: #f8d7da;
    }
    .alert ul {
        padding-left: 1.5rem;
    }
</style>
@endpush
