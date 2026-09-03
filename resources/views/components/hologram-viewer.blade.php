@props([
    'poster' => null,
    'videoUrl' => null,
    'hlsUrl' => null,
    'splatUrl' => null,
    'title' => '4D Experience',
    'subtitle' => null,
])

{{-- Tier 3 viewer: mounts heavy render (WebGL splat OR full HLS video) ONLY on click --}}
<div class="relative bg-black rounded-2xl overflow-hidden group"
     x-data="hologramViewer('{{ $splatUrl ?? '' }}', '{{ $hlsUrl ?? '' }}', '{{ $videoUrl ?? '' }}')">

    {{-- Poster (Tier 1) --}}
    <template x-if="!started">
        <div class="absolute inset-0 flex items-center justify-center cursor-pointer"
             @click="start()">
            @if($poster)
            <img src="{{ $poster }}" alt="{{ $title }}" loading="lazy"
                 class="absolute inset-0 w-full h-full object-cover">
            @endif
            <div class="absolute inset-0 bg-black/40 flex items-center justify-center">
                <div class="flex flex-col items-center gap-2">
                    <div class="w-16 h-16 rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center hover:bg-white/35 transition-all">
                        <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    </div>
                    <span class="text-white text-xs font-bold drop-shadow">{{ $splatUrl ? 'Open 4D Hologram' : 'Play Experience' }}</span>
                </div>
            </div>
        </div>
    </template>

    {{-- Interactive 4D Gaussian splat (WebGL — mounted only after click) --}}
    <template x-if="started && mode === 'splat' && splatUrl">
        <div class="relative w-full h-full">
            <canvas x-ref="splatCanvas" class="splat-viewer w-full h-full cursor-grab active:cursor-grabbing"></canvas>
            <button @click="mode = 'video'"
                    class="absolute top-3 right-3 px-2.5 py-1 rounded-full bg-black/50 backdrop-blur text-white/80 text-[10px] font-bold hover:bg-black/70 transition-all z-10">
                 Video
            </button>
        </div>
    </template>

    {{-- Full HLS/video (adaptive) --}}
    <template x-if="started && (mode === 'video' || !splatUrl)">
        <video x-ref="hlsVideo"
               class="w-full h-full object-cover"
               controls playsinline preload="auto"
               poster="{{ $poster ?? '' }}"></video>
    </template>

    {{-- Bottom labels --}}
    <div class="absolute bottom-0 left-0 right-0 p-4 bg-gradient-to-t from-black/70 to-transparent pointer-events-none z-10">
        <div class="text-white text-sm font-bold drop-shadow">{{ $title }}</div>
        @if($subtitle)
        <div class="text-white/60 text-[11px] drop-shadow">{{ $subtitle }}</div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function hologramViewer(splatUrl, hlsUrl, mp4Url) {
    return {
        started: false,
        mode: 'splat',
        splatViewer: null,
        mounted() {
            // Auto-switch to video when no splat is available
            if (!splatUrl) this.mode = 'video';
        },
        start() {
            this.started = true;
            // Defer heavy init to next frame so the click UI repaints first
            this.$nextTick(() => this.initMedia());
        },
        initMedia() {
            if (this.mode === 'splat' && splatUrl && window.SplatViewer) {
                try {
                    this.splatViewer = new SplatViewer(this.$refs.splatCanvas);
                    this.splatViewer.load(splatUrl);
                } catch (e) {
                    console.warn('Splat init failed, falling back to video', e);
                    this.mode = 'video';
                    this.initVideo();
                }
            } else {
                this.initVideo();
            }
        },
        initVideo() {
            const video = this.$refs.hlsVideo;
            if (!video) return;
            if (hlsUrl && typeof Hls !== 'undefined' && Hls.isSupported()) {
                // Adaptive bitrate — starts low, upgrades/downgrades with network
                const hls = new Hls({
                    enableWorker: true,
                    lowLatencyMode: false,
                    abrEwmaDefaultEstimate: 500000,
                    startLevel: -1,
                    capLevelToPlayerSize: true,
                    backbufferLength: 60,
                });
                hls.loadSource(hlsUrl);
                hls.attachMedia(video);
                video.play().catch(() => {});
            } else if (hlsUrl && video.canPlayType('application/vnd.apple.mpegurl')) {
                video.src = hlsUrl;
                video.play().catch(() => {});
            } else if (mp4Url) {
                video.src = mp4Url;
                video.play().catch(() => {});
            }
        },
    };
}
</script>
@endpush

@once
<script src="{{ asset('js/splat-viewer.js') }}" defer></script>
@endonce