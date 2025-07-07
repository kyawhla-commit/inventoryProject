@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Customer</h1>

    <form action="{{ route('customers.update', $customer->id) }}" method="POST">
        @method('PUT')
        @include('customers._form')
    </form>
</div>
@endsection
