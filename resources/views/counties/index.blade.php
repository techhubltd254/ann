@extends('layouts.app')

@section('title', 'Explore all 47 Counties of Kenya')
@section('description', 'Explore all 47 counties of Kenya — each with its own unique sectors, attractions, and exhibition opportunities.')

@section('content')
<div x-data="{ query: '', activeRegion: 'All' }" class="pt-20">
    <div class="bg-[#0D1220] border-b border-white/8 py-12">
        <div class="max-w-7xl mx-auto px-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="h-px w-8 bg-kicc-gold"></div>
                <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">Destinations</span>
            </div>
            <h1 class="text-4xl md:text-5xl font-black text-white tracking-tight">Explore Kenya's <span class="text-kicc-gold">47 Counties</span></h1>
            <p class="text-white/50 mt-3 text-base max-w-xl">Discover economic sectors, investment opportunities, tourism attractions, and trade exhibitions across every county.</p>
            <div class="flex flex-col sm:flex-row gap-3 mt-8">
                <div class="relative flex-1 max-w-sm">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 text-white/30" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    <input x-model="query" placeholder="Search counties…"
                        class="w-full pl-10 pr-4 h-11 rounded-xl bg-[#141B2E] border border-white/10 text-white/80 text-sm outline-none focus:ring-1 focus:ring-[#FFCD05] placeholder:text-white/25 transition-all">
                </div>
                <div class="flex gap-1.5 overflow-x-auto flex-wrap">
                    @foreach(['All','Central','Coast','Eastern','Nyanza','North Eastern','Rift Valley','Western'] as $r)
                    <button @click="activeRegion = '{{ $r }}'"
                        class="shrink-0 px-3 py-1.5 rounded-lg text-[11px] font-bold transition-all cursor-pointer"
                        :class="activeRegion === '{{ $r }}' ? 'bg-[#901C1E] text-white' : 'bg-[#141B2E] text-white/40 border border-white/8 hover:border-white/20 hover:text-white'">{{ $r }}</button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-5 py-10">
        @if($counties->count() > 0)
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            @foreach($counties as $c)
            <a href="{{ route('counties.show', $c->slug) }}"
               x-show="'{{ $c->name }}'.toLowerCase().includes(query.toLowerCase()) && (activeRegion === 'All' || '{{ $c->former_province ?? '' }}'.includes(activeRegion))"
               class="group bg-[#0D1220] rounded-2xl overflow-hidden border border-white/8 hover:border-kicc-gold/40 transition-all">
                <div class="h-32 overflow-hidden bg-[#141B2E]">
<img src="{{ media('counties/' . $c->slug . '/hero.jpeg') }}" alt="{{ $c->name }}"
                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                     loading="lazy" decoding="async"
                         onerror="this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center text-4xl bg-[#141B2E]\'>{{ $c->icon_emoji ?? '📍' }}</div>'">
                </div>
                <div class="p-3.5 text-center">
                    <h3 class="font-bold text-white text-sm leading-tight group-hover:text-kicc-gold transition-colors">{{ $c->name }}</h3>
                    <p class="text-white/35 text-xs mt-1">{{ $c->primary_sectors ? count($c->primary_sectors) . ' sectors' : '' }} · {{ number_format($c->population_2024 ?? 0) }} people</p>
                    @if($c->primary_sectors)
                    <div class="flex flex-wrap justify-center gap-1 mt-2">
                        @foreach(array_slice($c->primary_sectors, 0, 2) as $ps)
                        <span class="text-xs bg-[#901C1E]/20 text-kicc-gold px-2 py-0.5 rounded-full font-medium">{{ $ps }}</span>
                        @endforeach
                    </div>
                    @endif
                </div>
            </a>
            @endforeach
        </div>
        <div class="mt-8 text-center" x-show="!($el.parentElement.querySelector('a:not([style*=\"display:none\"])'))">
            <p class="text-white/40" x-text="'No counties match &quot;' + query + '&quot;'"></p>
            <button @click="query = ''; activeRegion = 'All'" class="mt-3 text-kicc-gold text-sm underline">Clear filters</button>
        </div>
        @else
        <div class="text-center py-16">
            <p class="text-white/40">County data will appear once the system is populated.</p>
        </div>
        @endif
    </div>
</div>
@endSection
