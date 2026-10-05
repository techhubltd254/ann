@extends('layouts.app')
@section('title', 'National Government of Kenya — KICC')
@section('description', 'Kenya\'s national government ministries, state departments, agencies, and the 5 BETA delivery pillars.')

@push('styles')
<style>
.gov-hero{min-height:60vh;background:#0A1024;position:relative;overflow:hidden}
.gov-hero::before{content:'';position:absolute;inset:0;
  background:radial-gradient(ellipse at 30% 50%,rgba(166,25,46,0.12) 0%,transparent 60%),
    radial-gradient(ellipse at 70% 30%,rgba(255,205,5,0.08) 0%,transparent 50%);
  pointer-events:none}
.pillars-section{background:#0A1024;padding:60px 20px;max-width:1200px;margin:0 auto}
.pillar-card{background:#1B1E3F;border:1px solid rgba(90,100,128,0.19);border-radius:18px;padding:28px;
  transition:all .4s cubic-bezier(0.34,1.56,0.64,1);opacity:0;transform:translateY(24px) scale(0.97)}
.pillar-card.revealed{opacity:1;transform:translateY(0) scale(1)}
.pillar-card:hover{transform:translateY(-4px);border-color:rgba(255,205,5,0.2);box-shadow:0 8px 24px rgba(255,205,5,0.05)}
.gov-stats{display:flex;gap:24px;justify-content:center;padding:28px 16px;border-radius:16px;flex-wrap:wrap}
.gov-stat{text-align:center}
.gov-stat-num{font-size:28px;font-weight:900;color:#FFCD05;line-height:1}
.gov-stat-label{color:#5A6480;font-size:11px;margin-top:3px;text-transform:uppercase;letter-spacing:1px}
.delivery-badge{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:6px;font-size:10px;font-weight:700;
  background:linear-gradient(135deg,#11820B,#0A7530);color:#fff}
.ministry-card{background:#fff;border-radius:18px;overflow:hidden;border:1px solid #E5E7EB;
  transition:all .3s cubic-bezier(0.34,1.56,0.64,1);opacity:0;transform:translateY(20px)}
.ministry-card.revealed{opacity:1;transform:translateY(0)}
.ministry-card:hover{transform:translateY(-3px);border-color:#FFCD0540;box-shadow:0 6px 20px rgba(255,205,5,0.05)}
</style>
@endpush

@section('content')
@php
    $heroVideo = $heroVid ?? null;
    $heroPoster = $heroPoster ?? media('kicc/national-hero.jpeg');
    $betaPillars = [
        ['name'=>'Roads', 'emoji'=>'🛣️', 'desc'=>'Highway networks, rural access roads, bridges, and urban transport infrastructure across all 47 counties.', 'color'=>'#901C1E'],
        ['name'=>'Housing', 'emoji'=>'🏠', 'desc'=>'Affordable housing schemes, slum upgrading, mortgage access, and the Boma Yangu housing initiative.', 'color'=>'#1890D7'],
        ['name'=>'Agriculture', 'emoji'=>'🌾', 'desc'=>'Food security, farmer cooperatives, irrigation schemes, value addition, and export crop development.', 'color'=>'#11820B'],
        ['name'=>'Water', 'emoji'=>'💧', 'desc'=>'Bulk water supply, piped last-mile connections, dam construction, and water resource management.', 'color'=>'#0B1E57'],
        ['name'=>'Energy', 'emoji'=>'⚡', 'desc'=>'Grid connectivity, last-mile electrification, geothermal, solar mini-grids, and clean cooking.', 'color'=>'#FFCD05'],
    ];
@endphp
<div class="pt-20">
    {{-- ─── HERO ─── --}}
    <div class="gov-hero">
        @if($heroVideo)
        <video autoplay muted loop playsinline preload="metadata" class="absolute inset-0 w-full h-full object-cover" poster="{{ $heroPoster }}">
            <source src="{{ $heroVideo }}" type="video/mp4">
        </video>
        @else
        <div class="absolute inset-0" style="background:linear-gradient(135deg,#0B1E57 0%,#A6192E 50%,#0B1E57 100%);"></div>
        @endif
        <div class="absolute inset-0" style="background:linear-gradient(to top,rgba(0,0,0,0.8) 0%,rgba(0,0,0,0.2) 50%,transparent 100%);"></div>
        <div class="absolute bottom-0 left-0 right-0 max-w-7xl mx-auto px-5 pb-10 md:pb-16" style="z-index:5">
            <div class="flex items-center gap-3 mb-3">
                <span class="h-px w-8" style="background:var(--kicc-gold);"></span>
                <span class="text-xs font-bold tracking-[0.2em] uppercase" style="color:var(--kicc-gold);">National Government</span>
            </div>
            <h1 class="text-4xl md:text-6xl font-black leading-tight text-white" style="text-shadow:0 2px 20px rgba(0,0,0,0.3);">
                Kenya's <span style="color:var(--kicc-gold);">Government</span>
            </h1>
            <p class="text-white/80 text-lg mt-3 max-w-2xl">Ministries, state departments, agencies, and the 5 BETA delivery pillars powering Kenya's digital economy.</p>
            <div class="flex gap-3 mt-4">
                <a href="#pillars" class="px-5 py-2.5 rounded-lg font-semibold text-sm inline-flex items-center gap-2" style="background:linear-gradient(135deg,#901C1E,#7B1618);color:white;">
                    <span>🏛️</span> 5 BETA Pillars
                </a>
                <a href="{{ route('national.index') }}" class="px-5 py-2.5 rounded-lg font-semibold text-sm inline-flex items-center gap-2" style="background:var(--kicc-crimson);color:white;">
                    <span class="animate-pulse">●</span> Live Exhibition
                </a>
                <a href="#ministries" class="px-5 py-2.5 rounded-lg font-semibold text-sm" style="background:rgba(255,255,255,0.15);color:white;backdrop-filter:blur(8px);">Explore Ministries →</a>
            </div>
        </div>
    </div>

    {{-- ─── LIVE BOOTH ─── --}}
    <div class="max-w-7xl mx-auto px-5 mt-6">
        <x-live-booths-widget county-slug="national" />
    </div>

    {{-- ═══════════════════ BETA PILLARS ═══════════════════ --}}
    <section class="pillars-section" id="pillars">
        <div class="flex items-center gap-3 mb-6">
            <span class="h-px w-8" style="background:var(--kicc-gold);"></span>
            <span class="text-xs font-bold tracking-[0.2em] uppercase" style="color:var(--kicc-gold);">Government Delivery Unit</span>
            <span class="delivery-badge ml-auto">✓ GDU Verified</span>
        </div>
        <h2 class="text-3xl md:text-4xl font-black text-white leading-[1.1]">The 5 <span style="color:var(--kicc-gold);">BETA Pillars</span></h2>
        <p class="text-gray-400 mt-3 text-base max-w-xl leading-relaxed">
            Flagship national development sectors tracked by the Government Delivery Unit across all 47 counties.
            <a href="https://delivery.go.ke/delivery-corner" target="_blank" rel="noopener"
               class="text-kicc-gold underline hover:text-white transition-colors text-sm">View on Delivery Corner →</a>
        </p>

        {{-- Stats row --}}
        <div class="gov-stats">
            <div class="gov-stat"><div class="gov-stat-num">247</div><div class="gov-stat-label">Field Reports</div></div>
            <div class="gov-stat"><div class="gov-stat-num">47</div><div class="gov-stat-label">Counties Covered</div></div>
            <div class="gov-stat"><div class="gov-stat-num">5</div><div class="gov-stat-label">Flagship Sectors</div></div>
            <div class="gov-stat"><div class="gov-stat-num">98%</div><div class="gov-stat-label">Verification Rate</div></div>
        </div>

        {{-- Pillar cards --}}
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5" id="pillarGrid">
            @foreach($betaPillars as $i => $p)
            <div class="pillar-card" style="border-top:3px solid {{ $p['color'] }};transition-delay:{{ 80 + $i * 100 }}ms">
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
                    <span style="font-size:36px">{{ $p['emoji'] }}</span>
                    <div>
                        <div style="font-size:18px;font-weight:700;color:#fff">{{ $p['name'] }}</div>
                        <span class="delivery-badge">BETA Pillar</span>
                    </div>
                </div>
                <p style="color:#B0B0C0;font-size:13px;line-height:1.6">{{ $p['desc'] }}</p>
            </div>
            @endforeach
            {{-- Delivery Corner CTA card --}}
            <div class="pillar-card" style="transition-delay:580ms;background:linear-gradient(135deg,#0B1E57,#1B1E3F)">
                <div style="text-align:center;margin-bottom:8px">
                    <span style="font-size:36px">📋</span>
                    <div style="font-size:18px;font-weight:700;color:#fff;margin-top:4px">Delivery Corner</div>
                    <span class="delivery-badge">GDU Verified</span>
                </div>
                <p style="color:#B0B0C0;font-size:13px;line-height:1.6;margin-top:8px">
                    Verified field reports from the Government Delivery Unit. Real sites, real status, real impact.
                </p>
                <a href="https://delivery.go.ke/delivery-corner" target="_blank" rel="noopener"
                   style="display:inline-block;margin-top:12px;padding:10px 24px;border-radius:10px;
                          background:linear-gradient(135deg,#FFCD05,#E6B800);color:#0B1E57;font-size:14px;font-weight:800;text-decoration:none">
                    View All Reports →
                </a>
                <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:12px">
                    <a href="https://delivery.go.ke/my-county" target="_blank" rel="noopener"
                       style="color:#1890D7;font-size:12px;font-weight:600;text-decoration:none">My County →</a>
                    <a href="https://delivery.go.ke/scorecards" target="_blank" rel="noopener"
                       style="color:#1890D7;font-size:12px;font-weight:600;text-decoration:none">Scorecards →</a>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════ MINISTRIES ═══════════════════ --}}
    <section id="ministries" class="max-w-7xl mx-auto px-5 py-12" style="background:#F9FAFB">
        <div class="flex items-center gap-3 mb-8">
            <span class="h-px w-8" style="background:var(--kicc-gold);"></span>
            <span class="text-xs font-bold tracking-[0.2em] uppercase" style="color:var(--kicc-navy);">Ministries</span>
            <span class="text-sm ml-auto" style="color:var(--kicc-text-light);">{{ $stats['ministries'] ?? 0 }} total</span>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($ministries as $idx => $m)
            @php
                $tile = $tileMedia[$m['slug']] ?? [];
                $hasVideo = !empty($tile['videoUrl']);
                $tileHover = $tile['hoverLoopUrl'] ?? $tile['videoUrl'] ?? null;
                $tilePoster = $tile['posterUrl'] ?? null;
            @endphp
            <div class="ministry-card" style="transition-delay:{{ 80 + $idx * 60 }}ms"
                 x-data="mediaTile()"
                 @mouseenter="onHoverEnter()"
                 @mouseleave="onHoverLeave()">
                <a href="{{ route('national.site', $m['slug']) }}" class="block">
                    <div class="aspect-[4/3] overflow-hidden relative bg-gradient-to-br from-[#0A1024] to-[#1a1a2e]">
                        @if($tileHover)
                        <video x-ref="video" muted loop playsinline preload="auto"
                               class="absolute inset-0 w-full h-full object-cover transition-opacity duration-500"
                               :class="videoReady ? 'opacity-100' : 'opacity-0'"
                               poster="{{ $tilePoster ?? '' }}"
                               @playing="onVideoPlaying()">
                            <source src="{{ $tileHover }}" type="video/mp4">
                        </video>
                        @endif
                        <img src="{{ $tilePoster ?? '' }}" alt="{{ $m['name'] }}" loading="lazy"
                             class="absolute inset-0 w-full h-full object-cover transition-opacity duration-500"
                             :class="(videoReady && $tileHover) ? 'opacity-0' : 'opacity-100'"
                             onerror="this.remove()">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/10 to-transparent pointer-events-none"></div>
                    </div>
                    <div class="p-4">
                        <div class="flex items-center gap-3 mb-2">
                            <h3 class="font-bold text-sm" style="color:var(--kicc-navy);">{{ $m['name'] }}</h3>
                        </div>
                        @if($m['description'])<p class="text-xs" style="color:var(--kicc-text);">{{ Str::limit($m['description'], 100) }}</p>@endif
                        @if($m['agencies'])
                        <div class="mt-2 flex flex-wrap gap-1">
                            @foreach(array_slice($m['agencies'], 0, 3) as $a)
                            <span class="text-[10px] px-1.5 py-0.5 rounded-full" style="background:var(--kicc-bg-alt);color:var(--kicc-text-light);">{{ $a['name'] }}</span>
                            @endforeach
                            @if(count($m['agencies']) > 3)
                            <span class="text-[10px] px-1.5 py-0.5 rounded-full font-semibold" style="color:var(--kicc-crimson);">+{{ count($m['agencies']) - 3 }}</span>
                            @endif
                        </div>
                        @endif
                    </div>
                </a>
            </div>
            @empty
            <div class="col-span-3 text-center py-16" style="color:var(--kicc-text-light);">
                <div class="text-4xl mb-3">🏛️</div>
                <p class="text-sm">No ministries listed yet.</p>
            </div>
            @endforelse
        </div>

        @if(!empty($agencies))
        <div class="mt-12">
            <h2 class="text-xl font-bold mb-4" style="color:var(--kicc-navy);">All Agencies ({{ $stats['agencies'] ?? 0 }})</h2>
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
                @foreach($agencies as $a)
                <div class="p-3 rounded-xl" style="background:#F3F4F6;border:1px solid #E5E7EB">
                    <div class="font-semibold text-sm" style="color:var(--kicc-navy);">{{ $a['name'] }}</div>
                    <div class="text-xs mt-1" style="color:var(--kicc-text-light);">{{ $a['ministry_name'] }}</div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </section>
</div>
@endsection

@push('scripts')
<script>
(function(){
    /* Stagger pillar cards */
    document.querySelectorAll('.pillar-card').forEach(function(c,i){
        var delay = 80 + i * 100;
        setTimeout(function(){ c.classList.add('revealed'); }, delay);
    });
    /* Stagger ministry cards */
    document.querySelectorAll('.ministry-card').forEach(function(c,i){
        var delay = 80 + i * 60;
        setTimeout(function(){ c.classList.add('revealed'); }, delay);
    });
})();
</script>
@endpush