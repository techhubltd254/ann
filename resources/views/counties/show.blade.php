@extends('layouts.app')

@section('title', $county->name . ' County — KICC Kenya')
@section('description', $county->tagline ?? 'Explore ' . $county->name . ' County')

<style>
@keyframes heroFade {
    0% { opacity: 1; }
    17% { opacity: 1; }
    23% { opacity: 0; }
    100% { opacity: 0; }
}
.hero-video-layer {
    position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover;
    animation: heroFade 24s infinite;
    will-change: opacity;
}
.hero-video-layer:nth-child(1) { animation-delay: 0s; }
.hero-video-layer:nth-child(2) { animation-delay: 6s; }
.hero-video-layer:nth-child(3) { animation-delay: 12s; }
.hero-video-layer:nth-child(4) { animation-delay: 18s; }
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
        {{-- Hero fallback: no county hero uploaded — cycle sector/entity videos --}}
        <img src="{{ $heroPosterImg }}" alt="{{ $county->name }}" class="absolute inset-0 w-full h-full object-cover" loading="lazy" style="z-index:0">
        @foreach($countyHeroFallback as $vi)
        <video autoplay muted loop playsinline loading="lazy" preload="metadata" class="hero-video-layer"
               style="z-index:1"
               onerror="this.style.display='none'">
            <source src="{{ $vi }}" type="video/mp4">
        </video>
        @endforeach
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
                    🏛 Capital: {{ $county->capital ?? '—' }} &nbsp;·&nbsp; 👥 Population: {{ $county->population_2024 ? number_format($county->population_2024) : '—' }} &nbsp;·&nbsp; 📐 Area: {{ $county->area_km2 ? number_format($county->area_km2) . ' km²' : '—' }} &nbsp;·&nbsp; 🏭 Economic Zone: {{ $county->economic_zone ?? '—' }}
                </span>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-5">
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
                <a href="{{ route('login') }}" class="px-4 py-2 rounded-xl border border-[#0B1E57]/40 text-[#0B1E57] text-xs font-bold hover:bg-[#0B1E57]/10 transition-all">County Admin Login</a>
                @endauth
            </div>
        </div>

        {{-- COUNTY MAP — pins for county + all institutions --}}
        <div class="mb-14">
            <div class="flex items-center gap-3 mb-6">
                <span class="h-px w-8 bg-kicc-gold"></span>
                <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">Map &amp; Locations</span>
                <span class="h-px flex-1 bg-gray-200"></span>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="lg:col-span-2">
                    <x-county-map
                        :county="$county"
                        :institutions="$mapInstitutions ?? []"
                        height="400px"
                    />
                </div>
                <div class="space-y-3">
                    <div class="bg-white border border-gray-200 rounded-2xl p-5">
                        <h4 class="text-xs font-bold text-gray-900 uppercase tracking-widest mb-3">County Location</h4>
                        <div class="flex items-start gap-2.5 text-sm text-gray-700">
                            <span class="text-gray-400 mt-0.5">📍</span>
                            <span>{{ $county->latitude ?? '—' }}, {{ $county->longitude ?? '—' }}</span>
                        </div>
                        @if($county->website)
                        <a href="{{ $county->website }}" target="_blank" rel="noopener"
                           class="mt-4 w-full inline-flex items-center justify-center gap-2 font-bold text-sm h-10 rounded-xl bg-[#0B1E57] text-white hover:bg-[#16275f] transition-all">
                            🌐 Official County Website
                        </a>
                        @endif
                    </div>
                    @if(isset($mapInstitutions) && $mapInstitutions->count() > 0)
                    <div class="bg-white border border-gray-200 rounded-2xl p-5 max-h-[340px] overflow-y-auto">
                        <h4 class="text-xs font-bold text-gray-900 uppercase tracking-widest mb-3">{{ $mapInstitutions->count() }} Locations</h4>
                        <div class="space-y-2.5">
                            @foreach($mapInstitutions as $mi)
                            <a href="{{ route('counties.institution', $mi->slug) }}" class="flex items-center gap-2 text-sm text-gray-700 hover:text-kicc-gold transition-colors">
                                <span class="w-2 h-2 rounded-full bg-[#0B1E57] shrink-0"></span>
                                <span class="truncate">{{ $mi->name }}</span>
                                @if($mi->website)
                                <svg class="w-3 h-3 ml-auto text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                @endif
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
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
                    $sectorVideo = $sectorVideos[$s['sector_slug']] ?? null;
                    $entityVids = $sectorEntityVideos[$s['sector_slug']] ?? [];
                    $hasVideo = count($entityVids) > 0 || $sectorVideo;
                    $pitch = $sectorPitches[$s['sector_slug']] ?? '';
                @endphp
                <a href="{{ route('counties.sector', [$county->slug, $s['route']]) }}"
                   class="group bg-white border border-gray-200 hover:border-kicc-gold/40 rounded-2xl overflow-hidden transition-all block card-hover"
                   data-tilt="6" data-reveal data-reveal-delay="{{ $loop->index * 80 }}">
                    <div class="aspect-[4/3] overflow-hidden relative {{ $hasVideo ? 'bg-black' : 'bg-gradient-to-br from-[#0A1024] to-[#1a1a2e]' }}">
                        @if(count($entityVids) > 0)
                        @foreach($entityVids as $vi)
                        <video autoplay muted loop playsinline preload="auto" loading="lazy" class="absolute inset-0 w-full h-full object-cover hero-video-layer"
                               onerror="this.style.display='none'">
                            <source src="{{ $vi }}" type="video/mp4">
                        </video>
                        @endforeach
                        @elseif($sectorVideo)
                        @php $sectorWebm = $sectorWebmVideos[$s['sector_slug']] ?? null; @endphp
                        <video autoplay muted loop playsinline preload="auto" loading="lazy" class="absolute inset-0 w-full h-full object-cover"
                               onerror="this.style.display='none'">
                            @if($sectorWebm)
                            <source src="{{ $sectorWebm }}" type="video/webm">
                            @endif
                            <source src="{{ $sectorVideo }}" type="video/mp4">
                        </video>
                        @endif
                        @if($hasVideo)
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/10 to-black/30 pointer-events-none"></div>
                        @endif
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
                @php
                    $aKey = strtolower($a->category ?? 'default');
                @endphp
                <a href="{{ route('attractions.show', $a->id) }}" class="bg-white border border-gray-200 rounded-2xl overflow-hidden hover:border-kicc-gold/40 transition-all group card-hover">
                    <div class="h-36 overflow-hidden relative">
                        @if($a->image_url || ($attractionThumbs[$a->id] ?? null))
                        <x-fast-image :src="$attractionThumbs[$a->id] ?? $a->image_url" :alt="$a->name" :width="640" :quality="75" class="w-full h-full" />
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent pointer-events-none"></div>
                    </div>
                    <div class="p-4">
                        <div class="flex items-center gap-1.5 mb-1">
                            <span class="text-[10px] font-bold text-[#0B1E57] uppercase tracking-widest">{{ $a->category }}</span>
                        </div>
                        <div class="font-bold text-gray-900 text-sm">{{ $a->name }}</div>
                        @if($a->entry_fee)<div class="text-kicc-gold text-xs mt-1 font-bold">KES {{ number_format($a->entry_fee) }}</div>@endif
                    </div>
                </a>
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
                    $hKey = strtolower($h->category ?? 'hotel');
                    $stars = $h->star_rating ? str_repeat('★', $h->star_rating) . str_repeat('☆', 5 - $h->star_rating) : '—';
                @endphp
                <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden card-hover">
                    <div class="h-36 overflow-hidden relative">
                        @if($h->image_url || ($hotelThumbs[$h->id] ?? null))
                        <x-fast-image :src="$hotelThumbs[$h->id] ?? $h->image_url" :alt="$h->name" :width="640" :quality="75" class="w-full h-full" />
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent pointer-events-none"></div>
                    </div>
                    <div class="p-4">
                        <div class="font-bold text-gray-900 text-sm">{{ $h->name }}</div>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-[10px] font-bold text-[#0B1E57] uppercase tracking-wider">{{ $h->category }}</span>
                            <span class="text-amber-400 text-xs">{{ $stars }}</span>
                        </div>
                    </div>
                </div>
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
                <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden card-hover hover:border-kicc-gold/40 transition-all flex flex-col">
                    <div class="h-36 bg-gray-100 flex items-center justify-center overflow-hidden relative">
                        @php $firstVideo = $p->videos[0] ?? $p->video_url; @endphp
                        @if($firstVideo)
                        <video autoplay muted loop playsinline preload="auto" class="absolute inset-0 w-full h-full object-cover" onerror="this.style.display='none'">
                            <source src="{{ $firstVideo }}" type="video/mp4">
                        </video>
                        @elseif($p->image_url || ($productThumbs[$p->id] ?? null))
                        <x-fast-image :src="$productThumbs[$p->id] ?? $p->image_url" :alt="$p->name" :width="640" :quality="75" class="w-full h-full" />
                        @endif
                        <div class="absolute inset-0 flex items-center justify-center {{ $firstVideo ? 'opacity-0' : '' }}">
                        </div>
                    </div>
                    <div class="p-4 flex-1 flex flex-col">
                        <span class="text-[10px] font-bold text-[#0B1E57] uppercase tracking-widest">{{ $p->category }}</span>
                        <div class="font-bold text-gray-900 text-sm mt-1">{{ $p->name }}</div>
                        <div class="mt-auto pt-3 flex items-center justify-between">
                            <span class="font-black text-[#0B1E57] text-sm">KES {{ number_format($p->price) }}</span>
                            <span class="text-gray-400 text-xs">/ {{ $p->unit }}</span>
                        </div>
                        <a href="{{ route('county.product.booking', [$county->slug, $p->id]) }}" class="mt-3 block text-center py-2 rounded-xl bg-[#901C1E] text-white text-xs font-bold hover:bg-[#7b1618] transition-all">
                            {{ $p->booking_type === 'book' ? 'Book Now' : 'Order Now' }}
                        </a>
                    </div>
                </div>
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
                <a href="{{ route('exhibitions.show', $ex->slug) }}" class="bg-white border border-gray-200 rounded-2xl p-5 card-hover hover:border-kicc-gold/40 transition-all">
                    <div class="font-bold text-gray-900 text-sm">{{ $ex->name }}</div>
                    <div class="text-gray-400 text-xs mt-1">{{ $ex->start_date?->format('M d') }} – {{ $ex->end_date?->format('M d, Y') }}</div>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Packages strip --}}
        <div class="pb-12">
            @include('components.packages-strip')
        </div>
    </div>
</div>
@endsection