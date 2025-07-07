@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Sales Report</h1>
        <button class="btn btn-primary" onclick="window.print()">Print</button>
    </div>

    <div class="card">
        <div class="card-header">
            Sales from <strong>{{ $startDate }}</strong> to <strong>{{ $endDate }}</strong>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Sale ID</th>
                        <th>Date</th>
                        <th>Products</th>
                        <th>Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sales as $sale)
                        <tr>
                            <td>#{{ $sale->id }}</td>
                            <td>{{ $sale->sale_date }}</td>
                            <td>
                                <ul>
                                    @foreach ($sale->items as $item)
                                        <li>{{ $item->product->name }} ({{ $item->quantity }} x ${{ number_format($item->unit_price, 2) }})</li>
                                    @endforeach
                                </ul>
                            </td>
                            <td>${{ number_format($sale->total_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">No sales found for this period.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-end">Total Sales:</th>
                        <th>${{ number_format($totalSales, 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
