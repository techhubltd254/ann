@extends('layouts.app')

@section('title', $county->name . ' County — KICC Kenya')
@section('description', $county->tagline ?? 'Explore ' . $county->name . ' County')

@php
$sectorMeta = [
    'Tourism' => ['route' => 'tourism', 'icon' => '🏖️', 'color' => 'amber', 'label' => 'Attractions'],
    'Hospitality' => ['route' => 'hotels', 'icon' => '🏨', 'color' => 'rose', 'label' => 'Hotels'],
    'Trade & Products' => ['route' => 'products', 'icon' => '🛍️', 'color' => 'violet', 'label' => 'Products'],
    'Education' => ['route' => 'institutions', 'icon' => '🎓', 'color' => 'blue', 'label' => 'Institutions'],
    'Agriculture' => ['route' => 'farms', 'icon' => '🌾', 'color' => 'emerald', 'label' => 'Farms'],
    'Transport' => ['route' => 'transport', 'icon' => '🚢', 'color' => 'cyan', 'label' => 'Transport'],
    'Healthcare' => ['route' => 'health', 'icon' => '🏥', 'color' => 'red', 'label' => 'Health'],
    'Culture' => ['route' => 'culture', 'icon' => '🎭', 'color' => 'orange', 'label' => 'Culture'],
];
@endphp

@section('content')
<div class="relative h-[85vh] min-h-[600px] flex items-center overflow-hidden">
    <div class="absolute inset-0">
        <video autoplay muted loop playsinline
               poster="{{ asset('storage/counties/' . $county->slug . '/hero.jpeg') }}"
               class="w-full h-full object-cover"
               onerror="this.style.display='none';this.nextElementSibling.style.display='block'"
               onloadeddata="this.style.opacity='1'"
               style="opacity:0;transition:opacity 0.8s">
            <source src="{{ asset('storage/counties/' . $county->slug . '/showcase.mp4') }}" type="video/mp4">
        </video>
        <img src="{{ asset('storage/counties/' . $county->slug . '/hero.jpeg') }}"
             alt="{{ $county->name }} County"
             class="w-full h-full object-cover"
             style="display:none"
             onerror="this.style.display='block';this.style.objectFit='none';this.parentElement.style.background='linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%)'">
        <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/50 to-transparent"></div>
    </div>

    <div class="relative w-full max-w-7xl mx-auto px-6 lg:px-8 py-20">
        <a href="{{ route('counties.index') }}" class="inline-flex items-center gap-2 text-white/70 hover:text-white mb-8 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            <span class="text-sm font-medium">All Counties</span>
        </a>
        <div class="max-w-3xl">
            <div class="text-5xl mb-4 drop-shadow-lg">{{ $county->icon_emoji ?? '📍' }}</div>
            <h1 class="text-5xl md:text-7xl font-bold text-white mb-4 leading-tight">{{ $county->name }} County</h1>
            @if($county->tagline)
            <p class="text-xl md:text-2xl text-amber-300 font-light mb-6">{{ $county->tagline }}</p>
            @endif
            @if($county->description)
            <p class="text-gray-300 text-lg leading-relaxed max-w-2xl">{{ Str::limit($county->description, 250) }}</p>
            @endif
        </div>

        <div class="mt-10 flex flex-wrap gap-4">
            @if($county->capital)
            <div class="bg-white/10 backdrop-blur-md rounded-2xl px-6 py-4 border border-white/10 min-w-[140px]">
                <div class="text-xs text-gray-400 uppercase tracking-widest mb-1">Capital</div>
                <div class="text-white font-bold text-xl">{{ $county->capital }}</div>
            </div>
            @endif
            @if($county->population_2024)
            <div class="bg-white/10 backdrop-blur-md rounded-2xl px-6 py-4 border border-white/10 min-w-[140px]">
                <div class="text-xs text-gray-400 uppercase tracking-widest mb-1">Population</div>
                <div class="text-white font-bold text-xl">{{ number_format($county->population_2024) }}</div>
            </div>
            @endif
            @if($county->area_km2)
            <div class="bg-white/10 backdrop-blur-md rounded-2xl px-6 py-4 border border-white/10 min-w-[140px]">
                <div class="text-xs text-gray-400 uppercase tracking-widest mb-1">Area</div>
                <div class="text-white font-bold text-xl">{{ number_format($county->area_km2) }} km²</div>
            </div>
            @endif
        </div>

        @if($county->primary_sectors)
        <div class="mt-6 flex flex-wrap gap-2">
            @foreach($county->primary_sectors as $ps)
            <span class="text-xs bg-amber-500/20 text-amber-300 px-3 py-1.5 rounded-full border border-amber-500/30 font-medium">{{ $ps }}</span>
            @endforeach
        </div>
        @endif
    </div>
</div>

<div class="max-w-7xl mx-auto px-6 lg:px-8 -mt-8 relative z-20">
    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
            <div class="text-center">
                <div class="text-3xl font-bold text-amber-600">{{ $sectors->count() }}</div>
                <div class="text-sm text-gray-500 mt-1">Economic Sectors</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-amber-600">{{ array_sum(array_column($sectorData, 'count')) }}</div>
                <div class="text-sm text-gray-500 mt-1">Total Listings</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-amber-600">{{ $exhibitions->count() }}</div>
                <div class="text-sm text-gray-500 mt-1">Exhibitions</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-amber-600">{{ number_format($county->population_2024 ?? 0) }}</div>
                <div class="text-sm text-gray-500 mt-1">Population</div>
            </div>
        </div>
    </div>
</div>

<section class="max-w-7xl mx-auto px-6 lg:px-8 py-20">
    <div class="text-center mb-12">
        <h2 class="text-4xl font-bold text-gray-900 mb-3">Explore {{ $county->name }} by Sector</h2>
        <p class="text-lg text-gray-500 max-w-2xl mx-auto">Discover businesses, attractions, and opportunities across every sector.</p>
    </div>

    @php $sectorIndex = 0; @endphp
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach($sectors as $sector)
        @php
            $keys = array_keys($sectorMeta);
            $key = $keys[$sectorIndex] ?? $sector->name;
            $meta = $sectorMeta[$key] ?? ['route' => '#', 'icon' => '📋', 'color' => 'gray', 'label' => ''];
            $sd = $sectorData[$key] ?? ['count' => 0];
            $img = asset('storage/counties/' . $county->slug . '/' . $meta['route'] . '.jpeg');
            $sectorIndex++;
        @endphp
        <a href="{{ route('counties.sector', [$county->slug, $meta['route']]) }}"
           class="group relative rounded-2xl overflow-hidden bg-white shadow-md hover:shadow-2xl transition-all duration-500 border border-gray-100 hover:border-{{ $meta['color'] }}-300 hover:-translate-y-1">
            <div class="h-52 overflow-hidden relative">
                <img src="{{ $img }}" alt="{{ $key }}"
                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                     onerror="this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center text-6xl bg-gradient-to-br from-gray-100 to-gray-200\'>{{ $meta['icon'] }}</div>'">
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent"></div>
                <div class="absolute bottom-4 left-4 right-4">
                    <span class="text-white text-lg font-bold drop-shadow-lg">{{ $meta['icon'] }} {{ $key }}</span>
                </div>
            </div>
            <div class="p-5">
                <div class="flex items-baseline gap-2 mb-1">
                    <span class="text-3xl font-bold text-gray-900">{{ $sd['count'] }}</span>
                    <span class="text-sm text-gray-400">{{ $meta['label'] }}</span>
                </div>
                @if($sector->description)
                <p class="text-sm text-gray-500 line-clamp-2 mb-3">{{ $sector->description }}</p>
                @endif
                <div class="flex items-center text-{{ $meta['color'] }}-600 font-medium text-sm group-hover:gap-2 transition-all">
                    Browse {{ $key }}
                    <svg class="w-4 h-4 ml-1 group-hover:ml-2 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </div>
            </div>
        </a>
        @endforeach
    </div>
</section>

@if($featuredAttractions->count() > 0)
<section class="bg-gradient-to-br from-amber-50 via-orange-50 to-amber-100 py-20">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="flex items-end justify-between mb-10">
            <div>
                <h2 class="text-4xl font-bold text-gray-900">Top Attractions</h2>
                <p class="text-lg text-gray-600 mt-1">Must-visit destinations in {{ $county->name }}</p>
            </div>
            <a href="{{ route('counties.sector', [$county->slug, 'tourism']) }}"
               class="hidden sm:flex items-center gap-2 text-amber-600 hover:text-amber-700 font-semibold transition-colors">
                View All
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($featuredAttractions as $attraction)
            <div class="bg-white rounded-2xl shadow-md overflow-hidden hover:shadow-xl transition-shadow group">
                <div class="h-52 bg-gradient-to-br from-amber-100 to-orange-200 overflow-hidden relative">
                    <div class="w-full h-full flex items-center justify-center text-6xl group-hover:scale-110 transition-transform duration-500">🏛️</div>
                </div>
                <div class="p-5">
                    <h4 class="font-bold text-gray-900 mb-1">{{ $attraction->name }}</h4>
                    @if($attraction->category)
                    <span class="inline-block text-xs font-medium bg-amber-100 text-amber-700 px-2.5 py-1 rounded-full mb-2">{{ $attraction->category }}</span>
                    @endif
                    @if($attraction->location)
                    <p class="text-sm text-gray-500 flex items-center gap-1.5 mt-2">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ $attraction->location }}
                    </p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

@if($featuredHotels->count() > 0)
<section class="py-20">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="flex items-end justify-between mb-10">
            <div>
                <h2 class="text-4xl font-bold text-gray-900">Places to Stay</h2>
                <p class="text-lg text-gray-500 mt-1">Hotels and resorts in {{ $county->name }}</p>
            </div>
            <a href="{{ route('counties.sector', [$county->slug, 'hotels']) }}"
               class="hidden sm:flex items-center gap-2 text-rose-600 hover:text-rose-700 font-semibold transition-colors">
                View All
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($featuredHotels as $hotel)
            <div class="bg-white rounded-2xl shadow-md overflow-hidden hover:shadow-xl transition-shadow group">
                <div class="h-48 bg-gradient-to-br from-rose-100 to-pink-200 overflow-hidden relative">
                    <div class="w-full h-full flex items-center justify-center">
                        <div class="text-center">
                            <div class="text-5xl mb-2 group-hover:scale-110 transition-transform duration-500">🏨</div>
                            @if($hotel->star_rating)
                            <div class="text-yellow-500 text-sm">{{ str_repeat('⭐', $hotel->star_rating) }}</div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="p-5">
                    <h4 class="font-bold text-gray-900 mb-1">{{ $hotel->name }}</h4>
                    @if($hotel->category)
                    <span class="text-xs font-medium text-rose-600 bg-rose-50 px-2.5 py-1 rounded-full">{{ $hotel->category }}</span>
                    @endif
                    @if($hotel->price_range_min && $hotel->price_range_max)
                    <p class="text-sm font-semibold text-gray-700 mt-2">KSh {{ number_format($hotel->price_range_min) }} – {{ number_format($hotel->price_range_max) }}</p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

@if($exhibitions->count() > 0)
<section class="bg-gray-50 py-20">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-bold text-gray-900 mb-3">Upcoming Exhibitions</h2>
            <p class="text-lg text-gray-500">Trade shows and events in {{ $county->name }}</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($exhibitions as $exhibition)
            <div class="bg-white rounded-2xl shadow-md p-6 hover:shadow-xl transition-shadow border border-gray-100">
                <div class="w-14 h-14 bg-amber-100 rounded-2xl flex items-center justify-center text-2xl mb-4">📅</div>
                <h4 class="font-bold text-gray-900 text-lg mb-2">{{ $exhibition->name }}</h4>
                <p class="text-sm text-gray-500 mb-4">{{ $exhibition->start_date->format('M d, Y') }} – {{ $exhibition->end_date->format('M d, Y') }}</p>
                <a href="{{ route('exhibitions.show', $exhibition->slug) }}" class="inline-flex items-center gap-2 text-amber-600 hover:text-amber-700 font-semibold text-sm transition-colors">
                    View Details
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

@if($county->tourism_highlights && count($county->tourism_highlights) > 0)
<section class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 py-20">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 text-center">
        <h2 class="text-4xl font-bold text-white mb-3">Discover {{ $county->name }}</h2>
        <p class="text-gray-400 text-lg mb-10">Featured highlights and destinations</p>
        <div class="flex flex-wrap justify-center gap-3">
            @foreach($county->tourism_highlights as $highlight)
            <span class="bg-white/10 backdrop-blur-sm text-white px-6 py-3 rounded-xl text-sm font-medium border border-white/10 hover:bg-white/20 transition-colors shadow-lg">
                {{ $highlight }}
            </span>
            @endforeach
        </div>
    </div>
</section>
@endif

@push('styles')
<style>
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .group:hover .group-hover\:gap-2 { gap: 0.5rem; }
    .group:hover .group-hover\:ml-2 { margin-left: 0.5rem; }
</style>
@endpush
@endSection
