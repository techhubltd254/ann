@extends('layouts.app')

@section('title', $screen->label . ' — KICC Exhibition Screens')

@push('styles')
<style>
    .vid-container { position: relative; width: 100%; background: #000; }
    .vid-container video {
        width: 100%; max-height: 80vh; object-fit: contain;
        display: block; margin: 0 auto;
    }
</style>
@endpush

@section('content')
<div class="bg-gray-900 min-h-screen">
    <div class="max-w-5xl mx-auto px-6 lg:px-8 py-8">
        <a href="{{ route('screens.directory') }}" class="inline-flex items-center gap-2 text-gray-400 hover:text-white mb-6 transition-colors text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            <span>All Screens</span>
        </a>

        <div class="flex items-center gap-3 mb-6">
            <h1 class="text-2xl font-bold text-white">{{ $screen->label }}</h1>
            <span class="text-xs px-2 py-0.5 rounded-full bg-gray-700 text-gray-400 font-mono">{{ $screen->id }}</span>
        </div>

        <div class="vid-container rounded-xl overflow-hidden shadow-2xl border border-gray-700/50 mb-8">
            @if($screen->video_exists)
            <video controls autoplay playsinline>
                <source src="{{ $screen->video_url }}" type="video/mp4">
            </video>
            @else
            <div class="flex items-center justify-center h-64 text-gray-500">
                <div class="text-center">
                    <div class="text-4xl mb-3">🎬</div>
                    <p class="text-lg">Video not yet generated</p>
                    <p class="text-sm text-gray-600 mt-1">Run <code class="text-amber-400">php artisan screen:generate {{ $screen->id }}</code></p>
                </div>
            </div>
            @endif
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-gray-800/50 rounded-xl p-4 border border-gray-700/50">
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Duration</div>
                <div class="text-white font-bold text-lg">{{ $screen->target_duration_sec }}s</div>
            </div>
            <div class="bg-gray-800/50 rounded-xl p-4 border border-gray-700/50">
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Location</div>
                <div class="text-white font-bold text-lg">{{ $screen->location ?: '—' }}</div>
            </div>
            @if($screen->video_size_mb)
            <div class="bg-gray-800/50 rounded-xl p-4 border border-gray-700/50">
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">File Size</div>
                <div class="text-white font-bold text-lg">{{ $screen->video_size_mb }} MB</div>
            </div>
            @endif
            <div class="bg-gray-800/50 rounded-xl p-4 border border-gray-700/50">
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Refresh</div>
                <div class="text-white font-bold text-lg">Every {{ $screen->refresh_interval_min }}min</div>
            </div>
        </div>

        @if($screen->county_id || $screen->sector_id)
        <div class="bg-gray-800/50 rounded-xl p-5 border border-gray-700/50">
            <h3 class="text-white font-semibold mb-3">Screen Configuration</h3>
            <div class="text-sm text-gray-400 space-y-2">
                @if($screen->county_id)
                <div><span class="text-gray-500">County:</span> <span class="text-amber-400">{{ Str::title($screen->county_id) }}</span></div>
                @endif
                @if($screen->sector_id)
                <div><span class="text-gray-500">Sector:</span> <span class="text-emerald-400">{{ Str::title($screen->sector_id) }}</span></div>
                @endif
                <div><span class="text-gray-500">Min images:</span> <span class="text-white">{{ $screen->min_images }}</span></div>
                <div><span class="text-gray-500">Max images:</span> <span class="text-white">{{ $screen->max_images }}</span></div>
                <div><span class="text-gray-500">Preset:</span> <span class="text-white">{{ $screen->preset_key }}</span></div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
