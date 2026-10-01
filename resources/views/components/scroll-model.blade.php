@props([
    'modelUrl' => 'https://media.kicctest.org/models/exhibit_asset.glb',
    'sectionId' => 'scroll-3d-section',
    'title' => 'VIRTUAL EXHIBITION',
    'subtitle' => 'Scroll to explore real-time spatial captures',
    'cards' => [],
])

<section id="{{ $sectionId }}" class="scroll-3d-section" {{ $attributes }}>
    <div class="scroll-3d-sticky">
        <div id="{{ $sectionId }}-canvas" class="absolute inset-0 kicc-canvas-3d"
             data-scroll-model="{{ $modelUrl }}"
             data-scroll-section="{{ $sectionId }}"
             data-scroll-container="{{ $sectionId }}-canvas"
             style="pointer-events: none;">
        </div>

        @if($title || $subtitle)
        <div class="scroll-3d-overlay" style="pointer-events: none;">
            <div style="pointer-events: auto;">
                @if($title)
                <h2 class="text-5xl md:text-7xl font-black tracking-tight text-white mb-4 drop-shadow-lg">{{ $title }}</h2>
                @endif
                @if($subtitle)
                <p class="text-lg text-white/70 font-medium drop-shadow">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
        @endif

        @foreach($cards as $index => $card)
        <div class="absolute z-20 pointer-events-auto"
             style="top: {{ 20 + $index * 25 }}%; {{ $index % 2 == 0 ? 'left' : 'right' }}: 8%; max-width: 320px;">
            <div class="glass-card-dark rounded-xl p-6 border border-white/10 backdrop-blur-xl">
                @if(!empty($card['label']))
                <span class="text-xs font-mono uppercase tracking-widest text-white/50">{{ $card['label'] }}</span>
                @endif
                @if(!empty($card['title']))
                <h3 class="text-xl font-bold text-white mt-2">{{ $card['title'] }}</h3>
                @endif
                @if(!empty($card['desc']))
                <p class="text-sm text-white/60 mt-2 leading-relaxed">{{ $card['desc'] }}</p>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</section>

@push('scripts')
<script type="module">
import { KiccScroll3D } from '{{ asset('js/kicc-scroll3d.js') }}?v={{ filemtime(public_path('js/kicc-scroll3d.js')) }}';
window._kiccScroll3D = new KiccScroll3D({
    modelUrl: '{{ $modelUrl }}',
    sectionId: '{{ $sectionId }}',
    containerId: '{{ $sectionId }}-canvas',
});
</script>
@endpush