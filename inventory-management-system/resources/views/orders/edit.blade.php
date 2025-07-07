@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Order #{{ $order->id }}</h1>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('orders.update', $order->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="status" class="form-label">Order Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="processing" {{ $order->status === 'processing' ? 'selected' : '' }}>Processing</option>
                        <option value="shipped" {{ $order->status === 'shipped' ? 'selected' : '' }}>Shipped</option>
                        <option value="completed" {{ $order->status === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Update Status</button>
                <a href="{{ route('orders.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
