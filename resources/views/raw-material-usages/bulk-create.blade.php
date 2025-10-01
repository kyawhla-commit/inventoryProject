@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Bulk Record Raw Material Usage</h4>
                    <a href="{{ route('raw-material-usages.index') }}" class="btn btn-secondary">Back to List</a>
                </div>
                <div class="card-body">
                    @if(isset($productionPlan))
                    <div class="alert alert-info">
                        <strong>Recording for Production Plan:</strong> {{ $productionPlan->name }} ({{ $productionPlan->plan_number }})
                    </div>
                    @endif

                    <form action="{{ route('raw-material-usages.bulk-store') }}" method="POST">
                        @csrf
                        
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="usage_date">Usage Date <span class="text-danger">*</span></label>
                                    <input type="date" name="usage_date" id="usage_date" 
                                        class="form-control @error('usage_date') is-invalid @enderror" 
                                        value="{{ old('usage_date', now()->format('Y-m-d')) }}" required>
                                    @error('usage_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="usage_type">Usage Type <span class="text-danger">*</span></label>
                                    <select name="usage_type" id="usage_type" class="form-control @error('usage_type') is-invalid @enderror" required>
                                        @foreach($usageTypes as $value => $label)
                                            <option value="{{ $value }}" {{ old('usage_type', isset($productionPlan) ? 'production' : '') == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('usage_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="batch_number">Batch Number</label>
                                    <input type="text" name="batch_number" id="batch_number" 
                                        class="form-control @error('batch_number') is-invalid @enderror" 
                                        value="{{ old('batch_number', $batchNumber ?? '') }}">
                                    @error('batch_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="order_id">Order (Optional)</label>
                                    <select name="order_id" id="order_id" class="form-control @error('order_id') is-invalid @enderror">
                                        <option value="">Select Order</option>
                                        @foreach($orders as $order)
                                            <option value="{{ $order->id }}" {{ old('order_id') == $order->id ? 'selected' : '' }}>
                                                #{{ $order->order_number }} - {{ $order->customer->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('order_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <h5 class="mb-3">Materials</h5>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered" id="materials-table">
                                <thead>
                                    <tr>
                                        <th>Raw Material <span class="text-danger">*</span></th>
                                        <th>Quantity Used <span class="text-danger">*</span></th>
                                        <th>Product (Optional)</th>
                                        <th>Notes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="material-row">
                                        <td>
                                            <select name="materials[0][raw_material_id]" class="form-control raw-material-select" required>
                                                <option value="">Select Raw Material</option>
                                                @foreach($rawMaterials as $material)
                                                    <option value="{{ $material->id }}" 
                                                        data-unit="{{ $material->unit }}"
                                                        data-available="{{ $material->quantity }}">
                                                        {{ $material->name }} (Available: {{ $material->quantity }} {{ $material->unit }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <div class="input-group">
                                                <input type="number" step="0.01" min="0.01" name="materials[0][quantity_used]" 
                                                    class="form-control quantity-input" required>
                                                <span class="input-group-text unit-display">Unit</span>
                                            </div>
                                            <small class="text-muted available-display"></small>
                                        </td>
                                        <td>
                                            <select name="materials[0][product_id]" class="form-control">
                                                <option value="">Select Product</option>
                                                @foreach($products as $product)
                                                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="materials[0][notes]" class="form-control" 
                                                placeholder="Optional notes">
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-danger btn-sm remove-row" disabled>
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="5">
                                            <button type="button" class="btn btn-success btn-sm" id="add-material">
                                                <i class="fas fa-plus"></i> Add Material
                                            </button>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary">Record Usage</button>
                            <a href="{{ route('raw-material-usages.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // Update unit display when raw material changes
        $(document).on('change', '.raw-material-select', function() {
            const row = $(this).closest('.material-row');
            const selectedOption = $(this).find('option:selected');
            const unit = selectedOption.data('unit') || 'Unit';
            const available = selectedOption.data('available') || 0;
            
            row.find('.unit-display').text(unit);
            row.find('.available-display').text(`Available: ${available} ${unit}`);
        });
        
        // Add new material row
        $('#add-material').click(function() {
            const rowCount = $('.material-row').length;
            const newRow = $('.material-row').first().clone();
            
            // Reset values
            newRow.find('select, input').val('');
            newRow.find('.available-display').text('');
            newRow.find('.unit-display').text('Unit');
            
            // Update names with new index
            newRow.find('select, input').each(function() {
                const name = $(this).attr('name');
                if (name) {
                    $(this).attr('name', name.replace('[0]', `[${rowCount}]`));
                }
            });
            
            // Enable remove button
            newRow.find('.remove-row').prop('disabled', false);
            
            // Add to table
            $('#materials-table tbody').append(newRow);
        });
        
        // Remove material row
        $(document).on('click', '.remove-row', function() {
            $(this).closest('.material-row').remove();
        });
    });
</script>
@endpush
@endsection