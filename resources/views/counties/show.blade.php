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
$kiccBlue = '#046bd2';
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
        <video autoplay muted loop playsinline
               poster="{{ $heroPoster }}"
               class="w-full h-full object-cover absolute inset-0"
               data-depth="0.35" data-parallax-scroll
               onloadeddata="this.style.opacity='1'"
               onerror="this.style.display='none';this.nextElementSibling.style.display='block'"
               style="opacity:1">
            @if($heroWebm)
            <source src="{{ $heroWebm }}" type="video/webm">
            @endif
            @if($heroMp4)
            <source src="{{ $heroMp4 }}" type="video/mp4">
            @endif
            @if(!$heroVideo)
            @if($county->slug === 'muranga')
            <source src="{{ media('kicc/4d/clips/drone_aerial.mp4') }}" type="video/mp4">
            <source src="{{ media('kicc/4d/clips/scenic_wide.mp4') }}" type="video/mp4">
            @endif
            <source src="{{ media('counties/' . $county->slug . '/sectors-tour.mp4') }}" type="video/mp4">
            <source src="{{ media('counties/' . $county->slug . '/showcase.mp4') }}" type="video/mp4">
            <source src="{{ $fallbackVideo }}" type="video/mp4">
            @endif
        </video>
        <img src="{{ $heroPosterImg }}" alt="{{ $county->name }}"
             class="w-full h-full object-cover absolute inset-0" style="display:none" loading="lazy"
             onerror="this.onerror=null;this.src='{{ asset('storage/kicc/hero-1.jpg') }}'">
        <div class="absolute inset-0 bg-gradient-to-t from-[#07090F] via-[#07090F]/50 to-transparent"></div>
        <canvas id="county-3d-terrain" class="absolute inset-0 w-full h-full pointer-events-none" style="mix-blend-mode:screen;opacity:0.5"></canvas>
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
            </div>
        </div>

        {{-- GOVERNMENT DEPARTMENTS TILES --}}
        @if(($linkedSectors ?? collect())->isNotEmpty())
        <div class="mb-14">
            <div class="flex items-center gap-3 mb-6">
                <span class="h-px w-8 bg-[#046bd2]"></span>
                <span class="text-[#046bd2] text-xs font-bold tracking-[0.2em] uppercase">Government Departments</span>
                <span class="h-px flex-1 bg-gray-200"></span>
            </div>
            @php
            $govIcons = [
                'Agriculture' => '🌾', 'Commerce & End Products' => '🛒', 'Culture' => '🎭',
                'Education' => '📚', 'Education and Technical Training' => '🎓',
                'Empowering Farmers and Traders' => '👨‍🌾',
                'Environment, Natural Resources, Water and Irrigation' => '🌍',
                'Finance and Economic Planning' => '💰', 'Health and Sanitation' => '🩺',
                'Healthcare' => '🏥', 'Hospitality' => '🏨',
                'Infrastructure, Roads, Housing and Transport' => '🏗️',
                'Lands, Planning and Urban Development' => '🏛️',
                'Quality Healthcare for All' => '❤️',
                'Tourism' => '🏖️', 'Transport' => '🚢',
            ];
            $govGradients = [
                'Agriculture' => 'from-emerald-500 to-green-600',
                'Commerce' => 'from-amber-500 to-orange-600',
                'Culture' => 'from-violet-500 to-purple-600',
                'Education' => 'from-blue-500 to-indigo-600',
                'Empowering' => 'from-teal-500 to-emerald-600',
                'Environment' => 'from-green-500 to-teal-600',
                'Finance' => 'from-yellow-500 to-amber-600',
                'Health' => 'from-red-500 to-rose-600',
                'Hospitality' => 'from-pink-500 to-rose-600',
                'Infrastructure' => 'from-slate-500 to-gray-600',
                'Lands' => 'from-stone-500 to-brown-600',
                'Quality' => 'from-rose-500 to-red-600',
                'Tourism' => 'from-sky-500 to-cyan-600',
                'Transport' => 'from-cyan-500 to-blue-600',
            ];
            @endphp
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($linkedSectors as $ls)
                @php
                $icon = $govIcons[$ls->name] ?? '📋';
                $grad = 'from-[#046bd2] to-[#045cb4]';
                foreach ($govGradients as $key => $g) {
                    if (str_contains($ls->name, explode(' ', $key)[0])) { $grad = $g; break; }
                }
                @endphp
                <a href="{{ route('counties.sector', [$county->slug, $ls->slug]) }}"
                   class="group rounded-2xl overflow-hidden transition-all card-hover block"
                   data-reveal data-reveal-delay="{{ $loop->index * 60 }}">
                    <div class="h-28 relative overflow-hidden bg-gray-900">
                        @php
                        $govVideos = [
                            'Tourism' => 'tourism', 'Hospitality' => 'hospitality',
                            'Agriculture' => 'agriculture', 'Commerce & End Products' => 'commerce',
                            'Education' => 'education', 'Culture' => 'culture',
                            'Healthcare' => 'health', 'Transport' => 'transport',
                            'Education and Technical Training' => 'edu_technical',
                            'Empowering Farmers and Traders' => 'empowering',
                            'Environment, Natural Resources, Water and Irrigation' => 'environment',
                            'Finance and Economic Planning' => 'finance',
                            'Health and Sanitation' => 'health',
                            'Infrastructure, Roads, Housing and Transport' => 'infrastructure',
                        ];
                        $vFile = $govVideos[$ls->name] ?? null;
                        $vHls = $vFile ? media('kicc/4d/hls/' . $vFile . '/master.m3u8') : null;
                        @endphp
                        @if($vHls)
                        <video class="w-full h-full object-cover absolute inset-0" id="hls-{{ $loop->index }}" autoplay muted loop playsinline preload="auto"></video>
                        <script>
                        (function(){var v=document.getElementById('hls-{{ $loop->index }}');var s='{{ $vHls }}';if(typeof Hls!=='undefined'&&Hls.isSupported()){var h=new Hls({enableWorker:true,maxBufferLength:10,maxMaxBufferLength:20,backBufferLength:5,lowLatencyMode:true});h.loadSource(s);h.attachMedia(v);h.on(Hls.Events.MANIFEST_PARSED,function(){v.play().catch(function(){})});}else if(v.canPlayType('application/vnd.apple.mpegurl')){v.src=s;v.addEventListener('loadedmetadata',function(){v.play()});}v.addEventListener('ended',function(){v.currentTime=0;v.play()});})();
                        </script>
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent pointer-events-none"></div>
                        <div class="absolute bottom-2 left-2 w-8 h-8 rounded-lg flex items-center justify-center text-lg bg-white/90 shadow">{{ $icon }}</div>
                    </div>
                    <div class="p-3 bg-white border border-gray-200 border-t-0 rounded-b-2xl text-center">
                        <div class="font-bold text-gray-900 text-xs leading-snug">{{ $ls->name }}</div>
                    </div>
                </a>
                @endforeach
            </div>
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
                    // 4D video from media slot (uploaded by county admin) takes priority;
                    // fall back to pipeline-generated wiggle bursts
                    $wiggleMap = [
                        'Tea Highlands Tour' => 'library/tea-farms/_3d/burst-00/wiggle.mp4',
                        'Mugumo-ini Falls Canyoning' => 'library/adventure-rappelling/_3d/burst-00/wiggle.mp4',
                    ];
                    $wiggle = null;
                    if (isset($a->media4d) && $a->media4d) {
                        $wiggle = $a->media4d->url();
                    } elseif (isset($wiggleMap[$a->name])) {
                        $wiggle = media('counties/' . $county->slug . '/' . $wiggleMap[$a->name]);
                    }
                @endphp
                <a href="{{ route('attractions.show', $a->id) }}" class="bg-white border border-gray-200 rounded-2xl overflow-hidden hover:border-kicc-gold/40 transition-all group card-hover fx-sweep" data-tilt="7">
                    <div class="h-36 bg-gray-100 flex items-center justify-center overflow-hidden relative" @if($wiggle) data-wiggle="{{ $wiggle }}" @endif>
                        @if($a->image_url)
                        <img src="{{ $a->image_url }}" alt="{{ $a->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" onerror="this.style.display='none'">
                        @endif
                        @if($wiggle)
                        <span class="absolute top-2 right-2 z-10 px-2 py-0.5 rounded-full bg-black/60 text-white text-[9px] font-black uppercase tracking-wider">3D</span>
                        @endif
                        <div class="absolute inset-0 flex items-center justify-center {{ $a->image_url ? 'opacity-0 group-hover:opacity-100 transition-opacity bg-black/30' : '' }}">
                            <span class="text-4xl {{ $a->image_url ? 'text-white drop-shadow-lg' : 'text-gray-300' }} group-hover:scale-110 transition-transform">{{ $aIcon }}</span>
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="flex items-center gap-1.5 mb-1">
                            <span class="text-xs">{{ $aIcon }}</span>
                            <span class="text-[10px] font-bold text-[#046bd2] uppercase tracking-widest">{{ $a->category }}</span>
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
                <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden card-hover fx-sweep" data-tilt="7">
                    <div class="h-36 bg-gray-100 flex items-center justify-center overflow-hidden relative" @if($h->media4d ?? null) data-wiggle="{{ $h->media4d->url() }}" @endif>
                        @if($h->image_url)
                        <img src="{{ $h->image_url }}" alt="{{ $h->name }}" class="w-full h-full object-cover" onerror="this.style.display='none'">
                        @endif
                        @if($h->media4d ?? null)
                        <span class="absolute top-2 right-2 z-10 px-2 py-0.5 rounded-full bg-black/60 text-white text-[9px] font-black uppercase tracking-wider">4D</span>
                        @endif
                        <div class="absolute inset-0 flex items-center justify-center {{ $h->image_url ? 'opacity-0' : '' }}">
                            <span class="text-4xl text-gray-300">{{ $hIcon }}</span>
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="font-bold text-gray-900 text-sm">{{ $h->name }}</div>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-[10px] font-bold text-[#046bd2] uppercase tracking-wider">{{ $h->category }}</span>
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
                <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden card-hover fx-sweep hover:border-kicc-gold/40 transition-all flex flex-col" data-tilt="7">
                    <div class="h-36 bg-gray-100 flex items-center justify-center overflow-hidden relative" @if($p->media4d ?? null) data-wiggle="{{ $p->media4d->url() }}" @endif>
                        @if($p->image_url)
                        <img src="{{ $p->image_url }}" alt="{{ $p->name }}" class="w-full h-full object-cover" onerror="this.style.display='none'">
                        @endif
                        @if($p->media4d ?? null)
                        <span class="absolute top-2 right-2 z-10 px-2 py-0.5 rounded-full bg-black/60 text-white text-[9px] font-black uppercase tracking-wider">4D</span>
                        @endif
                        <div class="absolute inset-0 flex items-center justify-center {{ $p->image_url ? 'opacity-0' : '' }}">
                            <span class="text-4xl text-gray-300">🛍️</span>
                        </div>
                    </div>
                    <div class="p-4 flex-1 flex flex-col">
                        <span class="text-[10px] font-bold text-[#046bd2] uppercase tracking-widest">{{ $p->category }}</span>
                        <div class="font-bold text-gray-900 text-sm mt-1">{{ $p->name }}</div>
                        <div class="mt-auto pt-3 flex items-center justify-between">
                            <span class="font-black text-[#046bd2] text-sm">KES {{ number_format($p->price) }}</span>
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
                <a href="{{ route('marketplace.index', ['county' => $county->slug]) }}" class="inline-flex items-center gap-2 text-sm font-bold text-[#046bd2] hover:text-[#901C1E] transition-colors">
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

        {{-- Export opportunities from trade agreements --}}
        @if(($countyTradeAgreements ?? collect())->isNotEmpty() || ($countyTradeBlocs ?? collect())->isNotEmpty())
        <div class="mb-14">
            <div class="flex items-center gap-3 mb-6">
                <span class="h-px w-8 bg-[#046bd2]"></span>
                <span class="text-[#046bd2] text-xs font-bold tracking-[0.2em] uppercase">Export Opportunities</span>
                <span class="h-px flex-1 bg-gray-200"></span>
            </div>
            <div class="bg-gradient-to-r from-[#046bd2]/5 to-[#045cb4]/5 border border-[#046bd2]/20 rounded-2xl p-6">
                @if(($countyTradeAgreements ?? collect())->isNotEmpty())
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h3 class="font-bold text-gray-900 text-sm">Take {{ $county->name }} products to the world</h3>
                    <a href="{{ route('trade.agreements.index') }}" class="text-xs font-bold text-[#046bd2] hover:underline">All agreements →</a>
                </div>
                <div class="grid sm:grid-cols-3 gap-4">
                    @foreach($countyTradeAgreements as $a)
                    <a href="{{ route('trade.agreements.show', $a->slug) }}" class="bg-white border border-gray-200 rounded-xl p-4 hover:border-[#046bd2]/40 transition-all card-hover">
                        <span class="text-[10px] font-bold text-[#046bd2]">{{ $a->bloc?->name ?? $a->agreement_type }}</span>
                        <h4 class="font-bold text-gray-900 text-sm mt-1 leading-snug">{{ $a->title }}</h4>
                        <p class="text-gray-400 text-xs mt-1 line-clamp-2">{{ $a->summary }}</p>
                    </a>
                    @endforeach
                </div>
                @endif
                @if(($countyTradeBlocs ?? collect())->isNotEmpty())
                <div class="flex flex-wrap gap-2 mt-5 pt-5 border-t border-[#046bd2]/10">
                    <span class="text-xs text-gray-400 self-center mr-1">Member of:</span>
                    @foreach($countyTradeBlocs as $b)
                    <a href="{{ route('trade.blocs.show', $b->slug) }}" class="px-3 py-1 rounded-full text-[10px] font-bold bg-white border border-gray-200 text-gray-600 hover:border-[#046bd2]/40 hover:text-[#046bd2] transition-all">{{ $b->name }}</a>
                    @endforeach
                </div>
                @endif
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

@push('scripts')
{{-- Three.js 3D particle terrain — Murang'a highlands wireframe under the hero --}}
<script type="module">
import * as THREE from 'three';
(function () {
    const canvas = document.getElementById('county-3d-terrain');
    if (!canvas) return;
    const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(55, 2, 0.1, 100);
    camera.position.set(0, 2.1, 4.2);
    camera.lookAt(0, 0.4, 0);

    const W = 90, H = 90, SEP = 0.16;
    const geo = new THREE.PlaneGeometry(W * SEP, H * SEP, W - 1, H - 1);
    geo.rotateX(-Math.PI / 2);
    const pos = geo.attributes.position;
    const base = new Float32Array(pos.count);
    for (let i = 0; i < pos.count; i++) {
        const x = pos.getX(i), z = pos.getZ(i);
        base[i] = Math.sin(x * 0.9) * Math.cos(z * 0.7) * 0.35
                + Math.sin(x * 0.35 + z * 0.5) * 0.55
                + Math.cos(x * 1.7 + z * 1.3) * 0.12;
    }
    const mat = new THREE.PointsMaterial({ color: 0x046bd2, size: 0.035, transparent: true, opacity: 0.85 });
    const pts = new THREE.Points(geo, mat);
    scene.add(pts);
    const wire = new THREE.LineSegments(new THREE.WireframeGeometry(geo),
        new THREE.LineBasicMaterial({ color: 0xFFCD05, transparent: true, opacity: 0.06 }));
    scene.add(wire);

    function resize() {
        const w = canvas.clientWidth, h = canvas.clientHeight;
        if (canvas.width !== w || canvas.height !== h) {
            renderer.setSize(w, h, false);
            camera.aspect = w / h; camera.updateProjectionMatrix();
        }
    }
    let t = 0;
    (function loop() {
        requestAnimationFrame(loop);
        resize();
        t += 0.008;
        for (let i = 0; i < pos.count; i++) {
            const x = pos.getX(i), z = pos.getZ(i);
            pos.setY(i, base[i] + Math.sin(x * 1.4 + t) * Math.cos(z * 1.1 + t * 0.8) * 0.08);
        }
        pos.needsUpdate = true;
        pts.rotation.y = wire.rotation.y = Math.sin(t * 0.25) * 0.12;
        renderer.render(scene, camera);
    })();
})();
</script>
@endpush