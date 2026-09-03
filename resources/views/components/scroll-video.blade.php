@props([
    'videoUrl' => null,
    'posterUrl' => null,
    'title' => '',
    'subtitle' => '',
    'description' => '',
    'price' => null,
    'ctaText' => 'Explore',
    'ctaUrl' => '#',
    'id' => 'scroll-video-' . uniqid(),
    'overlayColor' => 'rgba(0,0,0,0.35)',
])

@php $sectionId = $id . '-section'; @endphp

<section id="{{ $sectionId }}" class="relative w-full overflow-hidden scroll-video-section" style="height: 300vh;">
    {{-- Sticky video container — fills viewport during scroll --}}
    <div class="sticky top-0 left-0 w-full h-screen overflow-hidden bg-[#0B1E57]">
        @if($videoUrl)
        <video id="{{ $id }}" class="absolute inset-0 w-full h-full object-cover" 
               muted playsinline preload="auto"
               poster="{{ $posterUrl ?? '' }}">
            <source src="{{ $videoUrl }}" type="video/mp4">
        </video>
        @endif

        {{-- Gradient overlays --}}
        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-black/20 pointer-events-none" style="z-index:2"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-black/40 to-transparent pointer-events-none" style="z-index:2"></div>

        {{-- Text content overlays — each fades at different scroll progress --}}
        <div class="absolute inset-0 flex flex-col items-center justify-center text-center px-6" style="z-index:3">
            @if($title)
            <h2 class="scroll-text text-5xl md:text-7xl font-black text-white leading-tight mb-4" 
                data-scroll-start="0" data-scroll-end="0.2"
                style="opacity:0; transform:translateY(30px); text-shadow: 0 4px 20px rgba(0,0,0,0.5);">
                {{ $title }}
            </h2>
            @endif
            @if($subtitle)
            <p class="scroll-text text-lg md:text-2xl text-white/80 font-medium max-w-2xl"
               data-scroll-start="0.15" data-scroll-end="0.35"
               style="opacity:0; transform:translateY(20px);">
                {{ $subtitle }}
            </p>
            @endif
            @if($description)
            <p class="scroll-text text-sm md:text-base text-white/60 max-w-xl mt-4 leading-relaxed"
               data-scroll-start="0.30" data-scroll-end="0.55"
               style="opacity:0; transform:translateY(20px);">
                {{ $description }}
            </p>
            @endif
            @if($price)
            <div class="scroll-text mt-6" data-scroll-start="0.50" data-scroll-end="0.70" style="opacity:0; transform:translateY(20px);">
                <span class="text-3xl md:text-4xl font-black text-[#FFCD05]">KES {{ number_format($price) }}</span>
            </div>
            @endif
            @if($ctaUrl)
            <div class="scroll-text mt-8" data-scroll-start="0.65" data-scroll-end="0.85" style="opacity:0; transform:translateY(20px);">
                <a href="{{ $ctaUrl }}" 
                   class="inline-flex items-center gap-2 font-bold text-sm h-12 px-8 rounded-xl bg-[#FFCD05] text-black hover:bg-[#e6b904] transition-all active:scale-95">
                    {{ $ctaText }}
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
            </div>
            @endif
        </div>

        {{-- Scroll progress bar --}}
        <div class="absolute bottom-0 left-0 h-1 bg-[#FFCD05]/50 pointer-events-none" id="{{ $id }}-progress" style="z-index:4;width:0%"></div>

        {{-- Scroll indicator --}}
        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex flex-col items-center gap-1 text-white/30 pointer-events-none" style="z-index:4">
            <span class="text-[10px] tracking-widest uppercase font-semibold">Scroll</span>
            <svg class="w-4 h-4 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
        </div>
    </div>
</section>

@push('scripts')
<script>
(function() {
    var video = document.getElementById('{{ $id }}');
    var section = document.getElementById('{{ $sectionId }}');
    var progress = document.getElementById('{{ $id }}-progress');
    var textEls = section.querySelectorAll('.scroll-text');

    if (!video || !section || typeof gsap === 'undefined') return;

    // Register ScrollTrigger plugin
    if (typeof ScrollTrigger !== 'undefined') {
        gsap.registerPlugin(ScrollTrigger);
    } else {
        return; // GSAP ScrollTrigger not loaded, video plays normally
    }

    // Wait for video metadata
    video.addEventListener('loadedmetadata', function() {
        var duration = video.duration || 16;
        
        // GSAP ScrollTrigger scrub: maps 0-1 scroll to 0-duration video
        gsap.to(video, {
            currentTime: duration,
            ease: 'none',
            scrollTrigger: {
                trigger: section,
                start: 'top top',
                end: 'bottom bottom',
                scrub: 0.8,
                invalidateOnRefresh: true,
                onUpdate: function(self) {
                    var progressVal = self.progress;
                    if (progress) progress.style.width = (progressVal * 100) + '%';

                    // Update text overlays based on scroll progress
                    textEls.forEach(function(el) {
                        var start = parseFloat(el.getAttribute('data-scroll-start')) || 0;
                        var end = parseFloat(el.getAttribute('data-scroll-end')) || 1;
                        var range = end - start;
                        if (range <= 0) return;
                        var p = Math.max(0, Math.min(1, (progressVal - start) / range));
                        el.style.opacity = p;
                        el.style.transform = 'translateY(' + (30 * (1 - p)) + 'px)';
                    });
                }
            }
        });
    });

    // Fallback: if video didn't start loading, play normally
    setTimeout(function() {
        if (video.readyState === 0 && video.paused) {
            video.play().catch(function(){});
        }
    }, 3000);
})();
</script>
@endpush