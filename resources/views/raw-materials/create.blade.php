@extends('layouts.app')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">{{ __('Add Raw Material') }}</h1>
            <p class="text-muted mb-0">{{ __('Create a new raw material for inventory') }}</p>
        </div>
        <a href="{{ route('raw-materials.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>{{ __('Back') }}
        </a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('raw-materials.store') }}" method="POST">
        @csrf
        
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
                                       value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label for="description" class="form-label">{{ __('Description') }}</label>
                                <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror"
                                          rows="2">{{ old('description') }}</textarea>
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
                                <label for="quantity" class="form-label">{{ __('Initial Quantity') }} <span class="text-danger">*</span></label>
                                <input type="number" name="quantity" id="quantity" class="form-control @error('quantity') is-invalid @enderror"
                                       value="{{ old('quantity', 0) }}" min="0" step="0.01" required>
                                @error('quantity')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="unit" class="form-label">{{ __('Unit') }} <span class="text-danger">*</span></label>
                                <select name="unit" id="unit" class="form-select @error('unit') is-invalid @enderror" required>
                                    <option value="">{{ __('Select Unit') }}</option>
                                    @foreach($units as $unit)
                                        <option value="{{ $unit }}" {{ old('unit') == $unit ? 'selected' : '' }}>{{ $unit }}</option>
                                    @endforeach
                                </select>
                                @error('unit')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="cost_per_unit" class="form-label">{{ __('Cost per Unit') }} <span class="text-danger">*</span></label>
                                <input type="number" name="cost_per_unit" id="cost_per_unit" class="form-control @error('cost_per_unit') is-invalid @enderror"
                                       value="{{ old('cost_per_unit', 0) }}" min="0" step="0.01" required>
                                @error('cost_per_unit')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="minimum_stock_level" class="form-label">{{ __('Minimum Stock Level') }} <span class="text-danger">*</span></label>
                                <input type="number" name="minimum_stock_level" id="minimum_stock_level" class="form-control @error('minimum_stock_level') is-invalid @enderror"
                                       value="{{ old('minimum_stock_level', 0) }}" min="0" step="0.01" required>
                                <small class="text-muted">{{ __('Alert when stock falls below this level') }}</small>
                                @error('minimum_stock_level')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="supplier_id" class="form-label">{{ __('Supplier') }}</label>
                                <select name="supplier_id" id="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror">
                                    <option value="">{{ __('Select Supplier') }}</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
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
                {{-- Actions --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save me-2"></i>{{ __('Create Material') }}
                        </button>
                        <a href="{{ route('raw-materials.index') }}" class="btn btn-outline-secondary w-100">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
