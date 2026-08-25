@extends('layouts.app')

@section('title', $institution->name . ' — ' . $county->name)
@section('description', $institution->description ?? $institution->story ?? $institution->name)

@section('content')
<div class="pt-20">

    {{-- ═══ HERO VIDEO SECTION ═══ --}}
    @if($heroVideo)
    <div class="relative h-[50vh] md:h-[60vh] overflow-hidden bg-black">
        <video autoplay muted loop playsinline preload="metadata" class="absolute inset-0 w-full h-full object-cover">
            <source src="{{ $heroVideo }}" type="video/mp4">
        </video>
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

    {{-- ═══ INSTITUTION INFO + PRODUCTS ═══ --}}
    <div class="max-w-7xl mx-auto px-5 py-10">
        <div class="grid lg:grid-cols-3 gap-8">
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
                        @php $vid = $product->videos[0] ?? $product->video_url; @endphp
                        <a href="{{ route('marketplace.show', $product->slug) }}" class="group bg-[#F9FAFB] rounded-2xl overflow-hidden border border-gray-200 hover:border-[#FFCD05]/30 transition-all card-hover">
                            <div class="aspect-square overflow-hidden bg-white relative">
                                @if($vid)
                                <video autoplay muted loop playsinline preload="metadata" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                       onerror="this.style.display='none'">
                                    <source src="{{ $vid }}" type="video/mp4">
                                </video>
                                @endif
                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 {{ $vid ? 'opacity-0' : '' }}"
                                     onerror="this.src='{{ asset('storage/kicc/kicc-logo.png') }}'">
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
                <div class="bg-white border border-gray-200 rounded-2xl p-5 sticky top-24">
                    <h3 class="text-xs font-bold text-gray-900 uppercase tracking-widest mb-4">Quick Info</h3>
                    <div class="space-y-3 text-sm">
                        @if($institution->location)
                        <div class="flex items-start gap-2.5">
                            <span class="text-gray-400 mt-0.5">📍</span>
                            <span class="text-gray-700">{{ $institution->location }}</span>
                        </div>
                        @endif
                        @if($institution->phone)
                        <div class="flex items-center gap-2.5">
                            <span class="text-gray-400">📞</span>
                            <a href="tel:{{ $institution->phone }}" class="text-gray-700 hover:text-kicc-gold">{{ $institution->phone }}</a>
                        </div>
                        @endif
                        @if($institution->email)
                        <div class="flex items-center gap-2.5">
                            <span class="text-gray-400">✉</span>
                            <a href="mailto:{{ $institution->email }}" class="text-gray-700 hover:text-kicc-gold">{{ $institution->email }}</a>
                        </div>
                        @endif
                        @if($institution->website)
                        <div class="flex items-center gap-2.5">
                            <span class="text-gray-400">🌐</span>
                            <a href="{{ $institution->website }}" target="_blank" class="text-gray-700 hover:text-kicc-gold truncate">{{ $institution->website }}</a>
                        </div>
                        @endif
                        @if($institution->headquarters)
                        <div class="flex items-center gap-2.5">
                            <span class="text-gray-400">🏢</span>
                            <span class="text-gray-700">{{ $institution->headquarters }}</span>
                        </div>
                        @endif
                        @if($institution->founded_year)
                        <div class="flex items-center gap-2.5">
                            <span class="text-gray-400">📅</span>
                            <span class="text-gray-700">Founded {{ $institution->founded_year }}</span>
                        </div>
                        @endif
                    </div>

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
</div>
@endsection