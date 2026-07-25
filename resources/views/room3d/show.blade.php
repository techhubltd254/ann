@extends('layouts.app')

@section('title', $room3d->title . ' - 3D Room - KICC')

@section('content')
<div class="max-w-4xl mx-auto px-5 py-8">
    <a href="{{ route('room3d.index') }}" class="text-kicc-gold hover:underline text-sm mb-4 inline-block">&larr; Back to Rooms</a>

    @if(session('success'))
    <div class="bg-emerald-500/15 border border-emerald-500/25 text-emerald-400 rounded-xl px-5 py-3 mb-6 text-sm" data-reveal>{{ session('success') }}</div>
    @endif

    <div class="bg-[#0D1220] rounded-2xl border border-white/8 overflow-hidden" data-reveal>
        @if($room3d->coverUrl())
        <div class="aspect-video bg-[#141B2E]">
            <img src="{{ $room3d->coverUrl() }}" alt="{{ $room3d->title }}" class="w-full h-full object-cover">
        </div>
        @endif
        <div class="p-6">
            <h1 class="text-2xl font-black text-white">{{ $room3d->title }}</h1>
            @if($room3d->description)
            <p class="text-white/45 mt-2 text-sm leading-relaxed">{{ $room3d->description }}</p>
            @endif

            <div class="flex flex-wrap gap-4 mt-4 text-xs text-white/35">
                <span>{{ count($room3d->images()) }} photos</span>
                <span>Pipeline: {{ str_replace('_', ' ', $room3d->pipeline) }}</span>
                <span class="capitalize">Status: {{ $room3d->status }}</span>
            </div>

            @if($room3d->isReady())
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('room3d.viewer', $room3d) }}" data-magnetic
                   class="inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-6 text-sm h-11 rounded-xl bg-kicc-gold text-[#07090F] hover:bg-[#e6b904]">
                    🔍 Open 3D Viewer
                </a>
                <a href="{{ route('room3d.viewer', $room3d) }}?mode=gyro" data-magnetic
                   class="inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-6 text-sm h-11 rounded-xl border border-white/25 text-white hover:bg-white/10">
                    📱 Phone Gyro Mode
                </a>
            </div>
            @endif

            <div class="mt-6">
                <h3 class="font-bold text-white text-sm mb-3">Photos ({{ count($room3d->images()) }})</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                    @foreach($room3d->images() as $image)
                    <div class="aspect-square bg-[#141B2E] rounded-lg overflow-hidden border border-white/8">
                        <img src="{{ url('storage/' . $image) }}" alt="" class="w-full h-full object-cover hover:scale-105 transition-transform duration-500">
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
