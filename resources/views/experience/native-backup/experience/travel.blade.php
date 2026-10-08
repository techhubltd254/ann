@extends('layouts.experience-live')
@section('title','Travel — KICC')
@section('content')
<x-experience.head
  eyebrow="Arrive, stay, and see the country"
  title="Travel to KICC"
  lead="Attractions, hotels and destinations around every county hosting the programme. Weather-aware, so you pack for the right season."
  :stats="['attractions' => $attractions->count(), 'hotels' => $hotels->count(), 'destinations' => $destinations->count()]"
/>

<section class="ex-page"><div class="wrap">

  @if($destinations->isNotEmpty())
  <div class="ex-section">
    <div class="ex-section-head"><h2>Fly in</h2><span class="ex-count">{{ $destinations->count() }} destinations</span></div>
    <div class="ex-grid">
      @foreach($destinations as $d)
        <x-experience.card
          :href="$d->county_slug ? route('counties.show',$d->county_slug) : route('travel.flights')"
          :tag="$d->from_price ? 'From KSh '.number_format($d->from_price) : null"
          :meta="collect([$d->city ?? $d->name, $d->iata_code])->filter()->implode(' · ')"
          :title="$d->name"
          :copy="'Direct into '.$d->name.'. Cheapest fare shown for upcoming dates.'"
          action="See flights"
          :initials="strtoupper(substr($d->iata_code ?? $d->name,0,2))"
        />
      @endforeach
    </div>
  </div>
  @endif

  @if($attractions->isNotEmpty())
  <div class="ex-section">
    <div class="ex-section-head"><h2>See</h2><span class="ex-count">{{ $attractions->count() }} attractions</span></div>
    <div class="ex-grid">
      @foreach($attractions as $a)
        <x-experience.card
          :href="$a->county?->slug ? route('counties.show',$a->county->slug) : route('travel.index')"
          :media="$a->image_url"
          :tag="$a->category ? ucfirst($a->category) : null"
          :meta="collect([$a->county->name ?? null, $a->location])->filter()->implode(' · ')"
          :title="$a->name"
          :copy="\Illuminate\Support\Str::limit(strip_tags($a->description ?? ''),130)"
          action="Open"
          :initials="strtoupper(substr($a->name,0,2))"
          :admin="auth()->user()?->isAdmin() ? 'Replace or delete this image' : null"
          :admin-href="auth()->user()?->isAdmin() ? route('experience.images.index') : null"
        />
      @endforeach
    </div>
  </div>
  @endif

  @if($hotels->isNotEmpty())
  <div class="ex-section">
    <div class="ex-section-head"><h2>Stay</h2><span class="ex-count">{{ $hotels->count() }} hotels</span></div>
    <div class="ex-grid">
      @foreach($hotels as $h)
        <x-experience.card
          :href="$h->county?->slug ? route('counties.show',$h->county->slug) : route('travel.index')"
          :media="$h->image_url"
          :tag="$h->star_rating ? $h->star_rating.'★' : null"
          :meta="collect([$h->county->name ?? null, $h->location, $h->price_range_min ? 'KSh '.number_format($h->price_range_min).'+' : null])->filter()->implode(' · ')"
          :title="$h->name"
          :copy="\Illuminate\Support\Str::limit(strip_tags($h->description ?? ''),130)"
          action="Open"
          :initials="strtoupper(substr($h->name,0,2))"
          :admin="auth()->user()?->isAdmin() ? 'Replace or delete this image' : null"
          :admin-href="auth()->user()?->isAdmin() ? route('experience.images.index') : null"
        />
      @endforeach
    </div>
  </div>
  @endif

  <div class="ex-section">
    <div class="ex-section-head"><h2>Book a flight</h2></div>
    <div class="ex-grid">
      <div class="ex-empty" style="text-align:left">
        <strong>Search live fares</strong>
        Use the flight search to see schedules and prices into Nairobi and the regional airports.
        <div style="margin-top:22px"><a class="btn" href="{{ route('travel.flights') }}">Open flight search</a></div>
      </div>
    </div>
  </div>
</div></section>
@endsection
