@extends('layouts.app')

@section('title', 'Live Events — KICC')
@section('description', 'Watch live streams from exhibitions and events across Kenya')

@section('content')
<div class="bg-[#0B0B0B] pt-20 pb-16 md:pb-20">
    <div class="max-w-7xl mx-auto px-5">
        <div data-reveal>
            <div class="flex items-center gap-3 mb-3">
                <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                <span class="text-red-400 text-xs font-bold tracking-[0.2em] uppercase">Live Events</span>
            </div>
            <h1 class="text-4xl md:text-5xl font-black text-white leading-tight" data-split>Watch <span class="text-red-400">Live</span><br>from Kenya</h1>
            <p class="text-white/60 mt-3 text-base max-w-xl">Live streams, events, and exhibitions happening right now across Kenya.</p>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-5 py-12">
    {{-- Live Now Section --}}
    @if($live->count() > 0)
    <div class="mb-16">
        <div class="flex items-center gap-3 mb-6">
            <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
            <span class="text-red-500 text-xs font-bold tracking-[0.2em] uppercase">Live Now</span>
            <span class="h-px flex-1 bg-gray-200"></span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($live as $stream)
            <a href="{{ route('streams.show', $stream) }}" class="group bg-white border border-gray-200 rounded-2xl overflow-hidden hover:border-red-500/40 transition-all card-hover">
                <div class="aspect-video bg-[#0B0B0B] relative overflow-hidden">
                    @if($stream->thumbnail_url)
                    <img src="{{ $stream->thumbnail_url }}" alt="{{ $stream->name }}" class="w-full h-full object-cover" loading="lazy">
                    @endif
                    <div class="absolute top-3 left-3 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-red-500/80 backdrop-blur text-white">LIVE</span>
                    </div>
                    <div class="absolute bottom-3 right-3">
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-black/50 backdrop-blur text-white/80">{{ $stream->formattedViewerCount() }} watching</span>
                    </div>
                </div>
                <div class="p-4">
                    <h3 class="font-bold text-gray-900 text-sm">{{ $stream->name }}</h3>
                    @if($stream->exhibition)<p class="text-gray-400 text-xs mt-1">{{ $stream->exhibition->name }}</p>@endif
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @else
    <div class="mb-16">
        <div class="flex items-center gap-3 mb-6">
            <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
            <span class="text-red-500 text-xs font-bold tracking-[0.2em] uppercase">Live Now</span>
            <span class="h-px flex-1 bg-gray-200"></span>
        </div>
        <div class="bg-gradient-to-br from-[#0B0B0B] to-[#0B0B0B] rounded-2xl p-8 md:p-12 text-center">
            <div class="w-16 h-16 rounded-full bg-white/10 mx-auto mb-4 flex items-center justify-center">
                <svg class="w-8 h-8 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            </div>
            <h3 class="text-white text-xl font-bold mb-2">No live events right now</h3>
            <p class="text-white/50 text-sm max-w-md mx-auto">Check back soon for live streams from exhibitions and events across Kenya.</p>
        </div>
    </div>
    @endif

    {{-- Upcoming Exhibitions Section --}}
    @if($upcomingExhibitions->count() > 0)
    <div class="mb-16">
        <div class="flex items-center gap-3 mb-6">
            <span class="h-px w-8 bg-kicc-gold"></span>
            <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">Upcoming Events</span>
            <span class="h-px flex-1 bg-gray-200"></span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($upcomingExhibitions as $exhibition)
            <a href="{{ route('exhibitions.show', $exhibition->slug) }}" class="group bg-white border border-gray-200 rounded-2xl overflow-hidden hover:border-kicc-gold/40 transition-all card-hover">
                @if($exhibition->cover_image)
                <div class="h-40 overflow-hidden">
                    <img src="{{ $exhibition->cover_image }}" alt="{{ $exhibition->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" loading="lazy">
                </div>
                @else
                <div class="h-40 bg-gradient-to-br from-[#0B0B0B] to-[#0B0B0B] flex items-center justify-center">
                    <span class="text-white/20 text-4xl font-black">{{ substr($exhibition->name, 0, 2) }}</span>
                </div>
                @endif
                <div class="p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs text-gray-400">{{ $exhibition->start_date->format('M d, Y') }}</span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-kicc-gold/10 text-kicc-gold">{{ $exhibition->booths_count ?? 0 }} booths</span>
                    </div>
                    <h3 class="font-bold text-gray-900 text-sm">{{ $exhibition->name }}</h3>
                    @if($exhibition->tagline)<p class="text-gray-400 text-xs mt-1 line-clamp-1">{{ $exhibition->tagline }}</p>@endif
                </div>
            </a>
            @endforeach
        </div>
        <div class="mt-6 text-center">
            <a href="{{ route('exhibitions.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-[#0B0B0B] hover:text-kicc-gold transition-colors">
                View all exhibitions
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>
    </div>
    @endif

    {{-- Past Recorded Streams --}}
    @if($ended->count() > 0)
    <div>
        <div class="flex items-center gap-3 mb-6">
            <span class="h-px w-8 bg-gray-300"></span>
            <span class="text-gray-500 text-xs font-bold tracking-[0.2em] uppercase">Past Events</span>
            <span class="h-px flex-1 bg-gray-200"></span>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            @foreach($ended as $stream)
            <a href="{{ route('streams.show', $stream) }}" class="group bg-white border border-gray-200 rounded-xl overflow-hidden hover:border-gray-300 transition-all">
                <div class="aspect-video bg-gray-100 relative">
                    @if($stream->thumbnail_url)
                    <img src="{{ $stream->thumbnail_url }}" alt="{{ $stream->name }}" class="w-full h-full object-cover" loading="lazy">
                    @endif
                    <div class="absolute inset-0 flex items-center justify-center bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity">
                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    </div>
                </div>
                <div class="p-2.5">
                    <h4 class="text-xs font-bold text-gray-900 line-clamp-1">{{ $stream->name }}</h4>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Upcoming streams (idle, not yet started) --}}
    @if($upcoming->count() > 0)
    <div class="mt-16">
        <div class="flex items-center gap-3 mb-6">
            <span class="h-px w-8 bg-gray-300"></span>
            <span class="text-gray-500 text-xs font-bold tracking-[0.2em] uppercase">Scheduled Streams</span>
            <span class="h-px flex-1 bg-gray-200"></span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($upcoming as $stream)
            <div class="bg-white border border-gray-200 rounded-2xl p-4 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-gray-100 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <div class="min-w-0">
                    <h4 class="font-bold text-gray-900 text-sm truncate">{{ $stream->name }}</h4>
                    @if($stream->exhibition)<p class="text-gray-400 text-xs truncate">{{ $stream->exhibition->name }}</p>@endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection