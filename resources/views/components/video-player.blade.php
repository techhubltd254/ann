@props([
    'asset' => null,
    'src' => null,
    'poster' => null,
    'id' => 'kicc-video-' . uniqid(),
    'class' => 'w-full h-full',
    'autoplay' => false,
    'loop' => false,
    'muted' => false,
    'controls' => false,
])

@php
    $mp4Url = $asset ? $asset->mp4Url() : $src;
    $webmUrl = $asset ? $asset->webmUrl() : null;
    $originalUrl = $asset ? $asset->url() : $src;
    $hlsUrl = $asset ? $asset->derivativeUrl('hls_master') : null;
    $directSrc = $mp4Url ?? $webmUrl ?? $originalUrl;
    $posterUrl = $poster ?? ($asset ? ($asset->posterUrl() ?? $asset->thumbnailUrl()) : null);
    $videoId = $id;
@endphp

<div class="absolute inset-0 {{ $class }}" id="{{ $videoId }}-container" style="background:#000">
    @if($posterUrl)
    <img src="{{ $posterUrl }}" alt="Video poster"
         class="absolute inset-0 w-full h-full object-cover"
         id="{{ $videoId }}-poster"
         style="z-index:1">
    @endif

    @if($autoplay)
    <video id="{{ $videoId }}"
           class="absolute inset-0 w-full h-full object-cover"
           style="z-index:2"
           autoplay muted playsinline
           loop
           preload="auto"
           poster="{{ $posterUrl ?? '' }}">
    </video>
    @else
    <video id="{{ $videoId }}"
           class="absolute inset-0 w-full h-full object-cover"
           style="z-index:2"
           playsinline
           preload="auto"
           poster="{{ $posterUrl ?? '' }}">
    </video>

    <div id="{{ $videoId }}-loading"
         class="absolute inset-0 flex items-center justify-center"
         style="z-index:3;background:rgba(0,0,0,0.3);display:none">
        <div class="w-10 h-10 border-4 border-white/30 border-t-white rounded-full animate-spin"></div>
    </div>

    <button id="{{ $videoId }}-playbtn"
            class="absolute inset-0 flex items-center justify-center cursor-pointer"
            style="z-index:4;display:none;background:rgba(0,0,0,0.15)">
        <svg viewBox="0 0 24 24" width="72" height="72" fill="white">
            <circle cx="12" cy="12" r="11" fill="rgba(0,0,0,0.6)" stroke="white" stroke-width="1.5"/>
            <polygon points="10,7 18,12 10,17" fill="white"/>
        </svg>
    </button>
    @endif
</div>

@push('scripts')
<script>
(function() {
    var video = document.getElementById('{{ $videoId }}');
    var poster = document.getElementById('{{ $videoId }}-poster');
    var loading = document.getElementById('{{ $videoId }}-loading');
    var playBtn = document.getElementById('{{ $videoId }}-playbtn');
    var autoplay = {{ $autoplay ? 'true' : 'false' }};
    var loopVideo = {{ $loop ? 'true' : 'false' }};

    if (!video) return;

    var hlsUrl = '{{ $hlsUrl ?? '' }}';
    var mp4Url = '{{ $mp4Url ?? '' }}';
    var webmUrl = '{{ $webmUrl ?? '' }}';
    var originalUrl = '{{ $originalUrl ?? '' }}';

    function hidePoster() { if (poster) poster.style.display = 'none'; }
    function showPlayBtn() { if (playBtn) playBtn.style.display = 'flex'; }
    function hidePlayBtn() { if (playBtn) playBtn.style.display = 'none'; }
    function showLoading() { if (loading) loading.style.display = 'flex'; }
    function hideLoading() { if (loading) loading.style.display = 'none'; }

    video.addEventListener('playing', function() {
        hideLoading();
        hidePoster();
    });
    // hls.js does not honour the native loop attribute — restart on ended
    video.addEventListener('ended', function() {
        if (loopVideo) {
            video.currentTime = 0;
            tryPlay();
        }
    });
    video.addEventListener('loadedmetadata', function() {
        hidePoster();
        hideLoading();
    });
    video.addEventListener('error', function() {
        hidePoster();
        if (!autoplay) showPlayBtn();
    });

    function tryPlay() {
        var p = video.play();
        if (p !== undefined) {
            p.catch(function() {
                if (!autoplay) { showPlayBtn(); return; }
                var onInteraction = function() {
                    video.play().catch(function(){});
                    document.removeEventListener('click', onInteraction);
                    document.removeEventListener('touchstart', onInteraction);
                };
                document.addEventListener('click', onInteraction, { once: true });
                document.addEventListener('touchstart', onInteraction, { once: true });
                if (playBtn) {
                    playBtn.style.display = 'flex';
                    playBtn.addEventListener('click', function() {
                        playBtn.style.display = 'none';
                        video.play().catch(function(){});
                    });
                }
            });
        }
    }

    function fallbackToMp4() {
        var source = webmUrl || mp4Url || originalUrl;
        if (!source) { if (!autoplay) showPlayBtn(); return; }
        video.src = source;
        video.type = webmUrl ? 'video/webm' : (mp4Url ? 'video/mp4' : '');
        video.addEventListener('loadedmetadata', function() {
            hideLoading();
            hidePoster();
            if (autoplay || !autoplay) tryPlay();
        });
        video.addEventListener('error', function() {
            if (loading) hideLoading();
            if (webmUrl && mp4Url && video.src === webmUrl) {
                video.src = mp4Url;
                video.load();
            } else if ((webmUrl || mp4Url) && originalUrl && video.src !== originalUrl) {
                video.src = originalUrl;
                video.load();
            } else if (!autoplay) showPlayBtn();
        });
    }

    function initHls() {
        showLoading();
        var hls = new Hls({
            enableWorker: true,
            lowLatencyMode: false,
            backbufferLength: 60,
            maxBufferLength: 60,
            maxMaxBufferLength: 60,
            abrEwmaDefaultEstimate: 400000,
            abrEwmaFastVoD: 3.0,
            abrEwmaSlowVoD: 6.0,
            abrBandWidthFactor: 0.9,
            abrBandWidthUpFactor: 0.7,
            startLevel: -1,
            capLevelToPlayerSize: true,
            maxStarvationDelay: 6,
        });
        hls.loadSource(hlsUrl);
        hls.attachMedia(video);
        hls.on(Hls.Events.MANIFEST_PARSED, function() {
            hideLoading();
            hidePoster();
            tryPlay();
        });
        hls.on(Hls.Events.ERROR, function(event, data) {
            if (data.fatal) {
                hls.destroy();
                fallbackToMp4();
            }
        });
        hls.on(Hls.Events.LEVEL_SWITCHED, function() {
            // ABR already handles up/down automatically; nothing to force
        });
        window['{{ $videoId }}-hls'] = hls;
    }

    // Main
    if (hlsUrl && typeof Hls !== 'undefined' && Hls.isSupported()) {
        initHls();
    } else if (hlsUrl && video.canPlayType('application/vnd.apple.mpegurl')) {
        video.src = hlsUrl;
        video.addEventListener('loadedmetadata', function() {
            hidePoster();
            tryPlay();
        });
    } else {
        if (!autoplay) showLoading();
        fallbackToMp4();
    }

    if (!autoplay && playBtn) {
        playBtn.addEventListener('click', function() {
            hidePlayBtn();
            showLoading();
            var p = video.play();
            if (p !== undefined) p.then(hideLoading).catch(function() { hideLoading(); showPlayBtn(); });
        });
    }
})();
</script>
@endpush