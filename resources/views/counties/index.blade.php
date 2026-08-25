@extends('layouts.app')

@section('title', 'Explore all 47 Counties of Kenya')
@section('description', 'Explore all 47 counties of Kenya — each with its own unique sectors, attractions, and exhibition opportunities.')

@section('content')
<div class="pt-20" id="county-index-page">
    <div class="bg-white border-b border-gray-200 py-12">
        <div class="max-w-7xl mx-auto px-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="h-px w-8 bg-kicc-gold"></div>
                <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">Destinations</span>
            </div>
            <h1 class="text-4xl md:text-5xl font-black text-gray-900 tracking-tight" data-split>Explore Kenya's <span class="text-kicc-gold">47 Counties</span></h1>
            <p class="text-[#5A6480] mt-3 text-base max-w-xl">Discover economic sectors, investment opportunities, tourism attractions, and trade exhibitions across every county.</p>
            <div class="flex flex-col sm:flex-row gap-3 mt-8">
                <div class="relative flex-1 max-w-sm">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 text-[#5A6480]" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    <input id="county-search-input" placeholder="Search counties…"
                        class="w-full pl-10 pr-4 h-11 rounded-xl bg-[#F9FAFB] border border-gray-200 text-gray-700 text-sm outline-none focus:ring-1 focus:ring-[#FFCD05] placeholder:text-[#5A6480] transition-all"
                        data-voice-search>
                </div>
                <div class="flex gap-1.5 overflow-x-auto flex-wrap" id="county-region-buttons">
                    @foreach(['All','Central','Coast','Eastern','Nairobi','Nyanza','North Eastern','Rift Valley','Western'] as $i => $r)
                    <button data-region="{{ $r }}"
                        class="shrink-0 px-3 py-1.5 rounded-lg text-[11px] font-bold transition-all cursor-pointer {{ $i === 0 ? 'bg-[#901C1E] text-white' : 'bg-[#F9FAFB] text-[#5A6480] border border-gray-200 hover:border-gray-200 hover:text-gray-900' }}">{{ $r }}</button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-5 py-10">
        @if($counties->count() > 0)
        <div class="flex items-center justify-between mb-4">
            <span id="county-count" class="text-[#5A6480] text-xs font-semibold">{{ $counties->count() }} counties</span>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4" id="county-grid">
            @foreach($counties as $c)
            <a href="{{ route('counties.show', $c->slug) }}"
               data-name="{{ strtolower($c->name) }}"
               data-region="{{ $c->former_province ?? '' }}"
               class="county-card group bg-white rounded-2xl overflow-hidden border border-gray-200 hover:border-kicc-gold/40 transition-all">
                <div class="h-32 overflow-hidden bg-[#F9FAFB]">
                    <img src="{{ media('counties/' . $c->slug . '/hero.jpeg') }}" alt="{{ $c->name }}"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                         loading="lazy" decoding="async"
                         onerror="this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center bg-gradient-to-br from-[#0A1024] to-[#1a1a2e]\'></div>'">
                </div>
                <div class="p-3.5 text-center">
                    <h3 class="font-bold text-gray-900 text-sm leading-tight group-hover:text-kicc-gold transition-colors">{{ $c->name }}</h3>
                    <p class="text-gray-400 text-xs mt-1">{{ is_array($c->primary_sectors) ? count($c->primary_sectors) . ' sectors' : '' }} · {{ number_format($c->population_2024 ?? 0) }} people</p>
                    @if(is_array($c->primary_sectors) && count($c->primary_sectors))
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
        <div class="mt-8 text-center" id="county-no-results" style="display:none">
            <p class="text-[#5A6480]">No counties match your filter.</p>
            <button onclick="clearFilters()" class="mt-3 text-kicc-gold text-sm underline" data-magnetic>Clear filters</button>
        </div>
        @else
        <div class="text-center py-16">
            <p class="text-[#5A6480]">County data will appear once the system is populated.</p>
        </div>
        @endif
    </div>
</div>

<script>
(function() {
    var searchInput = document.getElementById('county-search-input');
    var regionButtons = document.querySelectorAll('#county-region-buttons [data-region]');
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

    function clearFilters() {
        if (searchInput) searchInput.value = '';
        activeRegion = 'All';
        regionButtons.forEach(function(b) {
            if (b.getAttribute('data-region') === 'All') {
                b.className = 'shrink-0 px-3 py-1.5 rounded-lg text-[11px] font-bold transition-all cursor-pointer bg-[#901C1E] text-white';
            } else {
                b.className = 'shrink-0 px-3 py-1.5 rounded-lg text-[11px] font-bold transition-all cursor-pointer bg-[#F9FAFB] text-[#5A6480] border border-gray-200 hover:border-gray-200 hover:text-gray-900';
            }
        });
        filterCounties();
    }

    window.clearFilters = clearFilters;

    if (searchInput) {
        searchInput.addEventListener('input', filterCounties);
    }

    regionButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            activeRegion = this.getAttribute('data-region');
            regionButtons.forEach(function(b) {
                if (b === btn) {
                    b.className = 'shrink-0 px-3 py-1.5 rounded-lg text-[11px] font-bold transition-all cursor-pointer bg-[#901C1E] text-white';
                } else {
                    b.className = 'shrink-0 px-3 py-1.5 rounded-lg text-[11px] font-bold transition-all cursor-pointer bg-[#F9FAFB] text-[#5A6480] border border-gray-200 hover:border-gray-200 hover:text-gray-900';
                }
            });
            filterCounties();
        });
    });
})();
</script>
@push('styles')
<style>
/* Responsive touch targets */
@media (max-width: 640px) {
    .nav-link { padding: 0.625rem 0.75rem; font-size: 0.75rem; }
    .h1-responsive { font-size: 1.75rem !important; line-height: 1.2 !important; }
    .h2-responsive { font-size: 1.5rem !important; }
    .section-padding { padding-top: 2.5rem !important; padding-bottom: 2.5rem !important; }
    .sticky-sidebar { position: relative !important; top: auto !important; }
    .mobile-full { width: 100% !important; }
    .touch-target { min-height: 44px; min-width: 44px; }
}
@media (max-width: 768px) {
    .md-hidden { display: none !important; }
    .mobile-stack { flex-direction: column !important; }
    .mobile-text-center { text-align: center !important; }
}
</style>
@endpush
@endSection