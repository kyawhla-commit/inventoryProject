@extends('layouts.app')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">{{ __('Edit Purchase Order') }}</h1>
            <p class="text-muted mb-0">{{ $purchase->purchase_number }}</p>
        </div>
        <a href="{{ route('purchases.show', $purchase) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>{{ __('Back') }}
        </a>
    </div>

    <form action="{{ route('purchases.update', $purchase) }}" method="POST" id="purchaseForm">
        @csrf
        @method('PUT')
        
        <div class="row">
            {{-- Main Form --}}
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>{{ __('Purchase Details') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="supplier_id" class="form-label">{{ __('Supplier') }} <span class="text-danger">*</span></label>
                                <select name="supplier_id" id="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" required>
                                    <option value="">{{ __('Select Supplier') }}</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" 
                                                {{ old('supplier_id', $purchase->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('supplier_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="purchase_date" class="form-label">{{ __('Purchase Date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="purchase_date" id="purchase_date" 
                                       class="form-control @error('purchase_date') is-invalid @enderror"
                                       value="{{ old('purchase_date', $purchase->purchase_date->format('Y-m-d')) }}" required>
                                @error('purchase_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">{{ __('Status') }}</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="pending" {{ $purchase->status == 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                                    <option value="approved" {{ $purchase->status == 'approved' ? 'selected' : '' }}>{{ __('Approved') }}</option>
                                    <option value="received" {{ $purchase->status == 'received' ? 'selected' : '' }}>{{ __('Received') }}</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="notes" class="form-label">{{ __('Notes') }}</label>
                                <textarea name="notes" id="notes" class="form-control" rows="2">{{ old('notes', $purchase->notes) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Items --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-boxes me-2"></i>{{ __('Purchase Items') }}</h5>
                        <button type="button" class="btn btn-sm btn-primary" id="addItemBtn">
                            <i class="fas fa-plus me-1"></i>{{ __('Add Item') }}
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="itemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 35%">{{ __('Raw Material') }}</th>
                                        <th style="width: 15%">{{ __('Quantity') }}</th>
                                        <th style="width: 15%">{{ __('Unit') }}</th>
                                        <th style="width: 15%">{{ __('Unit Price') }}</th>
                                        <th style="width: 15%">{{ __('Total') }}</th>
                                        <th style="width: 5%"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody">
                                    {{-- Items will be loaded here --}}
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="4" class="text-end fw-bold">{{ __('Grand Total:') }}</td>
                                        <td class="fw-bold" id="grandTotal">0</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="col-lg-4">
                {{-- Summary --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent">
                        <h6 class="mb-0"><i class="fas fa-calculator me-2"></i>{{ __('Order Summary') }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">{{ __('Items:') }}</span>
                            <span id="summaryItems">0</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">{{ __('Total Quantity:') }}</span>
                            <span id="summaryQty">0</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">{{ __('Total Amount:') }}</span>
                            <span class="fw-bold text-primary" id="summaryTotal">0</span>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save me-2"></i>{{ __('Update Purchase Order') }}
                        </button>
                        <a href="{{ route('purchases.show', $purchase) }}" class="btn btn-outline-secondary w-100">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- Item Row Template --}}
<template id="itemRowTemplate">
    <tr class="item-row">
        <td>
            <select name="items[INDEX][raw_material_id]" class="form-select form-select-sm material-select" required>
                <option value="">{{ __('Select Material') }}</option>
                @foreach($rawMaterials as $material)
                    <option value="{{ $material->id }}" 
                            data-unit="{{ $material->unit }}" 
                            data-price="{{ $material->cost_per_unit }}">
                        {{ $material->name }}
                    </option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="number" name="items[INDEX][quantity]" class="form-control form-control-sm quantity-input" 
                   min="0.01" step="0.01" required>
        </td>
        <td>
            <span class="unit-display text-muted">-</span>
        </td>
        <td>
            <input type="number" name="items[INDEX][unit_price]" class="form-control form-control-sm price-input" 
                   min="0" step="0.01" required>
        </td>
        <td>
            <span class="line-total fw-semibold">0</span>
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-outline-danger remove-item-btn">
                <i class="fas fa-times"></i>
            </button>
        </td>
    </tr>
</template>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let itemIndex = 0;
    const itemsBody = document.getElementById('itemsBody');
    const template = document.getElementById('itemRowTemplate');
    
    function addItemRow(materialId = '', quantity = '', price = '') {
        const clone = template.content.cloneNode(true);
        const row = clone.querySelector('tr');
        row.innerHTML = row.innerHTML.replace(/INDEX/g, itemIndex);
        itemsBody.appendChild(row);
        
        if (materialId) {
            const select = row.querySelector('.material-select');
            select.value = materialId;
            const option = select.selectedOptions[0];
            if (option) {
                row.querySelector('.unit-display').textContent = option.dataset.unit || '-';
            }
        }
        if (quantity) row.querySelector('.quantity-input').value = quantity;
        if (price) row.querySelector('.price-input').value = price;
        
        itemIndex++;
        calculateLineTotal(row);
        updateSummary();
    }
    
    document.getElementById('addItemBtn').addEventListener('click', () => addItemRow());
    
    itemsBody.addEventListener('change', function(e) {
        if (e.target.classList.contains('material-select')) {
            const row = e.target.closest('tr');
            const option = e.target.selectedOptions[0];
            if (option && option.value) {
                row.querySelector('.unit-display').textContent = option.dataset.unit || '-';
                row.querySelector('.price-input').value = option.dataset.price || '';
                calculateLineTotal(row);
            }
        }
    });
    
    itemsBody.addEventListener('input', function(e) {
        if (e.target.classList.contains('quantity-input') || e.target.classList.contains('price-input')) {
            calculateLineTotal(e.target.closest('tr'));
        }
    });
    
    itemsBody.addEventListener('click', function(e) {
        if (e.target.closest('.remove-item-btn')) {
            e.target.closest('tr').remove();
            updateSummary();
        }
    });
    
    function calculateLineTotal(row) {
        const qty = parseFloat(row.querySelector('.quantity-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        row.querySelector('.line-total').textContent = (qty * price).toLocaleString();
        updateSummary();
    }
    
    function updateSummary() {
        const rows = itemsBody.querySelectorAll('.item-row');
        let totalItems = rows.length;
        let totalQty = 0;
        let grandTotal = 0;
        
        rows.forEach(row => {
            const qty = parseFloat(row.querySelector('.quantity-input').value) || 0;
            const price = parseFloat(row.querySelector('.price-input').value) || 0;
            totalQty += qty;
            grandTotal += qty * price;
        });
        
        document.getElementById('summaryItems').textContent = totalItems;
        document.getElementById('summaryQty').textContent = totalQty.toLocaleString();
        document.getElementById('summaryTotal').textContent = grandTotal.toLocaleString();
        document.getElementById('grandTotal').textContent = grandTotal.toLocaleString();
    }
    
    // Load existing items
    @foreach($purchase->items as $item)
        addItemRow('{{ $item->raw_material_id }}', '{{ $item->quantity }}', '{{ $item->unit_price }}');
    @endforeach
    
    if (itemsBody.querySelectorAll('.item-row').length === 0) {
        addItemRow();
    }
});
</script>
@endpush
@endsection
