@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
    </div>

    <!-- Content Row -->
    <div class="row">

        <!-- Total Products Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Products</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalProducts }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-box fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Customers Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Customers</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalCustomers }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Suppliers Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Suppliers</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalSuppliers }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-truck fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Sales Amount Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Total Sales</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">${{ number_format($totalSalesAmount, 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row -->
    <div class="row">
        <!-- Recent Sales -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Sales</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentSales as $sale)
                                    <tr>
                                        <td><a href="{{ route('sales.show', $sale->id) }}">#{{ $sale->id }}</a></td>
                                        <td>{{ $sale->customer->name ?? 'N/A' }}</td>
                                        <td>{{ $sale->sale_date }}</td>
                                        <td>${{ number_format($sale->total_amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">No recent sales found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low Stock Products -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <!-- Card Header -->
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Low Stock Products</h6>
                </div>
                <!-- Card Body -->
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($lowStockProducts as $product)
                                    <tr>
                                        <td><a href="{{ route('products.show', $product->id) }}">{{ $product->name }}</a></td>
                                        <td><span class="badge bg-danger">{{ $product->quantity }}</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center">No products are low on stock.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Monthly Sales Goal -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Monthly Sales Goal</h6>
                </div>
                <div class="card-body">
                    <h4 class="small font-weight-bold">
                        Sales Progress
                        <span class="float-right">${{ number_format($currentMonthSales, 2) }} / ${{ number_format($monthlySalesGoal, 2) }}</span>
                    </h4>
                    <div class="progress mb-4">
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $salesProgressPercentage }}%"
                             aria-valuenow="{{ $salesProgressPercentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="text-center">
                        <small>
                            {{ number_format($salesProgressPercentage, 2) }}% of your monthly goal has been reached.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Low Stock Raw Materials -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-danger">Low Stock Raw Materials</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Current Quantity</th>
                                    <th>Minimum Level</th>
                                    <th>Unit</th>
                                    <th>Supplier</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($lowStockRawMaterials as $material)
                                    <tr>
                                        <td><a href="{{ route('raw-materials.show', $material->id) }}">{{ $material->name }}</a></td>
                                        <td><span class="badge bg-danger">{{ $material->quantity }}</span></td>
                                        <td>{{ $material->minimum_stock_level }}</td>
                                        <td>{{ $material->unit }}</td>
                                        <td>{{ $material->supplier->name ?? 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No raw materials are low on stock.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
