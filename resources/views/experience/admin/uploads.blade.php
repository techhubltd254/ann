@extends('layouts.nexora')
@section('title','Entity video upload — KICC')
@section('content')
<section class="as-card" data-chunk-upload data-base="/portal/uploads">
 <span class="as-eyebrow">Content · R2 publishing</span><h1>Upload to the responsible owner.</h1>
 <p>Up to 2 GiB. Files travel in verified 4 MiB slices, not a single request through Cloudflare.</p>
 <div class="up-grid">
 <label>County<select data-up-county><option value="">Choose county</option>@foreach($counties as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></label>
 <label>Owner type<select data-up-owner-type><option value="App\Models\County">County</option><option value="App\Models\CountyInstitution">Institution</option>@if($venues->count())<option value="App\Models\Venue">KICC venue</option>@endif</select></label>
 <label>Owner<select data-up-owner-id><option value="">Choose responsible owner</option></select></label>
 <label>Linked sector<select data-up-sector disabled><option value="">Choose county or institution first</option></select></label>
 <label>Display slot<select data-up-slot><option value="hero_video">Hero video</option><option value="institution_video">Video collection</option><option value="4d_video">4D video</option><option value="flag_video">Flag video</option><option value="sector_video">Selected sector video</option></select></label>
 <label>Title<input type="text" data-up-title required maxlength="255"></label>
 <label>File<input type="file" data-up-file accept="video/mp4,video/webm,video/quicktime,video/x-matroska"></label>
 </div>
 <button type="button" class="as-cta" data-up-start>Upload & publish</button>
 <button type="button" class="as-cta" data-up-pause hidden>Pause upload</button><button type="button" class="as-cta" data-up-cancel hidden>Cancel pending upload</button><a data-up-preview hidden target="_blank" rel="noopener">Open public preview</a>
 <progress data-up-progress max="100" value="0"></progress>
 <p data-up-status role="status">Choose the county, responsible owner and linked sector before uploading.</p>
 <pre data-up-log hidden></pre>
 <script type="application/json" data-up-entities>@json(['counties'=>$counties,'institutions'=>$institutions,'venues'=>$venues])</script>
</section>
@endsection
@push('scripts')<script defer src="/js/chunk-upload.js?v=hierarchy-video-v1"></script>@endpush
