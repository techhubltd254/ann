@extends('layouts.app')

@section('title', 'Digital Screens — KICC Advertising')
@section('description', 'Advertise on 18 premium digital screens across KICC and Nairobi.')

@section('content')
<div class="pt-20">
    <div class="bg-[#0D1220] border-b border-white/8 py-12">
        <div class="max-w-7xl mx-auto px-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="h-px w-8 bg-kicc-gold"></div>
                <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">Digital Screens</span>
            </div>
            <h1 class="text-4xl md:text-5xl font-black text-white tracking-tight">Advertise on <span class="text-kicc-gold">Kenya's Most</span> Iconic Screens</h1>
            <p class="text-white/50 mt-3 text-base max-w-xl">18 premium screens reaching millions daily — book your slot now.</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-5 py-10">
        @forelse($screens ?? [] as $screen)
        <div class="bg-[#0D1220] rounded-2xl border border-white/8 overflow-hidden mb-4 hover:border-[#FFCD05]/30 transition-all group">
            <div class="flex flex-col md:flex-row">
                <div class="md:w-64 h-44 bg-[#141B2E] flex items-center justify-center relative overflow-hidden shrink-0">
                    @php $videoPath = media('screens/auto_' . $screen->id . '.mp4'); @endphp
                    <video src="{{ $videoPath }}" class="w-full h-full object-cover opacity-70 group-hover:opacity-100 transition-opacity" muted loop
                           onmouseover="this.play()" onmouseout="this.pause()" onerror="this.outerHTML='<div class=\'flex items-center justify-center w-full h-full\'><svg class=\'w-10 h-10 text-white/20\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z\'/><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M21 12a9 9 0 11-18 0 9 9 0 0118 0z\'/></svg></div>'"></video>
                    <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                        <div class="w-12 h-12 bg-[#901C1E]/80 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/></svg>
                        </div>
                    </div>
                </div>
                <div class="p-5 flex-1 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-[10px] font-bold text-kicc-gold uppercase tracking-widest">{{ $screen->location ?? 'KICC' }}</span>
                            <span class="text-xs text-white/30">{{ $screen->id }}</span>
                        </div>
                        <h3 class="font-black text-white text-lg">{{ $screen->label }}</h3>
                        @if($screen->description)
                        <p class="text-white/40 text-sm mt-1">{{ $screen->description }}</p>
                        @endif
                        <div class="flex items-center gap-4 mt-2 text-xs text-white/30">
                            @if($screen->video_exists)
                            <span class="text-emerald-400">● Video ready ({{ $screen->video_size_mb }} MB)</span>
                            @else
                            <span class="text-white/30">○ Video pending</span>
                            @endif
                            @if(isset($screen->image_count))
                            <span>{{ $screen->image_count }} images</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex gap-2 mt-4">
                        <a href="{{ route('screens.show', $screen->id) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-kicc-gold text-[#07090F] text-xs font-bold hover:bg-[#e6b904] transition-colors">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/></svg>
                            Play Showcase
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @empty
        @for($i = 0; $i < 4; $i++)
        <div class="bg-[#0D1220] rounded-2xl border border-white/8 overflow-hidden mb-4">
            <div class="flex flex-col md:flex-row">
                <div class="md:w-64 h-44 bg-[#141B2E] flex items-center justify-center shrink-0">
                    <svg class="w-10 h-10 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="p-5 flex-1">
                    <span class="text-[10px] font-bold text-kicc-gold uppercase tracking-widest">Screen {{ $i + 1 }}</span>
                    <h3 class="font-black text-white text-lg">Display Screen {{ $i + 1 }}</h3>
                    <p class="text-white/40 text-sm mt-1">Premium digital screen at KICC venue.</p>
                </div>
            </div>
        </div>
        @endfor
        @endforelse
    </div>
</div>
@endSection