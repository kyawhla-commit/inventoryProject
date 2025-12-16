@extends('layouts.app')

@section('title', __('Add Product Stock'))

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-plus-circle me-2"></i>{{ __('Add Product Stock') }}
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('stock-management.add-product.store') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="product_id" class="form-label">{{ __('Product') }} <span class="text-danger">*</span></label>
                            <select name="product_id" id="product_id" class="form-select @error('product_id') is-invalid @enderror" required>
                                <option value="">{{ __('Select Product') }}</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" 
                                            data-unit="{{ $product->unit ?? 'pcs' }}"
                                            data-current="{{ $product->quantity }}"
                                            data-cost="{{ $product->cost ?? 0 }}"
                                            {{ (old('product_id') ?? request('product_id')) == $product->id ? 'selected' : '' }}>
                                        {{ $product->name }} ({{ __('Current') }}: {{ number_format($product->quantity, 2) }} {{ $product->unit ?? 'pcs' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('product_id')
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
                                        <span class="input-group-text" id="unit-display">{{ __('pcs') }}</span>
                                    </div>
                                    @error('quantity')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cost" class="form-label">{{ __('Unit Cost') }} (Ks)</label>
                                    <input type="number" name="cost" id="cost" 
                                           class="form-control @error('cost') is-invalid @enderror"
                                           value="{{ old('cost') }}" step="0.01" min="0">
                                    @error('cost')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">{{ __('Leave empty to use product default cost') }}</small>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="type" class="form-label">{{ __('Stock Type') }} <span class="text-danger">*</span></label>
                            <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                                <option value="production" {{ old('type') == 'production' ? 'selected' : '' }}>{{ __('Production Output') }}</option>
                                <option value="return" {{ old('type') == 'return' ? 'selected' : '' }}>{{ __('Customer Return') }}</option>
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
    const productSelect = document.getElementById('product_id');
    const quantityInput = document.getElementById('quantity');
    const unitDisplay = document.getElementById('unit-display');
    const summaryCard = document.getElementById('summary-card');

    function updateSummary() {
        const selected = productSelect.options[productSelect.selectedIndex];
        if (!selected.value) {
            summaryCard.style.display = 'none';
            return;
        }

        const unit = selected.dataset.unit;
        const current = parseFloat(selected.dataset.current) || 0;
        const quantity = parseFloat(quantityInput.value) || 0;

        unitDisplay.textContent = unit;

        if (quantity > 0) {
            summaryCard.style.display = 'block';
            document.getElementById('summary-current').textContent = `${current.toFixed(2)} ${unit}`;
            document.getElementById('summary-adding').textContent = `+${quantity.toFixed(2)} ${unit}`;
            document.getElementById('summary-new').textContent = `${(current + quantity).toFixed(2)} ${unit}`;
        } else {
            summaryCard.style.display = 'none';
        }
    }

    productSelect.addEventListener('change', updateSummary);
    quantityInput.addEventListener('input', updateSummary);

    // Initialize
    if (productSelect.value) {
        const selected = productSelect.options[productSelect.selectedIndex];
        unitDisplay.textContent = selected.dataset.unit;
        updateSummary();
    }
});
</script>
@endpush
@endsection
