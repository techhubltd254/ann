@extends('layouts.app')

@section('title', 'Home')

@section('content')
<div class="relative bg-gradient-to-br from-gray-900 via-gray-800 to-amber-900 overflow-hidden min-h-[85vh] flex items-center">
    <div class="absolute inset-0">
        <img src="{{ asset('storage/kicc-hero.jpg') }}" class="w-full h-full object-cover opacity-20">
    </div>
    <div class="absolute inset-0 bg-gradient-to-r from-black/60 via-transparent to-black/30"></div>
    <div class="relative max-w-7xl mx-auto px-6 lg:px-8 w-full py-20">
        <div class="max-w-3xl">
            <div class="text-7xl mb-6 drop-shadow-lg">🇰🇪</div>
            <h1 class="text-5xl md:text-7xl font-bold text-white mb-6 leading-tight">Kenya's Premier<br><span class="text-amber-400">Exhibition & Trade</span> Platform</h1>
            <p class="text-xl md:text-2xl text-gray-300 mb-8 max-w-2xl leading-relaxed">Discover, book, and manage exhibitions, trade shows, and events across <strong class="text-amber-300">47 counties</strong> of Kenya.</p>
            <div class="flex flex-wrap gap-4">
                <a href="{{ route('counties.index') }}" class="btn-amber text-lg">
                    Explore Counties
                </a>
                <a href="{{ route('exhibitions.index') }}" class="btn-outline text-lg">
                    Browse Exhibitions
                </a>
                <a href="{{ route('exhibition-3d.map') }}" class="btn-outline text-lg">
                    🗺 3D Map
                </a>
            </div>
        </div>
    </div>
</div>

@if($counties && $counties->count() > 0)
<div class="bg-gray-900 py-12 overflow-hidden border-t border-gray-800">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-white">Explore All 47 Counties</h2>
                <p class="text-gray-400 text-sm mt-1">Scroll through Kenya's counties</p>
            </div>
            <div class="flex gap-2">
                <button onclick="document.getElementById('county-scroll').scrollBy({left: -320, behavior: 'smooth'})" class="bg-white/10 hover:bg-white/20 text-white w-9 h-9 rounded-lg flex items-center justify-center transition-all">&larr;</button>
                <button onclick="document.getElementById('county-scroll').scrollBy({left: 320, behavior: 'smooth'})" class="bg-white/10 hover:bg-white/20 text-white w-9 h-9 rounded-lg flex items-center justify-center transition-all">&rarr;</button>
            </div>
        </div>
    </div>
    <div id="county-scroll" class="flex gap-4 overflow-x-auto px-6 lg:px-8 pb-4 scroll-smooth scrollbar-hide">
        @foreach($counties as $county)
        @php $heroImg = asset('storage/counties/' . $county->slug . '/hero.jpeg'); @endphp
        <a href="{{ route('counties.show', $county->slug) }}" class="flex-shrink-0 w-48 group">
            <div class="bg-gray-800 rounded-2xl overflow-hidden border-2 border-transparent hover:border-amber-500 transition-all duration-200 shadow-lg hover:shadow-amber-500/20">
                <div class="h-32 overflow-hidden">
                    <img src="{{ $heroImg }}" alt="{{ $county->name }}"
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                         onerror="this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center text-5xl bg-gradient-to-br from-amber-700 to-orange-800 group-hover:scale-125 transition-transform duration-300\'>{{ $county->icon_emoji ?? '📍' }}</div>'">
                </div>
                <div class="h-16 flex flex-col items-center justify-center px-3">
                    <span class="text-sm font-semibold text-white group-hover:text-amber-400 text-center leading-tight">{{ $county->name }}</span>
                    <span class="text-xs text-gray-400 mt-0.5">{{ $county->primary_sectors ? count($county->primary_sectors) . ' sectors' : '' }}</span>
                </div>
            </div>
        </a>
        @endforeach
    </div>
</div>
@endif

<div class="max-w-7xl mx-auto px-6 lg:px-8 py-20">
    <div class="bg-gray-900 rounded-2xl p-8 mb-16 border border-gray-800">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
            <a href="{{ route('exhibition-3d.map') }}" class="group">
                <div class="text-4xl mb-2">🗺</div>
                <div class="text-white font-semibold text-sm group-hover:text-amber-400 transition-colors">47 Counties 3D Map</div>
                <div class="text-gray-500 text-xs mt-1">Interactive 3D terrain</div>
            </a>
            <a href="{{ route('exhibition-3d.sector') }}" class="group">
                <div class="text-4xl mb-2">🏖</div>
                <div class="text-white font-semibold text-sm group-hover:text-amber-400 transition-colors">Sector Explorer</div>
                <div class="text-gray-500 text-xs mt-1">Mombasa &amp; Kilifi 3D</div>
            </a>
            <a href="{{ route('exhibition-3d.booth') }}" class="group">
                <div class="text-4xl mb-2">🏛</div>
                <div class="text-white font-semibold text-sm group-hover:text-amber-400 transition-colors">Exhibition Hall</div>
                <div class="text-gray-500 text-xs mt-1">3D booth tour</div>
            </a>
            <a href="{{ route('screens.directory') }}" class="group">
                <div class="text-4xl mb-2">🎬</div>
                <div class="text-white font-semibold text-sm group-hover:text-amber-400 transition-colors">Screen Videos</div>
                <div class="text-gray-500 text-xs mt-1">18 showcase videos</div>
            </a>
        </div>
    </div>

    <div class="text-center mb-12">
        <h2 class="text-4xl font-bold text-gray-900 mb-3">Why KICC?</h2>
        <p class="text-lg text-gray-500 max-w-2xl mx-auto">Kenya's trusted platform for exhibitions and trade</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 text-center hover:shadow-lg transition-shadow">
            <div class="w-16 h-16 bg-amber-100 rounded-2xl flex items-center justify-center text-3xl mx-auto mb-5">🏛️</div>
            <h3 class="text-xl font-bold text-gray-900 mb-3">World-Class Venues</h3>
            <p class="text-gray-500 leading-relaxed">State-of-the-art exhibition halls and convention centres across Kenya.</p>
        </div>
        <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 text-center hover:shadow-lg transition-shadow">
            <div class="w-16 h-16 bg-amber-100 rounded-2xl flex items-center justify-center text-3xl mx-auto mb-5">🎫</div>
            <h3 class="text-xl font-bold text-gray-900 mb-3">Easy Booking</h3>
            <p class="text-gray-500 leading-relaxed">Book exhibition booths and tickets online with our seamless platform.</p>
        </div>
        <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 text-center hover:shadow-lg transition-shadow">
            <div class="w-16 h-16 bg-amber-100 rounded-2xl flex items-center justify-center text-3xl mx-auto mb-5">🌍</div>
            <h3 class="text-xl font-bold text-gray-900 mb-3">47 Counties</h3>
            <p class="text-gray-500 leading-relaxed">Connect with exhibitors and visitors from every county in Kenya.</p>
        </div>
    </div>
</div>

@if(isset($featuredExhibitions) && $featuredExhibitions->count() > 0)
<div class="bg-gray-50 py-20">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-bold text-gray-900 mb-3">Featured Exhibitions</h2>
            <p class="text-lg text-gray-500">Upcoming trade shows and events</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($featuredExhibitions as $exhibition)
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden hover:shadow-xl transition-all border border-gray-100 group">
                @if($exhibition->cover_image)
                <img src="{{ $exhibition->cover_image }}" alt="{{ $exhibition->name }}" class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-500">
                @else
                <div class="w-full h-48 bg-gradient-to-br from-amber-200 to-orange-300 flex items-center justify-center text-4xl">🏛️</div>
                @endif
                <div class="p-6">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-3 py-1 rounded-full">{{ $exhibition->start_date->format('M d, Y') }}</span>
                        @if($exhibition->is_featured)
                        <span class="text-xs font-semibold text-purple-600 bg-purple-50 px-3 py-1 rounded-full">Featured</span>
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

<div class="bg-amber-500 py-20">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 text-center">
        <h2 class="text-4xl font-bold text-white mb-4">Ready to Showcase Your Business?</h2>
        <p class="text-amber-100 text-lg mb-8 max-w-xl mx-auto">Book a booth at our next exhibition and connect with thousands of visitors from across Kenya.</p>
        <a href="{{ route('exhibitions.index') }}" class="bg-white text-amber-600 px-8 py-3.5 rounded-xl text-lg font-bold hover:bg-amber-50 shadow-xl transition-all inline-block">
            Get Started Today
        </a>
    </div>
</div>
@endSection
