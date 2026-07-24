@extends('layouts.app')

@section('title', 'Home')

@section('content')
{{-- ═══ HERO — the tower in national colours ═══ --}}
<div class="relative overflow-hidden min-h-[92vh] flex items-center bg-kicc-dark">
    <div class="absolute inset-0">
        <img src="{{ media('kicc/tower-night.jpg') }}" alt="KICC tower illuminated in Kenya's national colours"
             class="w-full h-full object-cover object-center opacity-70">
    </div>
    <div class="absolute inset-0 bg-gradient-to-r from-kicc-dark/95 via-kicc-dark/60 to-kicc-dark/20"></div>
    <div class="absolute inset-x-0 bottom-0 h-40 bg-gradient-to-t from-kicc-dark to-transparent"></div>

    <div class="relative max-w-7xl mx-auto px-6 lg:px-8 w-full py-24">
        <div class="max-w-3xl">
            <div class="flex items-center gap-3 mb-8">
                <span class="h-px w-10 bg-gold-400"></span>
                <span class="text-gold-400 text-xs font-semibold uppercase tracking-[0.25em]">A national icon · since 1973</span>
            </div>
            <h1 class="text-5xl md:text-7xl font-extrabold text-white mb-6 leading-[1.05] tracking-tight">
                Kenya's Premier<br>
                <span class="text-amber-500">Exhibition &amp; Trade</span> Platform
            </h1>
            <p class="text-lg md:text-xl text-gray-300 mb-10 max-w-2xl leading-relaxed">
                Discover, book, and manage exhibitions, trade shows, and events across
                <strong class="text-white font-semibold">47 counties</strong> of Kenya — powered by the Kenyatta International Convention Centre.
            </p>
            <div class="flex flex-wrap gap-4">
                <a href="{{ route('counties.index') }}" class="btn-amber text-lg">Explore Counties</a>
                <a href="{{ route('exhibitions.index') }}" class="btn-outline text-lg">Browse Exhibitions</a>
                <a href="{{ route('exhibition-3d.map') }}" class="btn-outline text-lg">3D Map</a>
            </div>
        </div>

        <div class="mt-16 grid grid-cols-3 max-w-lg border-t border-white/15 pt-6 gap-6">
            <div>
                <div class="text-3xl font-extrabold text-white">47</div>
                <div class="text-xs uppercase tracking-wider text-gray-400 mt-1">Counties</div>
            </div>
            <div>
                <div class="text-3xl font-extrabold text-white">50<span class="text-gold-400">+</span></div>
                <div class="text-xs uppercase tracking-wider text-gray-400 mt-1">Years of MICE</div>
            </div>
            <div>
                <div class="text-3xl font-extrabold text-white">18</div>
                <div class="text-xs uppercase tracking-wider text-gray-400 mt-1">Venue Screens</div>
            </div>
        </div>
    </div>
</div>

{{-- ═══ COUNTY RAIL ═══ --}}
@if($counties && $counties->count() > 0)
<div class="bg-kicc-cream py-14 overflow-hidden">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 mb-6">
        <div class="flex items-end justify-between">
            <div>
                <div class="text-amber-600 text-xs font-semibold uppercase tracking-[0.2em] mb-2">Destinations</div>
                <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">Explore all 47 counties</h2>
                <p class="text-gray-500 text-sm mt-1">Every county, its sectors, and its stories</p>
            </div>
            <div class="hidden sm:flex gap-2">
                <button onclick="document.getElementById('county-scroll').scrollBy({left: -320, behavior: 'smooth'})" aria-label="Scroll left"
                        class="bg-white border border-gray-200 hover:border-amber-500 hover:text-amber-600 text-gray-600 w-10 h-10 rounded-xl flex items-center justify-center transition-all shadow-sm">&larr;</button>
                <button onclick="document.getElementById('county-scroll').scrollBy({left: 320, behavior: 'smooth'})" aria-label="Scroll right"
                        class="bg-white border border-gray-200 hover:border-amber-500 hover:text-amber-600 text-gray-600 w-10 h-10 rounded-xl flex items-center justify-center transition-all shadow-sm">&rarr;</button>
            </div>
        </div>
    </div>
    <div id="county-scroll" class="flex gap-4 overflow-x-auto px-6 lg:px-8 pb-4 scroll-smooth scrollbar-hide">
        @foreach($counties as $county)
        @php $heroImg = media('counties/' . $county->slug . '/hero.jpeg'); @endphp
        <a href="{{ route('counties.show', $county->slug) }}" class="flex-shrink-0 w-48 group">
            <div class="bg-white rounded-2xl overflow-hidden border border-gray-200/80 hover:border-amber-500 transition-all duration-200 shadow-sm hover:shadow-xl hover:shadow-amber-500/10">
                <div class="h-32 overflow-hidden">
                    <img src="{{ $heroImg }}" alt="{{ $county->name }}" loading="lazy"
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                         onerror="this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center text-5xl bg-gradient-to-br from-amber-700 to-amber-900\'>{{ $county->icon_emoji ?? '📍' }}</div>'">
                </div>
                <div class="h-16 flex flex-col items-center justify-center px-3">
                    <span class="text-sm font-semibold text-gray-900 group-hover:text-amber-600 text-center leading-tight">{{ $county->name }}</span>
                    <span class="text-xs text-gray-400 mt-0.5">{{ $county->primary_sectors ? count($county->primary_sectors) . ' sectors' : '' }}</span>
                </div>
            </div>
        </a>
        @endforeach
    </div>
</div>
@endif

{{-- ═══ HERITAGE — KICC since 1973 ═══ --}}
<div class="max-w-7xl mx-auto px-6 lg:px-8 py-24">
    <div class="grid lg:grid-cols-2 gap-14 items-center">
        <div class="relative">
            <img src="{{ media('kicc/gate-day.jpg') }}" alt="KICC main gate" class="rounded-3xl shadow-2xl w-full object-cover aspect-[4/3]">
            <img src="{{ media('kicc/amphitheatre.jpg') }}" alt="KICC amphitheatre interior"
                 class="absolute -bottom-8 -right-4 sm:-right-8 w-1/2 rounded-2xl shadow-2xl border-4 border-white object-cover aspect-[4/3]">
        </div>
        <div class="lg:pl-6">
            <div class="text-amber-600 text-xs font-semibold uppercase tracking-[0.2em] mb-3">The venue of nations</div>
            <h2 class="text-4xl md:text-5xl font-extrabold text-gray-900 tracking-tight leading-tight mb-6">
                A national <span class="italic text-amber-600">icon</span>,<br>since 1973.
            </h2>
            <p class="text-gray-600 leading-relaxed mb-4">
                For over half a century the Kenyatta International Convention Centre has stood as Kenya's icon —
                hosting the meetings, summits and exhibitions that define Kenya on the global stage.
            </p>
            <p class="text-gray-600 leading-relaxed mb-8">
                From Tsavo Hall to the helipad, this platform brings every room, booth and screen of KICC online —
                and extends the same stage to all 47 counties.
            </p>
            <div class="flex flex-wrap gap-4">
                <a href="{{ route('venues.index') }}" class="btn-amber">Explore the venue</a>
                <a href="{{ route('screens.directory') }}" class="inline-flex items-center gap-2 text-amber-600 font-semibold hover:text-amber-700 transition-colors px-2 py-2.5">
                    Watch the screens
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
            </div>
        </div>
    </div>
</div>

{{-- ═══ 3D EXPERIENCES ═══ --}}
<div class="max-w-7xl mx-auto px-6 lg:px-8 pb-20">
    <div class="bg-kicc-dark rounded-3xl p-8 md:p-10 relative overflow-hidden">
        <div class="absolute -top-20 -right-20 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl"></div>
        <div class="flex items-center justify-between mb-8">
            <div>
                <div class="text-gold-400 text-xs font-semibold uppercase tracking-[0.2em] mb-2">Immersive</div>
                <h2 class="text-2xl font-extrabold text-white tracking-tight">Step inside the platform</h2>
            </div>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <a href="{{ route('exhibition-3d.map') }}" class="group bg-white/5 hover:bg-white/10 border border-white/10 rounded-2xl p-5 transition-all">
                <svg class="w-8 h-8 text-gold-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                <div class="text-white font-semibold text-sm group-hover:text-gold-400 transition-colors">47 Counties 3D Map</div>
                <div class="text-gray-500 text-xs mt-1">Interactive 3D terrain</div>
            </a>
            <a href="{{ route('exhibition-3d.sector') }}" class="group bg-white/5 hover:bg-white/10 border border-white/10 rounded-2xl p-5 transition-all">
                <svg class="w-8 h-8 text-gold-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div class="text-white font-semibold text-sm group-hover:text-gold-400 transition-colors">Sector Explorer</div>
                <div class="text-gray-500 text-xs mt-1">Mombasa &amp; Kilifi 3D</div>
            </a>
            <a href="{{ route('exhibition-3d.booth') }}" class="group bg-white/5 hover:bg-white/10 border border-white/10 rounded-2xl p-5 transition-all">
                <svg class="w-8 h-8 text-gold-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <div class="text-white font-semibold text-sm group-hover:text-gold-400 transition-colors">Exhibition Hall</div>
                <div class="text-gray-500 text-xs mt-1">3D booth tour</div>
            </a>
            <a href="{{ route('screens.directory') }}" class="group bg-white/5 hover:bg-white/10 border border-white/10 rounded-2xl p-5 transition-all">
                <svg class="w-8 h-8 text-gold-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div class="text-white font-semibold text-sm group-hover:text-gold-400 transition-colors">Screen Videos</div>
                <div class="text-gray-500 text-xs mt-1">18 showcase videos</div>
            </a>
        </div>
    </div>
</div>

{{-- ═══ WHY KICC ═══ --}}
<div class="max-w-7xl mx-auto px-6 lg:px-8 pb-24">
    <div class="text-center mb-12">
        <div class="text-amber-600 text-xs font-semibold uppercase tracking-[0.2em] mb-3">Why KICC</div>
        <h2 class="text-4xl font-extrabold text-gray-900 tracking-tight mb-3">Kenya's trusted stage for trade</h2>
        <p class="text-lg text-gray-500 max-w-2xl mx-auto">The same venue that hosts presidents and summits, now open to every exhibitor in every county.</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 hover:shadow-xl hover:border-amber-200 transition-all">
            <div class="w-14 h-14 bg-amber-50 rounded-2xl flex items-center justify-center mx-auto mb-5">
                <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-3 text-center">World-Class Venues</h3>
            <p class="text-gray-500 leading-relaxed text-center">Tsavo Hall, the amphitheatre, and convention spaces trusted for 50 years of national events.</p>
        </div>
        <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 hover:shadow-xl hover:border-amber-200 transition-all">
            <div class="w-14 h-14 bg-amber-50 rounded-2xl flex items-center justify-center mx-auto mb-5">
                <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-3 text-center">Easy Booking</h3>
            <p class="text-gray-500 leading-relaxed text-center">Book booths and tickets online with secure payments and instant confirmation.</p>
        </div>
        <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 hover:shadow-xl hover:border-amber-200 transition-all">
            <div class="w-14 h-14 bg-amber-50 rounded-2xl flex items-center justify-center mx-auto mb-5">
                <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-3 text-center">47 Counties</h3>
            <p class="text-gray-500 leading-relaxed text-center">Every county's sectors, products and tourism — one platform, one national marketplace.</p>
        </div>
    </div>
</div>

{{-- ═══ FEATURED EXHIBITIONS ═══ --}}
@if(isset($featuredExhibitions) && $featuredExhibitions->count() > 0)
<div class="bg-white py-20 border-t border-gray-100">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="text-center mb-12">
            <div class="text-amber-600 text-xs font-semibold uppercase tracking-[0.2em] mb-3">What's on</div>
            <h2 class="text-4xl font-extrabold text-gray-900 tracking-tight mb-3">Featured Exhibitions</h2>
            <p class="text-lg text-gray-500">Upcoming trade shows and events</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($featuredExhibitions as $exhibition)
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden hover:shadow-xl transition-all border border-gray-100 group">
                @if($exhibition->cover_image)
                <img src="{{ $exhibition->cover_image }}" alt="{{ $exhibition->name }}" class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-500">
                @else
                <div class="w-full h-48 bg-gradient-to-br from-amber-600 to-amber-800 flex items-center justify-center">
                    <span class="text-white/80 text-sm font-semibold uppercase tracking-widest">KICC Exhibition</span>
                </div>
                @endif
                <div class="p-6">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-3 py-1 rounded-full">{{ $exhibition->start_date->format('M d, Y') }}</span>
                        @if($exhibition->is_featured)
                        <span class="text-xs font-semibold text-gold-700 bg-gold-100 px-3 py-1 rounded-full">Featured</span>
                        @endif
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">{{ $exhibition->name }}</h3>
                    <p class="text-gray-500 text-sm mb-4 line-clamp-2">{{ Str::limit($exhibition->tagline ?? $exhibition->description, 100) }}</p>
                    <a href="{{ route('exhibitions.show', $exhibition->slug) }}" class="inline-flex items-center gap-2 text-amber-600 font-semibold text-sm hover:text-amber-700 transition-colors">
                        View Details
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ═══ CTA ═══ --}}
<div class="relative overflow-hidden bg-amber-600 py-20">
    <div class="absolute inset-0 opacity-10">
        <img src="{{ media('kicc/tower-night.jpg') }}" alt="" class="w-full h-full object-cover">
    </div>
    <div class="relative max-w-7xl mx-auto px-6 lg:px-8 text-center">
        <h2 class="text-4xl font-extrabold text-white tracking-tight mb-4">Ready to Showcase Your Business?</h2>
        <p class="text-amber-100 text-lg mb-8 max-w-xl mx-auto">Book a booth at our next exhibition and connect with thousands of visitors from across Kenya.</p>
        <a href="{{ route('exhibitions.index') }}" class="bg-white text-amber-700 px-8 py-3.5 rounded-xl text-lg font-bold hover:bg-kicc-cream shadow-xl transition-all inline-block">
            Get Started Today
        </a>
    </div>
</div>
@endSection
