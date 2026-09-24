@extends('layouts.app')

@section('title', $county->name . ' County — KICC Kenya')
@section('description', $county->tagline ?? 'Explore ' . $county->name . ' County')

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

@section('content')
@php $iconMap = []; @endphp
<div class="pt-20">
    {{-- HERO --}}
    <div class="relative min-h-[70vh] md:min-h-[85vh] overflow-hidden">
        {{-- 3D Waving County Flag background --}}
        @if(isset($countyFlagUri) && $countyFlagUri)
        <div id="county-flag-stage"
             data-flag="{{ $countyFlagUri }}"
             class="absolute inset-0 w-full h-full opacity-20"
             style="z-index:0; pointer-events:none;">
        </div>
        @endif
        @php
            $heroPoster = $countyMedia?->posterUrl() ?? media('counties/' . $county->slug . '/hero.jpeg');
            $heroPosterImg = $countyMedia?->thumbnailUrl() ?? media('counties/' . $county->slug . '/hero.jpeg');
            $hasHeroVideo = $countyMedia && ($countyMedia->mp4Url() ?? $countyMedia->url());
        @endphp
        @if($hasHeroVideo)
        <x-video-player
            :asset="$countyMedia"
            :poster="$heroPoster"
            id="county-hero-video"
            class="w-full h-full"
            :autoplay="true"
            :loop="true"
            :muted="true"
        />
        @elseif(count($countyHeroFallback ?? []) > 0)
        {{-- Hero fallback: no county hero uploaded — seamless loop of related videos --}}
        <div class="absolute inset-0 w-full h-full bg-black"
             x-data="heroFallbackPlayer({
                videos: {{ Js::from(array_values(array_slice($countyHeroFallback, 0, 8))) }},
                poster: '{{ $heroPosterImg }}'
             })">
            <img src="{{ $heroPosterImg }}" alt="{{ $county->name }}" class="absolute inset-0 w-full h-full object-cover"
                 :class="videoReady ? 'opacity-0' : 'opacity-100'"
                 style="transition: opacity 0.6s ease; z-index:1">
            <video x-ref="heroFallback"
                   autoplay muted loop playsinline preload="metadata"
                   class="absolute inset-0 w-full h-full object-cover"
                   :class="videoReady ? 'opacity-100' : 'opacity-0'"
                   style="transition: opacity 0.6s ease; z-index:2"
                   @playing="videoReady = true"
                   @ended="nextHeroVideo()">
                <source :src="currentSrc" type="video/mp4">
            </video>
        </div>
        @else
        <div class="absolute inset-0 w-full h-full" style="background:linear-gradient(135deg,#0A1024,#1a1a2e)"></div>
        @endif
        <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-black/10 to-transparent" style="z-index:3"></div>
        <div class="absolute bottom-0 left-0 right-0 max-w-7xl mx-auto px-5 pb-10 md:pb-16" style="z-index:5">
            <a href="{{ route('counties.index') }}" class="inline-flex items-center gap-1.5 text-white/60 hover:text-white text-sm mb-3 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                All Counties
            </a>
        </div>
        {{-- Floating stats bar — continuous scroll at 0.75 speed --}}
        <div class="absolute bottom-0 left-0 right-0 z-10 overflow-hidden bg-[#0B1E57]/70 backdrop-blur-sm border-t border-[#FFCD05]/20 pointer-events-none" style="height: 40px;">
            <div class="floating-stats whitespace-nowrap py-[9px]">
                <span class="floating-stat-text text-[13px] font-medium text-white/90 tracking-wide px-4">
                     Capital: {{ $county->capital ?? '—' }} &nbsp;·&nbsp;  Population: {{ $county->population_2024 ? number_format($county->population_2024) : '—' }} &nbsp;·&nbsp;  Area: {{ $county->area_km2 ? number_format($county->area_km2) . ' km²' : '—' }} &nbsp;·&nbsp;  Economic Zone: {{ $county->economic_zone ?? '—' }}
                </span>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-5">
        
        {{-- Live Booth Widget --}}
        <x-live-booths-widget :county-slug="$county->slug" />

        {{-- Quick actions bar --}}
        <div class="flex flex-wrap items-center justify-between gap-3 mb-10 mt-6">
            <div class="flex items-center gap-2">
                <span class="h-px w-8 bg-kicc-gold"></span>
                <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">{{ $county->name }}</span>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('marketplace.index', ['county' => $county->slug]) }}" class="px-4 py-2 rounded-xl bg-[#901C1E] text-white text-xs font-bold hover:bg-[#7b1618] transition-all">View Products</a>
                @auth
                    @if(auth()->user()->hasAnyRole(['county_admin','kicc_admin']) && (auth()->user()->county_id == $county->id || auth()->user()->hasRole('kicc_admin')))
                    <a href="{{ route('county.admin.pro', $county->slug) }}" class="px-4 py-2 rounded-xl bg-[#0B1E57] text-white text-xs font-bold hover:bg-[#0D2A7A] transition-all">County Admin</a>
                    @endif
                @else
                <a href="{{ route('login') }}" class="px-4 py-2 rounded-xl border border-[#0B1E57]/40 text-[#0B1E57] text-xs font-bold hover:bg-[#0B1E57]/10 transition-all">Admin Login</a>
                @endauth
            </div>
        </div>

        {{-- COUNTY LOCATION — compact pin link in sidebar --}}
        <div class="mb-14">
            <div class="flex items-center gap-3 mb-6">
                <span class="h-px w-8 bg-kicc-gold"></span>
                <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">Location</span>
                <span class="h-px flex-1 bg-gray-200"></span>
            </div>
            <div class="max-w-sm">
                <x-map-pin :entity="$county" />
            </div>
        </div>

        {{-- ECONOMIC SECTORS --}}
        <div class="mb-14">
            <div class="flex items-center gap-3 mb-6">
                <span class="h-px w-8 bg-kicc-gold"></span>
                <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">Economic Sectors</span>
                <span class="h-px flex-1 bg-gray-200"></span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($sectorData as $name => $s)
                @php
                    $tile = $tileMedia[$s['sector_slug']] ?? [];
                    $hasVideo = !empty($tile['videoUrl']);
                    $hoverLoop = $tile['hoverLoopUrl'] ?? $tile['videoUrl'] ?? null;
                    $sectorTilePoster = $tile['posterUrl'] ?? null;
                    $pitch = $sectorPitches[$s['sector_slug']] ?? '';
                @endphp
                <a href="{{ route('counties.sector', [$county->slug, $s['route']]) }}"
                   class="group bg-white border border-gray-200 hover:border-kicc-gold/40 rounded-2xl overflow-hidden transition-all block card-hover"
                   x-data="mediaTile()"
                   @mouseenter="onHoverEnter()"
                   @mouseleave="onHoverLeave()"
                   data-tilt="6" data-reveal data-reveal-delay="{{ $loop->index * 80 }}">
                    <div class="aspect-[4/3] overflow-hidden relative bg-gradient-to-br from-[#0A1024] to-[#1a1a2e]">
                        @if($hoverLoop)
                        <video x-ref="video" muted loop playsinline preload="auto"
                               class="absolute inset-0 w-full h-full object-cover transition-opacity duration-500"
                               :class="videoReady ? 'opacity-100' : 'opacity-0'"
                               poster="{{ $tile['posterUrl'] ?? '' }}"
                               @playing="onVideoPlaying()">
                            <source src="{{ $hoverLoop }}" type="video/mp4">
                        </video>
                        @endif
                        <img src="{{ $tile['posterUrl'] ?? '' }}" alt="{{ $name }}" loading="lazy"
                             class="absolute inset-0 w-full h-full object-cover transition-opacity duration-500"
                             :class="(videoReady && $hoverLoop) ? 'opacity-0' : 'opacity-100'"
                             onerror="this.remove()">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/10 to-black/30 pointer-events-none"></div>
                    </div>
                    <div class="p-4 text-center min-h-[80px] flex flex-col justify-center">
                        <div class="font-bold text-gray-900 text-sm leading-snug">{{ $name }}</div>
                        @if($pitch)
                        <div class="text-gray-500 text-[10px] leading-relaxed mt-1 line-clamp-2">{{ $pitch }}</div>
                        @else
                        <div class="text-gray-400 text-xs mt-1">{{ $s['count'] }} {{ Str::plural('entity', $s['count']) }}</div>
                        @endif
                    </div>
                </a>
                @if(isset($sectorPins[$s['sector_slug']]) && count($sectorPins[$s['sector_slug']]) > 0)
                <div class="px-4 pb-4 space-y-1.5 border-t border-gray-100 mt-1 pt-3">
                    @foreach($sectorPins[$s['sector_slug']] as $pin)
                    <div class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#0B1E57]/40 shrink-0"></span>
                        <a href="{{ $pin['pin_url'] }}" target="_blank" rel="noopener"
                           class="text-[10px] font-medium text-gray-600 hover:text-[#0B1E57] transition-colors truncate">
                            {{ $pin['name'] }}
                        </a>
                    </div>
                    @endforeach
                </div>
                @endif
                @endforeach
            </div>
        </div>

        {{-- ATTRACTIONS --}}
        @if(($featuredAttractions ?? collect())->isNotEmpty())
        <div class="mb-14">
            <div class="flex items-center gap-3 mb-6">
                <span class="h-px w-8 bg-kicc-gold"></span>
                <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">Top Attractions</span>
                <span class="h-px flex-1 bg-gray-200"></span>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($featuredAttractions as $a)
                @php $aMedia = $entityMedia['attraction_' . $a->id] ?? []; @endphp
                <x-media-tile
                    :media="$aMedia"
                    :title="$a->name"
                    :subtitle="'KES ' . number_format($a->entry_fee ?? 0)"
                    :category="$a->category ?? 'Attraction'"
                    :badge="$a->entry_fee > 0 ? 'KES ' . number_format($a->entry_fee) : 'Free'"
                    :href="route('attractions.show', $a->id)"
                    aspect="aspect-[4/3]"
                />
                @endforeach
            </div>
        </div>
        @endif

        {{-- HOTELS --}}
        @if(($featuredHotels ?? collect())->isNotEmpty())
        <div class="mb-14">
            <div class="flex items-center gap-3 mb-6">
                <span class="h-px w-8 bg-kicc-gold"></span>
                <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">Places to Stay</span>
                <span class="h-px flex-1 bg-gray-200"></span>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($featuredHotels as $h)
                @php
                    $hMedia = $entityMedia['hotel_' . $h->id] ?? [];
                    $stars = $h->star_rating ? str_repeat('', $h->star_rating) . str_repeat('', 5 - $h->star_rating) : '';
                @endphp
                <x-media-tile
                    :media="$hMedia"
                    :title="$h->name"
                    :subtitle="$h->phone ?? ''"
                    :category="$h->category ?? 'Hotel'"
                    :badge="$stars ?: ($h->phone ?? '')"
                    :href="route('counties.sector', [$county->slug, 'hotels'])"
                    aspect="aspect-[4/3]"
                />
                @endforeach
            </div>
        </div>
        @endif

        {{-- COMMERCE / END PRODUCTS --}}
        @if(($countyProducts ?? collect())->isNotEmpty())
        <div class="mb-14">
            <div class="flex items-center gap-3 mb-6">
                <span class="h-px w-8 bg-kicc-gold"></span>
                <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">Commerce &amp; End Products</span>
                <span class="h-px flex-1 bg-gray-200"></span>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($countyProducts as $p)
                @php $pMedia = $entityMedia['product_' . $p->id] ?? []; @endphp
                <x-media-tile
                    :media="$pMedia"
                    :title="$p->name"
                    :subtitle="'KES ' . number_format($p->price ?? 0) . ' / ' . ($p->unit ?? 'unit')"
                    :category="$p->category ?? 'Product'"
                    :badge="'KES ' . number_format($p->price ?? 0)"
                    :href="route('county.product.booking', [$county->slug, $p->id])"
                    aspect="aspect-[4/3]"
                />
                @endforeach
            </div>
            <div class="mt-5 text-center">
                <a href="{{ route('marketplace.index', ['county' => $county->slug]) }}" class="inline-flex items-center gap-2 text-sm font-bold text-[#0B1E57] hover:text-[#901C1E] transition-colors">
                    Browse full {{ $county->name }} marketplace
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
            </div>
        </div>
        @endif

        {{-- EXHIBITIONS --}}
        @if(($exhibitions ?? collect())->isNotEmpty())
        <div class="mb-14">
            <div class="flex items-center gap-3 mb-6">
                <span class="h-px w-8 bg-kicc-gold"></span>
                <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">Exhibitions</span>
                <span class="h-px flex-1 bg-gray-200"></span>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                @foreach($exhibitions as $ex)
                <div class="bg-white border border-gray-200 rounded-2xl p-5 hover:border-kicc-gold/40 hover:shadow-md transition-all group">
                    <a href="{{ route('exhibitions.show', $ex->slug) }}" class="block">
                        <div class="font-bold text-gray-900 text-sm group-hover:text-kicc-gold transition-colors">{{ $ex->name }}</div>
                        <div class="text-gray-400 text-xs mt-1">{{ $ex->start_date?->format('M d') }} – {{ $ex->end_date?->format('M d, Y') }}</div>
                    </a>
                    <a href="{{ route('exhibitions.show', $ex->slug) }}#book" class="inline-block text-xs font-bold text-white bg-kicc-gold hover:bg-yellow-600 rounded-lg py-1.5 px-3 mt-3 transition-colors">Book booth</a>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Packages strip --}}
        <div class="pb-12">
            @include('components.packages-strip')
        </div>

        {{-- County pipeline earnings --}}
        @if(($countyPipelines ?? collect())->isNotEmpty())
        <div class="max-w-7xl mx-auto px-5 pb-12">
            @include('components.county-pipelines', [
                'countyName' => $county->name,
                'countyPipelines' => $countyPipelines,
            ])
        </div>
        @endif
    </div>
</div>
@endsection
@push('scripts')
<script>
function heroFallbackPlayer(config) {
    return {
        videos: config.videos || [],
        currentIndex: 0,
        videoReady: false,
        get hasVideos() {
            return this.videos.length > 0;
        },
        get currentSrc() {
            return this.videos[this.currentIndex] || '';
        },
        nextHeroVideo() {
            if (this.videos.length <= 1) return;
            this.currentIndex = (this.currentIndex + 1) % this.videos.length;
            var video = this.$refs.heroFallback;
            if (video) {
                video.src = this.currentSrc;
                video.load();
                video.play().catch(function(){});
            }
        }
    };
}
</script>
<script defer src="{{ asset('js/waving-flag.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var flagContainer = document.getElementById('county-flag-stage');
    if (flagContainer && typeof KiccWavingFlag !== 'undefined') {
        var dataUri = flagContainer.dataset.flag;
        if (dataUri) {
            try {
                new KiccWavingFlag({ container: flagContainer, flagDataUri: dataUri });
            } catch(e) {}
        }
    }
});
</script>
@endpush