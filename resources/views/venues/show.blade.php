@extends('layouts.app')

@section('title', $venue->name . ' — KICC Venues')
@section('description', Str::limit($venue->description ?? '', 160))

@section('content')
<div class="pt-20 max-w-7xl mx-auto px-5 py-10">
    <a href="{{ route('venues.index') }}" class="inline-flex items-center gap-1.5 text-white/40 hover:text-white text-sm mb-6 transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        All Venues
    </a>

    <div class="grid lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
            <div class="rounded-2xl overflow-hidden h-80 bg-[#141B2E]">
                @php $img = media("kicc/{$venue->slug}.jpg"); @endphp
                <img src="{{ $img }}" alt="{{ $venue->name }}" class="w-full h-full object-cover"
                     onerror="this.style.display='none';this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center text-6xl font-black text-white/20\'>{{ $venue->name[0] }}</div>'">
            </div>
            <div class="mt-8">
                <h1 class="text-3xl font-black text-white">{{ $venue->name }}</h1>
                <div class="flex flex-wrap items-center gap-3 mt-3">
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold tracking-wide border bg-[#FFCD05]/15 text-[#FFCD05] border-[#FFCD05]/30 capitalize">{{ $venue->venue_type }}</span>
                    @if($venue->capacity)
                    <span class="text-sm text-white/60">{{ $venue->capacity }} capacity</span>
                    @endif
                    @if($venue->city)
                    <span class="flex items-center gap-1.5 text-sm text-white/40">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ $venue->city }}, Kenya
                    </span>
                    @endif
                </div>
                @if($venue->description)
                <p class="mt-5 text-white/50 leading-relaxed text-sm">{{ $venue->description }}</p>
                @endif
            </div>
        </div>
        <div class="lg:col-span-1">
            <div class="bg-[#0D1220] border border-white/10 rounded-2xl p-6 sticky top-24">
                <div class="font-black text-kicc-gold text-xl">{{ $venue->venue_type }}</div>
                <div class="text-white/35 text-sm">Venue</div>

                <div class="mt-6 space-y-3">
                    <a href="#" class="w-full inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-8 text-base h-14 rounded-xl bg-[#901C1E] text-white hover:bg-[#7b1618]">Request Booking</a>
                    <a href="{{ route('venues.index') }}" class="w-full inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-4 text-xs h-11 rounded-xl border border-white/25 text-white hover:bg-white/10">Explore Other Venues</a>
                </div>

                @if($venue->amenities)
                @php $amenities = json_decode($venue->amenities, true) ?? []; @endphp
                @if(count($amenities) > 0)
                <div class="mt-6 pt-5 border-t border-white/8">
                    <h4 class="text-xs font-bold text-white/40 uppercase tracking-wider mb-3">Amenities</h4>
                    <div class="flex flex-wrap gap-2">
                        @foreach($amenities as $a)
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold tracking-wide border bg-white/5 text-white/60 border-white/10">{{ $a }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
                @endif

                <div class="mt-6 pt-5 border-t border-white/8 space-y-3">
                    @if($venue->city)<div class="flex items-center gap-3 text-sm text-white/40"><svg class="w-3.5 h-3.5 text-kicc-gold shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg><span>{{ $venue->city }}, Kenya</span></div>@endif
                </div>
            </div>
        </div>
    </div>
</div>
@endSection