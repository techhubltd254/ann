@extends('layouts.app')

@section('title', 'KICC Global Exhibition Platform')
@section('description', 'Africa\'s Premier Meeting Venue. A national icon since 1973.')

@section('content')
{{-- ═══ HERO — Parallax tower + floating orbs + animated headline ═══ --}}
<section class="relative min-h-[92vh] flex items-center overflow-hidden">
    <img src="{{ media('kicc/tower-night.jpg') }}" alt="KICC Tower"
         class="absolute inset-0 w-full h-full object-cover" data-parallax="0.25">
    <div class="absolute inset-0 bg-gradient-to-r from-[#07090F] via-[#07090F]/75 to-transparent"></div>
    <div class="absolute inset-0 bg-gradient-to-t from-[#07090F] via-transparent to-[#07090F]/40"></div>

    {{-- Floating light orbs --}}
    <div class="absolute w-72 h-72 rounded-full bg-[#FFCD05]/10 blur-3xl animate-float-slow top-[15%] right-[10%]"></div>
    <div class="absolute w-96 h-96 rounded-full bg-[#901C1E]/15 blur-3xl animate-float-slower bottom-[5%] left-[35%]"></div>
    <div class="absolute w-40 h-40 rounded-full bg-[#0B1E57]/25 blur-2xl animate-float-slow top-[55%] right-[35%]"></div>

    <div class="relative max-w-7xl mx-auto px-5 pt-28 pb-20 w-full grid md:grid-cols-2 gap-10 items-center">
        <div>
            <div class="flex items-center gap-3 mb-6" data-reveal>
                <div class="h-px w-10 bg-kicc-gold"></div>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold tracking-wide border bg-[#FFCD05]/15 text-[#FFCD05] border-[#FFCD05]/30">
                    Africa's Premier Meeting Venue — Global Exhibition Platform
                </span>
            </div>
            <h1 class="text-5xl md:text-7xl font-black text-white leading-[1.0] tracking-tight">
                <span class="block" data-reveal>Kenya's</span>
                <span class="block text-shimmer" data-reveal data-reveal-delay="120">Digital</span>
                <span class="block" data-reveal data-reveal-delay="240">Economy</span>
                <span class="block" data-reveal data-reveal-delay="360">Gateway</span>
            </h1>
            <p class="text-white/55 text-lg leading-relaxed mt-6 max-w-md" data-reveal data-reveal-delay="450">
                From 47 county markets to world-class exhibition halls — KICC connects Kenya's entire economy on one platform.
            </p>
            <div class="mt-8 flex flex-wrap gap-3" data-reveal data-reveal-delay="550">
                <a href="{{ route('counties.index') }}" data-magnetic
                   class="inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-8 text-base h-14 rounded-xl bg-[#901C1E] text-white hover:bg-[#7b1618] active:scale-[0.97] animate-pulse-glow">
                    Explore Counties
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
                <a href="{{ route('exhibition-3d.map') }}" data-magnetic
                   class="inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-8 text-base h-14 rounded-xl border border-kicc-gold/40 text-kicc-gold hover:bg-kicc-gold/10 hover:border-kicc-gold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                    3D Virtual Tour
                </a>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4" data-reveal="zoom" data-reveal-delay="300">
            @php $stats = [['value'=>52,'suffix'=>'+','label'=>'Years of Excellence'],['value'=>47,'suffix'=>'','label'=>'Kenya Counties'],['value'=>18,'suffix'=>'','label'=>'Digital Screens'],['value'=>200,'suffix'=>'+','label'=>'Events per Year']]; @endphp
            @foreach($stats as $i => $s)
            <div class="bg-white/5 backdrop-blur-sm border border-white/12 rounded-2xl p-6 card-hover" data-tilt="10">
                <div class="tilt-glare"></div>
                <div class="text-4xl font-black text-kicc-gold">
                    <span data-count="{{ $s['value'] }}" data-count-duration="{{ 1400 + $i * 250 }}">0</span><span class="text-2xl">{{ $s['suffix'] }}</span>
                </div>
                <div class="text-white/50 text-xs font-medium mt-2 leading-snug">{{ $s['label'] }}</div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Scroll cue --}}
    <div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2 text-white/30" data-reveal data-reveal-delay="800">
        <span class="text-[10px] font-bold tracking-[0.25em] uppercase">Scroll</span>
        <div class="w-px h-8 bg-gradient-to-b from-kicc-gold to-transparent"></div>
    </div>
</section>

{{-- ═══ COUNTY STRIP ═══ --}}
<div data-reveal>
<x-county-strip :counties="$counties" />
</div>

{{-- ═══ 3D EXPERIENCES — interactive cards ═══ --}}
<section class="py-20 bg-[#07090F] relative overflow-hidden">
    <div class="absolute w-[500px] h-[500px] rounded-full bg-[#0B1E57]/10 blur-3xl -top-40 -right-40"></div>
    <div class="max-w-7xl mx-auto px-5 relative">
        <div class="mb-10" data-reveal>
            <div class="flex items-center gap-3 mb-3">
                <div class="h-px w-8 bg-kicc-gold"></div>
                <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">Immersive</span>
            </div>
            <h2 class="text-3xl md:text-4xl font-black text-white leading-[1.1]">Step Inside <span class="text-kicc-gold">Kenya in 3D</span></h2>
            <p class="text-white/40 mt-3 text-base max-w-xl leading-relaxed">Explore all 47 counties, walk the exhibition hall, or upload your own space — right from your phone.</p>
        </div>
        <div class="grid md:grid-cols-3 gap-5">
            @php $experiences = [
                ['route'=>'exhibition-3d.map','emoji'=>'🗺️','title'=>'47 Counties 3D Map','desc'=>'Fly over Kenya and tap any county for sectors, photos, and stats.','color'=>'#0B1E57'],
                ['route'=>'exhibition-3d.booth','emoji'=>'🏛️','title'=>'Exhibition Hall Tour','desc'=>'Walk the KICC hall in 3D. Click booths to play video showcases.','color'=>'#901C1E'],
                ['route'=>'room3d.index','emoji'=>'🏠','title'=>'3D Room Explorer','desc'=>'Upload photos of any room — explore it with phone gyroscope.','color'=>'#FFCD05'],
            ]; @endphp
            @foreach($experiences as $i => $x)
            <a href="{{ route($x['route']) }}" class="group relative bg-[#0D1220] border border-white/8 rounded-2xl p-7 overflow-hidden card-hover block" data-tilt="7" data-reveal data-reveal-delay="{{ $i * 120 }}">
                <div class="tilt-glare"></div>
                <div class="absolute -top-10 -right-10 w-32 h-32 rounded-full blur-2xl opacity-25 transition-opacity group-hover:opacity-50" style="background: {{ $x['color'] }}"></div>
                <div class="text-4xl mb-4 relative">{{ $x['emoji'] }}</div>
                <h3 class="font-black text-white text-lg relative">{{ $x['title'] }}</h3>
                <p class="text-white/45 text-sm mt-2 leading-relaxed relative">{{ $x['desc'] }}</p>
                <div class="mt-5 inline-flex items-center gap-2 text-sm font-bold text-kicc-gold relative">
                    Launch Experience
                    <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </div>
            </a>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══ MARKETPLACE PRODUCTS ═══ --}}
<section class="border-y border-white/8 py-20 bg-[#0D1220] relative overflow-hidden">
    <div class="absolute w-[400px] h-[400px] rounded-full bg-[#FFCD05]/5 blur-3xl top-0 left-1/3"></div>
    <div class="max-w-7xl mx-auto px-5 relative">
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-4" data-reveal>
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <div class="h-px w-8 bg-kicc-gold"></div>
                    <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">KICC Marketplace</span>
                </div>
                <h2 class="text-3xl md:text-4xl font-black text-white leading-[1.1]">Authentic Kenyan<br><span class="text-kicc-gold">Products</span></h2>
                <p class="text-white/40 mt-3 text-base max-w-xl leading-relaxed">Directly from county producers across Kenya.</p>
            </div>
            <a href="{{ route('marketplace.index') }}" data-magnetic class="inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-4 text-xs h-9 rounded-xl bg-white/10 text-white hover:bg-white/20 border border-white/15 shrink-0">
                Browse all
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @php $featured = $products ?? collect([]); @endphp
            @forelse($featured->take(8) as $i => $product)
            <a href="{{ route('marketplace.show', $product->slug) }}" class="group bg-[#141B2E] rounded-2xl overflow-hidden border border-white/8 hover:border-[#FFCD05]/30 transition-all text-left block card-hover" data-reveal data-reveal-delay="{{ ($i % 4) * 70 }}">
                <div class="aspect-square overflow-hidden bg-[#0D1220]">
                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                         onerror="this.src='{{ media('kicc/kicc-logo.png') }}'">
                </div>
                <div class="p-4">
                    <div class="text-[10px] font-bold text-kicc-gold uppercase tracking-widest mb-1">{{ $product->county?->name ?? 'Kenya' }} · {{ $product->category?->name ?? 'Product' }}</div>
                    <div class="font-bold text-white text-sm leading-snug line-clamp-2 mb-2">{{ $product->name }}</div>
                    <div class="font-black text-kicc-gold text-base">KES {{ number_format($product->price ?? 0) }}</div>
                </div>
            </a>
            @empty
            <div class="col-span-4 text-center py-16 text-white/30">Products loading...</div>
            @endforelse
        </div>
    </div>
</section>

{{-- ═══ EXHIBITIONS ═══ --}}
<section class="max-w-7xl mx-auto px-5 py-20">
    <div class="mb-10" data-reveal>
        <div class="flex items-center gap-3 mb-3">
            <div class="h-px w-8 bg-kicc-gold"></div>
            <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">Events</span>
        </div>
        <h2 class="text-3xl md:text-4xl font-black text-white leading-[1.1]">Upcoming<br><span class="text-kicc-gold">Exhibitions</span></h2>
        <p class="text-white/40 mt-3 text-base max-w-xl leading-relaxed">Book booths, showcase products, and connect with buyers across East Africa.</p>
    </div>
    @if(isset($featuredExhibitions) && $featuredExhibitions->count() > 0)
    <div class="grid md:grid-cols-3 gap-5">
        @foreach($featuredExhibitions as $i => $ex)
        <a href="{{ route('exhibitions.show', $ex->slug) }}"
           class="group bg-[#0D1220] rounded-2xl overflow-hidden border border-white/8 hover:border-[#901C1E]/50 transition-all block card-hover" data-tilt="5" data-reveal data-reveal-delay="{{ $i * 120 }}">
            <div class="tilt-glare"></div>
            <div class="h-44 overflow-hidden bg-[#141B2E] relative">
                @if($ex->cover_image)
                <img src="{{ $ex->cover_image }}" alt="{{ $ex->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 opacity-80">
                @else
                <div class="w-full h-full flex items-center justify-center text-kicc-gold/30 text-lg font-bold">KICC Exhibition</div>
                @endif
            </div>
            <div class="p-5 relative">
                <h3 class="font-black text-white text-base mt-3 leading-snug">{{ $ex->name }}</h3>
                <div class="mt-3 space-y-1.5 text-xs text-white/45">
                    <div class="flex items-center gap-2">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        {{ $ex->start_date?->format('M d, Y') ?? 'TBD' }}
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-between">
                    <span class="text-xs text-white/50 group-hover:text-kicc-gold transition-colors flex items-center gap-1">
                        Details
                        <svg class="w-3 h-3 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </span>
                </div>
            </div>
        </a>
        @endforeach
    </div>
    @else
    <div class="text-center py-12 text-white/30 bg-[#0D1220] rounded-2xl border border-white/8" data-reveal>
        <div class="text-4xl mb-3">📅</div>
        <p class="text-sm">No featured exhibitions right now — check back soon.</p>
        <a href="{{ route('exhibitions.index') }}" class="inline-block mt-4 text-kicc-gold text-sm font-bold hover:underline">Browse all events</a>
    </div>
    @endif
</section>

{{-- ═══ VENUES ═══ --}}
<section class="bg-[#0D1220] border-y border-white/8 py-20 relative overflow-hidden">
    <div class="absolute w-[450px] h-[450px] rounded-full bg-[#901C1E]/8 blur-3xl -bottom-40 -left-40"></div>
    <div class="max-w-7xl mx-auto px-5 relative">
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-4" data-reveal>
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <div class="h-px w-8 bg-kicc-gold"></div>
                    <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">KICC Facilities</span>
                </div>
                <h2 class="text-3xl md:text-4xl font-black text-white leading-[1.1]">World-Class<br><span class="text-kicc-gold">Venues</span></h2>
                <p class="text-white/40 mt-3 text-base max-w-xl leading-relaxed">From intimate boardrooms to the 2,000-capacity Tsavo Hall.</p>
            </div>
            <a href="{{ route('venues.index') }}" data-magnetic class="inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-4 text-xs h-9 rounded-xl bg-white/10 text-white hover:bg-white/20 border border-white/15 shrink-0">
                All venues
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @forelse($venues ?? [] as $i => $v)
            <a href="{{ route('venues.show', $v->slug) }}" class="group bg-[#141B2E] rounded-2xl overflow-hidden border border-white/8 hover:border-kicc-gold/40 transition-all card-hover" data-reveal data-reveal-delay="{{ ($i % 4) * 70 }}">
                <div class="h-36 overflow-hidden bg-[#0D1220] relative">
                    @if($v->cover_image)
                    <img src="{{ $v->cover_image }}" alt="{{ $v->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 opacity-80" onerror="this.style.display='none'">
                    @endif
                    <div class="absolute inset-0 flex items-center justify-center text-white/20 text-5xl font-black">{{ $v->name[0] }}</div>
                </div>
                <div class="p-4">
                    <div class="font-black text-white text-sm">{{ $v->name }}</div>
                    <div class="text-white/40 text-xs mt-1">{{ number_format($v->capacity ?? 0) }} capacity</div>
                    <div class="mt-3 flex items-center justify-between">
                        <span class="text-kicc-gold font-bold text-sm">{{ ucfirst($v->venue_type ?? '') }}</span>
                        <svg class="w-3.5 h-3.5 text-white/30 group-hover:text-kicc-gold group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </div>
                </div>
            </a>
            @empty
            @foreach([['Tsavo Hall','Ballroom',2000],['Amphitheatre','Theatre',800],['Aberdares','Meeting',120],['Courtyard','Outdoor',500]] as $i => $v)
            <div class="group bg-[#141B2E] rounded-2xl overflow-hidden border border-white/8 card-hover" data-reveal data-reveal-delay="{{ $i * 70 }}">
                <div class="h-36 bg-[#0D1220] flex items-center justify-center"><span class="text-white/20 text-5xl font-black">{{ $v[0][0] }}</span></div>
                <div class="p-4">
                    <div class="font-black text-white text-sm">{{ $v[0] }}</div>
                    <div class="text-white/40 text-xs mt-1">{{ $v[1] }} · {{ number_format($v[2]) }}</div>
                </div>
            </div>
            @endforeach
            @endforelse
        </div>
    </div>
</section>

{{-- ═══ SCREENS CTA ═══ --}}
<section class="max-w-7xl mx-auto px-5 py-20">
    <div class="relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-[#0D1220] to-[#07090F]" data-reveal="zoom">
        <img src="{{ media('kicc/tower-night.jpg') }}" alt="" class="absolute inset-0 w-full h-full object-cover opacity-20" data-parallax="0.12">
        <div class="absolute w-80 h-80 rounded-full bg-[#FFCD05]/10 blur-3xl -top-20 -right-20 animate-float-slow"></div>
        <div class="relative px-10 py-16 md:py-20 flex flex-col md:flex-row items-center justify-between gap-8">
            <div data-reveal>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold tracking-wide border bg-[#FFCD05]/15 text-[#FFCD05] border-[#FFCD05]/30">18 Digital Screens</span>
                <h2 class="text-3xl md:text-5xl font-black text-white mt-4 leading-tight">Advertise on<br><span class="text-shimmer">Kenya's Most</span><br>Iconic Screens</h2>
                <p class="text-white/45 mt-4 max-w-md text-sm leading-relaxed">The KICC tower rooftop LED, highway billboards, and premium screens reaching millions daily.</p>
            </div>
            <div class="flex flex-col gap-3 shrink-0" data-reveal data-reveal-delay="200">
                <a href="{{ route('screens.directory') }}" data-magnetic class="inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-8 text-base h-14 rounded-xl bg-[#FFCD05] text-[#07090F] hover:bg-[#e6b904] animate-pulse-glow">Book a Screen</a>
                <a href="{{ route('screens.directory') }}" data-magnetic class="inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-4 text-xs h-9 rounded-xl border border-white/25 text-white hover:bg-white/10">View all 18 screens</a>
            </div>
        </div>
    </div>
</section>
@endsection
