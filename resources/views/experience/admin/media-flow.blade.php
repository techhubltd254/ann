@extends('layouts.experience-live')
@section('title','Sequential media control — KICC')
@push('styles')<link rel="stylesheet" href="/css/media-flow.css?v=owner-video-v1">@endpush
@section('content')
<x-experience.head eyebrow="County → Institution → Sector → Media" title="Control the right owner's media." lead="The existing county_sector and sector_entities relationships determine every choice. Files are changed by stable media ID, never by their position in a list." />
<section class="wrap admin-module" data-media-flow data-base="{{ secure_url('/portal/media-flow') }}">
 <div class="media-flow-selectors">
  <label>1 · County<select data-flow-county><option value="">Choose county</option>@foreach($counties as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></label>
  <label>2 · Institution<select data-flow-institution disabled><option value="">Choose county first</option>@foreach($institutions as $i)<option value="{{ $i->id }}" data-county="{{ $i->county_id }}" data-slug="{{ $i->slug }}">{{ $i->name }}</option>@endforeach</select></label>
  <label>3 · Linked sector<select data-flow-sector disabled><option value="">Choose institution first</option></select></label>
  <label>4 · Media ID<select data-flow-media disabled><option value="">Choose sector first</option></select></label>
 </div>
 <p data-flow-status role="status">Choose the responsible county to start.</p>
 <div data-flow-display class="media-flow-panel"></div>
 <form data-flow-upload class="media-flow-panel" hidden>
  <h2>Add a video to this owner</h2>
  <p>Maximum 90 MiB per request. Larger files require a separately verified direct-upload workflow; this form does not claim 2 GB support.</p>
  <label>Title<input name="title" required maxlength="255"></label>
  <label>Display slot<select name="slot"><option value="institution_video">Institution video collection</option><option value="hero_video">Institution hero — changes the current public hero</option><option value="4d_video">4D video collection</option></select></label>
  <label>Video file<input name="video" type="file" accept="video/mp4,video/webm,video/quicktime" required></label>
  <button class="btn gold" type="submit">Upload to selected institution and sector</button>
 </form>
 <div data-flow-actions class="media-flow-panel" hidden>
  <h2>Selected video</h2><p data-selected-details></p>
  <video data-selected-preview controls playsinline preload="none" hidden></video>
  <form data-flow-replace><label>Replacement video<input name="video" type="file" accept="video/mp4,video/webm,video/quicktime" required></label><button type="submit" class="btn ghost">Replace this media ID</button></form>
  <button data-flow-delete type="button" class="btn crimson">Delete only this media ID</button>
 </div>
 <progress data-flow-progress max="100" value="0" hidden aria-label="Video upload progress"></progress>
</section>
@endsection
@push('scripts')<script defer src="/js/media-flow.js?v=owner-video-v1"></script>@endpush
