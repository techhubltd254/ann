@extends('layouts.app')

@section('title', 'Live Streams — KICC')
@section('content')
<div class="pt-20 max-w-7xl mx-auto px-5 py-10">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-black text-gray-900">Live Streams</h1>
            <p class="text-gray-400 text-sm mt-1">Watch live broadcasts from exhibitions and events.</p>
        </div>
        @auth
        <a href="{{ route('streams.create') }}" class="inline-flex items-center gap-2 font-bold text-sm h-11 px-5 rounded-xl bg-[#901C1E] text-white hover:bg-[#7b1618]">Start a Stream</a>
        @endauth
    </div>

    @if($streams->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($streams as $stream)
        <a href="{{ route('streams.show', $stream) }}" class="group bg-white border border-gray-200 rounded-2xl overflow-hidden hover:border-[#FFCD05]/40 transition-all card-hover">
            <div class="aspect-video bg-[#0B1E57] relative overflow-hidden">
                @if($stream->thumbnail_url)
                <img src="{{ $stream->thumbnail_url }}" alt="{{ $stream->name }}" class="w-full h-full object-cover" loading="lazy">
                @endif
                @if($stream->isLive())
                <div class="absolute top-3 left-3 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-red-500/80 text-white">LIVE</span>
                </div>
                @endif
                <div class="absolute bottom-3 right-3">
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-black/50 backdrop-blur text-white/80">{{ $stream->formattedViewerCount() }} watching</span>
                </div>
            </div>
            <div class="p-4">
                <h3 class="font-bold text-gray-900 text-sm">{{ $stream->name }}</h3>
                @if($stream->exhibition)<p class="text-gray-400 text-xs mt-1">{{ $stream->exhibition->name }}</p>@endif
                @if($stream->description)<p class="text-gray-500 text-xs mt-1 line-clamp-2">{{ $stream->description }}</p>@endif
            </div>
        </a>
        @endforeach
    </div>
    {{ $streams->links() }}
    @else
    <div class="text-center py-20 bg-white rounded-2xl border border-gray-100">
        <div class="w-16 h-16 rounded-2xl mx-auto mb-4 flex items-center justify-center bg-gray-50">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
        </div>
        <h3 class="text-gray-900 font-bold mb-2">No streams yet</h3>
        <p class="text-gray-400 text-sm">Live streams will appear here when exhibitions go live.</p>
    </div>
    @endif
</div>
@endsection