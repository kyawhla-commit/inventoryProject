@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>Record New Sale</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('sales.index') }}"> Back</a>
            </div>
        </div>
    </div>

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
    @if ($message = Session::get('error'))
        <div class="alert alert-danger">
            <p>{{ $message }}</p>
        </div>
    @endif


    <form action="{{ route('sales.store') }}" method="POST">
        @csrf

        <div class="row">
            <div class="col-xs-12 col-sm-12 col-md-6">
                <div class="form-group">
                    <strong>Sale Date:</strong>
                    <input type="date" name="sale_date" class="form-control" value="{{ old('sale_date', date('Y-m-d')) }}">
                </div>
            </div>
        </div>

        <h4 class="mt-4">Products</h4>
        <table class="table table-bordered" id="products_table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Subtotal</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <!-- Product rows will be added here dynamically -->
            </tbody>
        </table>
        <button type="button" class="btn btn-primary" id="add_product_row">Add Product</button>
        <div class="row mt-3">
            <div class="col-xs-12 col-sm-12 col-md-12 text-right">
                <h4>Total Amount: $<span id="total_amount">0.00</span></h4>
            </div>
        </div>


        <div class="col-xs-12 col-sm-12 col-md-12 text-center mt-3">
            <button type="submit" class="btn btn-primary">Submit</button>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const addProductRowBtn = document.getElementById('add_product_row');
            const productsTableBody = document.querySelector('#products_table tbody');
            let productIndex = 0;

            addProductRowBtn.addEventListener('click', function () {
                const newRow = document.createElement('tr');
                newRow.innerHTML = `
                    <td>
                        <select name="products[${productIndex}][product_id]" class="form-control product-select" required>
                            <option value="">Select Product</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" data-price="{{ $product->price }}" data-stock="{{ $product->quantity }}">{{ $product->name }} (In Stock: {{ $product->quantity }})</option>
                            @endforeach
                        </select>
                    </td>
                    <td><input type="number" name="products[${productIndex}][quantity]" class="form-control quantity" min="1" value="1" required></td>
                    <td><input type="number" name="products[${productIndex}][unit_price]" class="form-control unit-price" step="0.01" min="0" required></td>
                    <td><input type="text" class="form-control subtotal" readonly></td>
                    <td><button type="button" class="btn btn-danger remove-product-row">Remove</button></td>
                `;
                productsTableBody.appendChild(newRow);
                productIndex++;
            });

            productsTableBody.addEventListener('click', function (e) {
                if (e.target.classList.contains('remove-product-row')) {
                    e.target.closest('tr').remove();
                    updateTotalAmount();
                }
            });

            productsTableBody.addEventListener('change', function (e) {
                const row = e.target.closest('tr');
                if (e.target.classList.contains('product-select')) {
                    const selectedOption = e.target.options[e.target.selectedIndex];
                    const price = selectedOption.dataset.price || 0;
                    row.querySelector('.unit-price').value = parseFloat(price).toFixed(2);
                    const stock = selectedOption.dataset.stock || 0;
                    row.querySelector('.quantity').max = stock;
                }
                updateRowSubtotal(row);
            });
             productsTableBody.addEventListener('input', function (e) {
                const row = e.target.closest('tr');
                 if (e.target.classList.contains('quantity') || e.target.classList.contains('unit-price')) {
                    updateRowSubtotal(row);
                }
            });

            function updateRowSubtotal(row) {
                const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
                const unitPrice = parseFloat(row.querySelector('.unit-price').value) || 0;
                const subtotal = quantity * unitPrice;
                row.querySelector('.subtotal').value = '$' + subtotal.toFixed(2);
                updateTotalAmount();
            }

            function updateTotalAmount() {
                let total = 0;
                document.querySelectorAll('#products_table tbody tr').forEach(row => {
                    const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
                    const unitPrice = parseFloat(row.querySelector('.unit-price').value) || 0;
                    total += quantity * unitPrice;
                });
                document.getElementById('total_amount').textContent = total.toFixed(2);
            }
        });
    </script>
@endsection
