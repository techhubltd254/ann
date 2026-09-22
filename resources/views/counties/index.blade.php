@extends('layouts.app')

@section('title', 'Explore all 47 Counties of Kenya')
@section('description', 'Discover economic sectors, investment opportunities, tourism attractions, and trade exhibitions across every county.')

@section('content')
<div id="county-index-page">
    {{-- ─── 3D PARALLAX HERO ─── --}}
    <section class="relative min-h-[55vh] flex items-center overflow-hidden bg-[#0B0E17]">
        <div class="absolute inset-0 opacity-20" style="background: radial-gradient(ellipse at 30% 50%, #901C1E 0%, transparent 60%), radial-gradient(ellipse at 70% 30%, #FFCD05 0%, transparent 50%);"></div>
        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=60 height=60 viewBox=0 0 60 60 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cg fill=%22none%22 fill-rule=%22evenodd%22%3E%3Cg fill=%22%23ffffff%22 fill-opacity=%220.03%22%3E%3Cpath d=%22M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z%22/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
        <div class="max-w-7xl mx-auto px-5 w-full relative z-10 py-20">
            <div data-reveal="up">
                <div class="flex items-center gap-3 mb-4">
                    <div class="h-px w-8 bg-kicc-gold"></div>
                    <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">47 Destinations</span>
                </div>
                <h1 class="text-4xl md:text-6xl font-black text-white tracking-tight leading-[1.1]">Explore Kenya's <br><span class="text-kicc-gold">47 Counties</span></h1>
                <p class="text-gray-400 mt-4 text-base md:text-lg max-w-2xl leading-relaxed">Discover economic sectors, investment opportunities, tourism attractions, and trade exhibitions across every county.</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 mt-10" data-reveal="up" data-reveal-delay="200">
                <div class="relative flex-1 max-w-md">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    <input id="county-search-input" placeholder="Search counties…"
                        class="w-full pl-11 pr-4 h-12 rounded-xl bg-white/10 border border-white/10 text-white text-sm outline-none focus:ring-1 focus:ring-kicc-gold placeholder:text-gray-500 backdrop-blur-sm transition-all">
                </div>
            </div>
            <div class="flex gap-1.5 overflow-x-auto flex-wrap mt-4" data-reveal="up" data-reveal-delay="300">
                @foreach(['All','Central','Coast','Eastern','Nairobi','Nyanza','North Eastern','Rift Valley','Western'] as $i => $r)
                <button data-region="{{ $r }}"
                    class="shrink-0 px-4 py-2 rounded-lg text-xs font-bold tracking-wide transition-all cursor-pointer {{ $i === 0 ? 'bg-kicc-gold text-gray-900' : 'bg-white/10 text-gray-300 border border-white/10 hover:bg-white/20 hover:text-white' }}">{{ $r }}</button>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ─── MAP ─── --}}
    <section class="bg-[#0B0E17] border-t border-white/5">
        <div class="max-w-7xl mx-auto px-5 py-8">
            <div data-reveal="zoom" class="rounded-2xl overflow-hidden border border-white/10 shadow-2xl">
                <x-all-counties-map :counties="$counties" height="380px" />
            </div>
        </div>
    </section>

    {{-- ─── COUNTY GRID ─── --}}
    <section class="bg-[#0B0E17] py-10">
        <div class="max-w-7xl mx-auto px-5">
            <div class="flex items-center justify-between mb-6">
                <span id="county-count" class="text-gray-400 text-sm font-medium">{{ $counties->count() }} counties</span>
            </div>

            @if($counties->count() > 0)
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-5" id="county-grid">
                @foreach($counties as $idx => $c)
                @php
                $ch = $countyHeroes[$c->slug] ?? null;
                $delay = ($idx % 6) * 50;
                @endphp
                <a href="{{ route('counties.show', $c->slug) }}"
                   data-name="{{ strtolower($c->name) }}"
                   data-region="{{ $c->former_province ?? '' }}"
                   data-reveal="up"
                   data-reveal-delay="{{ $delay }}"
                   data-tilt="6"
                   class="county-card group bg-[#131724] rounded-2xl overflow-hidden border border-white/5 hover:border-kicc-gold/30 transition-all duration-500">
                    <div class="h-36 overflow-hidden relative bg-gradient-to-br from-[#1a1f33] to-[#0f1322]">
                        @if($ch && $ch['video'])
                        <x-media-tile
                            :poster="$ch['poster']"
                            :hover-loop="$ch['hover'] ?? null"
                            :video-url="$ch['video']"
                            :title="$c->name"
                            class="absolute inset-0 w-full h-full"
                        />
                        @else
                        <div class="absolute inset-0 flex items-center justify-center">
                            <svg class="w-12 h-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        @endif
                        {{-- tilt glare overlay --}}
                        <div class="tilt-glare absolute inset-0 pointer-events-none" style="background: linear-gradient(135deg, rgba(255,255,255,0.08) 0%, transparent 50%);"></div>
                    </div>
                    <div class="p-4">
                        <h3 class="font-bold text-white text-sm leading-tight group-hover:text-kicc-gold transition-colors">{{ $c->name }}</h3>
                        <p class="text-gray-500 text-xs mt-1.5">
                            {{ is_array($c->primary_sectors) ? count($c->primary_sectors) : '0' }} sectors · {{ number_format($c->population_2024 ?? 0) }} people
                        </p>
                        @if(is_array($c->primary_sectors) && count($c->primary_sectors))
                        <div class="flex flex-wrap gap-1.5 mt-3">
                            @foreach(array_slice($c->primary_sectors, 0, 2) as $ps)
                            <span class="text-[10px] bg-kicc-gold/15 text-kicc-gold px-2 py-0.5 rounded-full font-semibold tracking-wide">{{ $ps }}</span>
                            @endforeach
                        </div>
                        @endif
                        <div class="mt-3 flex items-center gap-3 text-xs">
                            <a href="https://www.google.com/maps/search/?api=1&query={{ $c->latitude }},{{ $c->longitude }}" target="_blank" rel="noopener"
                               class="inline-flex items-center gap-1.5 text-gray-500 hover:text-white transition-colors">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.73 7 13 7 13s7-7.27 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5S14.5 7.62 14.5 9s-1.12 2.5-2.5 2.5z"/></svg>
                                Map
                            </a>
                            <span class="text-gray-700">|</span>
                            <a href="{{ route('counties.show', $c->slug) }}" class="inline-flex items-center gap-1.5 text-gray-500 hover:text-kicc-gold transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                Explore
                            </a>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>

            <div class="mt-12 text-center" id="county-no-results" style="display:none">
                <p class="text-gray-500">No counties match your filter.</p>
                <button onclick="clearFilters()" class="mt-3 text-kicc-gold text-sm underline hover:text-white transition-colors">Clear filters</button>
            </div>
            @else
            <div class="text-center py-20">
                <p class="text-gray-500">County data will appear once the system is populated.</p>
            </div>
            @endif
        </div>
    </section>
</div>

{{-- ─── FILTER SCRIPT ─── --}}
<script>
(function() {
    var searchInput = document.getElementById('county-search-input');
    var regionButtons = document.querySelectorAll('[data-region]');
    var countyCards = document.querySelectorAll('.county-card');
    var countEl = document.getElementById('county-count');
    var noResults = document.getElementById('county-no-results');
    var activeRegion = 'All';

    function filterCounties() {
        var query = searchInput ? searchInput.value.toLowerCase() : '';
        var visible = 0;
        countyCards.forEach(function(card) {
            var name = card.getAttribute('data-name') || '';
            var region = card.getAttribute('data-region') || '';
            var match = (query === '' || name.includes(query)) &&
                        (activeRegion === 'All' || region === activeRegion);
            card.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        if (countEl) countEl.textContent = visible + ' counties';
        if (noResults) noResults.style.display = visible === 0 ? '' : 'none';
    }

    window.clearFilters = function() {
        if (searchInput) searchInput.value = '';
        activeRegion = 'All';
        regionButtons.forEach(function(b) {
            var isAll = b.getAttribute('data-region') === 'All';
            b.className = 'shrink-0 px-4 py-2 rounded-lg text-xs font-bold tracking-wide transition-all cursor-pointer ' +
                (isAll ? 'bg-kicc-gold text-gray-900' : 'bg-white/10 text-gray-300 border border-white/10 hover:bg-white/20 hover:text-white');
        });
        filterCounties();
    };

    if (searchInput) searchInput.addEventListener('input', filterCounties);

    regionButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            activeRegion = this.getAttribute('data-region');
            regionButtons.forEach(function(b) {
                var isActive = b === btn;
                b.className = 'shrink-0 px-4 py-2 rounded-lg text-xs font-bold tracking-wide transition-all cursor-pointer ' +
                    (isActive ? 'bg-kicc-gold text-gray-900' : 'bg-white/10 text-gray-300 border border-white/10 hover:bg-white/20 hover:text-white');
            });
            filterCounties();
        });
    });
})();
</script>
@endsection