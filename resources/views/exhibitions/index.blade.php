@extends('layouts.app')

@section('title', 'Exhibitions')
@section('description', 'Browse exhibitions, trade shows, and events across Kenya')

@section('content')
<div class="bg-[#0D1220] border-b border-white/8 py-14 relative overflow-hidden">
    <div class="absolute w-96 h-96 rounded-full bg-[#901C1E]/10 blur-3xl -top-20 right-0"></div>
    <div class="max-w-7xl mx-auto px-5 relative" data-reveal>
        <div class="flex items-center gap-3 mb-3">
            <div class="h-px w-8 bg-kicc-gold"></div>
            <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">Events</span>
        </div>
        <h1 class="text-4xl md:text-5xl font-black text-white tracking-tight">Exhibitions & <span class="text-kicc-gold">Trade Shows</span></h1>
        <p class="text-white/50 mt-3 text-base max-w-xl">Discover exhibitions, book booths, and purchase tickets across Kenya.</p>
    </div>
</div>

<div class="max-w-7xl mx-auto px-5 py-12">
    @if($exhibitions->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach($exhibitions as $i => $exhibition)
        <a href="{{ route('exhibitions.show', $exhibition->slug) }}"
           class="bg-[#0D1220] rounded-2xl overflow-hidden border border-white/8 hover:border-[#901C1E]/50 transition-all group card-hover block" data-tilt="5" data-reveal data-reveal-delay="{{ ($i % 3) * 90 }}">
            <div class="tilt-glare"></div>
            @if($exhibition->cover_image)
            <div class="h-48 overflow-hidden relative">
                <img src="{{ $exhibition->cover_image }}" alt="{{ $exhibition->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 opacity-85">
            </div>
            @else
            <div class="w-full h-48 bg-gradient-to-br from-[#141B2E] to-[#0D1220] flex items-center justify-center text-5xl relative">
                <span class="opacity-30">🏛️</span>
                <div class="absolute w-32 h-32 rounded-full bg-[#901C1E]/15 blur-2xl"></div>
            </div>
            @endif
            <div class="p-5 relative">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs text-white/40">{{ $exhibition->start_date->format('M d') }} - {{ $exhibition->end_date->format('M d, Y') }}</span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border {{ $exhibition->status === 'published' ? 'text-emerald-400 bg-emerald-500/15 border-emerald-500/25' : 'text-white/40 bg-white/5 border-white/10' }}">
                        {{ ucfirst($exhibition->status) }}
                    </span>
                </div>
                <h3 class="font-black text-white text-base leading-snug">{{ $exhibition->name }}</h3>
                <p class="text-white/40 text-sm mt-2 leading-relaxed">{{ Str::limit($exhibition->tagline ?? $exhibition->description, 100) }}</p>
                <div class="flex items-center justify-between mt-4">
                    <span class="text-xs text-white/30">{{ $exhibition->booths_count ?? $exhibition->booths?->count() ?? 0 }} booths</span>
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
    <div class="text-center py-16 bg-[#0D1220] rounded-2xl border border-white/8" data-reveal>
        <div class="text-5xl mb-4">🏛️</div>
        <h3 class="text-xl font-semibold text-white mb-2">No exhibitions yet</h3>
        <p class="text-white/40 text-sm">Check back soon for upcoming exhibitions.</p>
    </div>
    @endif
</div>
@endsection
