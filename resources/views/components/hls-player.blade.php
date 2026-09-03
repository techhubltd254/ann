@props([
    'hlsUrl' => null,
    'poster' => null,
    'title' => '',
    'id' => 'hls-player-' . uniqid(),
    'class' => 'w-full h-full',
    'autoplay' => false,
    'muted' => false,
])

<div class="relative bg-black overflow-hidden rounded-xl {{ $class }}"
     x-data="hlsPlayer('{{ $hlsUrl }}', '{{ $id }}')"
     x-init="init()">
    @if($poster)
    <img src="{{ $poster }}" alt="{{ $title }}"
         class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300"
         :class="started ? 'opacity-0' : 'opacity-100'"
         loading="lazy" decoding="async"
         onerror="this.style.display='none'">
    @endif

    <video x-ref="video"
           id="{{ $id }}"
           class="absolute inset-0 w-full h-full object-cover"
           :class="started ? 'opacity-100' : 'opacity-0'"
           style="transition: opacity 0.3s ease;"
           playsinline
           x-show="started"
           controls></video>

    {{-- Quality selector --}}
    <div x-show="started && levels.length > 1"
         x-cloak
         class="absolute top-3 right-3 z-10">
        <select x-model="currentLevel"
                @change="switchLevel()"
                class="text-[10px] font-bold px-2 py-1 rounded-lg bg-black/60 backdrop-blur text-white border border-white/20 outline-none appearance-none cursor-pointer">
            <option value="-1">Auto</option>
            <template x-for="(l, i) in levels" :key="i">
                <option :value="i" x-text="l.height + 'p'"></option>
            </template>
        </select>
    </div>

    {{-- LIVE badge --}}
    <div class="absolute top-3 left-3 z-10 flex items-center gap-1.5">
        <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-red-500/80 backdrop-blur text-white">LIVE</span>
    </div>

    {{-- Viewer count --}}
    <div class="absolute bottom-3 left-3 z-10 flex items-center gap-1.5">
        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-black/50 backdrop-blur text-white/80">
            <span x-text="viewerCount"></span> watching
        </span>
    </div>

    {{-- Play button (before start) --}}
    <div x-show="!started" @click="start()"
         class="absolute inset-0 flex items-center justify-center cursor-pointer z-10"
         style="background: rgba(0,0,0,0.15)">
        <div class="w-16 h-16 rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center hover:bg-white/35 transition-all">
            <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
<script>
function hlsPlayer(hlsUrl, playerId) {
    return {
        started: false,
        hls: null,
        levels: [],
        currentLevel: -1,
        viewerCount: 0,
        viewerInterval: null,

        init() {
            if (hlsUrl && typeof Hls !== 'undefined' && Hls.isSupported()) {
                this.hls = new Hls({
                    enableWorker: true,
                    lowLatencyMode: true,
                    backbufferLength: 30,
                    maxBufferLength: 30,
                    startLevel: -1,
                    capLevelToPlayerSize: true,
                });
                this.hls.loadSource(hlsUrl);
                this.hls.attachMedia(this.$refs.video);
                this.hls.on(Hls.Events.MANIFEST_PARSED, () => {
                    this.levels = this.hls.levels;
                });
                this.hls.on(Hls.Events.LEVEL_SWITCHED, (event, data) => {
                    this.currentLevel = data.level;
                });
            } else if (hlsUrl && this.$refs.video.canPlayType('application/vnd.apple.mpegurl')) {
                this.$refs.video.src = hlsUrl;
            }
        },

        start() {
            this.started = true;
            this.$nextTick(() => {
                this.$refs.video.play().catch(() => {});
            });
            // Simulate viewer count
            this.viewerCount = Math.floor(Math.random() * 50) + 10;
            this.viewerInterval = setInterval(() => {
                this.viewerCount += Math.floor(Math.random() * 3) - 1;
                if (this.viewerCount < 5) this.viewerCount = 5;
            }, 5000);
        },

        switchLevel() {
            if (this.hls) {
                this.hls.currentLevel = parseInt(this.currentLevel);
            }
        },

        destroy() {
            if (this.viewerInterval) clearInterval(this.viewerInterval);
            if (this.hls) this.hls.destroy();
        }
    };
}
</script>
@endpush