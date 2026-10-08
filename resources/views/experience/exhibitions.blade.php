@extends('layouts.experience-live')
@section('title','Exhibitions — KICC')
@section('content')
<x-experience.head
  eyebrow="The programme"
  title="What is on,<br>and what is next."
  lead="Each exhibition is a chapter: its county, its halls, its booths and its live stream."
  :stats="['exhibitions' => $exhibitions->total()]"
/>

<section class="ex-page"><div class="wrap">
  <div class="ex-section">
    <div class="ex-grid">
      @forelse($exhibitions as $x)
        @php
          $isLive = in_array($x->id, $liveStreams ?? [], true);
          $img = $x->cover_image ? (str_starts_with($x->cover_image,'http') ? $x->cover_image : '/media/video/'.$x->cover_image) : null;
          $assigned = \App\Support\LiveExperienceMedia::resolve($x, 'exhibitions');
          $img = $assigned['poster'] ?? $img;
        @endphp
        <x-experience.card
          :href="route('exhibitions.show',$x->slug)"
          :media="$assigned['video'] ?? $img"
          :media-type="!empty($assigned['video']) ? 'video' : 'image'"
          :poster="$img"
          :illustrative="$assigned['illustrative'] ?? false"
          :tag="$isLive ? 'Live now' : ($x->is_featured ? 'Featured' : null)"
          :tag-tone="$isLive ? 'red' : null"
          :meta="collect([$x->county->name ?? null, $x->start_date ? \Illuminate\Support\Carbon::parse($x->start_date)->format('d M Y') : null, $x->booths_count.' booths'])->filter()->implode(' · ')"
          :title="$x->name"
          :copy="\Illuminate\Support\Str::limit(strip_tags($x->tagline ?? $x->description ?? ''),130)"
          action="View exhibition"
          :initials="strtoupper(substr($x->name,0,2))"
        />
      @empty
        <div class="ex-empty"><strong>No exhibitions are published yet</strong>Published exhibitions appear here automatically.</div>
      @endforelse
    </div>
    <div class="ex-pager">{{ $exhibitions->links() }}</div>
  </div>
</div></section>
@endsection
