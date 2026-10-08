@extends('layouts.app')

@section('title', 'Explore all 47 Counties of Kenya')
@section('description', 'Discover economic sectors, investment opportunities, tourism attractions, and trade exhibitions across every county.')

@push('styles')
<style>
/* ── Counties 3D Immersive Design ── */
.counties-3d-hero{min-height:60vh;background:#FFFFFF;position:relative;overflow:hidden}
.counties-3d-hero::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse at 30% 50%,rgba(179,38,30,0.12) 0%,transparent 60%),radial-gradient(ellipse at 70% 30%,rgba(255,205,5,0.08) 0%,transparent 50%);pointer-events:none}

.map-scroll-section{position:relative;height:250vh;background:#FFFFFF;margin:0}
.map-scroll-sticky{position:sticky;top:0;height:100vh;overflow:hidden;display:flex;align-items:center;justify-content:center}
#kenya-3d-canvas{position:absolute;inset:0;width:100%;height:100vh;pointer-events:none}
.map-overlay-content{position:absolute;z-index:10;text-align:center;width:100%;padding:0 24px;transition:opacity .6s}
.map-overlay-content h1{font-size:clamp(36px,6vw,72px);font-weight:900;background:linear-gradient(135deg,#FFCD05,#B3261E);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;line-height:1.1;margin-bottom:12px}
.map-overlay-content p{color:#FFFFFF;font-size:clamp(14px,1.5vw,18px);max-width:640px;margin:0 auto;line-height:1.6}
.map-scroll-hint{position:absolute;bottom:32px;left:50%;transform:translateX(-50%);color:#0B0B0B;font-size:12px;display:flex;flex-direction:column;align-items:center;gap:6px;z-index:20;transition:opacity .6s}
.map-scroll-hint .arrow{animation:bounce-arrow 2s ease-in-out infinite;width:16px;height:16px}
@keyframes bounce-arrow{0%,100%{transform:translateY(0)}50%{transform:translateY(6px)}}

.counties-section{padding:60px 20px;max-width:1200px;margin:0 auto;position:relative;z-index:5}
.section-tag{display:inline-flex;padding:5px 12px;border-radius:8px;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;background:#FFCD05;color:#0B0B0B;margin-bottom:8px}
.section-title{font-size:32px;font-weight:700;margin:0 0 8px;color:#FFFFFF}
.section-sub{color:#FFFFFF;font-size:14px;max-width:600px;margin:0 0 28px;line-height:1.5}

.filters-row{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-bottom:28px}
.search-field{width:100%;max-width:340px;padding:12px 16px;border-radius:12px;background:#FFFFFF;border:1px solid #0B0B0B;color:#FFFFFF;font-size:14px;outline:none;transition:border-color .3s}
.search-field:focus{border-color:#FFCD05}
.filter-pills{display:flex;gap:5px;flex-wrap:wrap}
.filter-pill{padding:5px 12px;border-radius:16px;font-size:11px;font-weight:600;cursor:pointer;transition:all .3s cubic-bezier(0.34,1.56,0.64,1);
  background:#FFFFFF;border:1px solid rgba(11,11,11,0.19);color:#FFFFFF}
.filter-pill.active,.filter-pill:hover{background:#B3261E;color:#FFFFFF;border-color:#B3261E;transform:translateY(-1px)}

.stats-row{display:flex;gap:32px;justify-content:center;padding:32px 16px;background:linear-gradient(90deg,transparent,#0B0B0B,transparent);margin:0 0 32px;border-radius:16px;flex-wrap:wrap}
.stat-cell{text-align:center}
.stat-number{font-size:28px;font-weight:900;color:#FFCD05;line-height:1;font-variant-numeric:tabular-nums}
.stat-label{color:#0B0B0B;font-size:12px;margin-top:4px;text-transform:uppercase;letter-spacing:1px}

.county-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px;padding:0 0 32px}
.county-card{background:#FFFFFF;border:1px solid rgba(11,11,11,0.19);border-radius:18px;overflow:hidden;
  transition:all .4s cubic-bezier(0.34,1.56,0.64,1)}
.county-card:hover{transform:translateY(-5px) scale(1.01);border-color:rgba(255,205,5,0.25);box-shadow:0 10px 32px rgba(255,205,5,0.06)}
.county-card-media{height:130px;border-radius:12px;margin:0;overflow:hidden;position:relative}
.county-card-emoji{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:44px}
.county-card-play{position:absolute;bottom:6px;right:6px;background:rgba(0,0,0,0.35);backdrop-filter:blur(4px);padding:3px 8px;border-radius:6px;font-size:9px;color:#FFFFFF;opacity:0;transition:opacity .3s}
.county-card:hover .county-card-play{opacity:1}
.county-card-body{padding:16px 16px 20px}
.county-card-name{font-size:17px;font-weight:700;color:#FFFFFF;margin:0 0 3px}
.county-card-desc{color:#FFFFFF;font-size:12px;line-height:1.5;margin:0 0 10px}
.county-card-tags{display:flex;gap:4px;flex-wrap:wrap;margin:0 0 10px}
.county-card-tag{padding:2px 7px;border-radius:5px;font-size:9px;font-weight:600;background:#B3261E;color:#FFFFFF}
.county-card-tag.gold{background:#FFCD05;color:#0B0B0B}
.county-card-tag.blue{background:#FFFFFF;color:#FFFFFF}
.county-card-tag.green{background:#FFFFFF;color:#FFFFFF}

.cta-3d-section{text-align:center;padding:48px 20px;background:linear-gradient(180deg,#0B0B0B,#0B0B0B);border-radius:24px;margin:32px 0}
</style>
@endpush

@section('content')
<div class="counties-3d-hero">
    <div class="max-w-7xl mx-auto px-5 w-full relative z-10 py-24">
        <div data-reveal="up">
            <div class="flex items-center gap-3 mb-4">
                <div class="h-px w-8 bg-kicc-gold"></div>
                <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">47 Destinations</span>
            </div>
            <h1 class="text-4xl md:text-6xl font-black text-white tracking-tight leading-[1.1]">
                Explore Kenya's <br><span class="text-kicc-gold">47 Counties</span>
            </h1>
            <p class="text-gray-400 mt-4 text-base md:text-lg max-w-2xl leading-relaxed">Discover economic sectors, investment opportunities, tourism attractions, and trade exhibitions across every county.</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-3 mt-10" data-reveal="up" data-reveal-delay="200">
            <div class="relative flex-1 max-w-md">
                <input id="county-search-input" placeholder="Search counties…"
                    class="search-field w-full pl-11 pr-4 h-12 rounded-xl bg-white/10 border border-white/10 text-white text-sm outline-none focus:ring-1 focus:ring-kicc-gold placeholder:text-gray-500 backdrop-blur-sm transition-all">
            </div>
        </div>
        <div class="filters-row mt-4" data-reveal="up" data-reveal-delay="300">
            <div class="filter-pills" id="region-filters">
                @foreach(['All','Central','Coast','Eastern','Nairobi','Nyanza','North Eastern','Rift Valley','Western'] as $i => $r)
                <button data-region="{{ $r }}"
                    class="filter-pill {{ $i === 0 ? 'active' : '' }}">{{ $r }}</button>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- ─── 3D SCROLL-DRIVEN MAP ─── --}}
<section class="map-scroll-section" id="mapScrollSection">
    <div class="map-scroll-sticky">
        <canvas id="kenya-3d-canvas"></canvas>
        <div class="map-overlay-content" id="mapOverlay">
            <h1>47 Counties<br><span style="color:#FFCD05;font-size:clamp(18px,2.5vw,30px);font-weight:600">One Kenya</span></h1>
            <p>From the tea highlands of Murang'a to the coral coast of Mombasa — scroll through every county's products, tourism, and opportunities.</p>
        </div>
        <div class="map-scroll-hint" id="scrollHint">
            <span>Scroll to explore counties</span>
            <svg class="arrow" viewBox="0 0 24 24" fill="none" stroke="#0B0B0B" stroke-width="2"><path d="M12 4l-4 4 8 4m-4-4 8 4"/></svg>
        </div>
    </div>
</section>

{{-- ─── COUNTY GRID ─── --}}
<section class="counties-section" id="countiesSection">
    <div class="section-tag">Explore Kenya</div>
    <h2 class="section-title">All <span style="color:#FFCD05">47 Counties</span></h2>
    <p class="section-sub">Search by name or filter by region — click any county to explore its sectors, businesses, and investment opportunities.</p>

    {{-- Stats --}}
    <div class="stats-row" id="statsRow">
        <div class="stat-cell"><div class="stat-number" data-count="47" data-duration="1000">0</div><div class="stat-label">Counties</div></div>
        <div class="stat-cell"><div class="stat-number" data-count="214" data-duration="1200">0</div><div class="stat-label">Trade Pipelines</div></div>
        <div class="stat-cell"><div class="stat-number" data-count="20" data-duration="800">0</div><div class="stat-label">Algorithms</div></div>
        <div class="stat-cell"><div class="stat-number" data-count="341" data-duration="1400">0</div><div class="stat-label">Services</div></div>
    </div>

    @if($counties->count() > 0)
    <div class="county-grid" id="countyGrid">
        @foreach($counties as $idx => $c)
        @php
        $ch = $countyHeroes[$c->slug] ?? null;
        $emojiList = ['🏔️','🏖️','🌋','🏕️','🏙️','🌊','🏞️','🌄','🌲','🏜️','🏘️','🏭','🏝️','🐘','🌿','🌾','🌲','🌋'];
        $tagList = $c->primary_sectors ?? ['tourism','agriculture'];
        $tagColors = ['blue','gold','green','','gold','blue','green'];
        $gradients = ['#B3261E30,#0B0B0B','#0B0B0B30,#0B0B0B','#0B0B0B30,#0B0B0B','#FFCD0530,#0B0B0B','#0B0B0B30,#0B0B0B'];
        $grad = $gradients[$idx % count($gradients)];
        $emoji = $emojiList[$idx % count($emojiList)];
        $delay = 80 + $idx * 50;
        @endphp
        <a href="{{ route('counties.show', $c->slug) }}"
           data-name="{{ strtolower($c->name) }}"
           data-region="{{ $c->former_province ?? '' }}"
           class="county-card"
           style="transition-delay:{{ $delay }}ms">
            <div class="county-card-media" style="background:linear-gradient(135deg,{{ $grad }})">
                <div class="county-card-emoji">{{ $emoji }}</div>
                @if($ch && $ch['video'])
                <x-media-tile
                    :poster="$ch['poster']"
                    :hover-loop="$ch['hover'] ?? null"
                    :video-url="$ch['video']"
                    :title="$c->name"
                    class="absolute inset-0 w-full h-full"
                />
                @elseif($ch && !empty($ch['image']))
                {{-- No county film of its own: this county's own still, not another county's film --}}
                <img src="{{ $ch['image'] }}" alt="{{ $c->name }}"
                     loading="lazy" class="absolute inset-0 w-full h-full object-cover" />
                @endif
                <div class="county-card-play">▶ Preview</div>
            </div>
            <div class="county-card-body">
                <div class="county-card-name">{{ $c->name }}</div>
                <div class="county-card-desc">
                    {{ is_array($c->primary_sectors) ? count($c->primary_sectors) : '0' }} sectors · {{ number_format($c->population_2024 ?? 0) }} people
                </div>
                @if(is_array($c->primary_sectors) && count($c->primary_sectors))
                <div class="county-card-tags">
                    @foreach(array_slice($c->primary_sectors, 0, 3) as $i2 => $ps)
                    <span class="county-card-tag {{ $tagColors[$i2 % count($tagColors)] ?? '' }}">{{ $ps }}</span>
                    @endforeach
                </div>
                @endif
                <span style="color:#0B0B0B;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:4px">
                    View County <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </span>
            </div>
        </a>
        @endforeach
    </div>

    <div class="mt-8 text-center py-8" id="countyNoResults" style="display:none">
        <p class="text-gray-500">No counties match your filter.</p>
        <button onclick="clearFilters()" class="mt-3 text-kicc-gold text-sm underline hover:text-white transition-colors">Clear filters</button>
    </div>
    @else
    <div class="text-center py-16">
        <p class="text-gray-500">County data will appear once the system is populated.</p>
    </div>
    @endif

    {{-- 3D CTA --}}
    <div class="cta-3d-section" data-reveal="up">
        <div style="font-size:40px;margin-bottom:8px">🌍</div>
        <h2 style="font-size:24px;font-weight:700;color:#FFFFFF;margin:0 0 6px">Explore in 3D</h2>
        <p style="color:#FFFFFF;font-size:14px;max-width:560px;margin:0 auto 16px">View Kenya's 47 counties as an interactive 3D extruded map — fly between regions, discover sectors, and dive into each county's unique profile.</p>
        <a href="{{ route('exhibition-3d.map') }}" style="display:inline-block;background:linear-gradient(135deg,#FFCD05,#FFCD05);color:#0B0B0B;padding:14px 32px;border-radius:12px;font-size:15px;font-weight:800;text-decoration:none">Open 3D Map →</a>
    </div>
</section>
@endsection

@push('scripts')
<script>
(function(){
    'use strict';

    /* ── 3D SCROLL-DRIVEN KENYA MAP ── */
    var kenyaCanvas = document.getElementById('kenya-3d-canvas');
    if(kenyaCanvas && window.gsap && window.ScrollTrigger){
        var ctx = kenyaCanvas.getContext('2d');
        var cw, ch;
        function resizeCanvas(){cw=kenyaCanvas.width=window.innerWidth;ch=kenyaCanvas.height=window.innerHeight}
        window.addEventListener('resize',resizeCanvas); resizeCanvas();

        // 47 county positions (procedural distribution)
        var countyPts = [];
        for(var i=0;i<47;i++){
            var angle = i/47 * Math.PI * 2;
            var r = 0.26 + 0.14 * Math.sin(i * 1.7);
            countyPts.push({x:0.5 + r*Math.cos(angle), y:0.5 + r*Math.sin(angle), h:0.1+0.12*Math.sin(i*2.3), s:8+18*Math.sin(i*1.3)});
        }

        ScrollTrigger.create({
            trigger:'#mapScrollSection', start:'top top', end:'bottom bottom',
            onUpdate: function(self){
                var pct = self.progress;
                var zoom = 1 + pct * 0.7;
                var dx = -pct * 0.06;
                var dy = -pct * 0.04;
                var extrude = Math.min(pct * 2, 1);

                ctx.fillStyle = '#0B0B0B';
                ctx.fillRect(0,0,cw,ch);

                // Draw extruded county blobs
                for(var i=0;i<countyPts.length;i++){
                    var pt = countyPts[i];
                    var cx = (pt.x + dx) * cw;
                    var cy = (pt.y + dy) * ch;
                    var rw = pt.s * zoom;
                    var rh = pt.s * zoom * 0.55;
                    var depth = pt.h * 50 * extrude;

                    // Extrusion side
                    ctx.fillStyle = 'rgba(179,38,30,'+(0.08+0.06*extrude)+')';
                    ctx.fillRect(cx - rw/2, cy - rh/2 + depth/2, rw, depth/2);

                    // Top face
                    ctx.fillStyle = 'rgba(255,205,5,'+(0.06+0.05*extrude)+')';
                    ctx.beginPath();
                    ctx.arc(cx, cy - depth, rw/2.2, 0, Math.PI*2);
                    ctx.fill();

                    // Center glow
                    ctx.fillStyle = 'rgba(255,205,5,'+(0.015+0.008*extrude)+')';
                    ctx.beginPath();
                    ctx.arc(cx, cy - depth, rw * 0.25, 0, Math.PI*2);
                    ctx.fill();
                }

                // Overlay fade
                var titleOpacity = 1 - Math.min(pct * 2.2, 1);
                var overlay = document.getElementById('mapOverlay');
                if(overlay) overlay.style.opacity = titleOpacity;
                var hint = document.getElementById('scrollHint');
                if(hint) hint.style.opacity = pct < 0.08 ? 1 : 1 - Math.min((pct-0.08)*6, 1);
            }
        });
    }

    /* ── COUNTY CARD STAGGER REVEAL ── */
    var cards = document.querySelectorAll('.county-card');
    cards.forEach(function(card, i){
        var delay = parseInt(card.style.transitionDelay) || (80 + i * 50);
        setTimeout(function(){
            card.classList.add('revealed');
        }, delay);
    });

    /* ── STATS COUNT-UP ── */
    document.querySelectorAll('[data-count]').forEach(function(el){
        var target = parseInt(el.getAttribute('data-count'));
        var duration = parseInt(el.getAttribute('data-duration')) || 1200;
        var startTime = null;
        function tick(now){
            if(!startTime) startTime = now;
            var p = Math.min((now - startTime) / duration, 1);
            var eased = 1 - Math.pow(1 - p, 3);
            el.textContent = Math.floor(eased * target).toLocaleString();
            if(p < 1) requestAnimationFrame(tick);
            else el.textContent = target.toLocaleString();
        }
        requestAnimationFrame(tick);
    });

    /* ── SEARCH + FILTER ── */
    var searchInput = document.getElementById('county-search-input');
    var regionBtns = document.querySelectorAll('.filter-pill');
    var countyCards = document.querySelectorAll('.county-card');
    var noResults = document.getElementById('countyNoResults');
    var activeRegion = 'All';

    function filterCounties(){
        var query = searchInput ? searchInput.value.toLowerCase() : '';
        var visible = 0;
        countyCards.forEach(function(card){
            var name = card.getAttribute('data-name') || '';
            var region = card.getAttribute('data-region') || '';
            var match = (query === '' || name.includes(query)) &&
                        (activeRegion === 'All' || region === activeRegion);
            card.style.display = match ? '' : 'none';
            if(match) visible++;
        });
        if(noResults) noResults.style.display = visible === 0 ? '' : 'none';
    }

    window.clearFilters = function(){
        if(searchInput) searchInput.value = '';
        activeRegion = 'All';
        regionBtns.forEach(function(b){
            b.classList.toggle('active', b.getAttribute('data-region') === 'All');
        });
        filterCounties();
    };

    if(searchInput) searchInput.addEventListener('input', filterCounties);

    regionBtns.forEach(function(btn){
        btn.addEventListener('click', function(){
            activeRegion = this.getAttribute('data-region');
            regionBtns.forEach(function(b){ b.classList.remove('active'); });
            this.classList.add('active');
            filterCounties();
        });
    });
})();
</script>
@endpush