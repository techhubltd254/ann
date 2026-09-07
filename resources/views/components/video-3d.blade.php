@props([
    'asset' => null,   // MediaAsset model
    'videoUrl' => null, // fallback if no asset
    'poster' => null,
    'mode' => 'parallax', // parallax, sphere, card, float, flat
    'aspect' => 'aspect-video',
    'class' => '',
])

@php
    // Resolve URLs from MediaAsset
    if ($asset) {
        $videoUrl = $asset->mp4Url() ?? $asset->url();
        $depthMapUrl = $asset->depthMapUrl();
        $poster = $poster ?? $asset->posterUrl();
        $mode = $asset->display_mode ?? $mode;
    }
    $depthMapUrl = $depthMapUrl ?? null;
    $isFlat = $mode === 'flat' || !$videoUrl;
@endphp

@if($isFlat)
{{-- Standard 2D video fallback --}}
<div class="relative {{ $aspect }} bg-black overflow-hidden rounded-xl {{ $class }}">
    <video controls autoplay muted loop playsinline preload="metadata"
           poster="{{ $poster ?? '' }}"
           class="absolute inset-0 w-full h-full object-cover">
        @if($videoUrl)
        <source src="{{ $videoUrl }}" type="video/mp4">
        @endif
    </video>
    @if($poster)
    <img src="{{ $poster }}" alt="" loading="lazy"
         class="absolute inset-0 w-full h-full object-cover"
         onerror="this.style.display='none'">
    @endif
</div>
@else
{{-- Three.js 3D video player --}}
<div class="three-video-container relative {{ $aspect }} overflow-hidden rounded-xl {{ $class }}"
     data-video="{{ $videoUrl }}"
     data-depth="{{ $depthMapUrl }}"
     data-mode="{{ $mode }}"
     data-poster="{{ $poster ?? '' }}"
     style="background: transparent;">
</div>
@endif