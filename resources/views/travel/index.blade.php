@extends('layouts.app')

@section('title', 'Travel & Tourism — Kenya')
@section('description', 'Discover Kenya\'s top attractions, hotels, and destinations across 47 counties.')

@section('content')
<div class="relative bg-charcoal overflow-hidden">
    <div class="absolute inset-0">
        <img src="{{ media('kicc/tower-night.jpg') }}" alt="" class="w-full h-full object-cover object-top opacity-20">
    </div>
    <div class="absolute inset-0 bg-gradient-to-b from-charcoal/60 via-charcoal/80 to-charcoal"></div>
    <div class="relative max-w-7xl mx-auto px-6 lg:px-8 py-16">
        <div class="flex items-center gap-3 mb-4">
            <span class="h-px w-10 bg-gold-400"></span>
            <span class="text-gold-400 text-xs font-semibold uppercase tracking-[0.25em]">Travel & Tourism</span>
        </div>
        <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight text-white mb-3">Explore Kenya</h1>
        <p class="text-lg text-gray-300 max-w-2xl">From the Maasai Mara to the coast — discover Kenya's world-class destinations, accommodation and attractions.</p>
    </div>
</div>

<div class="max-w-7xl mx-auto px-6 lg:px-8 py-12">
    {{-- Attractions --}}
    <div class="mb-16">
        <div class="flex items-center justify-between mb-6">
            <div>
                <div class="text-amber-600 text-xs font-semibold uppercase tracking-[0.2em] mb-1">Destinations</div>
                <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Top Attractions</h2>
            </div>
            <div class="flex gap-2">
                @foreach($counties->take(6) as $c)
                <a href="{{ route('counties.show', $c->slug) }}" class="text-xs px-3 py-1.5 rounded-full bg-gray-100 text-gray-600 hover:bg-amber-50 hover:text-amber-600 transition-all font-medium">{{ $c->name }}</a>
                @endforeach
            </div>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            @forelse($attractions as $a)
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-lg transition-all group">
                <div class="h-36 bg-gradient-to-br from-amber-600 to-amber-800 flex items-center justify-center overflow-hidden">
                    <span class="text-white/60 text-5xl opacity-30 group-hover:scale-125 transition-transform duration-500">📍</span>
                </div>
                <div class="p-4">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-[11px] font-medium text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full">{{ $a->type }}</span>
                        @if($a->county)
                        <span class="text-[11px] text-gray-400">{{ $a->county->name }}</span>
                        @endif
                    </div>
                    <h3 class="font-bold text-gray-900">{{ $a->name }}</h3>
                </div>
            </div>
            @empty
            <div class="col-span-3 text-center py-10 text-gray-400">Attractions loading...</div>
            @endforelse
        </div>
    </div>

    {{-- Hotels --}}
    <div>
        <div class="mb-6">
            <div class="text-amber-600 text-xs font-semibold uppercase tracking-[0.2em] mb-1">Accommodation</div>
            <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Places to Stay</h2>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
            @forelse($hotels as $h)
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:shadow-lg hover:border-amber-200 transition-all">
                <div class="flex items-center gap-1 mb-2">
                    @for($i = 0; $i < ($h->star_rating ?? 3); $i++)
                    <svg class="w-4 h-4 text-gold-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    @endfor
                </div>
                <h3 class="font-bold text-gray-900 text-sm">{{ $h->name }}</h3>
                <p class="text-xs text-gray-500 mt-1">{{ $h->city }}, {{ $h->county?->name ?? '' }}</p>
            </div>
            @empty
            <div class="col-span-5 text-center py-10 text-gray-400">Hotels loading...</div>
            @endforelse
        </div>
    </div>
</div>
@endSection