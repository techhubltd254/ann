@extends('layouts.experience-live')
@section('main-class','home-main')
@section('content')
{{-- Hero Section --}}
<div class="hero-wrapper">
  <div class="hero-sheen"></div>
  <div class="hero-content">
    {{-- Full-width KICC Logo --}}
    <img src="{{ asset('kicclogo.png') }}" alt="KICC" class="kicc-logo-fullscreen" loading="lazy">
    
    {{-- Tagline --}}
    <p class="tagline">The whole country,<br><em>in one room.</em></p>
    
    {{-- Subtitle --}}
    <p class="subtitle">Forty-seven county economies arrive here as goods, produce and people — and leave as trade.</p>
    
    {{-- CTA Buttons --}}
    <div class="cta-buttons">
      <a href="#chapter-nation" class="btn gold">Enter the archive</a>
      <a href="#chapter-halls" class="btn ghost">Step inside a hall</a>
    </div>
  </div>
</div>

{{-- Chapter 01 — The Nation --}}
<section class="ed-chapter ed-flat" id="chapter-nation"><div class="ed-layer wrap"><div class="ed-grid"><div class="ed-col-5"><div class="ed-chapter-num">Chapter 01 — The Nation</div><h2 style="margin-top:22px">Forty-seven<br>devolved units,<br>one pavilion floor.</h2></div><div class="ed-col-6 ed-offset-6"><p class="ed-lead">Every county sends its own economy to Nairobi — a sector, a population, a specialism. Explore the original county records through their published experiences.</p><div class="ed-figure" style="margin-top:44px">{{ $counties->count() }}<small>County portals published</small></div></div></div><div class="county-orbit" data-orbit tabindex="0" role="region" aria-label="County experience orbit"><div class="orbit-stage">@foreach($counties as $i=>$c)<article class="orbit-screen" data-orbit-card><div class="rb-fallback"><span>{{ strtoupper(substr($c->name,0,2)) }}</span></div>@if($countyHeroVideos[$c->slug]??null)<video muted loop playsinline preload="none" data-orbit-video><source src="{{ $countyHeroVideos[$c->slug] }}"></video>@endif<a class="orbit-open" href="{{ route('counties.show',$c->slug) }}">Open →</a><div class="orbit-caption"><span class="orbit-code">{{ $c->code??'' }}</span><h3>{{ $c->name }}</h3><p>{{ implode(' · ',array_slice(is_array($c->primary_sectors)?$c->primary_sectors:[],0,3)) }}</p></div></article>@endforeach</div><div class="orbit-controls"><button class="btn ghost" type="button" data-orbit-prev aria-label="Previous county">←</button><button class="btn ghost" type="button" data-orbit-pause>Pause rotation</button><button class="btn ghost" type="button" data-orbit-next aria-label="Next county">→</button><a href="/counties" class="btn ghost">All county portals →</a></div></div></div><a class="ed-handoff" href="#chapter-halls"><span class="n">02</span><span class="t">From the counties, to the halls</span><span class="ln"></span><span class="ar">↓</span></a></section>

{{-- Chapter 02 — The Halls --}}
<section class="ed-chapter ed-flat" id="chapter-halls"><div class="ed-layer wrap"><div class="tm-head"><div><div class="ed-chapter-num">Chapter 02 — The Halls</div><h2 style="margin-top:22px">Rooms built<br>to hold a country.</h2><p class="ed-lead">Choose your setting through the films, photographs and details published by the venue team.</p></div><a href="/venues" class="btn ghost">All venues →</a></div><x-live-gallery :records="$venues" type="venues"/></div><a class="ed-handoff" href="#chapter-makers"><span class="n">03</span><span class="t">A place to meet. A reason to trade.</span><span class="ln"></span><span class="ar">↓</span></a></section>

{{-- Chapter 03 — The Makers --}}
<section class="ed-chapter ed-flat" id="chapter-makers"><div class="ed-layer wrap"><div class="tm-head"><div><div class="ed-chapter-num">Chapter 03 — The Makers</div><h2 style="margin-top:22px">What the<br>counties send<br>to market.</h2><p class="ed-lead">County producers and their products. See the making, inspect the details, and connect with the maker.</p></div><a href="/marketplace" class="btn ghost">Enter marketplace →</a></div><x-live-gallery :records="$products" type="products"/></div><a class="ed-handoff" href="#chapter-government"><span class="n">—</span><span class="t">The institutions behind the exhibition</span><span class="ln"></span><span class="ar">↓</span></a></section>

{{-- National Government Portal --}}
<section class="ed-chapter ed-flat" id="chapter-government"><div class="ed-layer wrap"><div class="ed-chapter-num">National Government Portal</div><h2 style="margin-top:22px">The institutions<br>behind the nation.</h2><p class="ed-lead" style="margin:30px 0">Ministries, agencies, economic sectors and public institutions remain part of the platform—not a feature lost in the redesign.</p><div class="wrapflex"><a href="/national-government" class="btn gold">Explore national government →</a><a href="/national-sector" class="btn ghost">Every economic sector →</a></div></div><a class="ed-handoff" href="#chapter-programme"><span class="n">04</span><span class="t">An invitation to come together</span><span class="ln"></span><span class="ar">↓</span></a></section>

{{-- The Programme --}}
<section class="ed-chapter ed-flat" id="chapter-programme"><div class="ed-layer wrap"><div class="tm-head"><div><div class="ed-chapter-num">The Programme</div><h2 style="margin-top:22px">What is on,<br>and what is next.</h2></div><a href="/exhibitions" class="btn ghost">All exhibitions →</a></div><x-live-gallery :records="$featuredExhibitions" type="exhibitions"/></div></section>
@endsection
