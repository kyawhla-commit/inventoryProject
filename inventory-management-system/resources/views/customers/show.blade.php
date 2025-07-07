@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Customer Details</h1>

    <div class="card">
        <div class="card-header">{{ $customer->name }}</div>
        <div class="card-body">
            <p><strong>Email:</strong> {{ $customer->email ?? 'N/A' }}</p>
            <p><strong>Phone:</strong> {{ $customer->phone ?? 'N/A' }}</p>
            <p><strong>Address:</strong> {{ $customer->address ?? 'N/A' }}</p>
        </div>
    </div>

    <a href="{{ route('customers.index') }}" class="btn btn-secondary mt-3">Back to List</a>
</div>
@endsection
