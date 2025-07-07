@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>Sales</h2>
            </div>
            <div class="pull-right mb-2">
                <a class="btn btn-success" href="{{ route('sales.create') }}"> Record New Sale</a>
            </div>
        </div>
    </div>

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif
    @if ($message = Session::get('error'))
        <div class="alert alert-danger">
            <p>{{ $message }}</p>
        </div>
    @endif

    <table class="table table-bordered">
        <tr>
            <th>No</th>
            <th>Sale Date</th>
            <th>Total Amount</th>
            <th width="280px">Action</th>
        </tr>
        @foreach ($sales as $sale)
        <tr>
            <td>{{ ++$i }}</td>
            <td>{{ $sale->sale_date }}</td>
            <td>${{ number_format($sale->total_amount, 2) }}</td>
            <td>
                <form action="{{ route('sales.destroy',$sale->id) }}" method="POST">
                    <a class="btn btn-info" href="{{ route('sales.show',$sale->id) }}">Show</a>
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </td>
        </tr>
        @endforeach
    </table>

    {!! $sales->links() !!}
@endsection
