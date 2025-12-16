@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{__('Edit Order')}} #{{ $order->id }}</h1>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">{{__('Order Information')}}</h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <p><strong>{{__('Customer')}}:</strong> {{ $order->customer->name ?? 'N/A' }}</p>
                    <p><strong>{{__('Order Date')}}:</strong> {{ $order->order_date->format('Y-m-d') }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>{{__('Status')}}:</strong> <span class="badge bg-{{ $order->badge_class }}">{{ __(ucfirst($order->status)) }}</span></p>
                    <p><strong>{{__('Total Amount')}}:</strong> {{ number_format($order->total_amount, 2) }} {{ config('settings.currency_symbol', '$') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{__('Order Items')}}</h5>
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="fas fa-plus"></i> {{__('Add Product')}}
            </button>
        </div>
        <div class="card-body">
            @if($order->items->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>{{__('Product')}}</th>
                                <th class="text-end">{{__('Price')}}</th>
                                <th class="text-center">{{__('Quantity')}}</th>
                                <th class="text-end">{{__('Total')}}</th>
                                <th class="text-end">{{__('Actions')}}</th>
                            </tr>
                        </thead>
                        <tbody id="order-items">
                            @foreach($order->items as $item)
                            <tr data-item-id="{{ $item->id }}">
                                <td>{{ $item->product->name }}</td>
                                <td class="text-end" id="price-{{ $item->id }}">{{ number_format($item->price, 2) }}</td>
                                <td class="text-center">
                                    <div class="input-group input-group-sm" style="max-width: 120px;">
                                        <button type="button" class="btn btn-outline-secondary btn-sm update-quantity" data-action="decrement" data-item-id="{{ $item->id }}">-</button>
                                        <input type="number" class="form-control text-center quantity-input" value="{{ $item->quantity }}" min="1" data-item-id="{{ $item->id }}" data-original-quantity="{{ $item->quantity }}">
                                        <button type="button" class="btn btn-outline-secondary btn-sm update-quantity" data-action="increment" data-item-id="{{ $item->id }}">+</button>
                                    </div>
                                </td>
                                <td class="text-end item-total" id="total-{{ $item->id }}">
                                    {{ number_format($item->price * $item->quantity, 2) }}
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-danger remove-item" data-item-id="{{ $item->id }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>{{__('Subtotal')}}:</strong></td>
                                <td class="text-end" id="subtotal">{{ number_format($order->total_amount, 2) }}</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end"><strong>{{__('Tax')}}:</strong></td>
                                <td class="text-end" id="tax">0.00</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end"><strong>{{__('Total')}}:</strong></td>
                                <td class="text-end" id="total">{{ number_format($order->total_amount, 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="alert alert-info">{{__('No items in this order.')}}</div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">{{__('Order Actions')}}</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('orders.update', $order->id) }}" method="POST" id="order-form">
                @csrf
                @method('PUT')
                <input type="hidden" name="status" id="order-status" value="{{ $order->status }}">
                <input type="hidden" name="items" id="order-items-data" value="">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="status" class="form-label">{{__('Order Status')}}</label>
                        <select class="form-select" id="status" name="status_display" onchange="document.getElementById('order-status').value = this.value">
                            <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>{{__('Pending')}}</option>
                            <option value="confirmed" {{ $order->status === 'confirmed' ? 'selected' : '' }}>{{__('Confirmed')}}</option>
                            <option value="processing" {{ $order->status === 'processing' ? 'selected' : '' }}>{{__('Processing')}}</option>
                            <option value="shipped" {{ $order->status === 'shipped' ? 'selected' : '' }}>{{__('Shipped')}}</option>
                            <option value="completed" {{ $order->status === 'completed' ? 'selected' : '' }}>{{__('Completed')}}</option>
                            <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>{{__('Cancelled')}}</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="notes" class="form-label">{{__('Order Notes')}}</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2">{{ old('notes', $order->notes ?? '') }}</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('orders.index') }}" class="btn btn-secondary">{{__('Back to Orders')}}</a>
                    <div>
                        <button type="button" class="btn btn-outline-danger me-2" onclick="confirmDelete()">
                            <i class="fas fa-trash"></i> {{__('Delete Order')}}
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> {{__('Save Changes')}}
                        </button>
                    </div>
                </div>
            </form>

            <form id="delete-form" action="{{ route('orders.destroy', $order->id) }}" method="POST" class="d-none">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addProductModalLabel">{{__('Add Product to Order')}}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="product_id" class="form-label">{{__('Product')}}</label>
                    <select class="form-select" id="product_id" required>
                        <option value="">{{__('Select a product')}}</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" data-price="{{ $product->price }}">
                                {{ $product->name }} ({{ number_format($product->price, 2) }} {{ config('settings.currency_symbol', '$') }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="quantity" class="form-label">{{__('Quantity')}}</label>
                    <input type="number" class="form-control" id="quantity" min="1" value="1" required>
                </div>
                <div class="mb-3">
                    <label for="price" class="form-label">{{__('Unit Price')}}</label>
                    <input type="number" class="form-control" id="price" step="0.01" min="0" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{__('Cancel')}}</button>
                <button type="button" class="btn btn-primary" id="add-to-order">{{__('Add to Order')}}</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Store order items data
    let orderItems = [];
    
    // Initialize order items from server data
    @foreach($order->items as $item)
        orderItems.push({
            id: {{ $item->id }},
            product_id: {{ $item->product_id }},
            quantity: {{ $item->quantity }},
            price: {{ (float)$item->price }},
            total: {{ (float)($item->price * $item->quantity) }}
        });
    @endforeach

    // Initialize the order items data
    updateOrderItemsData();

    // Update quantity buttons
    $(document).on('click', '.update-quantity', function() {
        const action = $(this).data('action');
        const itemId = $(this).data('item-id');
        const input = $(`input[data-item-id="${itemId}"]`);
        let quantity = parseInt(input.val());
        
        if (action === 'increment') {
            quantity++;
        } else if (action === 'decrement' && quantity > 1) {
            quantity--;
        }
        
        input.val(quantity);
        updateItemQuantity(itemId, quantity);
    });

    // Handle direct input change
    $(document).on('change', '.quantity-input', function() {
        const itemId = $(this).data('item-id');
        let quantity = parseInt($(this).val()) || 1;
        
        if (quantity < 1) {
            quantity = 1;
            $(this).val(1);
        }
        
        updateItemQuantity(itemId, quantity);
    });

    // Update item quantity and totals
    function updateItemQuantity(itemId, quantity) {
        const item = orderItems.find(item => item.id == itemId);
        if (!item) return;
        
        item.quantity = quantity;
        item.total = item.price * quantity;
        
        // Update the display
        $(`#total-${itemId}`).text(item.total.toFixed(2));
        updateOrderTotals();
        updateOrderItemsData();
    }

    // Remove item from order
    $(document).on('click', '.remove-item', function() {
        const itemId = $(this).data('item-id');
        if (confirm('{{ __("Are you sure you want to remove this item?") }}')) {
            orderItems = orderItems.filter(item => item.id != itemId);
            $(`tr[data-item-id="${itemId}"]`).remove();
            updateOrderTotals();
            updateOrderItemsData();
            
            if (orderItems.length === 0) {
                $('#order-items').html('<tr><td colspan="5" class="text-center">{{__("No items in this order.")}}</td></tr>');
            }
        }
    });

    // Add product to order
    $('#add-to-order').click(function() {
        const productId = $('#product_id').val();
        const productName = $('#product_id option:selected').text().split(' (')[0];
        const price = parseFloat($('#price').val());
        let quantity = parseInt($('#quantity').val());
        
        if (!productId || isNaN(quantity) || quantity < 1 || isNaN(price) || price < 0) {
            alert('{{__("Please fill in all fields with valid values.")}}');
            return;
        }
        
        // Check if product already exists in order
        const existingItem = orderItems.find(item => item.product_id == productId && item.price == price);
        
        if (existingItem) {
            // Update existing item quantity
            existingItem.quantity += quantity;
            existingItem.total = existingItem.price * existingItem.quantity;
            
            // Update the display
            const input = $(`input[data-item-id="${existingItem.id}"]`);
            input.val(existingItem.quantity);
            input.attr('data-original-quantity', existingItem.quantity);
            $(`#total-${existingItem.id}`).text(existingItem.total.toFixed(2));
        } else {
            // Add new item
            const newItem = {
                id: 'new-' + Date.now(),
                product_id: parseInt(productId),
                quantity: quantity,
                price: price,
                total: price * quantity
            };
            
            orderItems.push(newItem);
            
            // Add to the display
            const newRow = `
                <tr data-item-id="${newItem.id}">
                    <td>${productName}</td>
                    <td class="text-end" id="price-${newItem.id}">${price.toFixed(2)}</td>
                    <td class="text-center">
                        <div class="input-group input-group-sm" style="max-width: 120px;">
                            <button type="button" class="btn btn-outline-secondary btn-sm update-quantity" data-action="decrement" data-item-id="${newItem.id}">-</button>
                            <input type="number" class="form-control text-center quantity-input" value="${quantity}" min="1" data-item-id="${newItem.id}" data-original-quantity="${quantity}">
                            <button type="button" class="btn btn-outline-secondary btn-sm update-quantity" data-action="increment" data-item-id="${newItem.id}">+</button>
                        </div>
                    </td>
                    <td class="text-end item-total" id="total-${newItem.id}">${(price * quantity).toFixed(2)}</td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-danger remove-item" data-item-id="${newItem.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            
            if ($('#order-items tr').length === 1 && $('#order-items tr td').attr('colspan')) {
                $('#order-items').html(newRow);
            } else {
                $('#order-items').append(newRow);
            }
        }
        
        // Reset the form
        $('#product_id').val('');
        $('#quantity').val(1);
        $('#price').val('');
        
        // Update totals and close modal
        updateOrderTotals();
        updateOrderItemsData();
        $('#addProductModal').modal('hide');
    });

    // Update price field when product is selected
    $('#product_id').change(function() {
        const price = $(this).find('option:selected').data('price');
        if (price) {
            $('#price').val(price);
        } else {
            $('#price').val('');
        }
    });

    // Update order totals
    function updateOrderTotals() {
        let subtotal = orderItems.reduce((sum, item) => sum + item.total, 0);
        const taxRate = 0; // You can implement tax calculation as needed
        const tax = subtotal * taxRate;
        const total = subtotal + tax;
        
        $('#subtotal').text(subtotal.toFixed(2));
        $('#tax').text(tax.toFixed(2));
        $('#total').text(total.toFixed(2));
    }
    
    // Update the hidden input with order items data
    function updateOrderItemsData() {
        // Ensure all numeric values are properly converted
        const formattedItems = orderItems.map(item => ({
            id: item.id,
            product_id: parseInt(item.product_id),
            quantity: parseInt(item.quantity),
            price: parseFloat(item.price),
            total: parseFloat(item.total)
        }));
        
        const itemsJson = JSON.stringify(formattedItems);
        $('#order-items-data').val(itemsJson);
        
        // Debug logging
        console.log('Updating order items data:', formattedItems);
        console.log('JSON string:', itemsJson);
        
        return formattedItems;
    }
    
    // Confirm order deletion
    function confirmDelete() {
        if (confirm('{{ __("Are you sure you want to delete this order? This action cannot be undone.") }}')) {
            $('#delete-form').submit();
        }
    }
    
// Wait for the document to be fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize totals
        updateOrderTotals();
        
        // Initialize order items data
        updateOrderItemsData();
        
        // Handle form submission
        document.getElementById('order-form').addEventListener('submit', function(e) {
            // Make sure the order items data is up to date
            updateOrderItemsData();
            
            // Ensure the status is set correctly
            document.getElementById('order-status').value = document.getElementById('status').value;
            
            // Log the data being submitted for debugging
            console.log('Submitting form with items:', orderItems);
            console.log('Form data:', new URLSearchParams(new FormData(this)).toString());
            
            // Form will be submitted normally after this
            return true;
        });
        
        // Initialize any other jQuery dependent code here
        $(function() {
            // jQuery dependent code can go here
        });
    });
</script>
@endpush

@endsection
