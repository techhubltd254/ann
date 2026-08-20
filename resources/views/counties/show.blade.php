@extends('layouts.app')

@section('title', $county->name . ' County — KICC Kenya')
@section('description', $county->tagline ?? 'Explore ' . $county->name . ' County')

@section('content')
@php
$iconMap = [
    'nature' => '🌲', 'adventure' => '🛶', 'agriculture' => '🍃', 'culture' => '🎭',
    'wildlife' => '🦁', 'beach' => '🏖️', 'historical' => '🏛️', 'waterfall' => '💧',
    'hotel' => '🏨', 'resort' => '🏝️', 'guest house' => '🏠', 'conference' => '🏢',
    'restaurant' => '🍽️', 'default' => '🏖️'
];
$kiccBlue = '#0B1E57';
@endphp
<div class="pt-20">
    {{-- HERO --}}
    <div class="relative min-h-[70vh] md:min-h-[85vh] overflow-hidden">
        @php
            $heroVideo = $countyMedia?->bestVideoUrl();
            $heroWebm = $countyMedia?->webmUrl();
            $heroMp4 = $countyMedia?->mp4Url();
            $heroPoster = $countyMedia?->posterUrl() ?? media('counties/' . $county->slug . '/hero.jpeg');
            $heroPosterImg = $countyMedia?->thumbnailUrl() ?? media('counties/' . $county->slug . '/hero.jpeg');
            $fallbackVideo = file_exists(public_path('videos/' . $county->slug . '.mp4')) ? asset('videos/' . $county->slug . '.mp4') : asset('videos/mombasa.mp4');
        @endphp
        <video autoplay muted loop playsinline controls
               poster="{{ $heroPoster }}"
               class="w-full h-full object-cover absolute inset-0"
               onerror="this.style.display='none';this.nextElementSibling.style.display='block'"
               style="cursor:pointer"
               id="county-hero-video">
            @if($heroWebm)
            <source src="{{ $heroWebm }}" type="video/webm">
            @endif
            @if($heroMp4)
            <source src="{{ $heroMp4 }}" type="video/mp4">
            @endif
            @if(!$heroVideo)
            <source src="{{ $fallbackVideo }}" type="video/mp4">
            <source src="{{ media('counties/' . $county->slug . '/showcase.mp4') }}" type="video/mp4">
            @endif
        </video>
        <img src="{{ $heroPosterImg }}" alt="{{ $county->name }}"
             class="w-full h-full object-cover absolute inset-0" style="display:none" loading="lazy"
             onerror="this.onerror=null;this.src='{{ asset('storage/kicc/hero-1.jpg') }}'">
        <div class="absolute inset-0 bg-gradient-to-t from-[#07090F] via-[#07090F]/50 to-transparent"></div>
        <div class="absolute bottom-0 left-0 right-0 max-w-7xl mx-auto px-5 pb-10 md:pb-16">
            <a href="{{ route('counties.index') }}" class="inline-flex items-center gap-1.5 text-white/60 hover:text-white text-sm mb-3 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                All Counties
            </a>
            <h1 class="text-4xl md:text-6xl font-black text-white leading-tight" data-split>{{ $county->name }} <span class="text-[#FFCD05]">County</span></h1>
            @if($county->tagline)
            <p class="text-white/70 text-lg mt-2 max-w-2xl">{{ $county->tagline }}</p>
            @endif
            <div class="flex flex-wrap gap-3 mt-4">
                @if($county->capital)<span class="text-white/50 text-sm">📍 {{ $county->capital }}</span>@endif
                @if($county->population_2024)<span class="text-white/50 text-sm">👥 {{ number_format($county->population_2024) }} people</span>@endif
                @if($county->area_km2)<span class="text-white/50 text-sm">📐 {{ number_format($county->area_km2) }} km²</span>@endif
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-5">
        {{-- Glassy description bar --}}
        @if($county->description || $county->capital || $county->population_2024 || $county->area_km2)
        <div class="relative z-10 -mt-32 md:-mt-40 mb-10 rounded-2xl p-6 border border-white/20 shadow-2xl" style="background: rgba(255,255,255,0.08); backdrop-filter: blur(14px);">
            @if($county->description)
            <p class="text-white/85 text-sm leading-relaxed mb-4">{{ $county->description }}</p>
            @endif
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                @if($county->capital)<div><div class="text-xs text-white/50 uppercase tracking-wider">Capital</div><div class="text-white font-bold text-lg">{{ $county->capital }}</div></div>@endif
                @if($county->population_2024)<div><div class="text-xs text-white/50 uppercase tracking-wider">Population</div><div class="text-white font-bold text-lg">{{ number_format($county->population_2024) }}</div></div>@endif
                @if($county->area_km2)<div><div class="text-xs text-white/50 uppercase tracking-wider">Area</div><div class="text-white font-bold text-lg">{{ number_format($county->area_km2) }} km²</div></div>@endif
                @if($county->economic_zone)<div><div class="text-xs text-white/50 uppercase tracking-wider">Economic Zone</div><div class="text-white font-bold text-lg">{{ $county->economic_zone }}</div></div>@endif
            </div>
        </div>
        @endif

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
                @endphp
                <a href="{{ route('counties.sector', [$county->slug, $s['route']]) }}"
                   class="group relative bg-white border border-gray-200 hover:border-kicc-gold/40 rounded-2xl p-5 text-center transition-all block card-hover overflow-hidden" data-tilt="6" data-reveal data-reveal-delay="{{ $loop->index * 80 }}">
                    @if($sectorVideo)
                    <video autoplay muted loop playsinline class="absolute inset-0 w-full h-full object-cover opacity-0 group-hover:opacity-20 transition-opacity duration-700"
                           onmouseover="this.play()" onmouseout="this.pause()"
                           onloadeddata="this.style.opacity='0.15'">
                        <source src="{{ $sectorVideo }}" type="video/mp4">
                    </video>
                    @endif
                    <div class="relative z-10">
                        <div class="w-12 h-12 rounded-xl mx-auto mb-3 flex items-center justify-center text-2xl bg-gray-100 group-hover:bg-[#FFCD05]/20 transition-colors">
                            {{ $s['icon'] }}
                        </div>
                        <div class="font-bold text-gray-900 text-sm leading-snug">{{ $name }}</div>
                        <div class="text-gray-400 text-xs mt-1">{{ $s['count'] }} {{ Str::plural('entity', $s['count']) }}</div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>

        {{-- GOVERNMENT DEPARTMENTS --}}
        @if(($linkedSectors ?? collect())->isNotEmpty())
        <div class="mb-14">
            <div class="flex items-center gap-3 mb-6">
                <span class="h-px w-8 bg-[#0B1E57]"></span>
                <span class="text-[#0B1E57] text-xs font-bold tracking-[0.2em] uppercase">Government Departments</span>
                <span class="h-px flex-1 bg-gray-200"></span>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach($linkedSectors as $ls)
                <a href="{{ route('county.admin.pro', [$county->slug, 'tab' => 'sectors']) }}" class="px-3.5 py-2 rounded-full text-xs font-bold bg-gray-100 text-gray-700 border border-gray-200 hover:bg-[#0B1E57]/10 hover:text-[#0B1E57] hover:border-[#0B1E57]/40 transition-all">
                    {{ $ls->name }}
                </a>
                @endforeach
            </div>
            <p class="text-gray-400 text-xs mt-3">Sourced from official county websites. <a href="{{ route('county.admin.pro', [$county->slug, 'tab' => 'sectors']) }}" class="text-[#0B1E57] hover:underline">Manage in County Admin</a>.</p>
        </div>
        @endif

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
                    $aIcon = $iconMap[$aKey] ?? $iconMap['default'];
                @endphp
                <a href="{{ route('attractions.show', $a->id) }}" class="bg-white border border-gray-200 rounded-2xl overflow-hidden hover:border-kicc-gold/40 transition-all group card-hover">
                    <div class="h-36 bg-gray-100 flex items-center justify-center overflow-hidden relative">
                        @if($a->image_url)
                        <img src="{{ $a->image_url }}" alt="{{ $a->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" onerror="this.style.display='none'">
                        @endif
                        <div class="absolute inset-0 flex items-center justify-center {{ $a->image_url ? 'opacity-0 group-hover:opacity-100 transition-opacity bg-black/30' : '' }}">
                            <span class="text-4xl {{ $a->image_url ? 'text-white drop-shadow-lg' : 'text-gray-300' }} group-hover:scale-110 transition-transform">{{ $aIcon }}</span>
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="flex items-center gap-1.5 mb-1">
                            <span class="text-xs">{{ $aIcon }}</span>
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
                    $hIcon = $iconMap[$hKey] ?? $iconMap['hotel'];
                    $stars = $h->star_rating ? str_repeat('★', $h->star_rating) . str_repeat('☆', 5 - $h->star_rating) : '—';
                @endphp
                <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden card-hover">
                    <div class="h-36 bg-gray-100 flex items-center justify-center overflow-hidden relative">
                        @if($h->image_url)
                        <img src="{{ $h->image_url }}" alt="{{ $h->name }}" class="w-full h-full object-cover" onerror="this.style.display='none'">
                        @endif
                        <div class="absolute inset-0 flex items-center justify-center {{ $h->image_url ? 'opacity-0' : '' }}">
                            <span class="text-4xl text-gray-300">{{ $hIcon }}</span>
                        </div>
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
                        @if($p->image_url)
                        <img src="{{ $p->image_url }}" alt="{{ $p->name }}" class="w-full h-full object-cover" onerror="this.style.display='none'">
                        @endif
                        <div class="absolute inset-0 flex items-center justify-center {{ $p->image_url ? 'opacity-0' : '' }}">
                            <span class="text-4xl text-gray-300">🛍️</span>
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