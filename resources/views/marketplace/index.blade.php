@extends('layouts.app')

@section('title', 'Marketplace — Products from 47 Counties')
@section('description', 'Authentic Kenyan products from all 47 counties — agriculture, crafts, textiles, food and more.')

@section('content')
<div class="pt-20">
    <div class="bg-[#0D1220] border-b border-white/8 py-12">
        <div class="max-w-7xl mx-auto px-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="h-px w-8 bg-kicc-gold"></div>
                <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">Marketplace</span>
            </div>
            <h1 class="text-4xl md:text-5xl font-black text-white tracking-tight">Products from <span class="text-kicc-gold">47 Counties</span></h1>
            <p class="text-white/50 mt-3 text-base max-w-xl">Authentic Kenyan goods, straight from county suppliers to your door.</p>
            <form method="GET" action="{{ route('marketplace.index') }}" class="mt-6 max-w-md">
                <div class="relative">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 text-white/30" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    <input type="text" name="q" value="{{ $q }}" placeholder="Search products…"
                        class="w-full pl-10 pr-4 h-11 rounded-xl bg-[#141B2E] border border-white/10 text-white/80 text-sm outline-none focus:ring-1 focus:ring-[#FFCD05] placeholder:text-white/25 transition-all">
                </div>
            </form>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-5 py-10">
        <div class="flex flex-wrap items-center gap-2 mb-8">
            <a href="{{ route('marketplace.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ !$activeCategory && !$activeCounty ? 'bg-[#901C1E] text-white' : 'bg-[#141B2E] text-white/50 hover:text-white border border-white/8 hover:border-white/20' }}">All</a>
            @foreach($categories as $cat)
            <a href="{{ route('marketplace.index', ['category' => $cat->slug]) }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ $activeCategory === $cat->slug ? 'bg-[#901C1E] text-white' : 'bg-[#141B2E] text-white/50 hover:text-white border border-white/8 hover:border-white/20' }}">
                {{ $cat->name }} <span class="text-white/40">({{ $cat->products_count }})</span>
            </a>
            @endforeach
            <form method="GET" action="{{ route('marketplace.index') }}" class="ml-auto">
                @if($activeCategory)<input type="hidden" name="category" value="{{ $activeCategory }}">@endif
                <select name="county" onchange="this.form.submit()" class="h-11 rounded-xl bg-[#141B2E] border border-white/10 text-white/80 text-sm outline-none focus:ring-1 focus:ring-[#FFCD05] px-4">
                    <option value="">All counties</option>
                    @foreach($counties as $county)
                    <option value="{{ $county->slug }}" {{ $activeCounty === $county->slug ? 'selected' : '' }}>{{ $county->name }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        @if($products->count())
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($products as $product)
            <a href="{{ route('marketplace.show', $product->slug) }}" class="group bg-[#141B2E] rounded-2xl overflow-hidden border border-white/8 hover:border-[#FFCD05]/30 transition-all">
                <div class="aspect-square overflow-hidden bg-[#0D1220]">
                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                         onerror="this.src='{{ asset('storage/kicc/kicc-logo.png') }}'">
                </div>
                <div class="p-4">
                    <div class="flex items-center gap-2 mb-1.5">
                        @if($product->county)
                        <span class="text-[10px] font-bold text-kicc-gold uppercase tracking-widest">{{ $product->county->name }}</span>
                        @endif
                        @if($product->is_featured)
                        <span class="text-[10px] font-bold text-white/40 bg-white/8 px-1.5 py-0.5 rounded">Featured</span>
                        @endif
                    </div>
                    <h3 class="font-bold text-white text-sm leading-snug line-clamp-2">{{ $product->name }}</h3>
                    <div class="mt-2 font-black text-kicc-gold text-base">KES {{ number_format($product->price ?? 0) }}</div>
                </div>
            </a>
            @endforeach
        </div>
        <div class="mt-10">{{ $products->links() }}</div>
        @else
        <div class="text-center py-20">
            <div class="w-16 h-16 rounded-2xl mx-auto mb-4 flex items-center justify-center bg-white/5">
                <svg class="w-8 h-8 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <h3 class="text-white font-bold mb-2">No products yet</h3>
            <p class="text-white/40">County suppliers are listing their first products — check back soon.</p>
        </div>
        @endif
    </div>
</div>
@endSection
