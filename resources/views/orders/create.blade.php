@extends('layouts.app')

@section('content')
<div class="container">
    {{-- Flash Messages --}}
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-shopping-cart me-2"></i>{{__('Create Order')}}</h1>
        <a href="{{ route('orders.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> {{__('Back to Orders')}}
        </a>
    </div>

    <form action="{{ route('orders.store') }}" method="POST" id="orderForm">
        @csrf
        
        {{-- Customer & Date Section --}}
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-user me-2"></i>{{__('Order Information')}}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <label for="customer_id" class="form-label">{{__('Customer')}} <span class="text-danger">*</span></label>
                        <select name="customer_id" id="customer_id" class="form-select @error('customer_id') is-invalid @enderror" required>
                            <option value="">{{__('Select Customer')}}</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }} {{ $customer->phone ? '- ' . $customer->phone : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label for="order_date" class="form-label">{{__('Order Date')}} <span class="text-danger">*</span></label>
                        <input type="date" name="order_date" id="order_date" 
                               class="form-control @error('order_date') is-invalid @enderror" 
                               value="{{ old('order_date', now()->format('Y-m-d')) }}" required>
                        @error('order_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label for="notes" class="form-label">{{__('Notes')}}</label>
                        <input type="text" name="notes" id="notes" class="form-control" 
                               value="{{ old('notes') }}" placeholder="{{__('Optional notes')}}">
                    </div>
                </div>
            </div>
        </div>

        {{-- Order Items Section --}}
        <div class="card mb-4">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-box me-2"></i>{{__('Order Items')}}</h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal">
                    <i class="fas fa-plus"></i> {{__('Add Product')}}
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="items_table">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40%">{{__('Product')}}</th>
                                <th class="text-center" style="width: 15%">{{__('Stock')}}</th>
                                <th class="text-center" style="width: 15%">{{__('Quantity')}}</th>
                                <th class="text-end" style="width: 15%">{{__('Unit Price')}}</th>
                                <th class="text-end" style="width: 10%">{{__('Subtotal')}}</th>
                                <th style="width: 5%"></th>
                            </tr>
                        </thead>
                        <tbody id="orderItemsBody">
                            <tr id="emptyRow">
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    {{__('No products added yet. Click "Add Product" to start.')}}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="4" class="text-end"><strong>{{__('Total')}}:</strong></td>
                                <td class="text-end"><strong id="orderTotal">Ks 0.00</strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Submit Buttons --}}
        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('orders.index') }}" class="btn btn-secondary">
                <i class="fas fa-times"></i> {{__('Cancel')}}
            </a>
            <button type="submit" class="btn btn-success" id="submitBtn" disabled>
                <i class="fas fa-save"></i> {{__('Create Order')}}
            </button>
        </div>
    </form>
</div>

{{-- Product Selection Modal --}}
<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="productModalLabel">
                    <i class="fas fa-box me-2"></i>{{__('Select Products')}}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- Search & Filter --}}
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="productSearch" 
                                   placeholder="{{__('Search by name or barcode...')}}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="categoryFilter">
                            <option value="">{{__('All Categories')}}</option>
                            @php
                                $categories = \App\Models\Category::orderBy('name')->get();
                            @endphp
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="stockFilter">
                            <option value="">{{__('All Stock Levels')}}</option>
                            <option value="in_stock">{{__('In Stock')}}</option>
                            <option value="low_stock">{{__('Low Stock')}}</option>
                            <option value="out_of_stock">{{__('Out of Stock')}}</option>
                        </select>
                    </div>
                </div>

                {{-- Products Grid --}}
                <div class="row" id="productsGrid">
                    @foreach($products as $product)
                    <div class="col-md-4 col-lg-3 mb-3 product-card" 
                         data-name="{{ strtolower($product->name) }}"
                         data-barcode="{{ strtolower($product->barcode ?? '') }}"
                         data-category="{{ $product->category_id }}"
                         data-stock="{{ $product->quantity }}">
                        <div class="card h-100 product-item {{ $product->quantity <= 0 ? 'border-danger' : ($product->quantity <= $product->minimum_stock_level ? 'border-warning' : '') }}"
                             data-product-id="{{ $product->id }}"
                             data-product-name="{{ $product->name }}"
                             data-product-price="{{ $product->price }}"
                             data-product-stock="{{ $product->quantity }}"
                             data-product-unit="{{ $product->unit ?? 'pcs' }}">
                            <div class="card-body p-2">
                                @if($product->image)
                                    <img src="{{ asset('storage/' . $product->image) }}" 
                                         class="img-fluid rounded mb-2" 
                                         style="height: 80px; width: 100%; object-fit: cover;"
                                         alt="{{ $product->name }}">
                                @else
                                    <div class="bg-light rounded mb-2 d-flex align-items-center justify-content-center" 
                                         style="height: 80px;">
                                        <i class="fas fa-box fa-2x text-muted"></i>
                                    </div>
                                @endif
                                <h6 class="card-title mb-1 text-truncate" title="{{ $product->name }}">
                                    {{ $product->name }}
                                </h6>
                                <p class="card-text mb-1">
                                    <small class="text-muted">{{ $product->category->name ?? 'Uncategorized' }}</small>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-primary">Ks {{ number_format($product->price, 0) }}</span>
                                    <span class="badge {{ $product->quantity <= 0 ? 'bg-danger' : ($product->quantity <= $product->minimum_stock_level ? 'bg-warning' : 'bg-success') }}">
                                        {{ $product->quantity }} {{ $product->unit ?? 'pcs' }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-footer p-2 bg-transparent">
                                <div class="input-group input-group-sm">
                                    <button type="button" class="btn btn-outline-secondary qty-minus">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" class="form-control text-center product-qty" 
                                           value="1" min="1" max="{{ $product->quantity }}"
                                           {{ $product->quantity <= 0 ? 'disabled' : '' }}>
                                    <button type="button" class="btn btn-outline-secondary qty-plus">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                    <button type="button" class="btn btn-primary add-product-btn"
                                            {{ $product->quantity <= 0 ? 'disabled' : '' }}>
                                        <i class="fas fa-cart-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                @if($products->isEmpty())
                    <div class="text-center py-5">
                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                        <p class="text-muted">{{__('No products available')}}</p>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <div class="me-auto">
                    <span class="text-muted">{{__('Selected')}}: <strong id="selectedCount">0</strong> {{__('items')}}</span>
                </div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> {{__('Close')}}
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .product-item {
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .product-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .product-item.selected {
        border: 2px solid #0d6efd !important;
        background-color: #f0f7ff;
    }
    .product-item.border-danger {
        opacity: 0.7;
    }
    .product-qty {
        max-width: 60px;
    }
    #productsGrid {
        max-height: 60vh;
        overflow-y: auto;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let orderItems = [];
    let itemIndex = 0;
    
    const orderItemsBody = document.getElementById('orderItemsBody');
    const emptyRow = document.getElementById('emptyRow');
    const orderTotal = document.getElementById('orderTotal');
    const submitBtn = document.getElementById('submitBtn');
    const selectedCount = document.getElementById('selectedCount');
    
    // Product search
    document.getElementById('productSearch').addEventListener('input', filterProducts);
    document.getElementById('categoryFilter').addEventListener('change', filterProducts);
    document.getElementById('stockFilter').addEventListener('change', filterProducts);
    
    function filterProducts() {
        const search = document.getElementById('productSearch').value.toLowerCase();
        const category = document.getElementById('categoryFilter').value;
        const stockFilter = document.getElementById('stockFilter').value;
        
        document.querySelectorAll('.product-card').forEach(card => {
            const name = card.dataset.name;
            const barcode = card.dataset.barcode;
            const cardCategory = card.dataset.category;
            const stock = parseInt(card.dataset.stock);
            
            let show = true;
            
            // Search filter
            if (search && !name.includes(search) && !barcode.includes(search)) {
                show = false;
            }
            
            // Category filter
            if (category && cardCategory !== category) {
                show = false;
            }
            
            // Stock filter
            if (stockFilter) {
                const minStock = parseInt(card.querySelector('.product-item').dataset.productStock);
                if (stockFilter === 'in_stock' && stock <= 0) show = false;
                if (stockFilter === 'low_stock' && (stock <= 0 || stock > 10)) show = false;
                if (stockFilter === 'out_of_stock' && stock > 0) show = false;
            }
            
            card.style.display = show ? '' : 'none';
        });
    }
    
    // Quantity buttons in modal
    document.querySelectorAll('.qty-minus').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.parentElement.querySelector('.product-qty');
            let val = parseInt(input.value) || 1;
            if (val > 1) input.value = val - 1;
        });
    });
    
    document.querySelectorAll('.qty-plus').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.parentElement.querySelector('.product-qty');
            const max = parseInt(input.max) || 999;
            let val = parseInt(input.value) || 1;
            if (val < max) input.value = val + 1;
        });
    });
    
    // Add product to order
    document.querySelectorAll('.add-product-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const card = this.closest('.product-item');
            const productId = card.dataset.productId;
            const productName = card.dataset.productName;
            const productPrice = parseFloat(card.dataset.productPrice);
            const productStock = parseInt(card.dataset.productStock);
            const productUnit = card.dataset.productUnit;
            const quantity = parseInt(this.parentElement.querySelector('.product-qty').value) || 1;
            
            // Check if product already in order
            const existingIndex = orderItems.findIndex(item => item.productId === productId);
            
            if (existingIndex >= 0) {
                // Update quantity
                const newQty = orderItems[existingIndex].quantity + quantity;
                if (newQty > productStock) {
                    alert('{{__("Cannot add more than available stock")}} (' + productStock + ' ' + productUnit + ')');
                    return;
                }
                orderItems[existingIndex].quantity = newQty;
                updateOrderItemRow(existingIndex);
            } else {
                // Add new item
                if (quantity > productStock) {
                    alert('{{__("Cannot add more than available stock")}} (' + productStock + ' ' + productUnit + ')');
                    return;
                }
                
                orderItems.push({
                    index: itemIndex,
                    productId: productId,
                    productName: productName,
                    price: productPrice,
                    quantity: quantity,
                    stock: productStock,
                    unit: productUnit
                });
                
                addOrderItemRow(orderItems[orderItems.length - 1]);
                itemIndex++;
            }
            
            // Visual feedback
            card.classList.add('selected');
            setTimeout(() => card.classList.remove('selected'), 500);
            
            // Reset quantity input
            this.parentElement.querySelector('.product-qty').value = 1;
            
            updateOrderTotal();
            updateSelectedCount();
        });
    });
    
    function addOrderItemRow(item) {
        if (emptyRow) emptyRow.style.display = 'none';
        
        const row = document.createElement('tr');
        row.id = 'item-row-' + item.index;
        row.innerHTML = `
            <td>
                <strong>${item.productName}</strong>
                <input type="hidden" name="products[${item.index}][product_id]" value="${item.productId}">
            </td>
            <td class="text-center">
                <span class="badge bg-secondary">${item.stock} ${item.unit}</span>
            </td>
            <td class="text-center">
                <div class="input-group input-group-sm" style="max-width: 120px; margin: 0 auto;">
                    <button type="button" class="btn btn-outline-secondary item-qty-minus" data-index="${item.index}">-</button>
                    <input type="number" name="products[${item.index}][quantity]" 
                           class="form-control text-center item-qty" 
                           value="${item.quantity}" min="1" max="${item.stock}"
                           data-index="${item.index}">
                    <button type="button" class="btn btn-outline-secondary item-qty-plus" data-index="${item.index}">+</button>
                </div>
            </td>
            <td class="text-end">
                <input type="number" name="products[${item.index}][price]" 
                       class="form-control form-control-sm text-end item-price" 
                       value="${item.price.toFixed(2)}" step="0.01" min="0"
                       data-index="${item.index}" style="max-width: 120px; margin-left: auto;">
            </td>
            <td class="text-end item-subtotal" data-index="${item.index}">
                Ks ${(item.quantity * item.price).toFixed(2)}
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger remove-item" data-index="${item.index}">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        
        orderItemsBody.appendChild(row);
        attachRowEvents(row, item.index);
    }
    
    function updateOrderItemRow(index) {
        const item = orderItems[index];
        const row = document.getElementById('item-row-' + item.index);
        if (row) {
            row.querySelector('.item-qty').value = item.quantity;
            row.querySelector('.item-subtotal').textContent = 'Ks ' + (item.quantity * item.price).toFixed(2);
        }
    }
    
    function attachRowEvents(row, index) {
        // Quantity minus
        row.querySelector('.item-qty-minus').addEventListener('click', function() {
            const itemIndex = orderItems.findIndex(i => i.index === index);
            if (itemIndex >= 0 && orderItems[itemIndex].quantity > 1) {
                orderItems[itemIndex].quantity--;
                updateOrderItemRow(itemIndex);
                updateOrderTotal();
            }
        });
        
        // Quantity plus
        row.querySelector('.item-qty-plus').addEventListener('click', function() {
            const itemIndex = orderItems.findIndex(i => i.index === index);
            if (itemIndex >= 0 && orderItems[itemIndex].quantity < orderItems[itemIndex].stock) {
                orderItems[itemIndex].quantity++;
                updateOrderItemRow(itemIndex);
                updateOrderTotal();
            }
        });
        
        // Quantity input change
        row.querySelector('.item-qty').addEventListener('change', function() {
            const itemIndex = orderItems.findIndex(i => i.index === index);
            if (itemIndex >= 0) {
                let qty = parseInt(this.value) || 1;
                qty = Math.max(1, Math.min(qty, orderItems[itemIndex].stock));
                this.value = qty;
                orderItems[itemIndex].quantity = qty;
                updateOrderItemRow(itemIndex);
                updateOrderTotal();
            }
        });
        
        // Price change
        row.querySelector('.item-price').addEventListener('change', function() {
            const itemIndex = orderItems.findIndex(i => i.index === index);
            if (itemIndex >= 0) {
                orderItems[itemIndex].price = parseFloat(this.value) || 0;
                updateOrderItemRow(itemIndex);
                updateOrderTotal();
            }
        });
        
        // Remove item
        row.querySelector('.remove-item').addEventListener('click', function() {
            const itemIndex = orderItems.findIndex(i => i.index === index);
            if (itemIndex >= 0) {
                orderItems.splice(itemIndex, 1);
                row.remove();
                updateOrderTotal();
                updateSelectedCount();
                
                if (orderItems.length === 0 && emptyRow) {
                    emptyRow.style.display = '';
                }
            }
        });
    }
    
    function updateOrderTotal() {
        const total = orderItems.reduce((sum, item) => sum + (item.quantity * item.price), 0);
        orderTotal.textContent = 'Ks ' + total.toFixed(2);
        submitBtn.disabled = orderItems.length === 0;
    }
    
    function updateSelectedCount() {
        selectedCount.textContent = orderItems.length;
    }
    
    // Form validation
    document.getElementById('orderForm').addEventListener('submit', function(e) {
        if (orderItems.length === 0) {
            e.preventDefault();
            alert('{{__("Please add at least one product to the order.")}}');
            return false;
        }
        
        // Validate stock
        for (const item of orderItems) {
            if (item.quantity > item.stock) {
                e.preventDefault();
                alert(`{{__("Insufficient stock for")}} ${item.productName}. {{__("Available")}}: ${item.stock}`);
                return false;
            }
        }
        
        return true;
    });
});
</script>
@endpush

@endsection
