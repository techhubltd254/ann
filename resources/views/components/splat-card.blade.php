{{-- Splat card: poster default, WebGL mounts ONLY after click (never on hover) --}}
<div class="splat-viewer-container relative bg-black rounded-2xl overflow-hidden group"
     x-data="splatCard('{{ $splatUrl ?? '' }}', '{{ $videoUrl ?? '' }}')">

    {{-- Tier 1 poster / video fallback --}}
    <template x-if="!started">
        <div class="relative w-full h-full cursor-pointer" @click="start()">
            @if($videoUrl)
            <video class="w-full h-full object-cover" muted loop playsinline
                   poster="{{ $poster ?? '' }}"
                   @mouseenter="this.play()" @mouseleave="this.pause(); this.currentTime=0">
                <source src="{{ $videoUrl }}" type="video/mp4">
            </video>
            @else
            <div class="w-full h-full bg-gray-900 flex items-center justify-center">
                <span class="text-white/40 text-xs">4D</span>
            </div>
            @endif
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent pointer-events-none"></div>
        </div>
    </template>

    {{-- Interactive splat — mounted only after click --}}
    <template x-if="started && splatUrl">
        <canvas x-ref="splatCanvas" class="splat-viewer w-full h-full absolute inset-0 cursor-grab active:cursor-grabbing"></canvas>
    </template>

    {{-- Labels --}}
    <div class="absolute bottom-3 left-3 right-3 flex items-end justify-between pointer-events-none z-10">
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
</div>

@push('scripts')
<script>
function splatCard(splatUrl, videoUrl) {
    return {
        started: false,
        viewer: null,
        start() {
            this.started = true;
            if (splatUrl) {
                this.$nextTick(() => {
                    if (window.SplatViewer) {
                        try {
                            this.viewer = new SplatViewer(this.$refs.splatCanvas);
                            this.viewer.load(splatUrl);
                        } catch (e) {
                            console.warn('Splat init failed', e);
                        }
                    }
                });
            }
        },
        destroyed() {
            if (this.viewer) { try { this.viewer.dispose?.(); } catch (e) {} }
        },
    };
}
</script>
@endpush

@once
<script src="{{ asset('js/splat-viewer.js') }}" defer></script>
@endonce