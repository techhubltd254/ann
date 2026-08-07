@props([
    'asset' => null,        // MediaAsset model OR
    'video' => null,        // direct video url
    'poster' => null,       // poster image url
    'model' => null,        // glb url
    'alt' => '',
    'className' => '',
    'autoPlay' => true,
    'loop' => true,
    'controls' => false,
])

@php
    $videoUrl = $video ?? ($asset ? $asset->bestVideoUrl() : null);
    $posterUrl = $poster ?? ($asset ? ($asset->posterUrl() ?? $asset->thumbnailUrl() ?? $asset->url()) : null);
    $glbUrl = $model ?? ($asset ? $asset->glbUrl() : null);
@endphp

<div class="relative overflow-hidden bg-[#0D1220] {{ $className }}"
     x-data="cinematicMedia({
        video: @js($videoUrl),
        poster: @js($posterUrl),
        model: @js($glbUrl),
        autoPlay: {{ $autoPlay ? 'true' : 'false' }},
        loop: {{ $loop ? 'true' : 'false' }},
        controls: {{ $controls ? 'true' : 'false' }}
     })"
     x-init="init()">

    {{-- Poster-first: image shows instantly — video never blocks the page load --}}
    <template x-if="poster && !started">
        <img :src="poster" :alt="@js($alt)" class="absolute inset-0 w-full h-full object-cover" loading="lazy" decoding="async">
    </template>

    <template x-if="started && video">
        <video :src="video"
               class="absolute inset-0 w-full h-full object-cover"
               :autoplay="autoPlay"
               :muted="autoPlay"
               :loop="loop"
               :controls="controls"
               playsinline
               preload="metadata"
               x-init="$el.addEventListener('loadeddata', () => $el.classList.add('opacity-100'), { once: true })"
               class="absolute inset-0 w-full h-full object-cover opacity-0 transition-opacity duration-700">
        </video>
    </template>

    <template x-if="started && model && !video">
        <div class="absolute inset-0 w-full h-full" x-init="loadModel($el, model)">
            <div class="w-full h-full flex items-center justify-center">
                <div class="skeleton w-24 h-24 rounded-2xl"></div>
            </div>
        </div>
    </template>

    <template x-if="!started">
        <div class="absolute bottom-0 inset-x-0 glass-frame glass-frame-dark px-5 pt-10 pb-4 flex items-end justify-between gap-3">
            <div class="text-white/85 text-xs font-semibold leading-snug max-w-[70%]">{{ $alt }}</div>
            <div class="text-[10px] font-bold uppercase tracking-widest text-white/50 shrink-0">Cinematic</div>
        </div>
    </template>

    <template x-if="started && video">
        <div class="absolute bottom-0 inset-x-0 glass-frame glass-frame-dark px-5 pt-10 pb-4 flex items-end justify-between gap-3">
            <div class="text-white/85 text-xs font-semibold leading-snug max-w-[70%]">{{ $alt }}</div>
            <button @click="$el.parentElement.querySelector('video').paused ? $el.parentElement.querySelector('video').play() : $el.parentElement.querySelector('video').pause()"
                    class="hit-target w-10 h-10 rounded-full glass-card-dark text-white flex items-center justify-center hover:scale-105 transition-transform">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
            </button>
        </div>
    </template>
</div>

<script>
    /**
     * CinematicMedia — poster-first lazy video/3D loader.
     * Loads media ONLY when scrolled into view (IntersectionObserver).
     * Respects prefers-reduced-motion. WebM is preferred by the server;
     * the <video> element falls back to MP4 automatically.
     */
    window.cinematicMedia = function (config) {
        return {
            ...config,
            started: false,
            init() {
                if (config.autoPlay && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    this.started = false;
                    return;
                }
                if (!config.video && !config.model) return;

                const el = this.$el;
                if ('IntersectionObserver' in window) {
                    const io = new IntersectionObserver(entries => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                this.started = true;
                                io.disconnect();
                            }
                        });
                    }, { rootMargin: '200px 0px', threshold: 0.05 });
                    io.observe(el);
                    window.KICCImmersive?.refresh?.();
                } else {
                    this.started = true;
                }
            },
            loadModel(el, url) {
                Promise.all([
                    import('three'),
                    import('three/addons/loaders/GLTFLoader.js'),
                    import('three/addons/controls/OrbitControls.js')
                ]).then(([THREE, { GLTFLoader }, { OrbitControls }]) => {
                    this.renderModel(el, url, THREE, GLTFLoader, OrbitControls);
                }).catch(() => {
                    el.innerHTML = '<div class="w-full h-full flex items-center justify-center text-white/40 text-sm">3D preview unavailable</div>';
                });
            },
            renderModel(el, url, THREE, GLTFLoader, OrbitControls) {
                const container = el;
                const width = container.clientWidth || 800;
                const height = container.clientHeight || 450;

                const scene = new THREE.Scene();
                const camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 100);
                camera.position.z = 3;

                const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
                renderer.setSize(width, height);
                renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.5)); // WebGL perf cap
                container.appendChild(renderer.domElement);

                scene.add(new THREE.AmbientLight(0xffffff, 0.9));
                const key = new THREE.DirectionalLight(0xffffff, 1.2);
                key.position.set(2, 3, 4);
                scene.add(key);

                const controls = new OrbitControls(camera, renderer.domElement);
                controls.enableDamping = true;
                controls.autoRotate = true;

                container.querySelector('.skeleton')?.remove();

                new GLTFLoader().load(url, gltf => {
                    const obj = gltf.scene;
                    obj.scale.setScalar(1);
                    scene.add(obj);
                    animate();
                }, undefined, () => {
                    container.innerHTML = '<div class="w-full h-full flex items-center justify-center text-white/40 text-sm">3D preview unavailable</div>';
                });

                function animate() {
                    requestAnimationFrame(animate);
                    controls.update();
                    renderer.render(scene, camera);
                }
            }
        };
    };
</script>
