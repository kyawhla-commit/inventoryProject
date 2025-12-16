@extends('layouts.app')

@section('title', __('Deduct Stock'))

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-minus-circle me-2"></i>{{ __('Deduct Stock') }}
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs mb-4" id="stockTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="raw-material-tab" data-bs-toggle="tab" 
                                    data-bs-target="#raw-material" type="button" role="tab">
                                <i class="fas fa-boxes me-1"></i> {{ __('Raw Material') }}
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="product-tab" data-bs-toggle="tab" 
                                    data-bs-target="#product" type="button" role="tab">
                                <i class="fas fa-box me-1"></i> {{ __('Product') }}
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="stockTabsContent">
                        <!-- Raw Material Tab -->
                        <div class="tab-pane fade show active" id="raw-material" role="tabpanel">
                            <form action="{{ route('stock-management.deduct-raw-material') }}" method="POST">
                                @csrf
                                
                                <div class="mb-3">
                                    <label for="raw_material_id" class="form-label">{{ __('Raw Material') }} <span class="text-danger">*</span></label>
                                    <select name="raw_material_id" id="raw_material_id" class="form-select" required>
                                        <option value="">{{ __('Select Raw Material') }}</option>
                                        @foreach($rawMaterials as $material)
                                            <option value="{{ $material->id }}" 
                                                    data-unit="{{ $material->unit }}"
                                                    data-current="{{ $material->quantity }}">
                                                {{ $material->name }} ({{ __('Available') }}: {{ number_format($material->quantity, 2) }} {{ $material->unit }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="rm_quantity" class="form-label">{{ __('Quantity to Deduct') }} <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="number" name="quantity" id="rm_quantity" 
                                                       class="form-control" step="0.001" min="0.001" required>
                                                <span class="input-group-text" id="rm-unit-display">{{ __('unit') }}</span>
                                            </div>
                                            <small class="text-muted" id="rm-available-hint"></small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="rm_type" class="form-label">{{ __('Deduction Type') }} <span class="text-danger">*</span></label>
                                            <select name="type" id="rm_type" class="form-select" required>
                                                <option value="usage">{{ __('Production Usage') }}</option>
                                                <option value="waste">{{ __('Waste/Spoilage') }}</option>
                                                <option value="adjustment">{{ __('Stock Adjustment') }}</option>
                                                <option value="damage">{{ __('Damaged') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="rm_notes" class="form-label">{{ __('Notes') }}</label>
                                    <textarea name="notes" id="rm_notes" rows="2" class="form-control"
                                              placeholder="{{ __('Reason for deduction') }}"></textarea>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('stock-management.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-1"></i> {{ __('Back') }}
                                    </a>
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-minus me-1"></i> {{ __('Deduct Stock') }}
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Product Tab -->
                        <div class="tab-pane fade" id="product" role="tabpanel">
                            <form action="{{ route('stock-management.deduct-product') }}" method="POST">
                                @csrf
                                
                                <div class="mb-3">
                                    <label for="product_id" class="form-label">{{ __('Product') }} <span class="text-danger">*</span></label>
                                    <select name="product_id" id="product_id" class="form-select" required>
                                        <option value="">{{ __('Select Product') }}</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}" 
                                                    data-unit="{{ $product->unit ?? 'pcs' }}"
                                                    data-current="{{ $product->quantity }}">
                                                {{ $product->name }} ({{ __('Available') }}: {{ number_format($product->quantity, 2) }} {{ $product->unit ?? 'pcs' }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="prod_quantity" class="form-label">{{ __('Quantity to Deduct') }} <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="number" name="quantity" id="prod_quantity" 
                                                       class="form-control" step="0.001" min="0.001" required>
                                                <span class="input-group-text" id="prod-unit-display">{{ __('pcs') }}</span>
                                            </div>
                                            <small class="text-muted" id="prod-available-hint"></small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="prod_type" class="form-label">{{ __('Deduction Type') }} <span class="text-danger">*</span></label>
                                            <select name="type" id="prod_type" class="form-select" required>
                                                <option value="sale">{{ __('Sale') }}</option>
                                                <option value="waste">{{ __('Waste/Spoilage') }}</option>
                                                <option value="adjustment">{{ __('Stock Adjustment') }}</option>
                                                <option value="damage">{{ __('Damaged') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="prod_notes" class="form-label">{{ __('Notes') }}</label>
                                    <textarea name="notes" id="prod_notes" rows="2" class="form-control"
                                              placeholder="{{ __('Reason for deduction') }}"></textarea>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('stock-management.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-1"></i> {{ __('Back') }}
                                    </a>
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-minus me-1"></i> {{ __('Deduct Stock') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Raw Material handlers
    const rmSelect = document.getElementById('raw_material_id');
    const rmQuantity = document.getElementById('rm_quantity');
    const rmUnitDisplay = document.getElementById('rm-unit-display');
    const rmHint = document.getElementById('rm-available-hint');

    rmSelect.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        if (selected.value) {
            rmUnitDisplay.textContent = selected.dataset.unit;
            rmHint.textContent = `{{ __('Available') }}: ${parseFloat(selected.dataset.current).toFixed(2)} ${selected.dataset.unit}`;
            rmQuantity.max = selected.dataset.current;
        }
    });

    // Product handlers
    const prodSelect = document.getElementById('product_id');
    const prodQuantity = document.getElementById('prod_quantity');
    const prodUnitDisplay = document.getElementById('prod-unit-display');
    const prodHint = document.getElementById('prod-available-hint');

    prodSelect.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        if (selected.value) {
            prodUnitDisplay.textContent = selected.dataset.unit;
            prodHint.textContent = `{{ __('Available') }}: ${parseFloat(selected.dataset.current).toFixed(2)} ${selected.dataset.unit}`;
            prodQuantity.max = selected.dataset.current;
        }
    });
});
</script>
@endpush
@endsection
