@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Purchases</h1>
        <a href="{{ route('purchases.create') }}" class="btn btn-primary">Add Purchase</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Supplier</th>
                <th>Purchase Date</th>
                <th>Total Amount</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($purchases as $purchase)
                <tr>
                    <td>#{{ $purchase->id }}</td>
                    <td>{{ $purchase->supplier->name }}</td>
                    <td>{{ $purchase->purchase_date }}</td>
                    <td>${{ number_format($purchase->total_amount, 2) }}</td>
                    <td>
                        <a href="{{ route('purchases.show', $purchase->id) }}" class="btn btn-sm btn-info">View</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">No purchases found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $purchases->links() }}
</div>
@endsection
