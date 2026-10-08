@extends('layouts.app')
@section('title', 'Safety & Security — KICC')
@section('content')
<div class="pt-24 max-w-7xl mx-auto px-5 py-10">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-2xl font-black text-gray-900">Safety & Security</h1>
        <div class="flex gap-2">
            <a href="{{ route('safety.alerts') }}" class="px-3 py-1.5 rounded-full text-[10px] font-bold bg-[#0B0B0B] text-white">Alerts</a>
            <a href="{{ route('safety.report') }}" class="px-3 py-1.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600 hover:bg-gray-200">Report Incident</a>
        </div>
    </div>
    @if($alerts->isEmpty())
    <div class="bg-white border border-gray-200 rounded-2xl p-12 text-center"><div class="text-4xl mb-3"></div><h3 class="font-bold text-gray-900 mb-1">No active alerts</h3><p class="text-gray-500 text-sm">Kenya is safe for travel. Check here for any advisories.</p></div>
    @else
    <div class="space-y-3">@foreach($alerts as $a)
        <div class="bg-white border border-gray-200 rounded-2xl p-5 {{ $a->severity === 'danger' ? 'border-red-300 bg-red-50' : ($a->severity === 'warning' ? 'border-amber-300 bg-amber-50' : '') }}">
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $a->severity === 'danger' ? 'bg-red-200 text-red-700' : ($a->severity === 'warning' ? 'bg-amber-200 text-amber-700' : 'bg-gray-200 text-gray-600') }}">{{ strtoupper($a->severity) }}</span>
                <span class="text-[10px] font-bold text-gray-400">{{ $a->type }}</span>
            </div>
            <h3 class="font-bold text-gray-900 text-sm">{{ $a->title }}</h3>
            @if($a->body)<p class="text-gray-600 text-xs mt-1">{{ $a->body }}</p>@endif
            @if($a->county)<div class="text-xs text-gray-400 mt-2"> {{ $a->county->name }}</div>@endif
        </div>
    @endforeach</div>
    @endif
</div>
@endsection