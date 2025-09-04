@extends('tenant.layouts.app')

@section('content')
    <tenant-seller-index route="{{ route('tenant.co-sellers.index') }}"></tenant-seller-index>
@endsection
