@extends('layouts.app')

@section('title', 'National Economic Sectors — KICC')
@section('description', 'All economic sectors across Kenya\'s 47 counties — aggregated entity counts per sector.')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="text-center mb-12">
        <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">National Economic Sectors</h1>
        <p class="mt-3 text-gray-500 max-w-2xl mx-auto">
            Aggregated across all 47 counties — {{ $sectors->count() }} active sectors with {{ $sectors->sum('count') }} registered entities.
        </p>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
        @foreach($sectors as $s)
        <a href="{{ route('national.sector.show', $s['slug']) }}"
           class="group bg-white border border-gray-200 hover:border-kicc-gold/40 rounded-2xl overflow-hidden transition-all block card-hover">
            <div class="aspect-[4/3] bg-gradient-to-br from-[#0A1024] to-[#1a1a2e] flex items-center justify-center relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/10 to-black/30 pointer-events-none"></div>
                @if($s['emoji'])
                <span class="text-5xl relative z-10">{{ $s['emoji'] }}</span>
                @else
                <div class="w-16 h-16 rounded-2xl bg-kicc-gold/20 flex items-center justify-center relative z-10">
                    <svg class="w-8 h-8 text-kicc-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
                @endif
            </div>
            <div class="p-4 text-center min-h-[80px] flex flex-col justify-center">
                <div class="font-bold text-gray-900 text-sm leading-snug">{{ $s['name'] }}</div>
                <div class="text-gray-400 text-xs mt-1">{{ $s['count'] }} {{ Str::plural('entity', $s['count']) }}</div>
            </div>
        </a>
        @endforeach
    </div>
</div>
@endsection