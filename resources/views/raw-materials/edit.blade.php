@extends('layouts.app')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">{{ __('Edit Raw Material') }}</h1>
            <p class="text-muted mb-0">{{ $rawMaterial->name }}</p>
        </div>
        <a href="{{ route('raw-materials.show', $rawMaterial) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>{{ __('Back') }}
        </a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('raw-materials.update', $rawMaterial) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="row">
            <div class="col-lg-8">
                {{-- Basic Information --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>{{ __('Basic Information') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="name" class="form-label">{{ __('Material Name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $rawMaterial->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label for="description" class="form-label">{{ __('Description') }}</label>
                                <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror"
                                          rows="2">{{ old('description', $rawMaterial->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Stock & Pricing --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent">
                        <h5 class="mb-0"><i class="fas fa-boxes me-2"></i>{{ __('Stock & Pricing') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="quantity" class="form-label">{{ __('Current Quantity') }} <span class="text-danger">*</span></label>
                                <input type="number" name="quantity" id="quantity" class="form-control @error('quantity') is-invalid @enderror"
                                       value="{{ old('quantity', $rawMaterial->quantity) }}" min="0" step="0.01" required>
                                @error('quantity')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="unit" class="form-label">{{ __('Unit') }} <span class="text-danger">*</span></label>
                                <select name="unit" id="unit" class="form-select @error('unit') is-invalid @enderror" required>
                                    <option value="">{{ __('Select Unit') }}</option>
                                    @foreach($units as $unit)
                                        <option value="{{ $unit }}" {{ old('unit', $rawMaterial->unit) == $unit ? 'selected' : '' }}>{{ $unit }}</option>
                                    @endforeach
                                </select>
                                @error('unit')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="cost_per_unit" class="form-label">{{ __('Cost per Unit') }} <span class="text-danger">*</span></label>
                                <input type="number" name="cost_per_unit" id="cost_per_unit" class="form-control @error('cost_per_unit') is-invalid @enderror"
                                       value="{{ old('cost_per_unit', $rawMaterial->cost_per_unit) }}" min="0" step="0.01" required>
                                @error('cost_per_unit')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="minimum_stock_level" class="form-label">{{ __('Minimum Stock Level') }} <span class="text-danger">*</span></label>
                                <input type="number" name="minimum_stock_level" id="minimum_stock_level" class="form-control @error('minimum_stock_level') is-invalid @enderror"
                                       value="{{ old('minimum_stock_level', $rawMaterial->minimum_stock_level) }}" min="0" step="0.01" required>
                                @error('minimum_stock_level')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="supplier_id" class="form-label">{{ __('Supplier') }}</label>
                                <select name="supplier_id" id="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror">
                                    <option value="">{{ __('Select Supplier') }}</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ old('supplier_id', $rawMaterial->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('supplier_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="col-lg-4">
                {{-- Current Stock Info --}}
                <div class="card border-0 shadow-sm mb-4 {{ $rawMaterial->isLowStock() ? 'border-warning' : '' }}">
                    <div class="card-header bg-transparent">
                        <h6 class="mb-0"><i class="fas fa-warehouse me-2"></i>{{ __('Current Stock') }}</h6>
                    </div>
                    <div class="card-body text-center">
                        <h2 class="{{ $rawMaterial->isLowStock() ? 'text-warning' : 'text-success' }}">
                            {{ number_format($rawMaterial->quantity, 2) }}
                        </h2>
                        <p class="text-muted mb-2">{{ $rawMaterial->unit }}</p>
                        <span class="badge {{ $rawMaterial->stock_status_badge }}">
                            {{ $rawMaterial->stock_status_label }}
                        </span>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save me-2"></i>{{ __('Update Material') }}
                        </button>
                        <a href="{{ route('raw-materials.show', $rawMaterial) }}" class="btn btn-outline-secondary w-100 mb-2">
                            {{ __('Cancel') }}
                        </a>
                        <hr>
                        <form action="{{ route('raw-materials.destroy', $rawMaterial) }}" method="POST" 
                              onsubmit="return confirm('{{ __('Are you sure you want to delete this material?') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="fas fa-trash me-2"></i>{{ __('Delete Material') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
