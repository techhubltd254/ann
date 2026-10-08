@extends('layouts.app')

@section('title', 'Security — Dashboard')
@section('content')
<div class="max-w-7xl mx-auto px-5 py-12">
    <div class="flex items-center gap-3 mb-8">
        <a href="{{ route('dashboard.index') }}" class="text-gray-400 hover:text-gray-600">&larr; Dashboard</a>
        <span class="text-gray-300">/</span>
        <h1 class="text-2xl font-black text-gray-900">{{ ucwords(str_replace('-', ' ', 'security')) }}</h1>
    </div>

    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-600 rounded-xl px-4 py-3 mb-6 text-sm">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <p class="text-gray-400 text-sm">{{ ucwords(str_replace('-', ' ', 'security')) }} management coming soon.</p>
    </div>
</div>
@endsection
