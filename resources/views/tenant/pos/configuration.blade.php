@extends('tenant.layouts.app')

@section('content')
    <tenant-configuration-software-eqdocs route="{{route('tenant.configuration')}}"></tenant-configuration-software-eqdocs>
    <tenant-pos-configuration :configuration="{{ json_encode($configuration)}}">
    </tenant-pos-configuration>
@endsection
