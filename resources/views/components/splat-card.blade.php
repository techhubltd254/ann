<div class="splat-viewer-container relative bg-black rounded-2xl overflow-hidden group" 
     x-data="{ active: false, mode: 'splat' }"
     @mouseenter="active = true" 
     @mouseleave="active = false">
    
    {{-- Splat (interactive 4D) --}}
    <canvas class="splat-viewer w-full h-full absolute inset-0 cursor-grab active:cursor-grabbing"
            data-splat="{{ $splatUrl ?? '' }}"
            :class="{ 'hidden': mode !== 'splat' || !active }">
    </canvas>

    {{-- Video fallback --}}
    @if($videoUrl ?? false)
    <video class="w-full h-full object-cover absolute inset-0"
           :class="{ 'hidden': mode === 'splat' && active }"
           muted loop playsinline
           @mouseenter="this.play()" @mouseleave="this.pause(); this.currentTime=0"
           poster="{{ $poster ?? '' }}">
        <source src="{{ $videoUrl }}" type="video/mp4">
    </video>
    @endif

    {{-- Hover overlay --}}
    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent pointer-events-none"
         :class="{ 'opacity-0': active, 'opacity-100': !active }"
         :style="{ transition: 'opacity 0.3s' }">
    </div>

    {{-- Labels --}}
    <div class="absolute bottom-3 left-3 right-3 flex items-end justify-between pointer-events-none"
         :class="{ 'opacity-0': active, 'opacity-100': !active }"
         :style="{ transition: 'opacity 0.3s' }">
        <div>
            <div class="text-white font-bold text-sm drop-shadow-lg">{{ $title ?? '' }}</div>
            <div class="text-white/60 text-xs drop-shadow">{{ $subtitle ?? '' }}</div>
        </div>
        @if($splatUrl ?? false)
        <div class="px-2 py-1 rounded-full bg-black/50 backdrop-blur text-white/90 text-[10px] font-bold flex items-center gap-1">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            4D Hologram
        </div>
        @endif
    </div>

    {{-- Mode toggle --}}
    @if(($splatUrl ?? false) && ($videoUrl ?? false))
    <button @click="mode = mode === 'splat' ? 'video' : 'splat'"
            class="absolute top-3 right-3 px-2 py-1 rounded-full bg-black/40 backdrop-blur text-white/80 text-[10px] font-bold hover:bg-black/60 transition-all z-10"
            x-text="mode === 'splat' ? '🎬 Video' : '🌀 4D'">
    </button>
    @endif
</div>

@once
<script src="{{ asset('js/splat-viewer.js') }}" defer></script>
@endonce