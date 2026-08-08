@extends('layouts.app')

@section('title', $county->name . ' County — KICC Kenya')
@section('description', $county->tagline ?? 'Explore ' . $county->name . ' County')

@section('content')
<div class="pt-20">
    {{-- HERO --}}
    <div class="relative min-h-[70vh] md:min-h-[85vh] overflow-hidden">
        @php
            $heroVideo = $countyMedia?->bestVideoUrl();
            $heroWebm = $countyMedia?->webmUrl();
            $heroMp4 = $countyMedia?->mp4Url();
            $heroPoster = $countyMedia?->posterUrl() ?? media('counties/' . $county->slug . '/hero.jpeg');
            $heroPosterImg = $countyMedia?->thumbnailUrl() ?? media('counties/' . $county->slug . '/hero.jpeg');
        @endphp
        <video autoplay muted loop playsinline
               poster="{{ $heroPoster }}"
               class="w-full h-full object-cover absolute inset-0"
               onloadeddata="this.style.opacity='1'"
               onerror="this.style.display='none';this.nextElementSibling.style.display='block'"
               style="opacity:0;transition:opacity 0.8s">
            @if($heroWebm)
            <source src="{{ $heroWebm }}" type="video/webm">
            @endif
            @if($heroMp4)
            <source src="{{ $heroMp4 }}" type="video/mp4">
            @endif
            @if(!$heroVideo)
            {{-- immersive 3D cinematic showcase (depth-parallax through the sectors) first, then any showcase --}}
            <source src="{{ media('counties/' . $county->slug . '/immersive.mp4') }}" type="video/mp4">
            <source src="/videos/{{ $county->slug }}.mp4" type="video/mp4">
            <source src="{{ media('counties/' . $county->slug . '/showcase.mp4') }}" type="video/mp4">
            @endif
        </video>
        <img src="{{ $heroPosterImg }}" alt="{{ $county->name }}"
             class="w-full h-full object-cover absolute inset-0" style="display:none" loading="lazy">
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
                    <a href="{{ route('county.admin.pro', $county->slug) }}" class="px-4 py-2 rounded-xl bg-[#11820B] text-white text-xs font-bold hover:bg-[#0d9488] transition-all">County Admin</a>
                    @endif
                @else
                <a href="{{ route('login') }}" class="px-4 py-2 rounded-xl border border-[#11820B]/40 text-[#11820B] text-xs font-bold hover:bg-[#11820B]/10 transition-all">County Admin Login</a>
                @endauth
            </div>
        </div>

        {{-- ECONOMIC SECTORS — rich tiles; image slot fills when admin uploads sector media --}}
        <div class="mb-14">
            <div class="flex items-center gap-3 mb-6">
                <span class="h-px w-8 bg-kicc-gold"></span>
                <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">Economic Sectors</span>
                <span class="h-px flex-1 bg-gray-200"></span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                @foreach($sectorData as $name => $s)
                @php
                    $tileImg = media('counties/' . $county->slug . '/' . $s['route'] . '.jpeg');
                @endphp
                <a href="{{ route('counties.sector', [$county->slug, $s['route']]) }}"
                   class="group bg-white border border-gray-200 hover:border-kicc-gold/40 rounded-2xl overflow-hidden transition-all block hover:shadow-lg hover:-translate-y-0.5" data-reveal data-reveal-delay="{{ $loop->index * 60 }}">
                    <div class="relative h-36 overflow-hidden bg-gradient-to-br from-[#0A1024] to-[#901C1E]/60">
                        {{-- Holographic tile: depth-wiggle video (autoplay muted loop). Falls back to the still image. --}}
                        <video autoplay muted loop playsinline preload="none"
                               poster="{{ media('counties/' . $county->slug . '/' . $s['route'] . '.jpeg') }}"
                               class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                               onerror="this.remove()">
                            <source src="{{ '/media/derivatives/holo/' . $county->slug . '-' . $s['route'] . '/wiggle.mp4' }}" type="video/mp4">
                        </video>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/10 to-transparent"></div>
                        <span class="absolute top-2.5 right-3 text-[10px] font-bold px-2 py-0.5 rounded-full bg-black/40 text-white/90 backdrop-blur-sm">
                            {{ $s['count'] }} {{ Str::plural('entity', $s['count']) }}
                        </span>
                    </div>
                    <div class="p-4">
                        <div class="font-bold text-gray-900 text-sm leading-snug group-hover:text-[#901C1E] transition-colors">{{ $name }}</div>
                        <p class="text-gray-500 text-xs leading-relaxed mt-1 line-clamp-2">{{ $s['desc'] }}</p>
                        <div class="flex items-center justify-between mt-3 pt-2.5 border-t border-gray-100">
                            <span class="text-xs font-bold text-[#901C1E] group-hover:underline">Explore sector</span>
                            <span class="text-gray-300 group-hover:text-[#901C1E] group-hover:translate-x-0.5 transition-all">&rarr;</span>
                        </div>
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
                <a href="{{ route('attractions.show', $a->id) }}" class="bg-white border border-gray-200 rounded-2xl overflow-hidden hover:border-kicc-gold/40 transition-all group card-hover">
                    <div class="h-36 bg-gray-100 flex items-center justify-center overflow-hidden">
                        <span class="text-4xl text-gray-300 group-hover:scale-110 transition-transform">🏖️</span>
                    </div>
                    <div class="p-4">
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
                <div class="bg-white border border-gray-200 rounded-2xl p-4 card-hover">
                    <div class="font-bold text-gray-900 text-sm">{{ $h->name }}</div>
                    <div class="text-gray-400 text-xs mt-1">{{ $h->star_rating ?? '—' }} ★</div>
                </div>
                @endforeach
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