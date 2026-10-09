@extends('layouts.nexora')
@section('title','Bulk video upload (2 GB) — KICC')
@push('styles')<style>
.up-wrap{max-width:900px}.up-grid{display:grid;gap:14px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr))}
.up-wrap label{display:block;font:600 12px/1.6 system-ui;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-soft,#555)}
.up-wrap select,.up-wrap input{width:100%;padding:10px;border:1px solid rgba(0,0,0,.15);border-radius:10px;background:#fff}
.up-bar{height:12px;border-radius:99px;background:rgba(0,0,0,.08);overflow:hidden;margin-top:12px}
.up-bar>i{display:block;height:100%;width:0;background:#b3261e;transition:width .2s}
.up-log{margin-top:14px;font:12px/1.7 ui-monospace,monospace;white-space:pre-wrap;background:#111;color:#eee;padding:12px;border-radius:10px;max-height:240px;overflow:auto}
</style>@endpush
@section('content')
<x-experience.head eyebrow="Content · chunked upload" title="Two gigabytes, in slices." lead="Cloudflare refuses any request body over about 100 MB, so a 2 GB film is sent as 4 MiB slices, appended on the server and streamed to R2 as one object." :stats="['ceiling'=>'2 GiB','slice'=>'4 MiB','storage'=>'Cloudflare R2']" />
<section class="wrap admin-module up-wrap" data-chunk-upload data-base="/portal/uploads">
  <div class="up-grid">
    <label>1 · Owner type
      <select data-up-owner-type>
        <option value="App\\Models\\CountyInstitution">Institution</option>
        <option value="App\\Models\\County">County</option>
        <option value="App\\Models\\Venue">Venue</option>
      </select>
    </label>
    <label>2 · Owner ID<input type="number" data-up-owner-id min="1" placeholder="e.g. 12"></label>
    <label>3 · Slot
      <select data-up-slot>
        <option value="hero_video">Hero video — replaces the public hero</option>
        <option value="institution_video">Institution video collection</option>
        <option value="4d_video">4D video collection</option>
        <option value="flag_video">Flag video</option>
      </select>
    </label>
    <label>4 · Title<input type="text" data-up-title maxlength="255" placeholder="Mombasa — port of the coast"></label>
  </div>
  <label style="margin-top:14px">5 · Video file (up to 2 GB)
    <input type="file" accept="video/mp4,video/quicktime,video/webm,video/x-matroska" data-up-file>
  </label>
  <p><button class="as-btn" type="button" data-up-start>Upload to R2</button></p>
  <div class="up-bar"><i data-up-bar></i></div>
  <p data-up-status role="status">Choose an owner and a file. Nothing is written until you start.</p>
  <pre class="up-log" data-up-log hidden></pre>
</section>
@endsection
@push('scripts')<script defer src="/js/chunk-upload.js?v=chunk-1"></script>@endpush
