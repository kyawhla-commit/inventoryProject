@extends('layouts.app')

@section('title', __('Add Raw Material Stock'))

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-plus-circle me-2"></i>{{ __('Add Raw Material Stock') }}
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('stock-management.add-raw-material.store') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="raw_material_id" class="form-label">{{ __('Raw Material') }} <span class="text-danger">*</span></label>
                            <select name="raw_material_id" id="raw_material_id" class="form-select @error('raw_material_id') is-invalid @enderror" required>
                                <option value="">{{ __('Select Raw Material') }}</option>
                                @foreach($rawMaterials as $material)
                                    <option value="{{ $material->id }}" 
                                            data-unit="{{ $material->unit }}"
                                            data-current="{{ $material->quantity }}"
                                            data-cost="{{ $material->cost_per_unit }}"
                                            {{ (old('raw_material_id') ?? request('material_id')) == $material->id ? 'selected' : '' }}>
                                        {{ $material->name }} ({{ __('Current') }}: {{ number_format($material->quantity, 2) }} {{ $material->unit }})
                                    </option>
                                @endforeach
                            </select>
                            @error('raw_material_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="quantity" class="form-label">{{ __('Quantity to Add') }} <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" name="quantity" id="quantity" 
                                               class="form-control @error('quantity') is-invalid @enderror"
                                               value="{{ old('quantity') }}" step="0.001" min="0.001" required>
                                        <span class="input-group-text" id="unit-display">{{ __('unit') }}</span>
                                    </div>
                                    @error('quantity')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="unit_price" class="form-label">{{ __('Unit Price') }} (Ks) <span class="text-danger">*</span></label>
                                    <input type="number" name="unit_price" id="unit_price" 
                                           class="form-control @error('unit_price') is-invalid @enderror"
                                           value="{{ old('unit_price') }}" step="0.01" min="0" required>
                                    @error('unit_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted" id="current-cost-hint"></small>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="type" class="form-label">{{ __('Stock Type') }} <span class="text-danger">*</span></label>
                            <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                                <option value="purchase" {{ old('type') == 'purchase' ? 'selected' : '' }}>{{ __('Purchase') }}</option>
                                <option value="return" {{ old('type') == 'return' ? 'selected' : '' }}>{{ __('Return from Production') }}</option>
                                <option value="adjustment" {{ old('type') == 'adjustment' ? 'selected' : '' }}>{{ __('Stock Adjustment') }}</option>
                                <option value="initial" {{ old('type') == 'initial' ? 'selected' : '' }}>{{ __('Initial Stock') }}</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">{{ __('Notes') }}</label>
                            <textarea name="notes" id="notes" rows="3" 
                                      class="form-control @error('notes') is-invalid @enderror"
                                      placeholder="{{ __('Optional notes about this stock addition') }}">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Summary Card -->
                        <div class="card bg-light mb-3" id="summary-card" style="display: none;">
                            <div class="card-body">
                                <h6 class="card-title">{{ __('Summary') }}</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <small class="text-muted">{{ __('Current Stock') }}</small>
                                        <p class="mb-0 fw-bold" id="summary-current">-</p>
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted">{{ __('Adding') }}</small>
                                        <p class="mb-0 fw-bold text-success" id="summary-adding">-</p>
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted">{{ __('New Stock') }}</small>
                                        <p class="mb-0 fw-bold text-primary" id="summary-new">-</p>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-md-6">
                                        <small class="text-muted">{{ __('Total Cost') }}</small>
                                        <p class="mb-0 fw-bold" id="summary-cost">-</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('stock-management.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> {{ __('Back') }}
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-plus me-1"></i> {{ __('Add Stock') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const materialSelect = document.getElementById('raw_material_id');
    const quantityInput = document.getElementById('quantity');
    const unitPriceInput = document.getElementById('unit_price');
    const unitDisplay = document.getElementById('unit-display');
    const currentCostHint = document.getElementById('current-cost-hint');
    const summaryCard = document.getElementById('summary-card');

    function updateSummary() {
        const selected = materialSelect.options[materialSelect.selectedIndex];
        if (!selected.value) {
            summaryCard.style.display = 'none';
            return;
        }

        const unit = selected.dataset.unit;
        const current = parseFloat(selected.dataset.current) || 0;
        const quantity = parseFloat(quantityInput.value) || 0;
        const unitPrice = parseFloat(unitPriceInput.value) || 0;

        unitDisplay.textContent = unit;
        currentCostHint.textContent = `{{ __('Current cost') }}: ${parseFloat(selected.dataset.cost).toLocaleString()} Ks/${unit}`;

        if (quantity > 0) {
            summaryCard.style.display = 'block';
            document.getElementById('summary-current').textContent = `${current.toFixed(2)} ${unit}`;
            document.getElementById('summary-adding').textContent = `+${quantity.toFixed(2)} ${unit}`;
            document.getElementById('summary-new').textContent = `${(current + quantity).toFixed(2)} ${unit}`;
            document.getElementById('summary-cost').textContent = `${(quantity * unitPrice).toLocaleString()} Ks`;
        } else {
            summaryCard.style.display = 'none';
        }
    }

    materialSelect.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        if (selected.value) {
            unitPriceInput.value = selected.dataset.cost;
        }
        updateSummary();
    });

    quantityInput.addEventListener('input', updateSummary);
    unitPriceInput.addEventListener('input', updateSummary);

    // Initialize
    if (materialSelect.value) {
        const selected = materialSelect.options[materialSelect.selectedIndex];
        unitDisplay.textContent = selected.dataset.unit;
        if (!unitPriceInput.value) {
            unitPriceInput.value = selected.dataset.cost;
        }
        updateSummary();
    }
});
</script>
@endpush
@endsection
