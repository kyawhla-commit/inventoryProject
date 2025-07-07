@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Add Raw Material</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('raw-materials.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description">{{ old('description') }}</textarea>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="quantity" class="form-label">Quantity</label>
                <input type="number" class="form-control" id="quantity" name="quantity" value="{{ old('quantity', 0) }}" min="0" step="0.01" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="unit" class="form-label">Unit (e.g., kg, liter, piece)</label>
                <input type="text" class="form-control" id="unit" name="unit" value="{{ old('unit') }}" required>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="cost_per_unit" class="form-label">Cost Per Unit</label>
                <input type="number" class="form-control" id="cost_per_unit" name="cost_per_unit" value="{{ old('cost_per_unit') }}" min="0" step="0.01" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="minimum_stock_level" class="form-label">Minimum Stock Level</label>
                <input type="number" class="form-control" id="minimum_stock_level" name="minimum_stock_level" value="{{ old('minimum_stock_level', 0) }}" min="0" step="0.01" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="supplier_id" class="form-label">Supplier</label>
                <select class="form-control" id="supplier_id" name="supplier_id">
                    <option value="">Select a supplier</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Add Material</button>
        <a href="{{ route('raw-materials.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
