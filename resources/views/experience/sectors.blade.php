@extends('layouts.experience-live')
@section('title','Sectors — KICC')
@section('content')
<x-experience.head
  eyebrow="The national economy, by sector"
  title="Thirteen sectors,<br>one national floor."
  lead="Each sector gathers the counties, institutions and producers that work inside it. Open a sector to see who is on the floor."
  :stats="['sectors' => $sectors->count()]"
/>

<section class="ex-page"><div class="wrap">
  <div class="ex-section">
    <div class="ex-grid">
      @forelse($sectors as $s)
        <x-experience.card
          :href="route('national.sector.show',$s['slug'])"
          :tag="$s['count'] ? $s['count'].' on the floor' : null"
          :meta="$s['emoji'] ? $s['emoji'].' · National sector' : 'National sector'"
          :title="$s['name']"
          :copy="\Illuminate\Support\Str::limit(strip_tags($s['description'] ?? ''),130)"
          action="Open the sector"
          :initials="strtoupper(substr($s['name'],0,2))"
        />
      @empty
        <div class="ex-empty"><strong>No sectors to show</strong>Sectors appear once county institutions are published against them.</div>
      @endforelse
    </div>
  </div>
</div></section>
@endsection
