@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Record Raw Material Usage</h4>
                    <a href="{{ route('raw-material-usages.index') }}" class="btn btn-secondary">Back to List</a>
                </div>
                <div class="card-body">
                    @if(isset($productionPlan))
                    <div class="alert alert-info">
                        <strong>Recording for Production Plan:</strong> {{ $productionPlan->name }} ({{ $productionPlan->plan_number }})
                    </div>
                    @endif

                    <form action="{{ route('raw-material-usages.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="raw_material_id">Raw Material <span class="text-danger">*</span></label>
                                    <select name="raw_material_id" id="raw_material_id" class="form-control @error('raw_material_id') is-invalid @enderror" required>
                                        <option value="">Select Raw Material</option>
                                        @foreach($rawMaterials as $material)
                                            <option value="{{ $material->id }}" 
                                                {{ (old('raw_material_id') == $material->id || (isset($selectedRawMaterial) && $selectedRawMaterial->id == $material->id)) ? 'selected' : '' }}
                                                data-unit="{{ $material->unit }}"
                                                data-available="{{ $material->quantity }}">
                                                {{ $material->name }} (Available: {{ $material->quantity }} {{ $material->unit }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('raw_material_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="quantity_used">Quantity Used <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" min="0.01" name="quantity_used" id="quantity_used" 
                                            class="form-control @error('quantity_used') is-invalid @enderror" 
                                            value="{{ old('quantity_used') }}" required>
                                        <span class="input-group-text" id="unit-display">Unit</span>
                                    </div>
                                    <small class="text-muted" id="available-display"></small>
                                    @error('quantity_used')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
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
                            
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="usage_date">Usage Date <span class="text-danger">*</span></label>
                                    <input type="date" name="usage_date" id="usage_date" 
                                        class="form-control @error('usage_date') is-invalid @enderror" 
                                        value="{{ old('usage_date', now()->format('Y-m-d')) }}" required>
                                    @error('usage_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="product_id">Product</label>
                                    <select name="product_id" id="product_id" class="form-control @error('product_id') is-invalid @enderror">
                                        <option value="">Select Product (Optional)</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                                {{ $product->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('product_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="order_id">Order</label>
                                    <select name="order_id" id="order_id" class="form-control @error('order_id') is-invalid @enderror">
                                        <option value="">Select Order (Optional)</option>
                                        @foreach($orders as $order)
                                            <option value="{{ $order->id }}" 
                                                {{ (old('order_id') == $order->id || (isset($selectedOrder) && $selectedOrder->id == $order->id)) ? 'selected' : '' }}>
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
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="batch_number">Batch Number</label>
                                    <input type="text" name="batch_number" id="batch_number" 
                                        class="form-control @error('batch_number') is-invalid @enderror" 
                                        value="{{ old('batch_number', $batchNumber ?? '') }}">
                                    @error('batch_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="notes">Notes</label>
                                    <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes', isset($productionPlan) ? 'Used for Production Plan: ' . $productionPlan->name : '') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mt-3">
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
        $('#raw_material_id').change(function() {
            const selectedOption = $(this).find('option:selected');
            const unit = selectedOption.data('unit') || 'Unit';
            const available = selectedOption.data('available') || 0;
            
            $('#unit-display').text(unit);
            $('#available-display').text(`Available: ${available} ${unit}`);
        }).trigger('change');
    });
</script>
@endpush
@endsection