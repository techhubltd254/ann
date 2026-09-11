@props([
    'poster' => null,
    'hoverLoop' => null,
    'videoUrl' => null,
    'title' => '',
    'subtitle' => '',
    'is4d' => false,
    'splatUrl' => null,
    'category' => null,
    'badge' => null,
    'href' => '#',
    'aspect' => 'aspect-video',
])

<div class="group relative {{ $aspect }} bg-[#0B1E57] overflow-hidden rounded-xl"
     x-data="mediaTile()"
     @mouseenter="onHoverEnter()"
     @mouseleave="onHoverLeave()">

    {{-- Poster: visible until video actually starts playing --}}
    @if($poster)
    <img src="{{ $poster }}" alt="{{ $title }}" loading="lazy"
         class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300"
         :class="videoReady ? 'opacity-0' : 'opacity-100'"
         onerror="this.style.display='none'">
    @endif

    {{-- Video: plays on hover, pauses on leave --}}
    <video x-ref="video"
           class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300"
           :class="videoReady ? 'opacity-100' : 'opacity-0'"
           muted loop playsinline preload="auto"
           x-on:playing="onVideoPlaying()">
        @if($hoverLoop)
        <source src="{{ $hoverLoop }}" type="video/mp4">
        @elseif($videoUrl)
        <source src="{{ $videoUrl }}" type="video/mp4">
        @endif
    </video>

    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent pointer-events-none"></div>

    @if($category)
    <span class="absolute top-2.5 left-2.5 text-[10px] font-bold px-2.5 py-1 rounded-full bg-black/45 text-white/90 backdrop-blur-sm capitalize z-10">{{ $category }}</span>
    @endif

    @if($badge)
    <span class="absolute top-2.5 right-2.5 text-[10px] font-bold px-2.5 py-1 rounded-full bg-[#FFCD05] text-black z-10">{{ $badge }}</span>
    @endif

    @if($is4d && $splatUrl)
    <span class="absolute bottom-2.5 right-2.5 flex items-center gap-1 bg-black/60 backdrop-blur-md px-2 py-0.5 rounded-full border border-cyan-500/30 text-[10px] text-cyan-300 font-mono z-10">
        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l8 4v12l-8 4-8-4V6l8-4zm0 2.5L6 7v10l6 3 6-3V7l-6-2.5z"/></svg>
        4D
    </span>
    @endif

    <div class="absolute bottom-2.5 left-2.5 right-2.5 z-10">
        <div class="text-white text-xs font-bold drop-shadow-lg truncate">{{ $title }}</div>
        @if($subtitle)
        <div class="text-white/60 text-[10px] drop-shadow truncate">{{ $subtitle }}</div>
        @endif
    </div>

    @if($href && $href !== '#')
    <a href="{{ $href }}" class="absolute inset-0 z-20" aria-label="{{ $title }}"></a>
    @endif
</div>