@extends('layouts.seller')
@section('title', 'New product')

@section('content')
<form method="POST" action="{{ route('seller.products.store') }}" enctype="multipart/form-data">
    @include('seller.products._form', ['isEdit' => false])
</form>
@endsection
