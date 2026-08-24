@extends('layouts.app')

@section('title', $sectorInfo['title'] . ' — ' . $county->name . ' County')
@section('description', 'Explore ' . $sectorInfo['title'] . ' in ' . $county->name . ' County')

@push('styles')
<style>
.entity-card {
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.entity-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 40px -12px rgba(0,0,0,0.15);
}
.entity-card .card-media {
    transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
.entity-card:hover .card-media {
    transform: scale(1.05);
}
.entity-card .card-overlay {
    transition: opacity 0.4s ease;
}
.entity-card:hover .card-overlay {
    opacity: 1;
}
.sector-stats {
    background: linear-gradient(135deg, #0A1024 0%, #1a1a2e 50%, #0A1024 100%);
}
@keyframes heroFade {
    0%, 20% { opacity: 1; }
    25%, 95% { opacity: 0; }
    100% { opacity: 1; }
}
.hero-video-layer {
    position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover;
    animation: heroFade 20s infinite;
}
.hero-video-layer:nth-child(1) { animation-delay: 0s; }
.hero-video-layer:nth-child(2) { animation-delay: 5s; }
.hero-video-layer:nth-child(3) { animation-delay: 10s; }
.hero-video-layer:nth-child(4) { animation-delay: 15s; }
</style>
@endpush

@section('content')
<div class="pt-20">
{{-- ═══ HERO ═══ --}}
@php $heroVids = $sectorHeroVideos ?? []; @endphp
@if(count($heroVids) > 0)
<div class="relative h-[45vh] md:h-[55vh] overflow-hidden bg-black">
    @foreach($heroVids as $vi)
    <video autoplay muted loop playsinline preload="auto" class="hero-video-layer" onerror="this.style.display='none'">
        <source src="{{ $vi }}" type="video/mp4">
    </video>
    @endforeach
    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent" style="z-index:5"></div>
    <div class="absolute bottom-0 left-0 right-0 max-w-7xl mx-auto px-5 pb-10" style="z-index:6">
        <a href="{{ route('counties.show', $county->slug) }}" class="inline-flex items-center gap-1.5 text-white/60 hover:text-white text-sm mb-3 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            {{ $county->name }} County
        </a>
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center text-3xl shrink-0">{{ $sectorInfo['icon'] ?? '📋' }}</div>
            <div>
                <h1 class="text-3xl md:text-5xl font-black text-white" data-split>{{ $sectorInfo['title'] }}</h1>
                <p class="text-white/70 text-sm mt-2">{{ $sectorInfo['desc'] }}</p>
            </div>
        </div>
    </div>
</div>
@elseif($fourDVideo)
<div class="relative h-[45vh] md:h-[55vh] overflow-hidden bg-black">
    <video autoplay muted loop playsinline preload="auto" class="absolute inset-0 w-full h-full object-cover">
        <source src="{{ $fourDVideo }}" type="video/mp4">
    </video>
    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
    <div class="absolute bottom-0 left-0 right-0 max-w-7xl mx-auto px-5 pb-10">
        <a href="{{ route('counties.show', $county->slug) }}" class="inline-flex items-center gap-1.5 text-white/60 hover:text-white text-sm mb-3 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            {{ $county->name }} County
        </a>
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center text-3xl shrink-0">{{ $sectorInfo['icon'] ?? '📋' }}</div>
            <div>
                <h1 class="text-3xl md:text-5xl font-black text-white" data-split>{{ $sectorInfo['title'] }}</h1>
                <p class="text-white/70 text-sm mt-2">{{ $sectorInfo['desc'] }}</p>
            </div>
        </div>
    </div>
</div>
@else
    <div class="sector-stats border-b border-white/10 py-12">
        <div class="max-w-7xl mx-auto px-5">
            <a href="{{ route('counties.show', $county->slug) }}" class="inline-flex items-center gap-1.5 text-[#5A6480] hover:text-white text-sm mb-4 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                {{ $county->name }} County
            </a>
            <div class="flex items-center gap-5">
                <div class="w-16 h-16 rounded-2xl bg-white/10 backdrop-blur-md border border-white/10 flex items-center justify-center text-3xl shrink-0">{{ $sectorInfo['icon'] ?? '📋' }}</div>
                <div>
                    <h1 class="text-3xl md:text-4xl font-black text-white" data-split>{{ $sectorInfo['title'] }}</h1>
                    <p class="text-zinc-400 mt-1 text-sm">{{ $items->count() }} {{ Str::plural('entity', $items->count()) }} · {{ $county->name }} County</p>
                </div>
            </div>
            <p class="text-zinc-500 text-sm mt-4 max-w-xl">{{ $sectorInfo['desc'] }}</p>
        </div>
    </div>
    @endif

    {{-- ═══ ENTITIES GRID ═══ --}}
    <div class="max-w-7xl mx-auto px-5 py-12">
        @if($items->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($items as $e)
            @php
            $isInst = $e->entity_type === 'App\Models\CountyInstitution' || $e->entity_type === 'institution' || $e->entity_type === \App\Services\InstitutionSyncService::ENTITY_TYPE;
            $instSlug = $isInst ? \App\Models\CountyInstitution::find($e->entity_id)?->slug : null;
            $entityVideo = $entityVideos[$e->id] ?? $institutionHeroVideos[$e->id] ?? null;
            $linkUrl = $isInst && $instSlug
                ? route('counties.institution', $instSlug)
                : ($e->entry_fee > 0 ? route('attractions.show', $e->id) : '#');
            @endphp
            <a href="{{ $linkUrl }}" class="entity-card group bg-white rounded-2xl overflow-hidden border border-gray-200 hover:border-[#FFCD05]/40 block">
                <div class="h-48 overflow-hidden relative bg-gray-100">
                    @if($entityVideo)
                    <video autoplay muted loop playsinline preload="auto" class="card-media absolute inset-0 w-full h-full object-cover"
                           onerror="this.style.display='none'">
                        <source src="{{ $entityVideo }}" type="video/mp4">
                    </video>
                    @endif
                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent"></div>
                    @if($e->category)
                    <span class="absolute top-3 left-3 text-[10px] font-bold px-2.5 py-1 rounded-full bg-black/40 text-white/90 backdrop-blur-sm capitalize">{{ $e->category }}</span>
                    @endif
                    @if(!empty($e->entry_fee))
                    <span class="absolute top-3 right-3 text-[10px] font-bold px-2.5 py-1 rounded-full bg-[#FFCD05] text-black">KES {{ number_format($e->entry_fee) }}</span>
                    @endif
                    <div class="absolute bottom-3 left-3 right-3 opacity-0">
                    </div>
                </div>
                <div class="p-4">
                    <h3 class="font-bold text-gray-900 text-sm leading-snug">{{ $e->name }}</h3>
                    @if($e->description)
                    <p class="text-gray-500 text-xs leading-relaxed line-clamp-2 mt-1">{{ $e->description }}</p>
                    @endif
                    <div class="flex items-center gap-3 mt-2 text-[11px] text-gray-400">
                        @if($e->location)<span class="truncate">{{ $e->location }}</span>@endif
                        @if(!empty($e->contact))<span>{{ $e->contact }}</span>@endif
                    </div>
                    <div class="mt-3 flex items-center justify-between">
                        <span class="text-[10px] font-bold text-indigo-500 group-hover:text-indigo-600 transition-colors">
                            {{ $isInst ? 'View Profile →' : 'Explore →' }}
                        </span>
                    </div>
                </div>
            </a>
            @endforeach
        </div>

        <div class="mt-10">
            {{ $items->links() }}
        </div>
        @else
        <div class="text-center py-20">
            <div class="w-16 h-16 rounded-2xl mx-auto mb-4 flex items-center justify-center bg-gray-100">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <h3 class="text-gray-900 font-bold mb-2">No entities registered yet</h3>
            <p class="text-gray-400 text-sm">Entities in this sector will appear here once registered.</p>
        </div>
        @endif
    </div>
</div>
@endsection