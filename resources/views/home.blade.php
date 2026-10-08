@extends('layouts.experience-live')
@section('main-class','home-main')
@section('content')
{{-- Chapter rail: mirrors the spec's chapter sequence. --}}
<nav class="chapter-rail" aria-label="Story chapters">@foreach(['nation'=>'The Nation','archive'=>'From the archive','halls'=>'The Halls','makers'=>'The Makers','government'=>'National Government','inside'=>'Step Inside','programme'=>'The Programme'] as $key=>$name)<a href="#chapter-{{ $key }}"><i></i><span>{{ $name }}</span></a>@endforeach</nav>

{{-- ═══ HERO — black film ground, frosted white glass plate ═══ --}}
<header class="sx-hero" id="chapter-top">
  @if($heroVideo??null)
    <div class="ed-bleed" data-depth><video muted playsinline loop autoplay data-preview aria-label="KICC published hero" @if($heroPoster??null) poster="{{ $heroPoster }}" @endif><source src="{{ $heroVideo }}"></video></div>
  @elseif($heroPoster??null)
    <div class="ed-bleed"><img src="{{ $heroPoster }}" alt="KICC"></div>
  @endif
  <div class="ed-layer wrap">
    <div class="ed-grid">
      <div class="ed-col-8">
        <div class="sx-glass">
          <div class="ed-label">Kenyatta International Convention Centre · Est. 1973</div>
          <h1 style="margin-top:26px"><span class="ed-mask"><span>The whole country,</span></span><span class="ed-mask"><span><em>in one room.</em></span></span></h1>
          <p class="ed-lead" style="margin-top:28px">Forty-seven county economies arrive here as goods, produce and people — and leave as trade.</p>
          <div class="wrapflex" style="margin-top:38px;gap:14px">
            <a href="#chapter-nation" class="btn gold">Enter the archive</a>
            <a href="#chapter-halls" class="btn ghost">Step inside a hall</a>
          </div>
          <div class="sx-chips sx-chips--light" style="margin-top:30px">
            <span class="sx-chip sx-chip--light"><b>{{ $counties->count() }}</b> counties</span>
            <span class="sx-chip sx-chip--light"><b>{{ $hallsCount }}</b> halls</span>
            <span class="sx-chip sx-chip--light"><b>{{ number_format($makersCount) }}</b> makers</span>
            @if($institutionCount)<span class="sx-chip sx-chip--light"><b>{{ number_format($institutionCount) }}</b> institutions</span>@endif
          </div>
        </div>
      </div>
    </div>
  </div>
  <a class="ed-handoff" href="#chapter-nation"><span class="n">01</span><span class="t">The Nation</span><span class="ln"></span><span class="s">Begin</span><span class="ar">↓</span></a>
</header>

{{-- ═══ CHAPTER 01 — THE NATION ═══ --}}
<section class="ed-chapter ed-flat" id="chapter-nation">
  <div class="ed-layer wrap">
    <div class="ed-grid">
      <div class="ed-col-5">
        <div class="ed-chapter-num">Chapter 01 — The Nation</div>
        <h2 style="margin-top:22px">Forty-seven<br>devolved units,<br>one pavilion floor.</h2>
      </div>
      <div class="ed-col-6 ed-offset-6">
        <p class="ed-lead">Every county sends its own economy to Nairobi — a sector, a population, a specialism. The archive holds all forty-seven, each with the sectors that define it.</p>
        <div class="ed-figure" style="margin-top:44px">{{ $counties->count() }}<small>Counties represented</small></div>
      </div>
    </div>
    <div class="county-orbit" data-orbit tabindex="0" role="region" aria-label="County experience orbit">
      <div class="orbit-stage">
        @foreach($counties as $i=>$c)
        <article class="orbit-screen" data-orbit-card>
          <div class="rb-fallback"><span>{{ strtoupper(substr($c->name,0,2)) }}</span></div>
          @if($countyHeroVideos[$c->slug]??null)<video muted loop playsinline preload="none" data-orbit-video><source src="{{ $countyHeroVideos[$c->slug] }}"></video>@endif
          @if(($countyHeroStates[$c->slug]??'')==='representative')<span class="gk-badge rep">no county film yet</span>@endif
          <a class="orbit-open" href="{{ route('counties.show',$c->slug) }}">Open →</a>
          <div class="orbit-caption">
            <span class="orbit-code">{{ $c->code??'' }}</span>
            <h3>{{ $c->name }}</h3>
            <p>{{ implode(' · ',array_slice(is_array($c->primary_sectors)?$c->primary_sectors:[],0,3)) }}</p>
          </div>
        </article>
        @endforeach
      </div>
      <div class="orbit-controls">
        <button class="btn ghost" type="button" data-orbit-prev aria-label="Previous county">←</button>
        <button class="btn ghost" type="button" data-orbit-pause>Pause rotation</button>
        <button class="btn ghost" type="button" data-orbit-next aria-label="Next county">→</button>
        <a href="/counties" class="btn ghost">All county portals →</a>
      </div>
    </div>
  </div>
  <a class="ed-handoff" href="#chapter-archive"><span class="n">—</span><span class="t">From the archive</span><span class="ln"></span><span class="ar">↓</span></a>
</section>

{{-- ═══ INTERLUDE — FROM THE ARCHIVE (the pipeline's own output) ═══ --}}
<section class="ed-interlude ed-flat sx-archive" id="chapter-archive">
  <div class="ed-layer wrap">
    <div class="ed-grid">
      <div class="ed-col-5">
        <div class="ed-label" style="color:var(--s-yellow)">Interlude — From the archive</div>
        <h2 style="margin-top:22px;color:var(--s-on-dark)">No image reaches<br>this page unfiltered.</h2>
      </div>
      <div class="ed-col-6 ed-offset-6">
        <p class="ed-lead">Every asset is captioned, tagged, quality-scored and assigned to a chapter before it is published. The strip below is that pipeline's output, read live from the store.</p>
        <div class="ed-figure" style="color:var(--s-yellow)">{{ $archiveCount }}<small style="color:var(--s-on-dark-muted)">Assets published</small></div>
      </div>
    </div>
    <div class="sx-strip" role="list" aria-label="Published media, read live from the store">
      @forelse($archive as $a)
        <article class="sx-slide" role="listitem">
          <div class="sx-slide-media">
            @if($a['video'])
              <video muted loop playsinline preload="metadata" data-preview @if($a['image']) poster="{{ $a['image'] }}" @endif><source src="{{ $a['video'] }}"></video>
            @elseif($a['image'])
              <img src="{{ $a['image'] }}" alt="{{ $a['owner'] }}" loading="lazy">
            @endif
            <span class="sx-kind">{{ $a['kind'] }}</span>
          </div>
          <div class="sx-slide-body">
            <div class="sx-owner">{{ $a['owner_type'] }} · {{ $a['slot'] }}</div>
            <h3>{{ $a['owner'] }}</h3>
          </div>
        </article>
      @empty
        <article class="sx-slide sx-slide--empty" role="listitem">The pipeline has published nothing yet — assets appear here the moment they pass the store.</article>
      @endforelse
    </div>
  </div>
  <a class="ed-handoff" href="#chapter-halls"><span class="n">02</span><span class="t">The Halls</span><span class="ln"></span><span class="s">Next</span><span class="ar">↓</span></a>
</section>

{{-- ═══ CHAPTER 02 — THE HALLS (dark plate) ═══ --}}
<section class="ed-chapter ed-plate" id="chapter-halls">
  <div class="ed-layer wrap">
    <div class="tm-head">
      <div>
        <div class="ed-chapter-num">Chapter 02 — The Halls</div>
        <h2 style="margin-top:22px">Ten rooms, from a boardroom<br>to two thousand seats.</h2>
        <p class="ed-lead">The Tsavo Hall holds a national assembly. The Helipad holds sixty people and a broadcast gantry. Both are bookable from this page.</p>
      </div>
      <a href="/venues" class="btn ghost">All venues →</a>
    </div>
    <x-live-gallery :records="$venues" type="venues"/>
  </div>
  <a class="ed-handoff" href="#chapter-makers"><span class="n">03</span><span class="t">The Makers</span><span class="ln"></span><span class="s">Next</span><span class="ar">↓</span></a>
</section>

{{-- ═══ CHAPTER 03 — THE MAKERS ═══ --}}
<section class="ed-chapter ed-flat" id="chapter-makers">
  <div class="ed-layer wrap">
    <div class="tm-head">
      <div>
        <div class="ed-chapter-num">Chapter 03 — The Makers</div>
        <h2 style="margin-top:22px">What the counties<br>send to market.</h2>
        <p class="ed-lead">Carved ebony from Mombasa. Cold-pressed coconut oil from Kilifi. Every listing is a named producer, a county, and a settled price — nothing in between.</p>
      </div>
      <a href="/marketplace" class="btn ghost">Enter the marketplace →</a>
    </div>
    <x-live-gallery :records="$products" type="products"/>
  </div>
  <a class="ed-handoff" href="#chapter-government"><span class="n">04</span><span class="t">Step Inside</span><span class="ln"></span><span class="s">Next</span><span class="ar">↓</span></a>
</section>

{{-- ═══ NATIONAL GOVERNMENT — preserved from the live build, restyled ═══ --}}
<section class="ed-chapter ed-flat" id="chapter-government">
  <div class="ed-layer wrap">
    <div class="ed-chapter-num">National Government Portal</div>
    <h2 style="margin-top:22px">The institutions<br>behind the nation.</h2>
    <p class="ed-lead" style="margin:30px 0">Ministries, agencies, economic sectors and public institutions remain part of the platform — not a feature lost in the redesign.</p>
    <div class="wrapflex">
      <a href="/national-government" class="btn gold">Explore national government →</a>
      <a href="/national-sector" class="btn ghost">Every economic sector →</a>
    </div>
  </div>
  <a class="ed-handoff" href="#chapter-inside"><span class="n">—</span><span class="t">Walk the room before you book the room</span><span class="ln"></span><span class="ar">↓</span></a>
</section>

{{-- ═══ CHAPTER 04 — STEP INSIDE (dark plate, 3D viewers) ═══ --}}
<section class="ed-chapter ed-plate" id="chapter-inside">
  <div class="ed-layer wrap">
    <div class="ed-chapter-num">Chapter 04 — Step Inside</div>
    <h2 style="margin-top:22px">Walk the room<br>before you book the room.</h2>
    <p class="ed-lead" style="max-width:62ch">Each hall is reconstructed from video as a 3D Gaussian splat. Open the viewer and the room is there — orbit it, read the sightlines, check the ceiling height.</p>
    <div class="sx-3d-grid">
      <article class="sx-3d">
        <div class="sx-3d-num">01 · Reconstructed rooms</div>
        <h3>Room 3D viewers</h3>
        <p>Rooms rebuilt from video and photographs by the pipeline, browsable in the viewer with measured sightlines.</p>
        <a href="/room3d" class="btn ghost">Open the room 3D viewer →</a>
      </article>
      <article class="sx-3d">
        <div class="sx-3d-num">02 · Kenya, in three dimensions</div>
        <h3>3D Kenya map</h3>
        <p>Terrain and sector geometry for the exhibition floor, drawn from the same published records as the counties.</p>
        <a href="/exhibition-3d/terrain" class="btn ghost">Open the 3D map →</a>
      </article>
      <article class="sx-3d">
        <div class="sx-3d-num">03 · Booth geometry</div>
        <h3>Booths &amp; sectors</h3>
        <p>Plan a booth against the real floor: sector blocks, aisle widths and stand footprints.</p>
        <a href="/exhibition-3d/booth" class="btn ghost">Open the booth planner →</a>
      </article>
    </div>
    <div class="sx-3d-note">Reconstructed from video · Gaussian splat · loads on demand</div>
  </div>
  <a class="ed-handoff" href="#chapter-programme"><span class="n">05</span><span class="t">The Programme</span><span class="ln"></span><span class="s">Next</span><span class="ar">↓</span></a>
</section>

{{-- ═══ CHAPTER 05 — THE PROGRAMME ═══ --}}
<section class="ed-chapter ed-flat" id="chapter-programme">
  <div class="ed-layer wrap">
    <div class="tm-head">
      <div>
        <div class="ed-chapter-num">Chapter 05 — The Programme</div>
        <h2 style="margin-top:22px">What is on,<br>and what is next.</h2>
      </div>
      <a href="/exhibitions" class="btn ghost">All exhibitions →</a>
    </div>
    <div class="sx-rows">
      @forelse($featuredExhibitions as $i=>$e)
        <a class="sx-row" href="{{ route('exhibitions.show',$e->slug) }}">
          <span class="sx-n">{{ str_pad($i+1,2,'0',STR_PAD_LEFT) }}</span>
          <span>
            <h3>{{ $e->name }}</h3>
            <span class="sx-meta">{{ $e->venue?->name ?? 'Tsavo Hall' }} · {{ $e->start_date?->format('M Y') ?? '2026' }}</span>
          </span>
          <span class="sx-go">View →</span>
        </a>
      @empty
        <div class="sx-row sx-row--empty">The programme is being published. Confirmed exhibitions appear here as soon as they are listed.</div>
      @endforelse
    </div>
    <div class="wrapflex" style="margin-top:34px;gap:14px">
      <a href="/exhibitions" class="btn gold">Book a booth</a>
      <a href="/streams" class="btn ghost">Watch live →</a>
    </div>
  </div>
</section>

{{-- ═══ CLOSE — one living archive ═══ --}}
<section class="ed-interlude ed-flat sx-close" id="chapter-close">
  <div class="ed-layer wrap">
    <div class="ed-label" style="color:var(--s-yellow)">One living archive</div>
    <h2 style="margin-top:24px">A national icon since 1973 —<br>now published as <em>one living archive.</em></h2>
    <div class="wrapflex">
      <a href="/counties" class="btn gold">Browse the counties</a>
      <a href="/marketplace" class="btn ghost">Buy from a maker</a>
      <a href="/venues" class="btn ghost">Book a hall</a>
    </div>
  </div>
</section>
@endsection
