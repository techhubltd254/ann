@extends('layouts.app')

@section('title', 'Venues & Services — KICC')

@section('content')
<div class="relative bg-kicc-dark overflow-hidden">
    <div class="absolute inset-0">
        <img src="{{ media('kicc/tower-night.jpg') }}" alt="" class="w-full h-full object-cover object-top opacity-25">
    </div>
    <div class="absolute inset-0 bg-gradient-to-b from-kicc-dark/70 via-kicc-dark/85 to-kicc-dark"></div>
    <div class="relative max-w-7xl mx-auto px-6 lg:px-8 py-16">
        <div class="flex items-center gap-3 mb-4">
            <span class="h-px w-10 bg-kicc-gold"></span>
            <span class="text-kicc-gold text-xs font-semibold uppercase tracking-[0.25em">The Venue</span>
        </div>
        <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight text-white mb-3">KICC <span class="text-kicc-gold">Rooms &amp; Services</span></h1>
        <p class="text-lg text-gray-300 max-w-2xl">From Tsavo Hall to the Helipad — Africa's premier events destination.</p>
    </div>
</div>

<div class="max-w-7xl mx-auto px-6 lg:px-8 py-12">
    {{-- MICE Services Banner --}}
    <div class="bg-gradient-to-r from-amber-500 to-amber-600 rounded-2xl p-8 mb-12 text-white">
        <div class="max-w-2xl">
            <div class="text-white/70 text-xs font-semibold uppercase tracking-[0.25em] mb-2">MICE</div>
            <h2 class="text-3xl font-extrabold mb-3">Meetings · Incentives · Conferences · Exhibitions</h2>
            <p class="text-white/80 leading-relaxed mb-5">KICC delivers end-to-end event execution — from Tsavo Hall banquets to helipad cocktail receptions. Every service is in-house.</p>
            <a href="{{ route('venues.show', 'mice-services') }}" class="inline-flex items-center gap-2 bg-white text-amber-700 font-bold px-6 py-3 rounded-xl hover:bg-kicc-cream transition-all">
                View all services
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>
    </div>

    {{-- Rooms Grid --}}
    <div class="flex items-center gap-3 mb-8">
        <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Rooms &amp; Spaces</h2>
        <span class="h-px flex-1 bg-gray-100"></span>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 mb-16">
        @foreach($venues as $v)
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-xl hover:border-amber-300 transition-all group">
            <div class="h-40 bg-gradient-to-br from-kicc-navy to-kicc-dark flex items-center justify-center overflow-hidden">
                @php $img = media("kicc/{$v->slug}.jpg"); @endphp
                <img src="{{ $img }}" alt="{{ $v->name }}" class="w-full h-full object-cover opacity-70 group-hover:opacity-100 group-hover:scale-105 transition-all duration-500"
                     onerror="this.style.display='none'">
                <span class="absolute text-white/20 text-6xl font-bold">{{ $v->name[0] }}</span>
            </div>
            <div class="p-5">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider text-amber-600">{{ $v->venue_type }}</span>
                    @if($v->capacity)
                    <span class="text-xs text-gray-400">{{ $v->capacity }} pax</span>
                    @endif
                </div>
                <h3 class="font-bold text-gray-900 mb-1">{{ $v->name }}</h3>
                <p class="text-sm text-gray-500 line-clamp-2 leading-relaxed">{{ $v->description }}</p>
                <div class="mt-3 flex flex-wrap gap-1.5">
                    @foreach(array_slice(json_decode($v->amenities ?? '[]', true) ?? [], 0, 3) as $a)
                    <span class="text-xs bg-amber-50 text-amber-600 px-2 py-0.5 rounded-full font-medium">{{ $a }}</span>
                    @endforeach
                </div>
                <a href="{{ route('venues.show', $v->slug) }}" class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-amber-600 hover:text-amber-700 transition-colors">
                    View details
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Partners --}}
    <div class="border-t border-gray-100 pt-12">
        <div class="flex items-center gap-3 mb-8">
            <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Accreditations</h2>
            <span class="h-px flex-1 bg-gray-100"></span>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @foreach(['ICCA','ISO 27001','AIPC','UNWTO','MPI'] as $p)
            <div class="bg-kicc-cream rounded-2xl p-6 text-center hover:shadow-md transition-all">
                <div class="text-2xl font-extrabold text-amber-600 mb-1">{{ explode(' ',$p)[0] }}</div>
                <div class="text-xs text-gray-500">{{ $p }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endSection