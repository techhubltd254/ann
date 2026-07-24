@extends('layouts.app')

@section('title', 'Marketplace — Products from 47 Counties')
@section('description', 'Authentic Kenyan products from all 47 counties — agriculture, crafts, textiles, food and more.')

@section('content')
<div class="relative bg-charcoal overflow-hidden">
    <div class="absolute inset-0">
        <img src="{{ media('kicc/tower-night.jpg') }}" alt="" class="w-full h-full object-cover object-top opacity-20">
    </div>
    <div class="absolute inset-0 bg-gradient-to-b from-charcoal/70 via-charcoal/85 to-charcoal"></div>
    <div class="relative max-w-7xl mx-auto px-6 lg:px-8 py-16">
        <div class="flex items-center gap-3 mb-4">
            <span class="h-px w-10 bg-gold-400"></span>
            <span class="text-gold-400 text-xs font-semibold uppercase tracking-[0.25em]">Marketplace</span>
        </div>
        <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight text-white mb-3">Products from <span class="text-amber-500">47 Counties</span></h1>
        <p class="text-lg text-gray-300 max-w-2xl">Authentic Kenyan goods, straight from county suppliers to your door.</p>
        <form method="GET" action="{{ route('marketplace.index') }}" class="mt-8 max-w-md">
            <div class="relative">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="q" value="{{ $q }}" placeholder="Search products..."
                       class="w-full pl-12 pr-5 py-3.5 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 text-white placeholder-gray-400 focus:outline-none focus:border-gold-400 focus:ring-2 focus:ring-gold-400/30">
            </div>
        </form>
    </div>
</div>

<div class="max-w-7xl mx-auto px-6 lg:px-8 py-10">
    {{-- Filters --}}
    <div class="flex flex-wrap items-center gap-2 mb-8">
        <a href="{{ route('marketplace.index') }}" class="px-4 py-2 rounded-full text-sm font-medium {{ !$activeCategory && !$activeCounty ? 'bg-amber-500 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:border-amber-400' }} transition-all">All</a>
        @foreach($categories as $cat)
        <a href="{{ route('marketplace.index', ['category' => $cat->slug]) }}"
           class="px-4 py-2 rounded-full text-sm font-medium {{ $activeCategory === $cat->slug ? 'bg-amber-500 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:border-amber-400' }} transition-all">
            {{ $cat->name }} <span class="text-xs opacity-70">({{ $cat->products_count }})</span>
        </a>
        @endforeach
        <form method="GET" action="{{ route('marketplace.index') }}" class="ml-auto">
            @if($activeCategory)<input type="hidden" name="category" value="{{ $activeCategory }}">@endif
            <select name="county" onchange="this.form.submit()" class="bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-700 focus:outline-none focus:border-amber-500">
                <option value="">All counties</option>
                @foreach($counties as $county)
                <option value="{{ $county->slug }}" {{ $activeCounty === $county->slug ? 'selected' : '' }}>{{ $county->name }}</option>
                @endforeach
            </select>
        </form>
    </div>

    @if($products->count())
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5">
        @foreach($products as $product)
        <a href="{{ route('marketplace.show', $product->slug) }}" class="group bg-white rounded-2xl overflow-hidden border border-gray-100 shadow-sm hover:shadow-xl hover:border-amber-200 transition-all">
            <div class="aspect-square overflow-hidden bg-gray-50">
                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy"
                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                     onerror="this.src='{{ asset('storage/kicc/logo.png') }}'; this.classList.add('object-contain','p-8')">
            </div>
            <div class="p-4">
                <div class="flex items-center gap-2 mb-1.5">
                    @if($product->county)
                    <span class="text-[11px] font-medium text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full">{{ $product->county->name }}</span>
                    @endif
                    @if($product->is_featured)
                    <span class="text-[11px] font-medium text-gold-700 bg-gold-100 px-2 py-0.5 rounded-full">Featured</span>
                    @endif
                </div>
                <h3 class="font-semibold text-gray-900 text-sm leading-snug line-clamp-2 group-hover:text-amber-600">{{ $product->name }}</h3>
                <div class="mt-2 flex items-baseline gap-2">
                    @if($product->price !== null)
                    <span class="text-lg font-extrabold text-gray-900">KES {{ number_format($product->price) }}</span>
                    @php $compare = $product->variants->max('compare_at_price'); @endphp
                    @if($compare && $compare > $product->price)
                    <span class="text-xs text-gray-400 line-through">KES {{ number_format($compare) }}</span>
                    @endif
                    @else
                    <span class="text-sm text-gray-400">Price on request</span>
                    @endif
                </div>
            </div>
        </a>
        @endforeach
    </div>
    <div class="mt-10">{{ $products->links() }}</div>
    @else
    <div class="text-center py-20">
        <div class="w-16 h-16 bg-amber-50 rounded-2xl flex items-center justify-center mx-auto mb-5">
            <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        </div>
        <h3 class="text-xl font-bold text-gray-700 mb-2">No products yet</h3>
        <p class="text-gray-500">County suppliers are listing their first products — check back soon.</p>
    </div>
    @endif
</div>
@endSection
