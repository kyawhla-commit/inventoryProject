@extends('layouts.app')

@section('title', __('Products'))

@section('content')
<div class="container-fluid">
    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-box me-2"></i>{{ __('Products') }}</h1>
            <p class="text-muted mb-0">{{ __('Manage your product catalog') }}</p>
        </div>
        <a href="{{ route('products.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> {{ __('Create Product') }}
        </a>
    </div>

    {{-- Stats Cards --}}
    @if(isset($stats))
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card bg-primary text-white">
                <div class="card-body py-3 text-center">
                    <h4 class="mb-0">{{ $stats['total'] }}</h4>
                    <small>{{ __('Total Products') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body py-3 text-center">
                    <h4 class="mb-0">{{ $stats['in_stock'] }}</h4>
                    <small>{{ __('In Stock') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-dark">
                <div class="card-body py-3 text-center">
                    <h4 class="mb-0">{{ $stats['low_stock'] }}</h4>
                    <small>{{ __('Low Stock') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-danger text-white">
                <div class="card-body py-3 text-center">
                    <h4 class="mb-0">{{ $stats['out_of_stock'] }}</h4>
                    <small>{{ __('Out of Stock') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-dark text-white">
                <div class="card-body py-3 text-center">
                    <h4 class="mb-0">Ks {{ number_format($stats['total_value'], 0) }}</h4>
                    <small>{{ __('Total Inventory Value') }}</small>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('products.index') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control" 
                               placeholder="{{ __('Search by name, description, barcode...') }}" 
                               value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="category_id" class="form-select">
                        <option value="">{{ __('All Categories') }}</option>
                        @if(isset($categories))
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="stock_status" class="form-select">
                        <option value="">{{ __('All Stock Status') }}</option>
                        <option value="in_stock" {{ request('stock_status') === 'in_stock' ? 'selected' : '' }}>{{ __('In Stock') }}</option>
                        <option value="low_stock" {{ request('stock_status') === 'low_stock' ? 'selected' : '' }}>{{ __('Low Stock') }}</option>
                        <option value="out_of_stock" {{ request('stock_status') === 'out_of_stock' ? 'selected' : '' }}>{{ __('Out of Stock') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="sort" class="form-select">
                        <option value="created_at" {{ request('sort') === 'created_at' ? 'selected' : '' }}>{{ __('Newest') }}</option>
                        <option value="name" {{ request('sort') === 'name' ? 'selected' : '' }}>{{ __('Name') }}</option>
                        <option value="quantity" {{ request('sort') === 'quantity' ? 'selected' : '' }}>{{ __('Stock') }}</option>
                        <option value="price" {{ request('sort') === 'price' ? 'selected' : '' }}>{{ __('Price') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> {{ __('Filter') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Products Table --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px">#</th>
                            <th>{{ __('Product') }}</th>
                            <th>{{ __('Category') }}</th>
                            <th class="text-end">{{ __('Stock') }}</th>
                            <th class="text-end">{{ __('Price') }}</th>
                            <th class="text-end">{{ __('Cost') }}</th>
                            <th class="text-center">{{ __('Status') }}</th>
                            <th style="width: 150px">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            @php
                                $stockStatus = $product->quantity <= 0 ? 'danger' : 
                                              ($product->quantity <= $product->minimum_stock_level ? 'warning' : 'success');
                            @endphp
                            <tr class="{{ $product->quantity <= 0 ? 'table-danger' : ($product->quantity <= $product->minimum_stock_level ? 'table-warning' : '') }}">
                                <td>{{ $loop->iteration + ($products->currentPage() - 1) * $products->perPage() }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($product->image)
                                            <img src="{{ asset($product->image) }}" class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                        @else
                                            <div class="bg-light rounded me-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="fas fa-box text-muted"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <a href="{{ route('products.show', $product) }}" class="fw-bold text-decoration-none">
                                                {{ $product->name }}
                                            </a>
                                            @if($product->barcode)
                                                <br><small class="text-muted">{{ $product->barcode }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $product->category->name ?? '-' }}</td>
                                <td class="text-end">
                                    <strong>{{ $product->quantity }}</strong>
                                    <small class="text-muted">{{ $product->unit ?? 'pcs' }}</small>
                                </td>
                                <td class="text-end">Ks {{ number_format($product->price, 0) }}</td>
                                <td class="text-end">Ks {{ number_format($product->cost ?? 0, 0) }}</td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $stockStatus }}">
                                        {{ $product->quantity <= 0 ? __('Out') : ($product->quantity <= $product->minimum_stock_level ? __('Low') : __('OK')) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('products.show', $product) }}" class="btn btn-outline-primary" title="{{ __('View') }}">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('products.edit', $product) }}" class="btn btn-outline-secondary" title="{{ __('Edit') }}">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="{{ route('products.raw-materials.index', $product) }}" class="btn btn-outline-info" title="{{ __('Raw Materials') }}">
                                            <i class="fas fa-industry"></i>
                                        </a>
                                        <form action="{{ route('products.destroy', $product) }}" method="POST" class="d-inline" 
                                              onsubmit="return confirm('{{ __('Are you sure?') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="{{ __('Delete') }}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-box-open fa-2x mb-2 d-block"></i>
                                    {{ __('No products found') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($products->hasPages())
            <div class="card-footer">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
