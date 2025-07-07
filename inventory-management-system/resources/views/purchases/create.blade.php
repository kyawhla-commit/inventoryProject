@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Add Purchase</h1>

    <form action="{{ route('purchases.store') }}" method="POST">
        @csrf
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="supplier_id">Supplier</label>
                    <select name="supplier_id" id="supplier_id" class="form-control" required>
                        <option value="">Select Supplier</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="purchase_date">Purchase Date</label>
                    <input type="date" name="purchase_date" id="purchase_date" class="form-control" required>
                </div>
            </div>
        </div>

        <h3 class="mt-4">Products</h3>
        <table class="table table-bordered" id="products_table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Cost</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <!-- Product rows will be added here dynamically -->
            </tbody>
        </table>

        <button type="button" class="btn btn-secondary" id="add_row">Add Product</button>
        <button type="submit" class="btn btn-primary">Save Purchase</button>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        let productRowIndex = 0;
        const addRowBtn = document.getElementById('add_row');
        const productsTableBody = document.querySelector('#products_table tbody');

        addRowBtn.addEventListener('click', function () {
            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td>
                    <select name="products[${productRowIndex}][product_id]" class="form-control" required>
                        <option value="">Select Product</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td><input type="number" name="products[${productRowIndex}][quantity]" class="form-control" min="1" required></td>
                <td><input type="number" name="products[${productRowIndex}][cost]" class="form-control" step="0.01" min="0" required></td>
                <td><button type="button" class="btn btn-danger remove-row">Remove</button></td>
            `;
            productsTableBody.appendChild(newRow);
            productRowIndex++;
        });

        productsTableBody.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-row')) {
                e.target.closest('tr').remove();
            }
        });
    });
</script>
@endsection
