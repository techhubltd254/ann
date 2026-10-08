@extends('layouts.experience-live')
@section('title','Counties — KICC')
@section('content')
<x-experience.head
  eyebrow="Forty-seven devolved units"
  title="Every county sends<br>its own economy."
  lead="Forty-seven counties, each with the sectors that define it. Open a county to walk its film, its institutions and the goods it brings to market."
  :stats="['counties represented' => $counties->count()]"
/>

<section class="ex-page"><div class="wrap">
  <div class="ex-bar">
    <form method="GET" action="{{ route('counties.index') }}">
      <input type="search" name="q" value="{{ request('q') }}" placeholder="Search a county, capital or sector" aria-label="Search counties">
      <button class="btn" type="submit">Search</button>
    </form>
    <div class="ex-view-controls"><button class="btn ghost" type="button" data-county-view="grid" aria-pressed="true">Grid</button><button class="btn ghost" type="button" data-county-view="strip" aria-pressed="false">Strip</button></div>
  </div>

  @if(auth()->user()?->isAdmin())
  <div class="ex-adminbar">
    <span>Signed in as an administrator — every image below can be replaced or deleted.</span>
    <a class="btn ghost sm" href="{{ route('experience.images.index') }}">Media library</a>
    <a class="btn ghost sm" href="{{ route('kicc.admin') }}">KICC admin</a>
  </div>
  @endif

  <div class="ex-section">
    <div class="ex-grid" data-county-results data-view="grid">
      @forelse($counties as $c)
        @php $h = $countyHeroes[$c->slug] ?? []; @endphp
        <x-experience.card
          :href="route('counties.show',$c->slug)"
          :media="$h['video'] ?? ($h['image'] ?? null)"
          :media-type="!empty($h['video']) ? 'video' : 'image'"
          :poster="$h['poster'] ?? ($h['image'] ?? null)"
          :tag="($h['state'] ?? '') === 'distinct' ? 'Own film' : null"
          :illustrative="!empty($h['image']) && str_contains($h['image'],'/image/fallback-')"
          :admin="auth()->user()?->isAdmin() ? 'Replace / delete media' : null"
          :admin-href="route('experience.images.index',['owner_type'=>'county','owner_id'=>$c->id])"
          :meta="($c->code ? $c->code.' · ' : '').($c->capital ?? '—').($c->economic_zone ? ' · '.$c->economic_zone : '')"
          :title="$c->name"
          :copy="\Illuminate\Support\Str::limit(strip_tags($c->tagline ?? $c->description ?? ''),130)"
          action="Enter the county"
          :initials="strtoupper(substr($c->name,0,2))"
        />
      @empty
        <div class="ex-empty"><strong>No counties to show</strong>Nothing matched this search.</div>
      @endforelse
    </div>
  </div>
</div></section>
@endsection
