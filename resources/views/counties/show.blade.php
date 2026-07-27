@extends('layouts.app')

@section('title', $county->name . ' County — KICC Kenya')
@section('description', $county->tagline ?? 'Explore ' . $county->name . ' County')

@section('content')
<div class="pt-20">
    {{-- HERO --}}
    <div class="relative min-h-[70vh] md:min-h-[85vh] overflow-hidden">
        <video autoplay muted loop playsinline
               poster="{{ media('counties/' . $county->slug . '/hero.jpeg') }}"
               class="w-full h-full object-cover"
               onloadeddata="this.style.opacity='1'"
               onerror="this.style.display='none';this.nextElementSibling.style.display='block'"
               style="opacity:0;transition:opacity 0.8s">
            <source src="/videos/{{ $county->slug }}.mp4" type="video/mp4">
            <source src="{{ media('counties/' . $county->slug . '/showcase.mp4') }}" type="video/mp4">
        </video>
        <img src="{{ media('counties/' . $county->slug . '/hero.jpeg') }}"
             alt="{{ $county->name }}"
             class="w-full h-full object-cover"
             style="display:none"
             loading="lazy" decoding="async"
             onerror="this.style.display='block';this.parentElement.style.background='#07090F'">
        <div class="absolute inset-0 bg-gradient-to-t from-[#07090F] via-[#07090F]/50 to-transparent"></div>
        <div class="absolute bottom-0 left-0 right-0 max-w-7xl mx-auto px-5 pb-10 md:pb-16">
            <a href="{{ route('counties.index') }}" class="inline-flex items-center gap-1.5 text-white/60 hover:text-white text-sm mb-3 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back to counties
            </a>
            <h1 class="text-4xl font-black text-white">{{ $county->name }} County</h1>
            @if($county->tagline)
            <p class="text-white/70 mt-1">{{ $county->tagline }}</p>
            @endif
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-5 py-10">
        {{-- INFO BAR --}}
        @if($county->description || $county->capital || $county->population_2024 || $county->area_km2)
        <div class="relative z-10 -mt-28 mb-10 rounded-2xl p-6 border border-white/10 shadow-2xl"
             style="background: rgba(7,9,15,0.7); backdrop-filter: blur(14px);">
            @if($county->description)
            <p class="text-white/70 text-sm leading-relaxed mb-4">{{ $county->description }}</p>
            @endif
            <div class="grid grid-cols-3 gap-4 text-center">
                @if($county->capital)
                <div>
                    <div class="text-xs text-white/40 uppercase tracking-wider">Capital</div>
                    <div class="text-white font-bold text-lg">{{ $county->capital }}</div>
                </div>
                @endif
                @if($county->population_2024)
                <div>
                    <div class="text-xs text-white/40 uppercase tracking-wider">Population</div>
                    <div class="text-white font-bold text-lg">{{ number_format($county->population_2024) }}</div>
                </div>
                @endif
                @if($county->area_km2)
                <div>
                    <div class="text-xs text-white/40 uppercase tracking-wider">Area</div>
                    <div class="text-white font-bold text-lg">{{ number_format($county->area_km2) }} km²</div>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- HEADER --}}
        <div class="flex items-center justify-between mb-8">
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <div class="h-px w-8 bg-[#FFCD05]"></div>
                    <span class="text-[#FFCD05] text-xs font-bold tracking-[0.2em] uppercase">{{ $county->name }}</span>
                </div>
                <h2 class="text-3xl md:text-4xl font-black text-white">Explore <span class="text-[#FFCD05]">Sectors</span></h2>
            </div>
            <a href="{{ route('marketplace.index', ['county' => $county->slug]) }}" class="inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-4 text-xs h-9 rounded-xl bg-[#901C1E] text-white hover:bg-[#7b1618]">
                View Products
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>

        {{-- GOVERNMENT SECTORS (from scraped county data) --}}
        @if($linkedSectors->isNotEmpty())
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-4">
                <span class="h-px w-6 bg-[#14B8A6]"></span>
                <span class="text-[#14B8A6] text-[10px] font-bold tracking-[0.2em] uppercase">Government Sectors &amp; Departments</span>
                <span class="h-px flex-1 bg-white/8"></span>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach($linkedSectors as $ls)
                <a href="{{ route('counties.sector', [$county->slug, $ls->slug]) }}" class="px-3 py-1.5 rounded-full text-xs font-bold bg-white/5 text-white/60 hover:bg-[#14B8A6]/10 hover:text-[#14B8A6] border border-white/10 hover:border-[#14B8A6]/40 transition-all">
                    {{ $ls->name }}
                </a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- SECTOR GRID (dynamic from linked sectors) --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @forelse($linkedSectors as $ls)
            @php
                $entityCount = \App\Models\SectorEntity::where('county_id', $county->id)
                    ->where('sector_id', $ls->id)
                    ->count();
                $colors = ['#FFCD05','#901C1E','#0B1E57','#14B8A6','#E76F51','#2D6A4F','#8a6b00','#0693e3'];
                $color = $colors[$loop->index % count($colors)];
            @endphp
            <a href="{{ route('counties.sector', [$county->slug, $ls->slug]) }}"
               class="group bg-[#0D1220] border border-white/8 hover:border-[#FFCD05]/40 rounded-2xl p-6 text-center transition-all block">
                <div class="w-14 h-14 rounded-2xl mx-auto mb-4 flex items-center justify-center bg-white/5 group-hover:bg-opacity-20 transition-all" style="background: {{ $color }}15;">
                    <span class="text-2xl">{{ $loop->first ? '🏛️' : ($loop->index == 1 ? '🌴' : ($loop->index == 2 ? '🏨' : ($loop->index == 3 ? '🛍️' : ($loop->index == 4 ? '🎓' : ($loop->index == 5 ? '🌾' : ($loop->index == 6 ? '🚛' : ($loop->index == 7 ? '🏥' : '📋')))))))) }}</span>
                </div>
                <div class="font-bold text-white text-sm leading-snug">{{ $ls->name }}</div>
                <div class="text-white/35 text-xs mt-1">{{ $entityCount }} {{ Str::plural('entity', $entityCount) }}</div>
            </a>
            @empty
            <div class="col-span-4 text-center py-12 text-white/30">
                <span class="text-4xl block mb-3">📂</span>
                <p class="text-sm">No sectors found for {{ $county->name }} yet.</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
<div class="max-w-7xl mx-auto px-5 pb-12">
    @include("components.packages-strip")
</div>
@endSection