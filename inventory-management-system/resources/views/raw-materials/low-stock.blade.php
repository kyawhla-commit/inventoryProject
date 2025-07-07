@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Low Stock Raw Materials</h1>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Supplier</th>
                <th>Current Quantity</th>
                <th>Minimum Stock Level</th>
                <th>Unit</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($lowStockMaterials as $material)
                <tr>
                    <td>{{ $material->id }}</td>
                    <td><a href="{{ route('raw-materials.show', $material->id) }}">{{ $material->name }}</a></td>
                    <td>{{ $material->supplier->name ?? 'N/A' }}</td>
                    <td><span class="badge bg-danger">{{ $material->quantity }}</span></td>
                    <td>{{ $material->minimum_stock_level }}</td>
                    <td>{{ $material->unit }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">No raw materials are low on stock.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
