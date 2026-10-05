@extends('layouts.app')
@section('title', 'National Government of Kenya — KICC')
@section('description', 'Kenya\'s national government ministries, state departments, agencies, and the 5 BETA delivery pillars.')

@push('styles')
<style>
.pillar-card{background:#1B1E3F;border:1px solid rgba(90,100,128,0.19);border-radius:18px;padding:28px;transition:all .4s cubic-bezier(0.34,1.56,0.64,1);opacity:0;transform:translateY(24px) scale(0.97)}
.pillar-card.revealed{opacity:1;transform:translateY(0) scale(1)}
.pillar-card:hover{transform:translateY(-4px);border-color:#FFCD0540;box-shadow:0 8px 24px rgba(255,205,5,0.05)}
.gov-stat{text-align:center}
.gov-stat-num{font-size:28px;font-weight:900;color:#FFCD05;line-height:1}
.gov-stat-label{color:#5A6480;font-size:11px;margin-top:3px;text-transform:uppercase;letter-spacing:1px}
</style>
@endpush

@section('content')
@php
$heroVideo = $heroVid ?? null;
$heroPoster = $heroPoster ?? media('kicc/national-hero.jpeg');
$pillars = [
['Agriculture & Food Security', 'Transforming Kenyan agriculture from subsistence to technology-driven — boosting food security and farmer incomes through KIAMIS, irrigation, subsidised inputs and market access.',
  '39% maize production increase; 730% livestock insurance growth; 7.1M+ farmers registered', 'https://delivery.go.ke/pillars'],
['Affordable Housing', 'Increasing Kenya\'s affordable housing supply from 2% to 50%, creating construction jobs and expanding mortgage access across all 47 counties.',
  '260,000+ units under construction; 640,000+ construction jobs; KSh 11B ring-fenced', 'https://delivery.go.ke/pillars'],
['MSME Economy', 'Reducing bureaucracy, providing affordable finance and building a credit culture for 7.4 million businesses through the Hustler Fund and eCitizen integration.',
  'KSh 82B+ disbursed to 26M+ Kenyans; 67% youth beneficiaries; 6M rated A-B credit', 'https://delivery.go.ke/pillars'],
['Universal Health Coverage', 'Providing every Kenyan with quality, affordable healthcare through the Social Health Authority — replacing NHIF with a universal, tax-funded model.',
  '28.5M+ Kenyans registered with SHA; 107,831+ CHPs trained; 8.82M+ households visited', 'https://delivery.go.ke/pillars'],
['Digital Superhighway & Creative Economy', 'Achieving universal broadband, digitising government services on eCitizen, empowering the creative economy and building a skilled digital workforce.',
  '23,000+ services digitized; 300,000+ youth employed via Ajira & Jitume; KES 1B daily revenue', 'https://delivery.go.ke/pillars'],
];
@endphp
<div class="pt-20">
    {{-- ─── HERO ─── --}}
    <div class="relative min-h-[60vh] md:min-h-[70vh] overflow-hidden bg-[#0A1024]">
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
                <span class="h-px w-8" style="background:#FFCD05;"></span>
                <span class="text-xs font-bold tracking-[0.2em] uppercase" style="color:#FFCD05;">National Government</span>
            </div>
            <h1 class="text-4xl md:text-6xl font-black leading-tight text-white" style="text-shadow:0 2px 20px rgba(0,0,0,0.3);">
                Kenya's <span style="color:#FFCD05;">Government</span>
            </h1>
            <p class="text-white/80 text-lg mt-3 max-w-2xl">Ministries, state departments, agencies, and the 5 BETA delivery pillars powering Kenya's digital economy.</p>
            <div class="flex gap-3 mt-4">
                <a href="{{ route('national.index') }}" class="px-5 py-2.5 rounded-lg font-semibold text-sm inline-flex items-center gap-2" style="background:#A6192E;color:white;">
                    <span class="animate-pulse">●</span> Live Exhibition
                </a>
                <a href="#ministries" class="px-5 py-2.5 rounded-lg font-semibold text-sm" style="background:rgba(255,255,255,0.15);color:white;backdrop-filter:blur(8px);">Explore Ministries →</a>
            </div>
        </div>
    </div>

    {{-- ─── BETA PILLARS ─── --}}
    <section class="max-w-7xl mx-auto px-5 py-12" id="pillars" style="background:#0A1024;">
        <div class="flex items-center gap-3 mb-6">
            <span class="h-px w-8" style="background:#FFCD05;"></span>
            <span class="text-xs font-bold tracking-[0.2em] uppercase" style="color:#FFCD05;">Government Delivery Unit</span>
            <span class="ml-auto inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-bold" style="background:#11820B;color:#fff;">Verified Reports</span>
        </div>
        <h2 class="text-3xl md:text-4xl font-black text-white leading-[1.1] mb-2">The 5 <span style="color:#FFCD05;">BETA Pillars</span></h2>
        <p class="text-gray-400 text-base max-w-2xl leading-relaxed">
            Flagship national development sectors tracked by the Government Delivery Unit.
            <a href="https://delivery.go.ke/delivery-corner" target="_blank" rel="noopener" class="text-[#1890D7] underline hover:text-white transition-colors text-sm">View on Delivery Corner →</a>
        </p>

        {{-- Stats --}}
        <div class="flex gap-6 justify-center py-6 flex-wrap" style="background:rgba(27,30,63,0.5);border-radius:12px;">
            <div class="gov-stat"><div class="gov-stat-num">247</div><div class="gov-stat-label">Field Reports</div></div>
            <div class="gov-stat"><div class="gov-stat-num">47</div><div class="gov-stat-label">Counties Covered</div></div>
            <div class="gov-stat"><div class="gov-stat-num">5</div><div class="gov-stat-label">Flagship Sectors</div></div>
            <div class="gov-stat"><div class="gov-stat-num">98%</div><div class="gov-stat-label">Verification Rate</div></div>
        </div>

        {{-- Pillar Cards (clickable) --}}
        <div class="grid sm:grid-cols-2 gap-5" style="margin-top:24px;">
            @foreach($pillars as $i => $p)
            <a href="{{ $p[3] }}" target="_blank" rel="noopener"
               class="pillar-card block text-left no-underline" style="transition-delay:{{ 80 + $i * 120 }}ms">
                <div class="flex items-center gap-3 mb-3">
                    <div style="font-size:16px;font-weight:700;color:#FFCD05;">0{{ $i + 1 }}</div>
                    <div style="font-size:17px;font-weight:700;color:#fff;">{{ $p[0] }}</div>
                </div>
                <p style="color:#B0B0C0;font-size:13px;line-height:1.6;">{{ $p[1] }}</p>
                <div style="display:flex;flex-wrap:wrap;gap:4px;margin-top:10px;">
                    @foreach(explode(';', $p[2]) as $pt)
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-semibold" style="background:rgba(255,205,5,0.12);color:#FFCD05;">{{ trim($pt) }}</span>
                    @endforeach
                </div>
                <span style="display:inline-flex;align-items:center;gap:6px;margin-top:10px;color:#1890D7;font-size:12px;font-weight:600;">
                    View on delivery.go.ke
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </span>
            </a>
            @endforeach
            {{-- Delivery Corner link card --}}
            <a href="https://delivery.go.ke/delivery-corner" target="_blank" rel="noopener"
               class="pillar-card block text-left no-underline" style="transition-delay:680ms;background:#0B1E57;border-color:rgba(255,255,255,0.1);">
                <div style="font-size:17px;font-weight:700;color:#FFCD05;margin-bottom:8px;">Delivery Corner</div>
                <p style="color:#B0B0C0;font-size:13px;line-height:1.5;">Verified field reports from the GDU Delivery Information Management team. Real sites, real status, real impact — every entry backed by a physical field visit.</p>
                <div class="flex flex-wrap gap-2 mt-3">
                    <span class="text-[10px] px-2 py-0.5 rounded-full" style="background:#11820B;color:#fff;">GDU Verified</span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full" style="background:rgba(90,100,128,0.3);color:#B0B0C0;">247 Reports</span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full" style="background:rgba(90,100,128,0.3);color:#B0B0C0;">47 Counties</span>
                </div>
                <span style="display:inline-flex;align-items:center;gap:6px;margin-top:10px;color:#1890D7;font-size:12px;font-weight:600;">
                    Browse all reports →
                </span>
                <div class="flex gap-2 mt-2">
                    <a href="https://delivery.go.ke/my-county" target="_blank" rel="noopener" style="color:#1890D7;font-size:11px;font-weight:600;text-decoration:none;">My County</a>
                    <span style="color:#5A6480;font-size:11px;">|</span>
                    <a href="https://delivery.go.ke/scorecards" target="_blank" rel="noopener" style="color:#1890D7;font-size:11px;font-weight:600;text-decoration:none;">Scorecards</a>
                    <span style="color:#5A6480;font-size:11px;">|</span>
                    <a href="https://delivery.go.ke/the-brief" target="_blank" rel="noopener" style="color:#1890D7;font-size:11px;font-weight:600;text-decoration:none;">The Brief</a>
                </div>
            </a>
        </div>
    </section>

    {{-- ─── MINISTRIES ─── --}}
    <section id="ministries" class="max-w-7xl mx-auto px-5 py-12" style="background:#F9FAFB;">
        <div class="flex items-center gap-3 mb-8">
            <span class="h-px w-8" style="background:#FFCD05;"></span>
            <span class="text-xs font-bold tracking-[0.2em] uppercase" style="color:#0B1E57;">Ministries</span>
            <span class="text-sm ml-auto" style="color:#8a94a6;">{{ $stats['ministries'] ?? 0 }} total</span>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($ministries as $idx => $m)
            @php
                $tile = $tileMedia[$m['slug']] ?? [];
                $tileHover = $tile['hoverLoopUrl'] ?? $tile['videoUrl'] ?? null;
                $tilePoster = $tile['posterUrl'] ?? null;
            @endphp
            <div class="bg-white rounded-2xl overflow-hidden border border-gray-200 hover:border-[#FFCD05]/40 transition-all" style="opacity:0;transform:translateY(20px);transition:all .4s cubic-bezier(0.34,1.56,0.64,1),opacity .4s;" data-stagger="{{ $idx }}"
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
                        <h3 class="font-bold text-sm" style="color:#0B1E57;">{{ $m['name'] }}</h3>
                        @if($m['description'])<p class="text-xs mt-1" style="color:#5A6480;">{{ Str::limit($m['description'], 100) }}</p>@endif
                        @if($m['agencies'])
                        <div class="mt-2 flex flex-wrap gap-1">
                            @foreach(array_slice($m['agencies'], 0, 3) as $a)
                            <span class="text-[10px] px-1.5 py-0.5 rounded-full" style="background:#f0f2f5;color:#8a94a6;">{{ $a['name'] }}</span>
                            @endforeach
                            @if(count($m['agencies']) > 3)
                            <span class="text-[10px] px-1.5 py-0.5 rounded-full font-semibold" style="color:#A6192E;">+{{ count($m['agencies']) - 3 }}</span>
                            @endif
                        </div>
                        @endif
                    </div>
                </a>
            </div>
            @empty
            <div class="col-span-3 text-center py-16" style="color:#8a94a6;">
                <p class="text-sm">No ministries listed yet.</p>
            </div>
            @endforelse
        </div>
        @if(!empty($agencies))
        <div class="mt-12">
            <h2 class="text-xl font-bold mb-4" style="color:#0B1E57;">All Agencies ({{ $stats['agencies'] ?? 0 }})</h2>
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
                @foreach($agencies as $a)
                <div class="p-3 rounded-xl" style="background:#F3F4F6;border:1px solid #E5E7EB;">
                    <div class="font-semibold text-sm" style="color:#0B1E57;">{{ $a['name'] }}</div>
                    <div class="text-xs mt-1" style="color:#8a94a6;">{{ $a['ministry_name'] }}</div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </section>
</div>

@push('scripts')
<script>
(function(){
    document.querySelectorAll('[data-stagger]').forEach(function(el,i){
        var delay = 80 + parseInt(el.getAttribute('data-stagger')) * 60;
        setTimeout(function(){
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
        }, delay);
    });
    document.querySelectorAll('.pillar-card').forEach(function(c,i){
        setTimeout(function(){ c.classList.add('revealed'); }, 80 + i * 120);
    });
})();
</script>
@endpush