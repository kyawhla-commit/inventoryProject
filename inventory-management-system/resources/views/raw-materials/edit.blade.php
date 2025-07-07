@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Raw Material</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Whoops!</strong> There were some problems with your input.<br><br>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('raw-materials.update', $rawMaterial->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $rawMaterial->name) }}" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $rawMaterial->description) }}</textarea>
            </div>
            <div class="col-md-6 mb-3">
                <label for="quantity" class="form-label">Quantity</label>
                <input type="number" class="form-control" id="quantity" name="quantity" value="{{ old('quantity', $rawMaterial->quantity) }}" min="0" step="any" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="unit" class="form-label">Unit</label>
                <input type="text" class="form-control" id="unit" name="unit" value="{{ old('unit', $rawMaterial->unit) }}" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="cost_per_unit" class="form-label">Cost Per Unit</label>
                <input type="number" class="form-control" id="cost_per_unit" name="cost_per_unit" value="{{ old('cost_per_unit', $rawMaterial->cost_per_unit) }}" min="0" step="0.01" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="minimum_stock_level" class="form-label">Minimum Stock Level</label>
                <input type="number" class="form-control" id="minimum_stock_level" name="minimum_stock_level" value="{{ old('minimum_stock_level', $rawMaterial->minimum_stock_level) }}" min="0" step="0.01" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="supplier_id" class="form-label">Supplier</label>
                <select class="form-control" id="supplier_id" name="supplier_id">
                    <option value="">Select a supplier</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ old('supplier_id', $rawMaterial->supplier_id) == $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Update Raw Material</button>
        <a href="{{ route('raw-materials.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
