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
/* Responsive touch targets */
        @media (max-width: 640px) {
            .nav-link { padding: 0.625rem 0.75rem; font-size: 0.75rem; }
            .h1-responsive { font-size: 1.75rem !important; line-height: 1.2 !important; }
            .h2-responsive { font-size: 1.5rem !important; }
            .section-padding { padding-top: 2.5rem !important; padding-bottom: 2.5rem !important; }
            .sticky-sidebar { position: relative !important; top: auto !important; }
            .mobile-full { width: 100% !important; }
            .touch-target { min-height: 44px; min-width: 44px; }
        }
        @media (max-width: 768px) {
            .md-hidden { display: none !important; }
            .mobile-stack { flex-direction: column !important; }
            .mobile-text-center { text-align: center !important; }
        }
</style>
@endpush

@section('content')
<div class="pt-20">
{{--  HERO  --}}
@php $heroVids = $sectorHeroVideos ?? []; @endphp
@if(count($heroVids) > 0)
<div class="relative h-[45vh] md:h-[55vh] overflow-hidden bg-black"
     x-data="sectorHeroPlayer({
        videos: {{ Js::from(array_values($heroVids)) }},
        poster: '{{ $sectorHeroPoster ?? "" }}'
     })">
    @php $isImagePoster = $sectorHeroPoster && !str_contains($sectorHeroPoster, '.mp4') && !str_contains($sectorHeroPoster, '.webm'); @endphp
    @if($isImagePoster)
    <img src="{{ $sectorHeroPoster }}" alt="{{ $sectorInfo['title'] }}"
         class="absolute inset-0 w-full h-full object-cover"
         :class="videoReady ? 'opacity-0' : 'opacity-100'"
         style="transition: opacity 0.6s ease; z-index:1"
         loading="lazy" decoding="async"
         onerror="this.style.display='none'">
    @endif
    <video x-ref="sectorHero"
           autoplay muted loop playsinline preload="auto"
           class="absolute inset-0 w-full h-full object-cover"
           :class="videoReady ? 'opacity-100' : 'opacity-0'"
           style="transition: opacity 0.6s ease; z-index:2"
           @playing="videoReady = true"
           @ended="nextSectorVideo()"
           poster="{{ $isImagePoster ? $sectorHeroPoster : '' }}">
        <source :src="currentSrc" type="video/mp4">
        <source src="{{ $heroVids[0] ?? '' }}" type="video/mp4">
    </video>
    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent" style="z-index:5"></div>
    <div class="absolute bottom-0 left-0 right-0 max-w-7xl mx-auto px-5 pb-10" style="z-index:6">
        <a href="{{ route('counties.show', $county->slug) }}" class="inline-flex items-center gap-1.5 text-white/60 hover:text-white text-sm mb-3 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            {{ $county->name }} County
        </a>
        <div class="flex items-center gap-4">
            <div>
                <h1 class="text-3xl md:text-5xl font-black text-white" data-split>{{ $sectorInfo['title'] }}</h1>
                <p class="text-white/70 text-sm mt-2">{{ $sectorInfo['desc'] }}</p>
            </div>
        </div>
    </div>
</div>
@elseif($fourDVideo)
<div class="relative h-[45vh] md:h-[55vh] overflow-hidden bg-black">
    <video autoplay muted loop playsinline loading="lazy" preload="metadata" class="absolute inset-0 w-full h-full object-cover">
        <source src="{{ $fourDVideo }}" type="video/mp4">
    </video>
    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
    <div class="absolute bottom-0 left-0 right-0 max-w-7xl mx-auto px-5 pb-10">
        <a href="{{ route('counties.show', $county->slug) }}" class="inline-flex items-center gap-1.5 text-white/60 hover:text-white text-sm mb-3 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            {{ $county->name }} County
        </a>
        <div class="flex items-center gap-4">
            <div>
                <h1 class="text-3xl md:text-5xl font-black text-white" data-split>{{ $sectorInfo['title'] }}</h1>
                <p class="text-white/70 text-sm mt-2">{{ $sectorInfo['desc'] }}</p>
            </div>
        </div>
    </div>
</div>
@else
<div class="relative h-[45vh] md:h-[55vh] overflow-hidden bg-black">
    <img src="{{ $sectorHeroPoster ?? media('counties/' . $county->slug . '/hero.jpeg') }}" alt="{{ $sectorInfo['title'] }}"
         class="absolute inset-0 w-full h-full object-cover"
         loading="lazy" decoding="async"
         onerror="this.style.display='none'">
    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
    <div class="absolute bottom-0 left-0 right-0 max-w-7xl mx-auto px-5 pb-10" style="z-index:6">
        <a href="{{ route('counties.show', $county->slug) }}" class="inline-flex items-center gap-1.5 text-white/60 hover:text-white text-sm mb-3 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            {{ $county->name }} County
        </a>
        <div class="flex items-center gap-4">
            <div>
                <h1 class="text-3xl md:text-5xl font-black text-white" data-split>{{ $sectorInfo['title'] }}</h1>
                <p class="text-white/70 text-sm mt-2">{{ $sectorInfo['desc'] }}</p>
            </div>
        </div>
    </div>
</div>
@endif

    {{--  ENTITIES GRID  --}}
    <div class="max-w-7xl mx-auto px-5 py-12">
        @if($items->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($items as $e)
            @php
            $isInst = $e->entity_type === 'App\Models\CountyInstitution' || $e->entity_type === 'institution' || $e->entity_type === \App\Services\InstitutionSyncService::ENTITY_TYPE;
            $instSlug = $isInst ? \App\Models\CountyInstitution::find($e->entity_id)?->slug : null;
            $entityVideo = $entityVideos[$e->id] ?? $institutionHeroVideos[$e->id] ?? null;
            $entityPoster = $entityPosters[$e->id] ?? $institutionHeroPosters[$e->id] ?? null;
            $entityHover = $entityHoverLoops[$e->id] ?? $institutionHeroLoops[$e->id] ?? null;
            $entitySplat = $entitySplats[$e->id] ?? null;
            $linkUrl = $isInst && $instSlug
                ? route('counties.institution', $instSlug)
                : ($e->entry_fee > 0 ? route('attractions.show', $e->id) : '#');
            @endphp
            <div class="entity-card group bg-white rounded-2xl overflow-hidden border border-gray-200 hover:border-[#FFCD05]/40 transition-all">
                <x-media-tile
                    :poster="$entityPoster"
                    :hover-loop="$entityHover"
                    :video-url="$entityVideo"
                    :splat-url="$entitySplat"
                    :is4d="(bool)$entitySplat"
                    :title="$e->name"
                    :subtitle="$e->location ?? null"
                    :category="$e->category"
                    :badge="!empty($e->entry_fee) ? 'KES ' . number_format($e->entry_fee) : null"
                    :href="$linkUrl"
                />
                <div class="p-4">
                    <h3 class="font-bold text-gray-900 text-sm leading-snug">{{ $e->name }}</h3>
                    @if(isset($entityReviewScores[$e->id]) && $entityReviewScores[$e->id]['count'] > 0)
                    <div class="flex items-center gap-1.5 mt-1.5" title="{{ $entityReviewScores[$e->id]['source'] ?? '' }}">
                        <span class="flex items-center gap-0.5">
                            @for($i = 1; $i <= 5; $i++)
                            <svg class="w-3 h-3 {{ $i <= round($entityReviewScores[$e->id]['avg']) ? 'fill-[#FFCD05]' : 'fill-gray-200' }}" viewBox="0 0 20 20"><path d="M10 15.27L16.18 19l-1.64-7.03L20 7.24l-7.19-.61L10 0 7.19 6.63 0 7.24l5.46 4.73L3.82 19z"/></svg>
                            @endfor
                        </span>
                        <span class="text-[11px] font-bold text-gray-700">{{ number_format($entityReviewScores[$e->id]['avg'], 1) }}</span>
                        <span class="text-[10px] text-gray-400">({{ number_format($entityReviewScores[$e->id]['count']) }})</span>
                    </div>
                    @endif
                    @if($e->description)
                    <p class="text-gray-500 text-xs leading-relaxed line-clamp-2 mt-1">{{ $e->description }}</p>
                    @endif
                    <div class="flex items-center gap-3 mt-2 text-[11px] text-gray-400">
                        @if($e->location)<span class="truncate">{{ $e->location }}</span>@endif
                        @if(!empty($e->contact))<span>{{ $e->contact }}</span>@endif
                    </div>
                    <div class="mt-3 flex items-center justify-between">
                        <a href="{{ $linkUrl }}" class="text-[10px] font-bold text-indigo-500 hover:text-indigo-600 transition-colors">
                            {{ $isInst ? 'View Profile →' : 'Explore →' }}
                        </a>
                    </div>
                </div>
            </div>
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

    {{--  SECTOR ENTITY REVIEWS  --}}
    <div class="max-w-7xl mx-auto px-5 pb-10">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white border border-gray-200 rounded-2xl p-5">
                <h3 class="text-xs font-bold text-gray-900 uppercase tracking-widest mb-4">Latest Reviews</h3>
                @php
                    $sectorEntityIds = $items->pluck('id');
                    $sectorReviews = $sectorEntityIds->isNotEmpty()
                        ? \App\Models\SectorEntityReview::whereIn('sector_entity_id', $sectorEntityIds)
                            ->with('user', 'entity')->latest()->take(10)->get()
                        : collect();
                @endphp
                @forelse($sectorReviews as $sr)
                <div class="border-b border-gray-100 pb-3 mb-3 last:border-0">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-bold text-gray-900">{{ $sr->entity?->name ?? 'Entity' }}</span>
                        <span class="text-xs font-bold text-[#FFCD05]">{{ str_repeat('', (int) round($sr->rating)) }}{{ str_repeat('', 5 - (int) round($sr->rating)) }}</span>
                    </div>
                    <p class="text-xs text-gray-600">{{ $sr->review }}</p>
                    <div class="text-[10px] text-gray-400 mt-1">{{ $sr->user?->name ?? 'Visitor' }} · {{ $sr->created_at?->diffForHumans() }}</div>
                </div>
                @empty
                <p class="text-xs text-gray-400 py-3 text-center">No reviews yet for this sector — be the first.</p>
                @endforelse
            </div>
            <x-review-form
                :reviewable-type="\App\Models\SectorEntity::class"
                :reviewable-id="$items->first()?->id ?? 0"
            />
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
function sectorHeroPlayer(config) {
    return {
        videos: config.videos || [],
        currentIndex: 0,
        videoReady: false,
        get hasVideos() {
            return this.videos.length > 0;
        },
        get currentSrc() {
            return this.videos[this.currentIndex] || '';
        },
        nextSectorVideo() {
            if (this.videos.length <= 1) return;
            this.currentIndex = (this.currentIndex + 1) % this.videos.length;
            var video = this.$refs.sectorHero;
            if (video) {
                video.src = this.currentSrc;
                video.load();
                video.play().catch(function(){});
            }
        }
    };
}
</script>
@endpush