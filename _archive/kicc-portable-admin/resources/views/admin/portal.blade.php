@extends('layouts.blank')

@section('content')
<div class="min-h-screen bg-gray-50 flex items-center justify-center p-5">
    <div class="text-center">
        <h1 class="text-3xl font-black text-gray-900 mb-2">KICC Admin Portal</h1>
        <p class="text-gray-500 mb-8">Select your portal</p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 max-w-3xl">
            <a href="/admin" class="bg-white border-2 border-blue-600/20 hover:border-blue-600 rounded-2xl p-6">
                <h2 class="font-bold text-lg">KICC Admin</h2>
                <p class="text-sm text-gray-500 mt-1">Full platform control</p>
            </a>
            <a href="/admin/national" class="bg-white border-2 border-gray-200 hover:border-gray-400 rounded-2xl p-6">
                <h2 class="font-bold text-lg">National Government</h2>
                <p class="text-sm text-gray-500 mt-1">Ministries & agencies</p>
            </a>
            <a href="/admin/county" class="bg-white border-2 border-amber-400/20 hover:border-amber-400 rounded-2xl p-6">
                <h2 class="font-bold text-lg">County Admin</h2>
                <p class="text-sm text-gray-500 mt-1">County content management</p>
            </a>
        </div>
    </div>
</div>
@endsection
