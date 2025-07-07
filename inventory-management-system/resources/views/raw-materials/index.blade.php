@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Raw Materials</h1>
        <a href="{{ route('raw-materials.create') }}" class="btn btn-primary">Add Raw Material</a>
    </div>

    <!-- Search Form -->
    <form action="{{ route('raw-materials.index') }}" method="GET" class="mb-3">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Search by name..." value="{{ request('search') }}">
            <div class="input-group-append">
                <button class="btn btn-outline-secondary" type="submit">Search</button>
            </div>
        </div>
    </form>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Supplier</th>
                <th>Quantity</th>
                <th>Unit</th>
                <th>Cost Per Unit</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rawMaterials as $material)
                <tr>
                    <td>{{ $material->id }}</td>
                    <td><a href="{{ route('raw-materials.show', $material->id) }}">{{ $material->name }}</a></td>
                    <td>{{ $material->supplier->name ?? 'N/A' }}</td>
                    <td>{{ $material->quantity }}</td>
                    <td>{{ $material->unit }}</td>
                    <td>${{ number_format($material->cost_per_unit, 2) }}</td>
                    <td>
                        <a href="{{ route('raw-materials.edit', $material->id) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('raw-materials.destroy', $material->id) }}" method="POST" style="display:inline-block;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No raw materials found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $rawMaterials->links() }}
</div>
@endsection
