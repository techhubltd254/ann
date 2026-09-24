@extends('layouts.app')

@section('title', $institution->name . ' — ' . $county->name)
@section('description', $institution->description ?? $institution->story ?? $institution->name)

@section('content')
<div class="pt-20">

    {{--  HERO VIDEO SECTION (adaptive HLS / 4D / fallback loop)  --}}
    @if($heroVideo || $heroSplat)
    <div class="relative h-[50vh] md:h-[60vh] overflow-hidden bg-black">
        @if($heroSplat)
        <x-hologram-viewer :poster="$heroPoster" :video-url="$heroVideo" :hls-url="$heroHls ?? null" :splat-url="$heroSplat" :title="$institution->name" />
        @else
        <x-video-player
            :asset="$heroAsset ?? null"
            :src="$heroVideo"
            :poster="$heroPoster"
            id="institution-hero"
            class="w-full h-full"
            :autoplay="true"
            :loop="true"
            :muted="true"
        />
        @endif
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
        <div class="absolute bottom-0 left-0 right-0 max-w-7xl mx-auto px-5 pb-10">
            <a href="{{ route('counties.show', $county->slug) }}" class="inline-flex items-center gap-1.5 text-white/60 hover:text-white text-sm mb-3 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                {{ $county->name }} County
            </a>
            <div class="flex items-center gap-4">
                @if($institution->logo_url)
                <img src="{{ $institution->logo_url }}" class="w-16 h-16 rounded-xl object-cover border-2 border-white/20">
                @endif
                <div>
                    <h1 class="text-3xl md:text-5xl font-black text-white" data-split>{{ $institution->name }}</h1>
                    <p class="text-white/70 text-sm mt-2">{{ $institution->type ?? 'Institution' }}
                        @if($institution->founded_year) · Founded {{ $institution->founded_year }}@endif
                        @if($institution->headquarters) · {{ $institution->headquarters }}@endif
                    </p>
                </div>
            </div>
        </div>
    </div>
    @elseif(!empty($institutionFallbackVideos))
    {{-- Fallback: no hero video — cycle related sector/institution videos seamlessly --}}
    @php $isImagePoster = $heroPoster && !str_contains($heroPoster, '.mp4') && !str_contains($heroPoster, '.webm'); @endphp
    <div class="relative h-[50vh] md:h-[60vh] overflow-hidden bg-black"
         x-data="institutionFallbackPlayer({
            videos: {{ Js::from($institutionFallbackVideos) }},
            poster: '{{ $isImagePoster ? $heroPoster : '' }}'
         })">
        @if($isImagePoster)
        <img src="{{ $heroPoster }}" alt="{{ $institution->name }}"
             class="absolute inset-0 w-full h-full object-cover"
             :class="videoReady ? 'opacity-0' : 'opacity-100'"
             style="transition: opacity 0.6s ease; z-index:1"
             loading="lazy" decoding="async"
onerror="this.remove()">
         @endif
         <video x-ref="fallbackVideo"
               autoplay muted loop playsinline preload="metadata"
               class="absolute inset-0 w-full h-full object-cover"
               :class="videoReady ? 'opacity-100' : 'opacity-0'"
               style="transition: opacity 0.6s ease; z-index:2"
               @playing="videoReady = true"
               @ended="nextVideo()"
               poster="{{ $isImagePoster ? $heroPoster : '' }}">
            <source :src="currentSrc" type="video/mp4">
        </video>
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent" style="z-index:3"></div>
        <div class="absolute bottom-0 left-0 right-0 max-w-7xl mx-auto px-5 pb-10" style="z-index:5">
            <a href="{{ route('counties.show', $county->slug) }}" class="inline-flex items-center gap-1.5 text-white/60 hover:text-white text-sm mb-3 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                {{ $county->name }} County
            </a>
            <div class="flex items-center gap-4">
                @if($institution->logo_url)
                <img src="{{ $institution->logo_url }}" class="w-16 h-16 rounded-xl object-cover border-2 border-white/20">
                @endif
                <div>
                    <h1 class="text-3xl md:text-5xl font-black text-white" data-split>{{ $institution->name }}</h1>
                    @if(($institution->user->trust_grade ?? null))
                    @php
                        $tg = $institution->user->trust_grade;
                        $tgBadge = match($tg) {
                            'A' => 'bg-emerald-500/20 text-emerald-400',
                            'B' => 'bg-sky-500/20 text-sky-400',
                            'C' => 'bg-amber-500/20 text-amber-400',
                            'D' => 'bg-rose-500/20 text-rose-400',
                            'F' => 'bg-zinc-500/20 text-zinc-400',
                            default => 'bg-zinc-500/20 text-zinc-400',
                        };
                    @endphp
                    <span class="inline-flex items-center gap-1 mt-2 text-[10px] font-bold px-2.5 py-1 rounded-full {{ $tgBadge }}">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        Trust Grade {{ $tg }}
                    </span>
                    @endif
                    <p class="text-white/70 text-sm mt-2">{{ $institution->type ?? 'Institution' }}
                        @if($institution->founded_year) · Founded {{ $institution->founded_year }}@endif
                        @if($institution->headquarters) · {{ $institution->headquarters }}@endif
                    </p>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="bg-white border-b border-gray-200 py-12">
        <div class="max-w-7xl mx-auto px-5">
            <a href="{{ route('counties.show', $county->slug) }}" class="inline-flex items-center gap-1.5 text-[#5A6480] hover:text-gray-900 text-sm mb-4 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                {{ $county->name }} County
            </a>
            <div class="flex items-center gap-5">
                @if($institution->logo_url)
                <img src="{{ $institution->logo_url }}" class="w-16 h-16 rounded-xl object-cover border-2 border-gray-200">
                @endif
                <div>
                    <h1 class="text-3xl md:text-4xl font-black text-gray-900" data-split>{{ $institution->name }}</h1>
                    <p class="text-[#5A6480] mt-1 text-sm">{{ $institution->type ?? 'Institution' }}
                        @if($institution->founded_year) · Founded {{ $institution->founded_year }}@endif
                    </p>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{--  INSTITUTION INFO + PRODUCTS  --}}
    <div class="max-w-7xl mx-auto px-5 py-10">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Left: Description & Story --}}
            <div class="lg:col-span-2 space-y-8">
                @if($institution->description)
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="h-px w-8 bg-kicc-gold"></div>
                        <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">About</span>
                    </div>
                    <p class="text-gray-700 leading-relaxed">{{ $institution->description }}</p>
                </div>
                @endif

                @if($institution->story)
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="h-px w-8 bg-kicc-gold"></div>
                        <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">Our Story</span>
                    </div>
                    <div class="text-gray-600 leading-relaxed text-sm whitespace-pre-line">{{ $institution->story }}</div>
                </div>
                @endif

                {{-- Sector Mappings --}}
                @if($sectorEntities->count() > 0)
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="h-px w-8 bg-kicc-gold"></div>
                        <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">Sectors</span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($sectorEntities as $se)
                        <a href="{{ route('counties.sector', [$county->slug, $se->sector?->slug ?? 'education']) }}" class="px-3 py-1.5 rounded-full text-xs font-bold bg-[#F9FAFB] border border-gray-200 text-gray-700 hover:border-kicc-gold hover:bg-[#FFCD05]/5 transition-all">
                            {{ $se->sector?->name ?? $se->name }}
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Products Grid --}}
                @if($products->count() > 0)
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="h-px w-8 bg-kicc-gold"></div>
                        <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">Products</span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                        @foreach($products as $product)
                        @php $vid = is_array($product->videos) ? ($product->videos[0] ?? null) : $product->video_url; @endphp
                        <a href="{{ route('marketplace.show', $product->slug) }}" class="group bg-[#F9FAFB] rounded-2xl overflow-hidden border border-gray-200 hover:border-[#FFCD05]/30 transition-all card-hover">
                            <div class="aspect-square overflow-hidden bg-white relative">
                                @if($vid)
                                <video autoplay muted loop playsinline preload="auto" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                       onerror="this.remove()">
                                    <source src="{{ $vid }}" type="video/mp4">
                                </video>
                                @endif
                                @if(!$vid && ($product->images->first()->url ?? null))
                                <x-fast-image :src="$product->images->first()->url" :alt="$product->name" :width="640" :quality="75" class="w-full h-full" />
                                @elseif(!$vid)
                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                     onerror="this.src='{{ asset('storage/kicc/kicc-logo.png') }}'">
                                @endif
                            </div>
                            <div class="p-3">
                                <div class="text-[10px] font-bold text-kicc-gold uppercase tracking-widest">{{ $county->name }}</div>
                                <h3 class="font-bold text-gray-900 text-sm leading-snug mt-1">{{ $product->name }}</h3>
                                <div class="mt-1 font-black text-kicc-gold text-sm">KES {{ number_format($product->price ?? 0) }}</div>
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Institution Videos Library --}}
                @if(count($libraryVideos) > 0)
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="h-px w-8 bg-kicc-gold"></div>
                        <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">Video Gallery</span>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        @foreach($libraryVideos as $v)
                        <div class="aspect-video bg-black rounded-2xl overflow-hidden">
                            <video autoplay muted loop playsinline preload="metadata" class="w-full h-full object-cover">
                                <source src="{{ $v['path'] ?? $v['url'] ?? '' }}" type="video/mp4">
                            </video>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            {{-- Right: Quick Info Sidebar --}}
            <div class="space-y-4">
                <div class="bg-white border border-gray-200 rounded-2xl p-5 sticky-sidebar sticky top-24">
                    <h3 class="text-xs font-bold text-gray-900 uppercase tracking-widest mb-4">Quick Info</h3>
                    <div class="space-y-3 text-sm">
                        @if($institution->location)
                        <div class="flex items-start gap-2.5">
                            <span class="text-gray-400 mt-0.5"></span>
                            <span class="text-gray-700">{{ $institution->location }}</span>
                        </div>
                        @endif
                        @if($institution->phone)
                        <div class="flex items-center gap-2.5">
                            <span class="text-gray-400"></span>
                            <a href="tel:{{ $institution->phone }}" class="text-gray-700 hover:text-kicc-gold">{{ $institution->phone }}</a>
                        </div>
                        @endif
                        @if($institution->email)
                        <div class="flex items-center gap-2.5">
                            <span class="text-gray-400"></span>
                            <a href="mailto:{{ $institution->email }}" class="text-gray-700 hover:text-kicc-gold">{{ $institution->email }}</a>
                        </div>
                        @endif
                        @if($institution->website)
                        <div class="flex items-center gap-2.5">
                            <span class="text-gray-400"></span>
                            <a href="{{ $institution->website }}" target="_blank" class="text-gray-700 hover:text-kicc-gold truncate">{{ $institution->website }}</a>
                        </div>
                        @endif
                        @if($institution->headquarters)
                        <div class="flex items-center gap-2.5">
                            <span class="text-gray-400"></span>
                            <span class="text-gray-700">{{ $institution->headquarters }}</span>
                        </div>
                        @endif
                        @if($institution->founded_year)
                        <div class="flex items-center gap-2.5">
                            <span class="text-gray-400"></span>
                            <span class="text-gray-700">Founded {{ $institution->founded_year }}</span>
                        </div>
                        @endif
                    </div>

                    {{-- Map — accurate geographic pin --}}
                    @if($institution->lat && $institution->lng)
                    <div class="mt-5 pt-4 border-t border-gray-100">
                        <h4 class="text-xs font-bold text-gray-900 uppercase tracking-widest mb-2">Location Pin</h4>
                        <x-map-pin :entity="$institution" />
                    </div>
                    @endif

                    {{-- Official website CTA --}}
                    @if($institution->website)
                    <div class="mt-5 pt-4 border-t border-gray-100">
                        <a href="{{ $institution->website }}" target="_blank" rel="noopener"
                           class="w-full inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-4 text-sm h-10 rounded-xl bg-[#0B1E57] text-white hover:bg-[#16275f]">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
                            Visit Official Website
                        </a>
                    </div>
                    @endif

                    {{-- Book as Experience --}}
                    @if($institution->lat && $institution->lng)
                    <div class="mt-3">
                        <x-experience-booking-modal
                            destination-type="institution"
                            :destination-id="$institution->id"
                            destination-name="{{ $institution->name }}"
                            :destination-lat="$institution->lat"
                            :destination-lng="$institution->lng"
                            :county-id="$county->id"
                        />
                    </div>
                    @endif

                    <div class="mt-5 pt-4 border-t border-gray-100">
                        <a href="{{ route('counties.show', $county->slug) }}" class="w-full inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-4 text-sm h-10 rounded-xl bg-[#901C1E] text-gray-900 hover:bg-[#7b1618]">
                            Browse {{ $county->name }} County
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{--  REVIEWS  --}}
    <div class="max-w-7xl mx-auto px-5 pb-10">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-review-widget
                :reviews="$institutionReviews ?? collect([])"
                :average="$institutionReviewAvg ?? 0"
                :count="$institutionReviewCount ?? 0"
                :seed-source="$institutionReviewSeed?->sourceLabel()"
                :seed-url="$institutionReviewSeed?->external_url"
            />
            <x-review-form
                :reviewable-type="\App\Models\CountyInstitution::class"
                :reviewable-id="$institution->id"
            />
        </div>
    </div>

    @if(isset($tripRecommendations) && (!empty($tripRecommendations['places_to_visit']) || !empty($tripRecommendations['places_to_stay']) || !empty($tripRecommendations['transport'])))
    <div class="max-w-7xl mx-auto px-5 pb-16" x-data="correlationLoader('institution', {{ $institution->id }})">
        <div class="flex items-center gap-3 mb-6">
            <span class="h-px w-8 bg-kicc-gold"></span>
            <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">Plan Your Trip</span>
            <span class="text-gray-400 text-xs">from {{ $institution->name }}</span>
            <span class="h-px flex-1 bg-gray-200"></span>
        </div>

        @if(!empty($tripRecommendations['places_to_visit']))
        <div class="mb-8">
            <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2"><span> Places to Visit</span><span class="text-[10px] font-normal text-gray-400">nearby</span></h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($tripRecommendations['places_to_visit'] as $rec)
                <a href="{{ route('counties.institution', $rec['slug']) }}" class="group bg-white border border-gray-200 rounded-2xl overflow-hidden hover:border-[#FFCD05]/40 transition-all card-hover">
                    <div class="h-28 bg-gray-100 overflow-hidden relative">
                        @if($rec['image_url'])
                        <img src="{{ $rec['image_url'] }}" alt="{{ $rec['name'] }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition-transform"
                             onerror="this.style.display='none'">
                        @endif
                        <div class="absolute top-2 right-2 bg-white/90 backdrop-blur rounded-full px-2 py-0.5 text-[10px] font-bold text-gray-600 shadow">{{ $rec['type_emoji'] }} {{ $rec['type_label'] ?? '' }}</div>
                        @if($rec['distance_km'])
                        <div class="absolute bottom-2 left-2 bg-black/60 backdrop-blur rounded-full px-2 py-0.5 text-[10px] font-bold text-white shadow">{{ $rec['distance_km'] }} km</div>
                        @endif
                    </div>
                    <div class="p-3">
                        <h4 class="font-bold text-gray-900 text-sm line-clamp-1">{{ $rec['name'] }}</h4>
                        @if($rec['description'])<p class="text-gray-400 text-xs line-clamp-2 mt-1">{{ Str::limit($rec['description'], 80) }}</p>@endif
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        @if(!empty($tripRecommendations['places_to_stay']))
        <div class="mb-8">
            <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2"><span> Places to Stay</span></h3>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                @foreach($tripRecommendations['places_to_stay'] as $rec)
                <a href="{{ route('counties.institution', $rec['slug']) }}" class="group bg-white border border-gray-200 rounded-2xl overflow-hidden hover:border-[#FFCD05]/40 transition-all card-hover">
                    <div class="h-24 bg-gray-100 overflow-hidden relative">
                        @if($rec['image_url'])<img src="{{ $rec['image_url'] }}" alt="{{ $rec['name'] }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition-transform" onerror="this.style.display='none'">@endif
                        @if($rec['distance_km'])<div class="absolute bottom-2 left-2 bg-black/60 backdrop-blur rounded-full px-2 py-0.5 text-[10px] font-bold text-white shadow">{{ $rec['distance_km'] }} km</div>@endif
                    </div>
                    <div class="p-3"><h4 class="font-bold text-gray-900 text-sm line-clamp-1">{{ $rec['name'] }}</h4></div>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        @if(!empty($tripRecommendations['transport']))
        <div>
            <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2"><span> Transport Options</span></h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($tripRecommendations['transport'] as $t)
                <div class="bg-white border border-gray-200 rounded-2xl p-4 card-hover hover:border-[#FFCD05]/40 transition-all">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">{{ $t['type_emoji'] ?? '' }}</span>
                        <div>
                            <h4 class="font-bold text-gray-900 text-sm">{{ $t['name'] }}</h4>
                            @if($t['type_label'])<span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t['type_label'] }}</span>@endif
                        </div>
                    </div>
                    @if($t['price'])<div class="font-black text-kicc-gold text-sm mt-1">KES {{ number_format($t['price']) }}/{{ $t['unit'] ?? 'trip' }}</div>@endif
                    @if($t['booking_url'])<a href="{{ $t['booking_url'] }}" target="_blank" rel="noopener" class="w-full mt-2 text-center py-1.5 rounded-lg bg-[#0B1E57] text-white text-xs font-bold hover:bg-[#16275f] transition-all">Book Now</a>@endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="mt-4 text-center" x-show="!loaded && !loading" x-cloak>
            <button @click="loadMore()" class="inline-flex items-center gap-2 text-xs font-bold text-[#0B1E57] hover:text-[#901C1E] transition-colors">
                <span x-show="!loading">Load more recommendations</span>
                <span x-show="loading" class="flex items-center gap-2"><svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg> Loading...</span>
            </button>
        </div>
        <div x-show="loaded" x-cloak><div x-html="html"></div></div>
    </div>
    @endif
</div>

{{-- Institution pipeline earnings --}}
@if(($institutionEarnings ?? collect())->isNotEmpty())
<div class="max-w-7xl mx-auto px-5 pb-12">
    @include('components.institution-earnings', [
        'institutionName' => $institution->name ?? 'this Institution',
        'institutionEarnings' => $institutionEarnings,
    ])
</div>
@endif

@push('styles')
<style>
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
@push('scripts')
<script>
function institutionFallbackPlayer(config) {
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
        nextVideo() {
            this.currentIndex = (this.currentIndex + 1) % this.videos.length;
            var video = this.$refs.fallbackVideo;
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
@endsection