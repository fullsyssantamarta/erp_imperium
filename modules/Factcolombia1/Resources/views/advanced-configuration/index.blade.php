@extends('tenant.layouts.app')

@section('content')
    <tenant-advanced-configuration-index :user="{{ auth()->user() }}"></tenant-advanced-configuration-index>
@endsection
