@extends('layouts.app')

@section('title', $exhibitor->name . ' — Official Storefront')

@section('content')
@php $accent = '#2a2a2a'; @endphp

{{-- Hero --}}
<div class="bg-[#2a2a2a] text-gray-900">
    <div class="max-w-6xl mx-auto px-5 py-14">
        <div class="flex items-center gap-3 mb-6">
            <img src="{{ media('kicc/kicc-logo.png') }}" alt="KICC" class="h-9 w-auto" style="filter: brightness(0) invert(1);">
            <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 border-l border-gray-200 pl-3">Official Exhibitor Website</span>
        </div>
        <div class="flex flex-wrap items-start justify-between gap-6">
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-[10px] font-bold uppercase tracking-widest px-2.5 py-1 rounded-full" style="background: {{ $accent }}">Verified Exhibitor</span>
                    <span class="text-[10px] font-bold uppercase tracking-widest px-2.5 py-1 rounded-full bg-gray-100">Escrow Protected</span>
                </div>
                <h1 class="text-4xl font-black mb-2" data-split>{{ $exhibitor->name }}</h1>
                @if($exhibitor->county)
                <a href="{{ route('counties.show', $exhibitor->county->slug) }}" class="text-gray-500 hover:text-gray-900 text-sm transition-colors">
                     {{ $exhibitor->county->name }} County, Kenya &nearr;
                </a>
                @endif
            </div>
            <div class="flex gap-6 text-center">
                <div><div class="text-2xl font-black">{{ $products->count() }}</div><div class="text-[10px] uppercase tracking-widest text-gray-400">Products</div></div>
                <div><div class="text-2xl font-black">{{ number_format($totalStock) }}</div><div class="text-[10px] uppercase tracking-widest text-gray-400">In Stock</div></div>
            </div>
        </div>
    </div>
</div>

{{-- Trust strip --}}
<div class="border-b border-gray-100 bg-white">
    <div class="max-w-6xl mx-auto px-5 py-3 flex flex-wrap gap-6 text-xs text-gray-500">
        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full" style="background: {{ $accent }}"></span> KICC National Exhibition exhibitor</span>
        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full" style="background: {{ $accent }}"></span> Payments held in trade escrow until delivery</span>
        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full" style="background: {{ $accent }}"></span> Nationwide &amp; international shipping</span>
    </div>
</div>

{{-- Products --}}
<div class="max-w-6xl mx-auto px-5 py-12">
    <h2 class="text-xl font-black text-gray-900 mb-6" data-split>Products from {{ $exhibitor->name }}</h2>
    @if($products->isEmpty())
    <div class="bg-white border border-gray-200 rounded-2xl p-12 text-center text-gray-400">This exhibitor is preparing their catalogue.</div>
    @else
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
        @foreach($products as $p)
        <a href="{{ route('marketplace.show', $p->slug) }}" class="bg-white border border-gray-200 rounded-2xl overflow-hidden hover:shadow-lg hover:-translate-y-0.5 transition-all group">
            <div class="aspect-square bg-gray-100 overflow-hidden">
                <img src="{{ $p->image_url }}" alt="{{ $p->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
            </div>
            <div class="p-4">
                <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">{{ $p->category?->name }}</div>
                <div class="font-bold text-gray-900 text-sm mb-2 leading-snug">{{ $p->name }}</div>
                <div class="flex items-center justify-between">
                    <div class="font-black" style="color: {{ $accent }}">KES {{ number_format($p->price ?? 0) }}</div>
                    <div class="text-[10px] text-gray-400">{{ $p->variants->sum('stock') }} left</div>
                </div>
            </div>
        </a>
        @endforeach
    </div>
    @endif

    {{-- Interconnection: county + marketplace --}}
    <div class="mt-12 grid md:grid-cols-2 gap-4">
        @if($exhibitor->county)
        <a href="{{ route('counties.show', $exhibitor->county->slug) }}" class="bg-white border border-gray-200 rounded-2xl p-6 hover:shadow-md transition-all flex items-center justify-between">
            <div>
                <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Part of</div>
                <div class="font-bold text-gray-900">{{ $exhibitor->county->name }} County Pavilion</div>
            </div>
            <span class="text-gray-300 text-xl">&nearr;</span>
        </a>
        @endif
        <a href="{{ route('marketplace.index') }}" class="bg-white border border-gray-200 rounded-2xl p-6 hover:shadow-md transition-all flex items-center justify-between">
            <div>
                <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Sold on</div>
                <div class="font-bold text-gray-900">KICC National Marketplace</div>
            </div>
            <span class="text-gray-300 text-xl">&nearr;</span>
        </a>
    </div>
    <div class="mt-8">
        @include("components.packages-strip")
    </div>
</div>
@endsection
