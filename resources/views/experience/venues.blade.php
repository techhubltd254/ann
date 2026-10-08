@extends('layouts.experience-live')
@section('title','Venues — KICC')
@section('content')
<x-experience.head
  eyebrow="Ten rooms, from a boardroom to two thousand seats"
  title="Walk the room<br>before you book it."
  lead="Every hall carries its own film, its capacity and its floor plan. Filter by county or type."
  :stats="['venues' => $venues->total(), 'counties' => $counties->count()]"
/>

<section class="ex-page"><div class="wrap">
  <div class="ex-bar">
    <form method="GET" action="{{ route('venues.index') }}">
      <input type="search" name="q" value="{{ request('q') }}" placeholder="Search venues" aria-label="Search venues">
      <select name="type" aria-label="Venue type">
        <option value="">All types</option>
        @foreach($venueTypes as $t)<option value="{{ $t }}" @selected(request('type')===$t)>{{ ucfirst($t) }}</option>@endforeach
      </select>
      <select name="min_capacity" aria-label="Minimum capacity">
        <option value="">Any capacity</option>
        @foreach([50,100,250,500,1000,2000] as $cap)
          <option value="{{ $cap }}" @selected(request('min_capacity')==$cap)>{{ $cap }}+ seats</option>
        @endforeach
      </select>
      <button class="btn" type="submit">Filter</button>
    </form>
    <span class="ex-count">{{ $venues->total() }} rooms</span>
  </div>

  <div class="ex-section">
    <div class="ex-grid">
      @forelse($venues as $v)
        @php $img = $v->cover_image ? (str_starts_with($v->cover_image,'http') ? $v->cover_image : '/media/video/'.$v->cover_image) : null; @endphp
        <x-experience.card
          :href="route('venues.show',$v->slug)"
          :media="$img"
          :tag="$v->venue_type ? ucfirst($v->venue_type) : null"
          :meta="collect([$v->city ?: $v->county, $v->capacity ? number_format($v->capacity).' seats' : null])->filter()->implode(' · ')"
          :title="$v->name"
          :copy="\Illuminate\Support\Str::limit(strip_tags($v->description ?? ''),130)"
          action="Open the room"
          :initials="strtoupper(substr($v->name,0,2))"
          :admin="auth()->check() ? 'Replace or delete this image' : null"
          :admin-href="auth()->check() ? route('admin.media.index') : null"
        />
      @empty
        <div class="ex-empty"><strong>No venues match</strong>Try a different county, type or capacity.</div>
      @endforelse
    </div>
    <div class="ex-pager">{{ $venues->withQueryString()->links() }}</div>
  </div>
</div></section>
@endsection
