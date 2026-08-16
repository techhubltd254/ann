@extends('layouts.app')
@section('title', '4D Experience — Murang\'a County')
@section('content')
<div class="pt-20 max-w-7xl mx-auto px-5 py-10">
    <div class="mb-10">
        <div class="flex items-center gap-3 mb-3">
            <div class="h-px w-8 bg-[#FFCD05]"></div>
            <span class="text-[#FFCD05] text-xs font-bold tracking-[0.2em] uppercase">4D Immersive</span>
        </div>
        <h1 class="text-3xl md:text-5xl font-black text-gray-900 leading-tight" data-split>Murang'a<br><span class="text-[#046bd2]">In 4D</span></h1>
        <p class="text-gray-400 mt-3 max-w-2xl">Photorealistic 4D reconstructions from real Murang'a footage. Hover to play.</p>
    </div>

    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
        @php
        $videos = [
            ['title' => 'Drone Aerials', 'subtitle' => 'Murang\'a from above — drone footage', 'file' => 'clips/drone_aerial.mp4', 'sector' => 'tourism'],
            ['title' => 'Mukurwe wa Nyagathanga', 'subtitle' => 'Kikuyu origin site — cultural heritage', 'file' => 'clips/mukurwe_culture.mp4', 'sector' => 'culture'],
            ['title' => 'Gorges Canyon', 'subtitle' => 'Murang\'a gorges — natural beauty', 'file' => 'clips/gorges_canyon.mp4', 'sector' => 'tourism'],
            ['title' => 'Scenic Panorama', 'subtitle' => 'Wide view of Murang\'a landscape', 'file' => 'clips/scenic_wide.mp4', 'sector' => 'tourism'],
            ['title' => 'Drone Wide Shot', 'subtitle' => 'Aerial panoramic view', 'file' => 'clips/drone_wide.mp4', 'sector' => 'tourism'],
            ['title' => 'Gorges Wide', 'subtitle' => 'Canyon wide angle', 'file' => 'clips/gorges_wide.mp4', 'sector' => 'tourism'],
        ];
        @endphp

        @foreach($videos as $v)
        @php $exists = \Illuminate\Support\Facades\Storage::disk('public')->exists('kicc/4d/' . $v['file']); @endphp
        @if($exists)
        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden group card-hover">
            <div class="aspect-video bg-gray-900 relative overflow-hidden">
                <video class="w-full h-full object-cover" muted loop playsinline
                    @mouseenter="this.play()" @mouseleave="this.pause();this.currentTime=0"
                    poster="{{ media('kicc/4d/' . str_replace('.mp4', '.jpg', $v['file'])) }}">
                    <source src="{{ media('kicc/4d/' . $v['file']) }}" type="video/mp4">
                </video>
                <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent pointer-events-none"></div>
                <div class="absolute bottom-3 left-3 right-3 pointer-events-none">
                    <div class="text-white font-bold text-sm drop-shadow-lg">{{ $v['title'] }}</div>
                    <div class="text-white/60 text-xs">{{ $v['subtitle'] }}</div>
                </div>
                <div class="absolute top-3 right-3 px-2 py-1 rounded-full bg-black/40 backdrop-blur text-white/80 text-[10px] font-bold pointer-events-none">4D</div>
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity">
                    <div class="w-14 h-14 rounded-full bg-white/20 backdrop-blur flex items-center justify-center">
                        <svg class="w-7 h-7 text-white ml-1" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    </div>
                </div>
            </div>
            <div class="p-4 flex items-center justify-between">
                <span class="text-xs text-gray-400">{{ ucfirst($v['sector']) }} Sector</span>
                <a href="{{ route('counties.sector', ['county' => 'muranga', 'sector' => $v['sector']]) }}" class="text-xs font-bold text-[#046bd2] hover:underline">Explore →</a>
            </div>
        </div>
        @endif
        @endforeach
    </div>
</div>
@endsection