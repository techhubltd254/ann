@extends('layouts.experience-live')
@section('title','Live — KICC')
@section('content')
<x-experience.head
  eyebrow="Live from the halls"
  title="Live Streams"
  lead="Live streams carry the exhibition floor to anyone who cannot be in the room."
  :stats="['live now' => $live->count(), 'upcoming' => $upcoming->count()]"
/>

<section class="ex-page"><div class="wrap">

  @if($live->isNotEmpty())
  <div class="ex-section">
    <div class="ex-section-head"><h2>On air</h2><span class="ex-count">{{ $live->count() }} live</span></div>
    <div class="ex-grid ex-grid--wide">
      @foreach($live as $s)
        <x-experience.card
          :href="route('streams.show',$s->id)"
          :media="$s->hls_url ?: $s->playback_url ?: $s->stream_url"
          media-type="video"
          :poster="$s->thumbnail_url"
          tag="Live"
          tag-tone="red"
          :meta="collect([$s->county->name ?? null, number_format($s->viewer_count ?? 0).' watching'])->filter()->implode(' · ')"
          :title="$s->name"
          :copy="\Illuminate\Support\Str::limit(strip_tags($s->description ?? ''),130)"
          action="Watch live"
          :initials="strtoupper(substr($s->name,0,2))"
        />
      @endforeach
    </div>
  </div>
  @endif

  @if($upcoming->isNotEmpty())
  <div class="ex-section">
    <div class="ex-section-head"><h2>Scheduled</h2><span class="ex-count">{{ $upcoming->count() }} upcoming</span></div>
    <div class="ex-grid">
      @foreach($upcoming as $s)
        <x-experience.card
          :href="route('streams.show',$s->id)"
          :media="$s->thumbnail_url"
          :meta="collect([$s->county->name ?? null, $s->scheduled_start_at ? \Illuminate\Support\Carbon::parse($s->scheduled_start_at)->format('d M · H:i') : null])->filter()->implode(' · ')"
          :title="$s->name"
          :copy="\Illuminate\Support\Str::limit(strip_tags($s->description ?? ''),130)"
          action="Open stream"
          :initials="strtoupper(substr($s->name,0,2))"
        />
      @endforeach
    </div>
  </div>
  @endif

  @if($live->isEmpty() && $upcoming->isEmpty())
  <div class="ex-section">
    <div class="ex-grid">
      <div class="ex-empty"><strong>Nothing is streaming right now</strong>When an exhibition goes live, its stream appears here automatically.</div>
    </div>
  </div>
  @endif

  @if($upcomingExhibitions->isNotEmpty())
  <div class="ex-section">
    <div class="ex-section-head"><h2>Next on the programme</h2></div>
    <div class="ex-grid">
      @foreach($upcomingExhibitions as $x)
        <x-experience.card
          :href="route('exhibitions.show',$x->slug)"
          :media="$x->cover_image ? (str_starts_with($x->cover_image,'http') ? $x->cover_image : '/media/video/'.$x->cover_image) : null"
          :meta="collect([$x->county->name ?? null, $x->start_date ? \Illuminate\Support\Carbon::parse($x->start_date)->format('d M Y') : null])->filter()->implode(' · ')"
          :title="$x->name"
          action="View exhibition"
          :initials="strtoupper(substr($x->name,0,2))"
        />
      @endforeach
    </div>
  </div>
  @endif
</div></section>
@endsection
