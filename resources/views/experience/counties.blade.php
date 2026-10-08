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
    <span class="ex-count">Film or still · bound per county</span>
  </div>

  <div class="ex-section">
    <div class="ex-grid">
      @forelse($counties as $c)
        @php $h = $countyHeroes[$c->slug] ?? []; @endphp
        <x-experience.card
          :href="route('counties.show',$c->slug)"
          :media="$h['video'] ?? ($h['image'] ?? null)"
          :media-type="!empty($h['video']) ? 'video' : 'image'"
          :poster="$h['poster'] ?? ($h['image'] ?? null)"
          :tag="$h['state'] === 'distinct' ? 'Own film' : null"
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
