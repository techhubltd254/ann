@extends('layouts.app')

@section('title', '3D Room Explorer - KICC')

@section('content')
<div class="max-w-7xl mx-auto px-5 py-8">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8" data-reveal>
        <div>
            <div class="flex items-center gap-3 mb-3">
                <div class="h-px w-8 bg-kicc-gold"></div>
                <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">Immersive 3D</span>
            </div>
            <h1 class="text-3xl md:text-4xl font-black text-white">3D Room <span class="text-kicc-gold">Explorer</span></h1>
            <p class="text-white/40 mt-2 text-sm max-w-xl leading-relaxed">Upload photos of any room and explore it in interactive 3D — tilt your phone to look around.</p>
        </div>
        <a href="{{ route('room3d.create') }}" data-magnetic
           class="inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-6 text-sm h-11 rounded-xl bg-kicc-gold text-[#07090F] hover:bg-[#e6b904] shrink-0">
            + New Room
        </a>
    </div>

    @if(session('success'))
    <div class="bg-emerald-500/15 border border-emerald-500/25 text-emerald-400 rounded-xl px-5 py-3 mb-6 text-sm" data-reveal>{{ session('success') }}</div>
    @endif

    @if($rooms->isEmpty())
    <div class="text-center py-20" data-reveal>
        <div class="text-6xl mb-4">🏗️</div>
        <h2 class="text-xl font-semibold text-white mb-2">No rooms yet</h2>
        <p class="text-white/40 mb-6 text-sm">Upload photos of a room to create your first 3D experience</p>
        <a href="{{ route('room3d.create') }}" data-magnetic
           class="inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-8 text-base h-12 rounded-xl bg-kicc-gold text-[#07090F] hover:bg-[#e6b904]">
            Create Your First Room
        </a>
    </div>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach($rooms as $i => $room)
        <div class="bg-[#0D1220] rounded-2xl border border-white/8 overflow-hidden card-hover" data-tilt="6" data-reveal data-reveal-delay="{{ ($i % 3) * 80 }}">
            <div class="tilt-glare"></div>
            <div class="aspect-video bg-[#141B2E] relative overflow-hidden">
                @if($room->coverUrl())
                <img src="{{ $room->coverUrl() }}" alt="{{ $room->title }}" class="w-full h-full object-cover">
                @else
                <div class="flex items-center justify-center h-full text-white/20 text-4xl">🏠</div>
                @endif
                <div class="absolute top-2 right-2">
                    <span class="px-2 py-1 text-[10px] font-bold rounded-full
                        @if($room->status === 'ready') bg-emerald-500/20 text-emerald-400
                        @elseif($room->status === 'processing') bg-yellow-500/20 text-yellow-400
                        @elseif($room->status === 'failed') bg-[#901C1E]/20 text-[#e86f71]
                        @else bg-white/10 text-white/50 @endif">
                        {{ ucfirst($room->status) }}
                    </span>
                </div>
            </div>
            <div class="p-4">
                <h3 class="font-bold text-white truncate">{{ $room->title }}</h3>
                <p class="text-xs text-white/40 mt-1">{{ count($room->images()) }} photos</p>
                <div class="flex gap-2 mt-3">
                    @if($room->isReady())
                    <a href="{{ route('room3d.viewer', $room) }}"
                       class="flex-1 text-center px-3 py-2 bg-kicc-gold text-[#07090F] rounded-lg text-xs font-bold hover:bg-[#e6b904] transition-colors">
                        View in 3D
                    </a>
                    @endif
                    <a href="{{ route('room3d.show', $room) }}"
                       class="flex-1 text-center px-3 py-2 bg-white/8 text-white/70 rounded-lg text-xs font-bold hover:bg-white/15 transition-colors">
                        Details
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    <div class="mt-8">{{ $rooms->links() }}</div>
    @endif
</div>
@endsection
