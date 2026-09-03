@extends('layouts.app')

@section('title', $stream->name . ' — Live Stream')
@section('content')
<div class="pt-20 max-w-7xl mx-auto px-5 py-10">
    <a href="{{ route('streams.index') }}" class="inline-flex items-center gap-1.5 text-gray-400 hover:text-gray-900 text-sm mb-6 transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        All Streams
    </a>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
            <x-hls-player
                :hls-url="$stream->hls_url"
                :poster="$stream->thumbnail_url ?? null"
                :title="$stream->name"
                :autoplay="$stream->isLive()"
                class="aspect-video"
            />

            <div class="bg-white border border-gray-200 rounded-2xl p-5">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h1 class="text-xl font-black text-gray-900">{{ $stream->name }}</h1>
                        @if($stream->exhibition)
                        <p class="text-gray-400 text-sm">{{ $stream->exhibition->name }}</p>
                        @endif
                    </div>
                    @if($stream->isLive())
                    <span class="text-[10px] font-bold px-2.5 py-1 rounded-full bg-red-100 text-red-600 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>LIVE
                    </span>
                    @elseif($stream->status === 'ended')
                    <span class="text-[10px] font-bold px-2.5 py-1 rounded-full bg-gray-100 text-gray-500">ENDED</span>
                    @else
                    <span class="text-[10px] font-bold px-2.5 py-1 rounded-full bg-amber-100 text-amber-600">IDLE</span>
                    @endif
                </div>
                @if($stream->description)
                <p class="text-gray-600 text-sm leading-relaxed">{{ $stream->description }}</p>
                @endif
            </div>
        </div>

        <div class="space-y-4">
            <x-live-chat :live-stream-id="$stream->id" height="500px" />

            @auth
            @if(auth()->user()->hasAnyRole(['kicc_admin', 'county_admin']))
            <div class="bg-white border border-gray-200 rounded-2xl p-5 space-y-3">
                <h3 class="text-xs font-bold text-gray-900 uppercase tracking-widest">Stream Controls</h3>
                @if($stream->isLive())
                <form method="POST" action="{{ route('streams.end', $stream) }}">
                    @csrf
                    <button class="w-full h-10 rounded-xl bg-red-500 text-white text-xs font-bold hover:bg-red-600">End Stream</button>
                </form>
                @elseif($stream->status === 'idle')
                <form method="POST" action="{{ route('streams.go-live', $stream) }}">
                    @csrf
                    <button class="w-full h-10 rounded-xl bg-green-500 text-white text-xs font-bold hover:bg-green-600">Go Live</button>
                </form>
                @endif
                @if($stream->stream_url)
                <div class="mt-3">
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Stream URL</label>
                    <p class="text-xs font-mono text-gray-700 bg-gray-50 rounded-xl p-2 mt-1 break-all">{{ $stream->stream_url }}</p>
                </div>
                @endif
            </div>
            @endif
            @endauth
        </div>
    </div>
</div>
@endsection