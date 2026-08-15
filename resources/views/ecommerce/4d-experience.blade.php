@extends('layouts.app')
@section('title', '4D Experience — Murang\'a County')
@section('content')
<div class="pt-20 max-w-7xl mx-auto px-5 py-10">
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-3">
            <div class="h-px w-8 bg-[#FFCD05]"></div>
            <span class="text-[#FFCD05] text-xs font-bold tracking-[0.2em] uppercase">4D Immersive</span>
        </div>
        <h1 class="text-3xl md:text-5xl font-black text-gray-900 leading-tight" data-split>Murang'a<br><span class="text-[#046bd2]">In 4 Dimensions</span></h1>
        <p class="text-gray-400 mt-3 max-w-2xl">Photorealistic 4D Gaussian Splat reconstructions from real Murang'a footage. Click to explore each location.</p>
    </div>

    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        @php
        $videos = [
            ['title' => 'Sagana Rafting', 'subtitle' => 'Havila Rafting — Sagana River', 'file' => 'rafting_cinematic.mp4', 'splat' => 'rafting.splat', 'sector' => 'tourism'],
            ['title' => 'Havila Resort', 'subtitle' => 'Resort & Conference Centre', 'file' => null, 'splat' => 'resort.splat', 'sector' => 'hotels'],
            ['title' => 'Mugumo-ini Falls', 'subtitle' => 'Twin Falls — Swinging Bridge', 'file' => 'falls_orbit.mp4', 'splat' => 'falls_splat.splat', 'sector' => 'tourism'],
            ['title' => 'Mukurwe wa Nyagathanga', 'subtitle' => 'Kikuyu Origin Site — Walkthrough', 'file' => 'mukurwe_s1_walk.mp4', 'splat' => 'mukurwe_s1.splat', 'sector' => 'culture'],
            ['title' => 'Muranga University', 'subtitle' => 'University Drone Panorama', 'file' => 'university_drone_orbit.mp4', 'splat' => 'university_drone.splat', 'sector' => 'education'],
            ['title' => 'Muranga Gorges', 'subtitle' => 'Gorges & Canyon Drone', 'file' => 'gorges_drone_orbit.mp4', 'splat' => 'gorges_drone.splat', 'sector' => 'tourism'],
            ['title' => 'Muranga Hospital', 'subtitle' => 'County Referral Hospital', 'file' => 'hospital_orig_cinematic.mp4', 'splat' => null, 'sector' => 'health'],
            ['title' => 'Aberdare Forest', 'subtitle' => 'Forest Canopy Trail', 'file' => null, 'splat' => 'forest_splat.splat', 'sector' => 'tourism'],
            ['title' => 'Falls Walkthrough', 'subtitle' => 'Mugumo-ini Falls Walk', 'file' => 'falls_walk.mp4', 'splat' => null, 'sector' => 'tourism'],
            ['title' => 'Mukurwe Angle 2', 'subtitle' => 'Mukurwe wa Nyagathanga — Side View', 'file' => 'mukurwe_s2_walk.mp4', 'splat' => 'mukurwe_s2.splat', 'sector' => 'culture'],
            ['title' => 'Mukurwe Angle 3', 'subtitle' => 'Mukurwe wa Nyagathanga — Panorama', 'file' => 'mukurwe_s3_walk.mp4', 'splat' => 'mukurwe_s3.splat', 'sector' => 'culture'],
            ['title' => 'Muranga School', 'subtitle' => 'Mukurwe Primary School', 'file' => 'school_orig_cinematic.mp4', 'splat' => null, 'sector' => 'education'],
        ];
        @endphp

        @foreach($videos as $v)
        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden card-hover group">
            <div class="aspect-video bg-gray-900 relative overflow-hidden">
                @php
                $mp4 = \Illuminate\Support\Facades\Storage::disk('public')->exists('kicc/4d/' . $v['file']);
                $splat = \Illuminate\Support\Facades\Storage::disk('public')->exists('kicc/4d/' . $v['splat']);
                @endphp
                @if($mp4)
                <video class="w-full h-full object-cover" muted loop playsinline
                    @mouseenter="this.play()" @mouseleave="this.pause(); this.currentTime=0"
                    poster="{{ media('kicc/4d/' . str_replace('.mp4', '.jpg', $v['file'])) }}">
                    <source src="{{ media('kicc/4d/' . $v['file']) }}" type="video/mp4">
                </video>
                @else
                <div class="w-full h-full bg-gradient-to-br from-[#046bd2]/20 to-[#045cb4]/5 flex items-center justify-center">
                    <span class="text-5xl opacity-30">🎯</span>
                </div>
                @endif
                <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
                <div class="absolute bottom-3 left-3 right-3">
                    <div class="text-white font-bold text-sm">{{ $v['title'] }}</div>
                    <div class="text-white/60 text-xs">{{ $v['subtitle'] }}</div>
                </div>
                @if($splat)
                <div class="absolute top-3 right-3">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-black/40 text-white/80 backdrop-blur-sm">3D</span>
                </div>
                @endif
            </div>
            <div class="p-4 flex items-center justify-between">
                <span class="text-xs text-gray-400">{{ ucfirst($v['sector']) }} Sector</span>
                <a href="{{ route('counties.sector', ['county' => 'muranga', 'sector' => $v['sector']]) }}" class="text-xs font-bold text-[#046bd2] hover:underline">View Sector →</a>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection