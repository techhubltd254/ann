@props(['counties' => [], 'heroVideos' => []])

@php $regions = ['All', 'Central', 'Coast', 'Eastern', 'Nyanza', 'North Eastern', 'Rift Valley', 'Western', 'Nairobi']; @endphp

<div id="kicc-county-strip" class="overflow-hidden">
    <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <div class="relative flex-1 max-w-sm">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 text-[#5A6480]" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            <input id="kicc-search-input" placeholder="Search county name…"
                class="w-full pl-10 pr-12 h-11 rounded-xl bg-white border border-gray-200 text-gray-900 text-sm outline-none focus:ring-1 focus:ring-[#FFCD05] placeholder:text-[#5A6480]/50 transition-all"
                data-voice-search>
        </div>
        <div class="flex gap-1.5 overflow-x-auto pb-1 flex-wrap sm:flex-nowrap" id="kicc-region-buttons">
            @foreach($regions as $i => $r)
            <button data-region="{{ $r }}"
                class="shrink-0 px-3 py-1.5 rounded-lg text-[11px] font-bold transition-all cursor-pointer {{ $i === 0 ? 'bg-[#901C1E] text-white' : 'bg-white text-[#5A6480] border border-gray-200 hover:border-[#901C1E]/30' }}">{{ $r }}</button>
            @endforeach
        </div>
    </div>

    <div class="flex items-center justify-between mb-3">
        <span id="kicc-county-count" class="text-[#5A6480] text-xs font-semibold">{{ count($counties) }} counties</span>
        <div class="flex gap-2" id="kicc-scroll-buttons">
            <button onclick="document.getElementById('kicc-county-strip-inner').scrollBy({left: -320, behavior: 'smooth'})" class="w-8 h-8 rounded-full border border-gray-200 text-[#5A6480] hover:border-[#FFCD05] hover:text-[#FFCD05] flex items-center justify-center transition-all cursor-pointer">&larr;</button>
            <button onclick="document.getElementById('kicc-county-strip-inner').scrollBy({left: 320, behavior: 'smooth'})" class="w-8 h-8 rounded-full border border-gray-200 text-[#5A6480] hover:border-[#FFCD05] hover:text-[#FFCD05] flex items-center justify-center transition-all cursor-pointer">&rarr;</button>
        </div>
    </div>

    <div class="relative">
        <div class="max-w-7xl mx-auto px-0">
            <div id="kicc-county-strip-inner" class="flex gap-4 overflow-x-auto pb-4 px-5 scrollbar-hide">
                @foreach($counties as $c)
                @php $v = $heroVideos[$c->slug] ?? null; @endphp
                <a href="{{ route('counties.show', $c->slug) }}"
                   data-name="{{ strtolower($c->name) }}"
                   data-region="{{ $c->former_province ?? '' }}"
                   data-video="{{ $v ?? '' }}"
                   class="kicc-county-card shrink-0 group relative overflow-hidden rounded-2xl block bg-white border border-gray-200 hover:border-[#FFCD05]/40 transition-all" style="width: 200px; height: 280px;">
                     @if($v)
                     <video autoplay muted loop playsinline preload="auto"
                            class="absolute inset-0 w-full h-full object-cover"
                            onerror="this.remove()">
                         <source src="{{ $v }}" type="video/mp4">
                     </video>
                     @endif
                     <img src="{{ media('counties/' . $c->slug . '/hero.jpeg') }}" alt="{{ $c->name }}"
                          class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                          loading="lazy" decoding="async"
                          onerror="this.remove()">
                     <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
                    <div class="absolute inset-x-0 bottom-0 p-4">
                        <div class="text-white font-black text-base leading-tight">{{ $c->name }}</div>
                        <div class="text-white/60 text-[11px] mt-1">{{ $c->tagline ?? Str::limit($c->description ?? '', 40) }}</div>
                    </div>
                </a>
                @endforeach
            </div>
            <div id="kicc-county-grid" class="hidden grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 px-5"></div>
        </div>
    </div>
</div>

<script>
(function() {
    var searchInput = document.getElementById('kicc-search-input');
    var regionButtons = document.querySelectorAll('#kicc-region-buttons [data-region]');
    var countyCards = document.querySelectorAll('#kicc-county-strip-inner .kicc-county-card');
    var countEl = document.getElementById('kicc-county-count');
    var stripInner = document.getElementById('kicc-county-strip-inner');
    var gridEl = document.getElementById('kicc-county-grid');
    var scrollButtons = document.getElementById('kicc-scroll-buttons');
    var activeRegion = 'All';

    function buildCard(card) {
        var clone = card.cloneNode(true);
        clone.style.width = '';
        clone.style.height = '';
        clone.style.aspectRatio = '3 / 4';
        // Clone the video element inside with preload metadata so it plays
        var origVideo = card.querySelector('video');
        if (origVideo) {
            var newVideo = origVideo.cloneNode();
            newVideo.removeAttribute('preload');
            newVideo.preload = 'none';
            newVideo.autoplay = true;
            newVideo.muted = true;
            newVideo.loop = true;
            newVideo.playsInline = true;
            // Insert before img
            var img = clone.querySelector('img');
            if (img && img.parentNode) {
                img.parentNode.insertBefore(newVideo, img);
            }
        }
        return clone;
    }

    // Clone cards into grid
    countyCards.forEach(function(card) {
        gridEl.appendChild(buildCard(card));
    });
    var gridCards = gridEl.querySelectorAll('.kicc-county-card');

    function filterCounties() {
        var query = (searchInput ? searchInput.value.toLowerCase() : '');
        var isFiltered = activeRegion !== 'All' || query !== '';
        var visible = 0;

        if (isFiltered) {
            stripInner.classList.add('hidden');
            gridEl.classList.remove('hidden');
            scrollButtons.classList.add('hidden');
        } else {
            stripInner.classList.remove('hidden');
            gridEl.classList.add('hidden');
            scrollButtons.classList.remove('hidden');
            stripInner.scrollTo({ left: 0, behavior: 'instant' });
        }

        var container = isFiltered ? gridCards : countyCards;
        container.forEach(function(card) {
            var name = card.getAttribute('data-name') || '';
            var region = card.getAttribute('data-region') || '';
            var match = (query === '' || name.includes(query)) &&
                        (activeRegion === 'All' || region === activeRegion);
            card.style.display = match ? '' : 'none';
            if (match) {
                visible++;
                // Play video if present
                var vid = card.querySelector('video');
                if (vid && card.style.display !== 'none') {
                    vid.play().catch(function(){});
                }
            }
        });
        if (countEl) countEl.textContent = visible + ' counties';
    }

    if (searchInput) {
        searchInput.addEventListener('input', filterCounties);
    }

    regionButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            activeRegion = this.getAttribute('data-region');
            regionButtons.forEach(function(b) {
                var isActive = b === btn;
                b.className = 'shrink-0 px-3 py-1.5 rounded-lg text-[11px] font-bold transition-all cursor-pointer ' +
                    (isActive ? 'bg-[#901C1E] text-white' : 'bg-white text-[#5A6480] border border-gray-200 hover:border-[#901C1E]/30');
            });
            filterCounties();
        });
    });
})();
</script>