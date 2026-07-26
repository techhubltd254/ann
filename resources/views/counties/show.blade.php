@extends('layouts.app')

@section('title', $county->name . ' County — KICC Kenya')
@section('description', $county->tagline ?? 'Explore ' . $county->name . ' County')

@php
$sectors = [
    ['id' => 'tourism', 'name' => 'Tourism & Hospitality', 'route' => 'tourism', 'svg' => 'M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0'],
    ['id' => 'hotels', 'name' => 'Hotels & Resorts', 'route' => 'hotels', 'svg' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
    ['id' => 'products', 'name' => 'Products & Trade', 'route' => 'products', 'svg' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
    ['id' => 'institutions', 'name' => 'Education & Institutions', 'route' => 'institutions', 'svg' => 'M12 14l9-5-9-5-9 5 9 5zm0 7l5.5-3M12 21l-5.5-3M12 14l5.5-3'],
    ['id' => 'farms', 'name' => 'Agriculture & Farming', 'route' => 'farms', 'svg' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
    ['id' => 'transport', 'name' => 'Transport & Logistics', 'route' => 'transport', 'svg' => 'M8 7h8m0 0v12H8V7zm0 0a2 2 0 014 0m-4 0a2 2 0 00-2 2v12a2 2 0 002 2h4a2 2 0 002-2V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2'],
    ['id' => 'health', 'name' => 'Healthcare', 'route' => 'health', 'svg' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z'],
    ['id' => 'culture', 'name' => 'Culture & Heritage', 'route' => 'culture', 'svg' => 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064'],
];
@endphp

@section('content')
<div class="pt-20">
    <div class="relative h-72 md:h-96 overflow-hidden">
        <video autoplay muted loop playsinline
               poster="{{ media('counties/' . $county->slug . '/hero.jpeg') }}"
               class="w-full h-full object-cover"
               onerror="this.style.display='none';this.nextElementSibling.style.display='block'"
               style="opacity:0;transition:opacity 0.8s">
            <source src="{{ media('counties/' . $county->slug . '/showcase.mp4') }}" type="video/mp4">
        </video>
        <img src="{{ media('counties/' . $county->slug . '/hero.jpeg') }}"
             alt="{{ $county->name }}"
             class="w-full h-full object-cover"
             style="display:none"
             onerror="this.style.display='block';this.parentElement.style.background='#0D1220'">
        <div class="absolute inset-0 bg-gradient-to-t from-[#07090F] via-[#07090F]/50 to-transparent"></div>
        <div class="absolute bottom-0 left-0 right-0 max-w-7xl mx-auto px-5 pb-8">
            <a href="{{ route('counties.index') }}" class="inline-flex items-center gap-1.5 text-white/50 hover:text-white text-sm mb-3 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back to counties
            </a>
            <h1 class="text-4xl font-black text-white">{{ $county->name }} County</h1>
            @if($county->tagline)
            <p class="text-white/50 mt-1">{{ $county->tagline }}</p>
            @endif
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-5 py-10">
        @if($county->description || $county->capital || $county->population_2024 || $county->area_km2)
        <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6 mb-10">
            @if($county->description)
            <p class="text-white/50 text-sm leading-relaxed mb-4">{{ $county->description }}</p>
            @endif
            <div class="grid grid-cols-3 gap-4 text-center">
                @if($county->capital)
                <div>
                    <div class="text-xs text-white/35 uppercase tracking-wider">Capital</div>
                    <div class="text-white font-bold text-lg">{{ $county->capital }}</div>
                </div>
                @endif
                @if($county->population_2024)
                <div>
                    <div class="text-xs text-white/35 uppercase tracking-wider">Population</div>
                    <div class="text-white font-bold text-lg">{{ number_format($county->population_2024) }}</div>
                </div>
                @endif
                @if($county->area_km2)
                <div>
                    <div class="text-xs text-white/35 uppercase tracking-wider">Area</div>
                    <div class="text-white font-bold text-lg">{{ number_format($county->area_km2) }} km²</div>
                </div>
                @endif
            </div>
        </div>
        @endif

        <div class="flex items-center justify-between mb-8">
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <div class="h-px w-8 bg-kicc-gold"></div>
                    <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">{{ $county->name }}</span>
                </div>
                <h2 class="text-3xl md:text-4xl font-black text-white">Explore <span class="text-kicc-gold">Sectors</span></h2>
            </div>
            <a href="{{ route('marketplace.index', ['county' => $county->slug]) }}" class="inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-4 text-xs h-9 rounded-xl bg-[#901C1E] text-white hover:bg-[#7b1618]">
                View Products
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($sectors as $s)
            @php
                $route = $s['route'];
                $entities = \App\Models\SectorEntity::where('county_id', $county->id)
                    ->join('sectors', 'sector_entities.sector_id', '=', 'sectors.id')
                    ->where('sectors.slug', $route)
                    ->count();
            @endphp
            <a href="{{ route('counties.sector', [$county->slug, $s['route']]) }}"
               class="group bg-[#0D1220] border border-white/8 hover:border-[#FFCD05]/40 rounded-2xl p-6 text-center transition-all block">
                <div class="w-14 h-14 rounded-2xl mx-auto mb-4 flex items-center justify-center bg-white/5 group-hover:bg-[#FFCD05]/10 transition-colors">
                    <svg class="w-6 h-6 text-kicc-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $s['svg'] }}"/></svg>
                </div>
                <div class="font-bold text-white text-sm leading-snug">{{ $s['name'] }}</div>
                <div class="text-white/35 text-xs mt-1">{{ $entities }} {{ Str::plural('entity', $entities) }}</div>
            </a>
            @endforeach
        </div>
    </div>
</div>
@endSection