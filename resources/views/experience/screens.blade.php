@extends('layouts.experience-live')
@section('title','Screens — KICC')
@section('content')
<x-experience.head
  eyebrow="Digital signage network"
  title="Kenya&#039;s Most Iconic Screens"
  lead="The screen network is driven by county and sector playlists. Open a screen to see its rotation and its live feed."
  :stats="['screens' => $screens->count()]"
/>

<section class="ex-page"><div class="wrap">
  <div class="ex-section">
    <div class="ex-grid">
      @forelse($screens as $s)
        @php
          $assets = $s->asset_count ?? null;
          $meta = collect([
            $s->location,
            $s->terminal_type ? ucfirst(str_replace('_',' ',$s->terminal_type)) : null,
            $s->refresh_interval_min ? 'Refreshes every '.$s->refresh_interval_min.' min' : null,
            $assets ? $assets.' assets' : null,
          ])->filter()->implode(' · ');
        @endphp
        <x-experience.card
          :href="route('screens.show',$s->id)"
          :media="$s->cover_image ?? null"
          :tag="$s->active ? 'Active' : 'Idle'"
          :tag-tone="$s->active ? null : 'red'"
          :meta="$meta"
          :title="$s->label"
          :copy="$s->location ? 'Positioned at '.$s->location.'. Carries county and sector content on rotation.' : 'A KICC digital screen carrying county and sector content.'"
          action="Open the screen"
          :initials="strtoupper(substr($s->label ?? 'SC',0,2))"
        />
      @empty
        <div class="ex-empty"><strong>No screens are registered yet</strong>Screens appear here once they are added to the network.</div>
      @endforelse
    </div>
  </div>
</div></section>
@endsection
