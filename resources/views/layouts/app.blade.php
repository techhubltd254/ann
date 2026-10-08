@extends('layouts.experience-live')
@push('styles')
<link rel="stylesheet" href="/css/experience-compat-structure.css?v=experience-full-bright-v3">
<script src="https://cdn.tailwindcss.com"></script>
<script src="{{ asset('js/gsap.min.js') }}"></script>
<script src="{{ asset('js/ScrollTrigger.min.js') }}"></script>
<script type="importmap">{"imports":{"three":"https://cdn.jsdelivr.net/npm/three@0.170.0/build/three.module.js","three/addons/":"https://cdn.jsdelivr.net/npm/three@0.170.0/examples/jsm/"}}</script>
<script>window.KICC_MOTION={active3d:false,cameraHandle:null,objectHandle:null};</script>
<script defer src="https://cdn.jsdelivr.net/npm/hls.js@1.5.13/dist/hls.min.js"></script>
<script defer src="{{ asset('js/media-tile.js') }}"></script>
<script defer src="{{ asset('js/alpine-data.js') }}"></script>
@endpush
@push('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
<script src="{{ asset('js/animations.js') }}"></script>
<script src="{{ asset('js/immersive.js') }}"></script>
@endpush

@push('scripts')<script>document.addEventListener('DOMContentLoaded', function() {
    // 3D is opt-in — only runs on pages with actual 3D elements.
    // This keeps THREE.js + WebGL off pages that don't need it,
    // which was blocking Alpine initialization and breaking video playback.
    var needs3d = document.querySelector('.three-video-container, .three-video-overlay');
    if (!needs3d) return;

    var script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js';
    script.onload = function() {
        // Load the 3D player + background scripts after THREE is ready
        ['three-video-player.js', 'three-background.js'].forEach(function(file) {
            var s = document.createElement('script');
            s.src = '/js/' + file;
            s.defer = false;
            document.body.appendChild(s);
        });
        // Small delay for scripts to parse, then init 3D elements
        setTimeout(function() {
            document.querySelectorAll('.three-video-container').forEach(function(el) {
                var videoUrl = el.dataset.video;
                if (videoUrl && window.Kicc3DVideoPlayer) {
                    try { new window.Kicc3DVideoPlayer({ container: el, videoUrl: videoUrl }); } catch(e) {}
                }
            });
        }, 500);
    };
    document.head.appendChild(script);
});
</script>@endpush
