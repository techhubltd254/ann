@extends('layouts.app')

@section('title', 'Exhibitions')
@section('description', 'Browse exhibitions, trade shows, and events across Kenya')

@section('content')
<div class="bg-white border-b border-gray-200 py-14 relative overflow-hidden">
    <div class="absolute w-96 h-96 rounded-full bg-[#B3261E]/10 blur-3xl -top-20 right-0"></div>
    <div class="max-w-7xl mx-auto px-5 relative" data-reveal>
        <div class="flex items-center gap-3 mb-3">
            <div class="h-px w-8 bg-kicc-gold"></div>
            <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">Events</span>
        </div>
        <h1 class="text-4xl md:text-5xl font-black text-gray-900 tracking-tight" data-split>Exhibitions & <span class="text-kicc-gold">Trade Shows</span></h1>
        <p class="text-[#0B0B0B] mt-3 text-base max-w-xl">Discover exhibitions, book booths, and purchase tickets across Kenya.</p>
    </div>
</div>

<div class="max-w-7xl mx-auto px-5 py-12">
    @if($exhibitions->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach($exhibitions as $i => $exhibition)
        <a href="{{ route('exhibitions.show', $exhibition->slug) }}"
           class="bg-white rounded-2xl overflow-hidden border border-gray-200 hover:border-[#B3261E]/50 transition-all group card-hover block" data-tilt="5" data-reveal data-reveal-delay="{{ ($i % 3) * 90 }}">
            <div class="tilt-glare"></div>
            @if($exhibition->cover_image)
            <div class="h-48 overflow-hidden relative">
                <img src="{{ $exhibition->cover_image }}" alt="{{ $exhibition->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 opacity-85" loading="lazy" decoding="async">
            </div>
            @else
            <div class="w-full h-48 bg-gradient-to-br from-[#0B0B0B] to-[#0B0B0B] flex items-center justify-center text-5xl relative">
                <span class="opacity-30"></span>
                <div class="absolute w-32 h-32 rounded-full bg-[#B3261E]/15 blur-2xl"></div>
            </div>
            @endif
            @if(in_array($exhibition->id, $liveStreams ?? []))
            <div class="absolute top-3 left-3 flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-red-500/80 backdrop-blur text-white">LIVE</span>
            </div>
            @endif
            <div class="p-5 relative">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs text-[#0B0B0B]">{{ $exhibition->start_date->format('M d') }} - {{ $exhibition->end_date->format('M d, Y') }}</span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border {{ $exhibition->status === 'published' ? 'text-emerald-400 bg-emerald-500/15 border-emerald-500/25' : 'text-[#0B0B0B] bg-sky-50 border-gray-200' }}">
                        {{ ucfirst($exhibition->status) }}
                    </span>
                </div>
                @if($exhibition->venue)
                <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($exhibition->venue->name . ', ' . ($exhibition->venue->city ?? $exhibition->county?->name ?? '')) }}" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-1 text-[10px] text-[#0B0B0B] hover:text-[#B3261E] transition-colors mb-1">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.73 7 13 7 13s7-7.27 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5S14.5 7.62 14.5 9s-1.12 2.5-2.5 2.5z"/></svg>
                    {{ $exhibition->venue->name }}@if($exhibition->venue->city), {{ $exhibition->venue->city }}@endif
                </a>
                @elseif($exhibition->county)
                <span class="inline-flex items-center gap-1 text-[10px] text-[#0B0B0B]">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.73 7 13 7 13s7-7.27 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5S14.5 7.62 14.5 9s-1.12 2.5-2.5 2.5z"/></svg>
                    {{ $exhibition->county->name }}
                </span>
                @endif
                <h3 class="font-black text-gray-900 text-base leading-snug">{{ $exhibition->name }}</h3>
                <p class="text-[#0B0B0B] text-sm mt-2 leading-relaxed">{{ Str::limit($exhibition->tagline ?? $exhibition->description, 100) }}</p>
                <div class="flex items-center justify-between mt-4">
                    <span class="text-xs text-[#0B0B0B]">{{ $exhibition->booths_count ?? $exhibition->booths?->count() ?? 0 }} booths</span>
                    <span class="text-kicc-gold font-bold text-xs group-hover:translate-x-1 transition-transform inline-flex items-center gap-1">
                        View Details
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </span>
                </div>
            </div>
        </a>
        @endforeach
    </div>
    <div class="mt-8">
        {{ $exhibitions->links() }}
    </div>
    @else
    <div class="text-center py-16 bg-white rounded-2xl border border-gray-200 card-hover" data-reveal>
        <div class="text-5xl mb-4"></div>
        <h3 class="text-xl font-semibold text-gray-900 mb-2">No exhibitions yet</h3>
        <p class="text-[#0B0B0B] text-sm">Check back soon for upcoming exhibitions.</p>
    </div>
    @endif
</div>
@endsection
