@extends('layouts.app')

@section('title', 'Venues & Services — KICC')

@section('content')
<div class="relative bg-[#F9FAFB] overflow-hidden border-b border-gray-200">
    <div class="absolute inset-0">
        <img src="{{ media('kicc/tower-night.jpg') }}" alt="" class="w-full h-full object-cover object-top opacity-25" data-parallax="0.15">
    </div>
    <div class="absolute inset-0 bg-gradient-to-b from-[#07090F]/70 via-[#07090F]/85 to-[#07090F]"></div>
    <div class="relative max-w-7xl mx-auto px-5 py-16" data-reveal>
        <div class="flex items-center gap-3 mb-4">
            <span class="h-px w-10 bg-kicc-gold"></span>
            <span class="text-kicc-gold text-xs font-semibold uppercase tracking-[0.25em]">The Venue</span>
        </div>
        <h1 class="text-4xl md:text-5xl font-black tracking-tight text-white mb-3" data-split>KICC <span class="text-kicc-gold">Rooms &amp; Services</span></h1>
        <p class="text-lg text-white/70 max-w-2xl">From Tsavo Hall to the Helipad — Africa's premier events destination.</p>
    </div>
</div>

<div class="max-w-7xl mx-auto px-5 py-12">
    {{-- MICE Services Banner --}}
    <div class="relative overflow-hidden rounded-2xl border border-[#FFCD05]/25 bg-gradient-to-r from-[#141B2E] to-[#0D1220] p-8 mb-12" data-reveal="zoom">
        <div class="absolute w-64 h-64 rounded-full bg-[#FFCD05]/10 blur-3xl -top-16 -right-16 animate-float-slow"></div>
        <div class="max-w-2xl relative">
            <div class="text-kicc-gold text-xs font-bold uppercase tracking-[0.25em] mb-2">MICE</div>
            <h2 class="text-2xl md:text-3xl font-black text-white mb-3" data-split>Meetings · Incentives · Conferences · Exhibitions</h2>
            <p class="text-white/70 leading-relaxed mb-5 text-sm">KICC delivers end-to-end event execution — from Tsavo Hall banquets to helipad cocktail receptions. Every service is in-house.</p>
            <a href="{{ route('venues.show', 'mice-services') }}" data-magnetic class="inline-flex items-center gap-2 bg-kicc-gold text-[#07090F] font-bold px-6 py-3 rounded-xl hover:bg-[#FFCD05] transition-all text-sm">
                View all services
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>
    </div>

    {{-- Rooms Grid --}}
    <div class="flex items-center gap-3 mb-8" data-reveal>
        <h2 class="text-2xl font-black text-gray-900 tracking-tight" data-split>Rooms &amp; Spaces</h2>
        <span class="h-px flex-1 bg-[#1890D7]/8"></span>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 mb-16">
        @foreach($venues as $i => $v)
        <a href="{{ route('venues.show', $v->slug) }}" class="bg-white rounded-2xl border border-gray-200 overflow-hidden hover:border-kicc-gold/40 transition-all group card-hover block" data-tilt="6" data-reveal data-reveal-delay="{{ ($i % 3) * 80 }}">
            <div class="tilt-glare"></div>
            <div class="h-40 bg-[#F9FAFB] flex items-center justify-center overflow-hidden relative">
                @php $img = media("kicc/{$v->slug}.jpg"); @endphp
                <img src="{{ $img }}" alt="{{ $v->name }}" loading="lazy" decoding="async" class="w-full h-full object-cover opacity-70 group-hover:opacity-100 group-hover:scale-105 transition-all duration-500"
                     onerror="this.style.display='none'">
                <span class="absolute text-gray-900/15 text-6xl font-black">{{ $v->name[0] }}</span>
            </div>
            <div class="p-5 relative">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-kicc-gold">{{ $v->venue_type }}</span>
                    @if($v->capacity)
                    <span class="text-xs text-[#5A6480]">{{ number_format($v->capacity) }} pax</span>
                    @endif
                </div>
                <h3 class="font-black text-gray-900 mb-1">{{ $v->name }}</h3>
                <p class="text-sm text-[#5A6480] line-clamp-2 leading-relaxed">{{ $v->description }}</p>
                <div class="mt-3 flex flex-wrap gap-1.5">
                    @foreach(array_slice(is_array($v->amenities) ? $v->amenities : (json_decode($v->amenities ?? '[]', true) ?? []), 0, 3) as $a)
                    <span class="text-[10px] bg-sky-50 text-[#5A6480] border border-gray-200 px-2 py-0.5 rounded-full font-semibold">{{ $a }}</span>
                    @endforeach
                </div>
                <span class="mt-4 inline-flex items-center gap-1.5 text-sm font-bold text-kicc-gold group-hover:translate-x-1 transition-transform">
                    View details
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </span>
            </div>
        </a>
        @endforeach
    </div>

    {{-- Accreditations --}}
    <div class="border-t border-gray-200 pt-12">
        <div class="flex items-center gap-3 mb-8" data-reveal>
            <h2 class="text-2xl font-black text-gray-900 tracking-tight" data-split>Accreditations</h2>
            <span class="h-px flex-1 bg-[#1890D7]/8"></span>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            @foreach(['ICCA','ISO 27001','AIPC','UNWTO','MPI'] as $i => $p)
            <div class="bg-white border border-gray-200 rounded-2xl p-6 text-center card-hover" data-reveal data-reveal-delay="{{ $i * 60 }}">
                <div class="text-xl font-black text-kicc-gold mb-1">{{ explode(' ',$p)[0] }}</div>
                <div class="text-xs text-gray-400">{{ $p }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
