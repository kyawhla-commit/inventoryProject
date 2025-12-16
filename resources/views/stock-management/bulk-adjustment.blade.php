@extends('layouts.app')

@section('title', __('Bulk Stock Adjustment'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">{{ __('Bulk Stock Adjustment') }}</h1>
            <p class="text-muted mb-0">{{ __('Adjust multiple items at once') }}</p>
        </div>
        <a href="{{ route('stock-management.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> {{ __('Back') }}
        </a>
    </div>

    <form action="{{ route('stock-management.bulk-adjustment.store') }}" method="POST" id="bulkForm">
        @csrf
        
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ __('Adjustment Items') }}</h5>
                <button type="button" class="btn btn-sm btn-success" id="addRow">
                    <i class="fas fa-plus me-1"></i> {{ __('Add Item') }}
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0" id="adjustmentTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 150px;">{{ __('Type') }}</th>
                                <th>{{ __('Item') }}</th>
                                <th style="width: 150px;">{{ __('Quantity (+/-)') }}</th>
                                <th>{{ __('Reason') }}</th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody id="adjustmentRows">
                            <tr class="adjustment-row">
                                <td>
                                    <select name="adjustments[0][type]" class="form-select item-type" required>
                                        <option value="raw_material">{{ __('Raw Material') }}</option>
                                        <option value="product">{{ __('Product') }}</option>
                                    </select>
                                </td>
                                <td>
                                    <select name="adjustments[0][id]" class="form-select item-select" required>
                                        <option value="">{{ __('Select Item') }}</option>
                                        @foreach($rawMaterials as $material)
                                            <option value="{{ $material->id }}" data-type="raw_material" data-current="{{ $material->quantity }}" data-unit="{{ $material->unit }}">
                                                {{ $material->name }} ({{ number_format($material->quantity, 2) }} {{ $material->unit }})
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <div class="input-group">
                                        <input type="number" name="adjustments[0][quantity]" class="form-control" step="0.001" required placeholder="0">
                                        <span class="input-group-text unit-display">-</span>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" name="adjustments[0][reason]" class="form-control" required placeholder="{{ __('Reason for adjustment') }}">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-row" disabled>
                                        <i class="fas fa-times"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> {{ __('Process Adjustments') }}
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const rawMaterials = @json($rawMaterials);
    const products = @json($products);
    let rowIndex = 1;

    function getItemOptions(type) {
        const items = type === 'raw_material' ? rawMaterials : products;
        let options = '<option value="">{{ __("Select Item") }}</option>';
        items.forEach(item => {
            const unit = item.unit || 'pcs';
            const qty = parseFloat(item.quantity).toFixed(2);
            options += `<option value="${item.id}" data-type="${type}" data-current="${item.quantity}" data-unit="${unit}">
                ${item.name} (${qty} ${unit})
            </option>`;
        });
        return options;
    }

    function updateItemSelect(row) {
        const typeSelect = row.querySelector('.item-type');
        const itemSelect = row.querySelector('.item-select');
        itemSelect.innerHTML = getItemOptions(typeSelect.value);
    }

    function updateUnitDisplay(row) {
        const itemSelect = row.querySelector('.item-select');
        const unitDisplay = row.querySelector('.unit-display');
        const selected = itemSelect.options[itemSelect.selectedIndex];
        unitDisplay.textContent = selected.dataset.unit || '-';
    }

    // Add row
    document.getElementById('addRow').addEventListener('click', function() {
        const tbody = document.getElementById('adjustmentRows');
        const newRow = document.createElement('tr');
        newRow.className = 'adjustment-row';
        newRow.innerHTML = `
            <td>
                <select name="adjustments[${rowIndex}][type]" class="form-select item-type" required>
                    <option value="raw_material">{{ __('Raw Material') }}</option>
                    <option value="product">{{ __('Product') }}</option>
                </select>
            </td>
            <td>
                <select name="adjustments[${rowIndex}][id]" class="form-select item-select" required>
                    ${getItemOptions('raw_material')}
                </select>
            </td>
            <td>
                <div class="input-group">
                    <input type="number" name="adjustments[${rowIndex}][quantity]" class="form-control" step="0.001" required placeholder="0">
                    <span class="input-group-text unit-display">-</span>
                </div>
            </td>
            <td>
                <input type="text" name="adjustments[${rowIndex}][reason]" class="form-control" required placeholder="{{ __('Reason for adjustment') }}">
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-outline-danger remove-row">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        `;
        tbody.appendChild(newRow);
        rowIndex++;
        updateRemoveButtons();
    });

    // Remove row
    document.getElementById('adjustmentRows').addEventListener('click', function(e) {
        if (e.target.closest('.remove-row')) {
            e.target.closest('.adjustment-row').remove();
            updateRemoveButtons();
        }
    });

    // Type change
    document.getElementById('adjustmentRows').addEventListener('change', function(e) {
        if (e.target.classList.contains('item-type')) {
            updateItemSelect(e.target.closest('.adjustment-row'));
        }
        if (e.target.classList.contains('item-select')) {
            updateUnitDisplay(e.target.closest('.adjustment-row'));
        }
    });

    function updateRemoveButtons() {
        const rows = document.querySelectorAll('.adjustment-row');
        rows.forEach((row, index) => {
            const btn = row.querySelector('.remove-row');
            btn.disabled = rows.length === 1;
        });
    }
});
</script>
@endpush
@endsection
