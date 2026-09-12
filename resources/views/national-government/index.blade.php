@extends('layouts.app')
@section('title', 'National Government of Kenya — KICC')
@section('description', 'Kenya\'s national government ministries, state departments, and agencies.')
@section('content')
@php
    $heroVideo = $heroVid ?? null;
    $heroPoster = $heroPoster ?? media('kicc/national-hero.jpeg');
@endphp
<div class="pt-20">
    {{-- HERO: video or fallback --}}
    <div class="relative min-h-[60vh] md:min-h-[70vh] overflow-hidden">
        @if($heroVideo)
        <video autoplay muted loop playsinline preload="metadata" class="absolute inset-0 w-full h-full object-cover" poster="{{ $heroPoster }}">
            <source src="{{ $heroVideo }}" type="video/mp4">
        </video>
        @else
        <div class="absolute inset-0" style="background: linear-gradient(135deg, #0B1E57 0%, #A6192E 50%, #0B1E57 100%);"></div>
        @endif
        <div class="absolute inset-0" style="background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.2) 50%, transparent 100%);"></div>

        <div class="absolute bottom-0 left-0 right-0 max-w-7xl mx-auto px-5 pb-10 md:pb-16" style="z-index:5">
            <div class="flex items-center gap-3 mb-3">
                <span class="h-px w-8" style="background: var(--kicc-gold);"></span>
                <span class="text-xs font-bold tracking-[0.2em] uppercase" style="color: var(--kicc-gold);">National Government</span>
            </div>
            <h1 class="text-4xl md:text-6xl font-black leading-tight text-white" style="text-shadow: 0 2px 20px rgba(0,0,0,0.3);">Kenya's <span style="color: var(--kicc-gold);">Government</span></h1>
            <p class="text-white/80 text-lg mt-3 max-w-2xl">Ministries, state departments and agencies powering Kenya's digital economy.</p>
            <div class="flex gap-3 mt-4">
                <a href="{{ route('national.index') }}" class="px-5 py-2.5 rounded-lg font-semibold text-sm inline-flex items-center gap-2" style="background: var(--kicc-crimson); color: white;">
                    <span class="animate-pulse">●</span> Live Exhibition
                </a>
                <a href="#ministries" class="px-5 py-2.5 rounded-lg font-semibold text-sm" style="background: rgba(255,255,255,0.15); color: white; backdrop-filter: blur(8px);">Explore Ministries →</a>
            </div>
        </div>
    </div>

    {{-- Live Booth Widget --}}
    <div class="max-w-7xl mx-auto px-5 mt-6">
        <x-live-booths-widget county-slug="national" />
    </div>

    {{-- Ministries section with hover tiles --}}
    <div id="ministries" class="max-w-7xl mx-auto px-5 py-12">
        <div class="flex items-center gap-3 mb-8">
            <span class="h-px w-8" style="background: var(--kicc-gold);"></span>
            <span class="text-xs font-bold tracking-[0.2em] uppercase" style="color: var(--kicc-navy);">Ministries</span>
            <span class="text-sm ml-auto" style="color: var(--kicc-text-light);">{{ $stats['ministries'] }} total</span>
        </div>

<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($ministries as $idx => $m)
            @php
                $tile = $tileMedia[$m['slug']] ?? [];
                $hasVideo = !empty($tile['videoUrl']);
                $tileHover = $tile['hoverLoopUrl'] ?? $tile['videoUrl'] ?? null;
                $tilePoster = $tile['posterUrl'] ?? null;
            @endphp
            <div class="group bg-white rounded-2xl overflow-hidden border border-gray-200 hover:border-[#FFCD05]/40 transition-all card-hover"
                 x-data="mediaTile()"
                 @mouseenter="onHoverEnter()"
                 @mouseleave="onHoverLeave()">
                <a href="{{ route('national.site', $m['slug']) }}" class="block">
                    <div class="aspect-[4/3] overflow-hidden relative {{ $hasVideo ? 'bg-[#0B1E57]' : 'bg-gradient-to-br from-[#0A1024] to-[#1a1a2e]' }}">
                        @if($hasVideo)
                        <video x-ref="video" muted loop playsinline preload="auto"
                               class="absolute inset-0 w-full h-full object-cover transition-opacity duration-500"
                               :class="videoReady ? 'opacity-100' : 'opacity-0'"
                               poster="{{ $tilePoster ?? '' }}"
                               @playing="onVideoPlaying()">
                            <source src="{{ $tileHover }}" type="video/mp4">
                        </video>
                        <img src="{{ $tilePoster ?? '' }}" alt="{{ $m['name'] }}" loading="lazy"
                             class="absolute inset-0 w-full h-full object-cover transition-opacity duration-500"
                             :class="videoReady ? 'opacity-0' : 'opacity-100'"
                             onerror="this.style.display='none'">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/10 to-transparent pointer-events-none"></div>
                        @else
                        <div class="absolute inset-0 bg-gradient-to-br from-[#0A1024] to-[#1a1a2e]"></div>
                        @endif
                        @if($tileHover)
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/10 to-transparent pointer-events-none"></div>
                        @endif
                    </div>
                    <div class="p-4">
                        <div class="flex items-center gap-3 mb-2">
                            <h3 class="font-bold text-sm" style="color: var(--kicc-navy);">{{ $m['name'] }}</h3>
                        </div>
                        @if($m['description'])<p class="text-xs" style="color: var(--kicc-text);">{{ Str::limit($m['description'], 100) }}</p>@endif
                        @if($m['agencies'])
                        <div class="mt-2 flex flex-wrap gap-1">
                            @foreach(array_slice($m['agencies'], 0, 3) as $a)
                            <span class="text-[10px] px-1.5 py-0.5 rounded-full" style="background: var(--kicc-bg-alt); color: var(--kicc-text-light);">{{ $a['name'] }}</span>
                            @endforeach
                            @if(count($m['agencies']) > 3)
                            <span class="text-[10px] px-1.5 py-0.5 rounded-full font-semibold" style="color: var(--kicc-crimson);">+{{ count($m['agencies']) - 3 }}</span>
                            @endif
                        </div>
                        @endif
                    </div>
                </a>
            </div>
            @empty
            <div class="col-span-3 text-center py-16" style="color: var(--kicc-text-light);">
                <div class="text-4xl mb-3">🏛️</div>
                <p class="text-sm">No ministries listed yet.</p>
            </div>
            @endforelse
        </div>

        @if(!empty($agencies))
        <div class="mt-12">
            <h2 class="text-xl font-bold mb-4" style="color: var(--kicc-navy);">All Agencies ({{ $stats['agencies'] }})</h2>
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
                @foreach($agencies as $a)
                <div class="card-kicc p-3">
                    <div class="font-semibold text-sm" style="color: var(--kicc-navy);">{{ $a['name'] }}</div>
                    <div class="text-xs mt-1" style="color: var(--kicc-text-light);">{{ $a['ministry_name'] }}</div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection