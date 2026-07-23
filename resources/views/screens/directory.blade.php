@extends('layouts.app')

@section('title', 'Exhibition Screen Videos — KICC')

@section('content')
<div class="bg-gray-900 py-12">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-gray-400 hover:text-white mb-6 transition-colors text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            <span>Back to Home</span>
        </a>
        <h1 class="text-3xl font-bold text-white mb-2">🎬 Exhibition Screen Videos</h1>
        <p class="text-gray-400 mb-8">{{ $screens->count() }} screens — auto-generated showcase videos for every display in the KICC National Exhibition</p>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($screens as $screen)
            <div class="bg-gray-800/50 border border-gray-700/50 rounded-xl p-5 hover:bg-gray-800 transition-colors">
                <div class="flex items-start justify-between mb-3">
                    <h3 class="text-white font-semibold text-sm">{{ $screen->label }}</h3>
                    <span class="inline-block text-xs px-2 py-0.5 rounded-full
                        @if($screen->type_tag === 'county') bg-blue-900/50 text-blue-300
                        @elseif($screen->type_tag === 'sector') bg-green-900/50 text-green-300
                        @elseif($screen->type_tag === 'hero') bg-amber-900/50 text-amber-300
                        @elseif($screen->type_tag === 'hallway') bg-purple-900/50 text-purple-300
                        @else bg-gray-700 text-gray-400 @endif">
                        {{ $screen->type_tag }}
                    </span>
                </div>
                <div class="text-xs text-gray-500 font-mono mb-3">{{ $screen->id }}</div>
                <div class="flex gap-3 text-xs text-gray-400 mb-4">
                    <span>⏱ {{ $screen->target_duration_sec }}s</span>
                    <span>🖼 {{ $screen->image_count ?? '?' }} images</span>
                    @if($screen->video_size_mb)
                    <span>💾 {{ $screen->video_size_mb }} MB</span>
                    @endif
                </div>
                @if($screen->county_id || $screen->sector_id)
                <div class="text-xs text-gray-500 mb-3">
                    @if($screen->county_id)<span class="text-amber-400">{{ Str::title($screen->county_id) }}</span>@endif
                    @if($screen->sector_id)<span class="text-gray-500"> / </span><span class="text-emerald-400">{{ Str::title($screen->sector_id) }}</span>@endif
                </div>
                @endif
                <div class="flex gap-2">
                    @if($screen->video_exists)
                    <a href="{{ $screen->video_url }}" target="_blank"
                       class="inline-flex items-center gap-1.5 bg-amber-500/20 text-amber-300 text-xs font-medium px-3 py-1.5 rounded-lg hover:bg-amber-500/30 transition-colors">
                        ▶ Play
                    </a>
                    <a href="{{ route('screens.show', $screen->id) }}"
                       class="inline-flex items-center gap-1.5 bg-white/10 text-gray-300 text-xs font-medium px-3 py-1.5 rounded-lg hover:bg-white/20 transition-colors">
                        Details
                    </a>
                    @else
                    <span class="text-xs text-gray-600 italic">No video generated yet</span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
