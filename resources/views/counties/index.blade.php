@extends('layouts.app')

@section('title', 'Explore All 47 Counties of Kenya')
@section('description', 'Explore all 47 counties of Kenya — each with its own unique sectors, attractions, and exhibition opportunities.')

@section('content')
<div class="relative bg-kicc-dark overflow-hidden">
    <div class="absolute inset-0">
        <img src="{{ media('kicc/tower-night.jpg') }}" alt="" class="w-full h-full object-cover object-top opacity-25">
    </div>
    <div class="absolute inset-0 bg-gradient-to-b from-kicc-dark/70 via-kicc-dark/85 to-kicc-dark"></div>
    <div class="relative max-w-7xl mx-auto px-6 lg:px-8 py-20">
        <div class="text-center">
            <div class="flex items-center justify-center gap-3 mb-5">
                <span class="h-px w-10 bg-gold-400"></span>
                <span class="text-gold-400 text-xs font-semibold uppercase tracking-[0.25em]">Destinations</span>
                <span class="h-px w-10 bg-gold-400"></span>
            </div>
            <h1 class="text-5xl md:text-6xl font-extrabold tracking-tight text-white mb-4">Explore Kenya's <span class="text-amber-500">47 Counties</span></h1>
            <p class="text-xl text-gray-300 mb-10 max-w-2xl mx-auto">Discover economic sectors, investment opportunities, tourism attractions, and trade exhibitions across every county.</p>
            <div class="max-w-md mx-auto">
                <div class="relative">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" id="county-search" placeholder="Search counties..."
                           class="w-full pl-12 pr-5 py-3.5 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 text-white placeholder-gray-400 focus:outline-none focus:border-gold-400 focus:ring-2 focus:ring-gold-400/30 text-lg">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-6 lg:px-8 py-12">
    @if($counties->count() > 0)
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4" id="county-grid">
        @foreach($counties as $county)
        @php $heroImg = media('counties/' . $county->slug . '/hero.jpeg'); @endphp
        <a href="{{ route('counties.show', $county->slug) }}"
           class="group block county-card"
           data-name="{{ strtolower($county->name) }}">
            <div class="bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100 hover:shadow-xl hover:border-amber-300 transition-all duration-300 hover:-translate-y-1">
                <div class="h-32 overflow-hidden relative">
                    <img src="{{ $heroImg }}" alt="{{ $county->name }}"
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                         onerror="this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center text-5xl bg-gradient-to-br from-amber-100 to-orange-200 group-hover:scale-125 transition-transform duration-300\'>{{ $county->icon_emoji ?? '📍' }}</div>'">
                </div>
                <div class="p-3.5 text-center">
                    <h3 class="font-semibold text-sm text-gray-900 group-hover:text-amber-600 truncate">{{ $county->name }}</h3>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ $county->sectors_count ?? 0 }} sectors
                        @if($county->population_2024) · {{ number_format($county->population_2024) }} people @endif
                    </p>
                    @if($county->primary_sectors)
                    <div class="flex flex-wrap justify-center gap-1 mt-2">
                        @foreach(array_slice($county->primary_sectors, 0, 2) as $ps)
                        <span class="text-xs bg-amber-50 text-amber-600 px-2 py-0.5 rounded-full font-medium">{{ $ps }}</span>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </a>
        @endforeach
    </div>
    <div class="mt-8" id="no-results" style="display:none">
        <div class="text-center py-16">
            <div class="text-5xl mb-4">🔍</div>
            <h3 class="text-xl font-semibold text-gray-600 mb-2">No counties found</h3>
            <p class="text-gray-500">Try a different search term.</p>
        </div>
    </div>
    <div class="mt-10">
        {{ $counties->links() }}
    </div>
    @else
    <div class="text-center py-16">
        <div class="text-5xl mb-4">🇰🇪</div>
        <h3 class="text-xl font-semibold text-gray-600 mb-2">No counties loaded yet</h3>
        <p class="text-gray-500">County data will appear once the system is populated.</p>
    </div>
    @endif
</div>

@push('scripts')
<script>
document.getElementById('county-search')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    const cards = document.querySelectorAll('.county-card');
    let visible = 0;
    cards.forEach(c => {
        const match = c.dataset.name.includes(q);
        c.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    document.getElementById('no-results').style.display = visible === 0 ? '' : 'none';
});
</script>
@endpush
@endSection
