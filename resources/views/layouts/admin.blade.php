@extends('layouts.app')
@section('body_class', 'bg-gray-100')
@section('content')
<nav class="bg-white shadow-sm border-b">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex gap-6 h-12 items-center text-sm">
            <a href="{{ route('admin.ecommerce.dashboard') }}" class="font-semibold text-blue-600 hover:text-blue-800">Ecommerce</a>
            <a href="{{ route('admin.ecommerce.products') }}" class="text-gray-600 hover:text-gray-900">Products</a>
            <a href="{{ route('admin.ecommerce.orders') }}" class="text-gray-600 hover:text-gray-900">Orders</a>
            <a href="{{ route('admin.ecommerce.import.form') }}" class="text-gray-600 hover:text-gray-900">Import</a>
            <a href="{{ route('admin.ecommerce.analytics') }}" class="text-gray-600 hover:text-gray-900">Analytics</a>
            <a href="{{ route('kicc.admin') }}" class="text-gray-600 hover:text-gray-900 ml-auto">Main Admin</a>
        </div>
    </div>
</nav>
@yield('content')
@endsection