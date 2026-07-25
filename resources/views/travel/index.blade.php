@extends('layouts.app')

@section('title', 'Travel & Tourism — Kenya')
@section('description', 'Discover Kenya\'s top attractions, hotels, and destinations across 47 counties.')

@section('content')
<div class="pt-20">
    <div class="bg-[#0D1220] border-b border-white/8 py-12">
        <div class="max-w-7xl mx-auto px-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="h-px w-8 bg-kicc-gold"></div>
                <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">Travel & Tourism</span>
            </div>
            <h1 class="text-4xl md:text-5xl font-black text-white tracking-tight">Discover <span class="text-kicc-gold">Kenya</span></h1>
            <p class="text-white/50 mt-3 text-base max-w-xl">From the Maasai Mara to the coast — explore Kenya's world-class destinations.</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-5 py-10">
        {{-- Attractions --}}
        <div class="mb-16">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="h-px w-8 bg-kicc-gold"></div>
                        <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">Destinations</span>
                    </div>
                    <h2 class="text-2xl font-black text-white">Top Attractions</h2>
                </div>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                @forelse($attractions as $a)
                <div class="bg-[#0D1220] rounded-2xl overflow-hidden border border-white/8 hover:border-kicc-gold/40 transition-all group">
                    <div class="h-32 bg-[#141B2E] flex items-center justify-center overflow-hidden">
                        <span class="text-4xl text-white/30">{{ $a->name[0] }}</span>
                    </div>
                    <div class="p-4">
                        <span class="text-[10px] font-bold text-kicc-gold uppercase tracking-widest">{{ $a->type }}</span>
                        <h3 class="font-bold text-white text-sm mt-1 leading-snug">{{ $a->name }}</h3>
                        <p class="text-white/35 text-xs mt-1">{{ $a->city }}</p>
                    </div>
                </div>
                @empty
                <div class="col-span-6 text-center py-12 text-white/30">Attractions loading...</div>
                @endforelse
            </div>
        </div>

        {{-- Hotels --}}
        <div>
            <div class="flex items-center gap-3 mb-6">
                <div class="h-px w-8 bg-kicc-gold"></div>
                <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">Accommodation</span>
            </div>
            <h2 class="text-2xl font-black text-white mb-6">Places to Stay</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                @forelse($hotels as $h)
                <div class="bg-[#141B2E] rounded-2xl border border-white/8 p-5 hover:border-kicc-gold/30 transition-all">
                    <div class="flex items-center gap-1 mb-2">
                        @for($i = 0; $i < ($h->star_rating ?? 3); $i++)
                        <svg class="w-4 h-4 text-kicc-gold" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @endfor
                    </div>
                    <h3 class="font-bold text-white text-sm">{{ $h->name }}</h3>
                    <p class="text-white/35 text-xs mt-1">{{ $h->city }}, {{ $h->county?->name ?? '' }}</p>
                </div>
                @empty
                <div class="col-span-5 text-center py-12 text-white/30">Hotels loading...</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endSection