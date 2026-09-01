@extends('layouts.app')

@section('title', 'KICC Global Exhibition Platform')
@section('description', 'Africa\'s Premier Meeting Venue. A national icon since 1973.')

@section('content')
{{-- HERO — fullscreen video background (YouTube-style HLS player) --}}
<section class="relative min-h-screen flex items-center overflow-hidden" id="hero-section">
    @php
        $heroAsset = \App\Models\MediaAsset::resolveSlot('landing_page', 1, 'hero_video');
        $heroPoster = $heroAsset?->posterUrl() ?? media('kicc/venues/kicc-main-gate.jpg');
    @endphp
    <div class="absolute inset-0 w-full h-full" id="hero-video-container">
        <x-video-player
            :asset="$heroAsset"
            :poster="$heroPoster"
            id="home-hero-video"
            class="w-full h-full"
            :autoplay="true"
            :loop="true"
            :muted="true"
        />
    </div>
    <div class="absolute inset-0 bg-gradient-to-r from-black/40 via-black/10 to-transparent pointer-events-none" style="z-index:3"></div>
    <div class="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent pointer-events-none" style="z-index:3"></div>
    <div class="relative max-w-7xl mx-auto px-5 pt-28 pb-20 w-full grid md:grid-cols-2 gap-10 items-center" style="z-index:5">
        <div>
            <h1 class="text-5xl sm:text-7xl md:text-8xl font-black text-white leading-[0.95] tracking-tight">
                Global<br>
                <span class="text-[#FFCD05]">Exhibition</span>
            </h1>
            <p class="text-white/70 text-base sm:text-lg leading-relaxed mt-4 max-w-md">
                From 47 county markets to world-class exhibition halls — KICC connects Kenya's entire economy on one platform.
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('counties.index') }}"
                   class="inline-flex items-center gap-2 font-bold px-6 sm:px-8 text-sm h-12 sm:h-14 rounded-xl bg-[#901C1E] text-white hover:bg-[#7b1618]">
                    Explore Counties
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
                <a href="{{ route('exhibitions.index') }}"
                   class="inline-flex items-center gap-2 font-bold px-8 text-sm h-14 rounded-xl border border-[#FFCD05]/40 text-[#FFCD05] hover:bg-[#FFCD05]/10">
                    Book Exhibition
                </a>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            @php $stats = [['value'=>52,'suffix'=>'+','label'=>'Years of Excellence'],['value'=>47,'suffix'=>'','label'=>'Kenya Counties'],['value'=>18,'suffix'=>'','label'=>'Digital Screens'],['value'=>200,'suffix'=>'+','label'=>'Events per Year']]; @endphp
            @foreach($stats as $i => $s)
            <div class="bg-white/10 backdrop-blur-sm border border-white/10 rounded-2xl p-6 card-hover">
                <div class="text-4xl font-black text-[#FFCD05]">
                    <span data-count="{{ $s['value'] }}" data-count-duration="{{ 1400 + $i * 250 }}" data-count-delay="{{ $i * 200 }}">0</span><span class="text-2xl">{{ $s['suffix'] }}</span>
                </div>
                <div class="text-white/60 text-xs font-medium mt-2 leading-snug">{{ $s['label'] }}</div>
            </div>
            @endforeach
        </div>
    </div>
    <div class="absolute bottom-8 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2 text-white/50">
        <div class="text-[10px] tracking-[0.2em] uppercase font-semibold">Scroll</div>
        <svg class="w-4 h-4 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
    </div>
</section>

{{-- COUNTY STRIP — Figma exact --}}
<section class="py-12 md:py-20 overflow-hidden section-transition" data-section="counties">
    <div class="max-w-7xl mx-auto px-5">
        <div data-reveal>
            <div class="flex items-center gap-3 mb-3">
                <div class="h-px w-8 bg-[#FFCD05]"></div>
                <span class="text-[#FFCD05] text-xs font-bold tracking-[0.2em] uppercase">Explore Kenya</span>
            </div>
            <h2 class="text-3xl md:text-4xl font-black text-gray-900 leading-[1.1]" data-split>Browse all <span class="text-[#FFCD05]">47 Counties</span></h2>
            <p class="text-gray-400 mt-3 text-base max-w-xl leading-relaxed">Search by name or filter by region — then click to explore sectors and businesses.</p>
        </div>
        <x-county-strip :counties="$counties" :hero-videos="$countyHeroVideos" />
    </div>
</section>

{{-- MARKETPLACE --}}
<section class="border-y border-gray-100 py-12 md:py-20 bg-white section-transition" data-section="marketplace">
    <div class="max-w-7xl mx-auto px-5">
        <div data-reveal>
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="h-px w-8 bg-[#FFCD05]"></div>
                        <span class="text-[#FFCD05] text-xs font-bold tracking-[0.2em] uppercase">KICC Marketplace</span>
                    </div>
                    <h2 class="text-3xl md:text-4xl font-black text-gray-900 leading-[1.1]" data-split>Authentic Kenyan<br><span class="text-[#FFCD05]">Products</span></h2>
                    <p class="text-gray-400 mt-3 text-base max-w-xl leading-relaxed">Directly from county producers across Kenya.</p>
                </div>
                <a href="{{ route('marketplace.index') }}" class="inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-4 text-xs h-9 rounded-xl bg-gray-100 text-gray-900 hover:bg-gray-100 border border-gray-200 shrink-0 card-hover">
                    Browse all
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
            </div>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @php $featured = $products ?? collect([]); @endphp
            @forelse($featured->take(8) as $i => $product)
            <a href="{{ route('marketplace.show', $product->slug) }}" class="group bg-gray-50 rounded-2xl overflow-hidden border border-gray-100 hover:border-[#FFCD05]/30 transition-all text-left block card-hover" data-tilt="7" data-reveal data-reveal-delay="{{ ($i % 4) * 70 }}">
                <div class="aspect-square overflow-hidden bg-white relative">
                    @php
                        $hpVideo = is_array($product->videos) ? ($product->videos[0] ?? null) : $product->video_url;
                        $hpImg = $product->images->first()->url ?? $product->image_url ?? null;
                        $hpFallback = media('kicc/kicc-logo.png');
                    @endphp
                    @if($hpVideo)
                    <video muted loop playsinline preload="none" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                           onerror="this.style.display='none'"
                           x-data="{ loaded: false }"
                           x-init="$el.parentElement.parentElement.addEventListener('mouseenter', function() { if(!loaded) { $el.src = '{{ $hpVideo }}'; $el.load(); loaded = true; } $el.play().catch(()=>{}); }, { once: true })"
                           x-on:mouseenter="$el.parentElement.parentElement.dispatchEvent(new CustomEvent('mouseenter'))">
                        <source src="{{ $hpVideo }}" type="video/mp4">
                    </video>
                    @endif
                    @if($hpImg && !$hpVideo)
                    <x-fast-image :src="$hpImg" :alt="$product->name" :width="640" :quality="75" class="w-full h-full" />
                    @elseif(!$hpVideo && !$hpImg)
                    <div class="w-full h-full flex items-center justify-center bg-gray-100 text-gray-300 text-2xl font-bold">
                        {{ strtoupper(substr($product->name, 0, 2)) }}
                    </div>
                    @endif
                </div>
                <div class="p-4">
                    <div class="text-[10px] font-bold text-[#FFCD05] uppercase tracking-widest mb-1">{{ $product->county?->name ?? 'Kenya' }} · {{ $product->category?->name ?? 'Product' }}</div>
                    <div class="font-bold text-gray-900 text-sm leading-snug line-clamp-2 mb-2">{{ $product->name }}</div>
                    <div class="font-black text-[#FFCD05] text-base">KES {{ number_format($product->price ?? 0) }}</div>
                </div>
            </a>
            @empty
            <div class="col-span-4 text-center py-16 text-[#5A6480]">Products loading...</div>
            @endforelse
        </div>
    </div>
</section>

{{-- EXHIBITIONS --}}
<section class="max-w-7xl mx-auto px-5 py-12 md:py-20 section-transition" data-section="exhibitions">
    <div data-reveal>
        <div class="flex items-center gap-3 mb-3">
            <div class="h-px w-8 bg-[#FFCD05]"></div>
            <span class="text-[#FFCD05] text-xs font-bold tracking-[0.2em] uppercase">Events</span>
        </div>
        <h2 class="text-3xl md:text-4xl font-black text-gray-900 leading-[1.1]" data-split>Upcoming<br><span class="text-[#FFCD05]">Exhibitions</span></h2>
        <p class="text-gray-400 mt-3 text-base max-w-xl leading-relaxed">Book booths, showcase products, and connect with buyers across East Africa.</p>
    </div>
    @if(isset($featuredExhibitions) && $featuredExhibitions->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        @foreach($featuredExhibitions as $i => $ex)
        <a href="{{ route('exhibitions.show', $ex->slug) }}"
           class="group bg-white rounded-2xl overflow-hidden border border-gray-200 hover:border-[#901C1E]/40 transition-all block card-hover" data-tilt="5" data-reveal data-reveal-delay="{{ $i * 120 }}">
            <div class="tilt-glare"></div>
            <div class="h-44 overflow-hidden bg-gray-50">
                @if($ex->cover_image)
                <x-fast-image :src="$ex->cover_image" :alt="$ex->name" :width="640" :quality="75" class="w-full h-full" />
                @else
                <div class="w-full h-full flex items-center justify-center text-[#FFCD05]/30 text-lg font-bold">KICC Exhibition</div>
                @endif
            </div>
            <div class="p-5">
                <h3 class="font-black text-gray-900 text-base mt-3 leading-snug">{{ $ex->name }}</h3>
                <div class="mt-3 space-y-1.5 text-xs text-gray-400">
                    <div class="flex items-center gap-2">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        {{ $ex->start_date?->format('M d, Y') ?? 'TBD' }}
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ $ex->venue?->name ?? 'KICC' }}
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-between">
                    <span class="text-gray-400 group-hover:text-[#FFCD05] text-xs transition-colors flex items-center gap-1">
                        Details
                        <svg class="w-3 h-3 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </span>
                </div>
            </div>
        </a>
        @endforeach
    </div>
    @else
    <div class="text-center py-12 text-[#5A6480] bg-white rounded-2xl border border-gray-200 card-hover" data-reveal>
        <div class="text-4xl mb-3">📅</div>
        <p class="text-sm">No featured exhibitions right now — check back soon.</p>
        <a href="{{ route('exhibitions.index') }}" class="inline-block mt-4 text-[#FFCD05] text-sm font-bold hover:underline" data-magnetic>Browse all events</a>
    </div>
    @endif
</section>

{{-- VENUES --}}
<section class="bg-white border-y border-gray-100 py-12 md:py-20 section-transition" data-section="venues">
    <div class="max-w-7xl mx-auto px-5">
        <div data-reveal>
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="h-px w-8 bg-[#FFCD05]"></div>
                        <span class="text-[#FFCD05] text-xs font-bold tracking-[0.2em] uppercase">KICC Facilities</span>
                    </div>
                    <h2 class="text-3xl md:text-4xl font-black text-gray-900 leading-[1.1]" data-split>World-Class<br><span class="text-[#FFCD05]">Venues</span></h2>
                    <p class="text-gray-400 mt-3 text-base max-w-xl leading-relaxed">From intimate boardrooms to the 2,000-capacity Tsavo Hall.</p>
                </div>
                <a href="{{ route('venues.index') }}" class="inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-4 text-xs h-9 rounded-xl bg-gray-100 text-gray-900 hover:bg-gray-100 border border-gray-200 shrink-0 card-hover">
                    All venues
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @forelse($venues ?? [] as $i => $v)
            <a href="{{ route('venues.show', $v->slug) }}" class="group bg-gray-50 rounded-2xl overflow-hidden border border-gray-100 hover:border-[#FFCD05]/40 transition-all card-hover" data-tilt="5" data-reveal data-reveal-delay="{{ ($i % 4) * 70 }}">
                <div class="h-36 overflow-hidden bg-white">
                    @if($v->cover_image)
                    <x-fast-image :src="$v->cover_image" :alt="$v->name" :width="640" :quality="75" class="w-full h-full" />
                    @endif
                    <div class="absolute inset-0 flex items-center justify-center text-gray-900/10 text-5xl font-black">{{ $v->name[0] }}</div>
                </div>
                <div class="p-4">
                    <div class="font-black text-gray-900 text-sm">{{ $v->name }}</div>
                    <div class="text-[#5A6480] text-xs mt-1">{{ number_format($v->capacity ?? 0) }} capacity</div>
                    <div class="mt-3 flex items-center justify-between">
                        <span class="text-[#FFCD05] font-bold text-sm">{{ ucfirst($v->venue_type ?? '') }}</span>
                        <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-[#FFCD05] group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </div>
                </div>
            </a>
            @empty
            @foreach([['Tsavo Hall','Ballroom',2000],['Amphitheatre','Theatre',800],['Aberdares','Meeting',120],['Courtyard','Outdoor',500]] as $i => $v)
            <div class="group bg-[#F9FAFB] rounded-2xl overflow-hidden border border-gray-200 card-hover" data-reveal data-reveal-delay="{{ $i * 70 }}">
                <div class="h-36 bg-white flex items-center justify-center"><span class="text-gray-900/10 text-5xl font-black">{{ $v[0][0] }}</span></div>
                <div class="p-4">
                    <div class="font-black text-gray-900 text-sm">{{ $v[0] }}</div>
                    <div class="text-[#5A6480] text-xs mt-1">{{ $v[1] }} · {{ number_format($v[2]) }}</div>
                </div>
            </div>
            @endforeach
            @endforelse
        </div>
    </div>
</section>

{{-- SCREENS CTA --}}
<section class="max-w-7xl mx-auto px-5 py-12 md:py-20 section-transition" data-section="screens">
    <div class="relative overflow-hidden rounded-3xl border border-gray-200 bg-gradient-to-br from-[#0D1220] to-[#07090F]" data-reveal="zoom">
        <img src="{{ media('kicc/gallery/kicc_DSC_6125.jpg') }}" alt="" class="absolute inset-0 w-full h-full object-cover opacity-20">
        <div class="relative px-5 md:px-10 py-16 md:py-20 flex flex-col md:flex-row items-center justify-between gap-8">
            <div data-reveal>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold tracking-wide border bg-[#FFCD05]/15 text-[#FFCD05] border-[#FFCD05]/30">18 Digital Screens</span>
                <h2 class="text-3xl md:text-5xl font-black text-white mt-4 leading-tight" data-split>Advertise on<br><span class="text-[#FFCD05]">Kenya's Most</span><br>Iconic Screens</h2>
                <p class="text-white/60 mt-4 max-w-md text-sm leading-relaxed">The KICC tower rooftop LED, highway billboards, and premium screens reaching millions daily.</p>
            </div>
            <div class="flex flex-col gap-3 shrink-0" data-reveal data-reveal-delay="200">
                <a href="{{ route('screens.directory') }}" class="inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-8 text-base h-14 rounded-xl bg-[#FFCD05] text-[#07090F] hover:bg-[#e6b904]" data-magnetic>Book a Screen</a>
                <a href="{{ route('screens.directory') }}" class="inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-4 text-xs h-9 rounded-xl border border-white/25 text-white hover:bg-white/10">View all 18 screens</a>
            </div>
        </div>
    </div>
</section>
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
@endsection