@php
$actualOwner=$ownerType==='national_page'?\App\Models\County::class:$ownerType;
$actualId=$ownerType==='national_page'?0:$ownerId;
$versions=$mediaLibrary->filter(fn($a)=>$a->owner_type===$actualOwner&&(int)$a->owner_id===(int)$actualId&&($a->metadata['target_slot']??preg_replace('/^(draft|archived)__/','',$a->slot))===$slot);
@endphp
<section class="nm-card" data-national-upload data-owner-type="{{ $ownerType }}" data-owner-id="{{ $ownerId }}" data-slot="{{ $slot }}" data-actor-id="{{ auth()->id() }}" data-base="{{ route('admin.uploads') }}">
<header><div><span class="nm-eyebrow">{{ $liveAsset?'Currently published':'No published video' }}</span><h2>{{ $label }}</h2><p>{{ $help }}</p></div><span class="nm-status">{{ $liveAsset?'LIVE':'EMPTY' }}</span></header>
@if($liveAsset)
<video controls muted playsinline preload="metadata" poster="{{ $liveAsset->posterUrl() }}" src="{{ app(\App\Services\NationalMediaService::class)->stream($liveAsset) }}"></video>
@else
<div class="nm-empty">No live video. Upload below. Verified browser-ready video syncs publicly automatically.</div>
@endif
<form data-nm-form method="GET" action="{{ route('national.admin.v2.dashboard') }}">
<div class="nm-fields"><label>Video title<input data-nm-title required maxlength="255" value="{{ $label }}"></label><label>Video file · up to 2 GiB<input data-nm-file type="file" accept="video/mp4,video/webm,video/quicktime,video/x-matroska" required></label></div>
<label><input data-nm-auto type="checkbox" checked> Sync publicly automatically when processing finishes</label>
<div class="nm-actions"><button type="button" data-nm-start class="as-cta" disabled>Upload & sync to public</button><button type="button" data-nm-pause hidden>Pause</button><button type="button" data-nm-cancel hidden>Cancel pending upload</button></div>
<progress data-nm-progress max="100" value="0"></progress><p data-nm-status role="status" aria-live="polite">Safe 4 MiB chunks · resume by reselecting the same file · the live video stays untouched.</p>
<noscript>Enable JavaScript to upload large files. This form never sends a full video as one request.</noscript>
</form>
<div class="nm-versions"><h3>Processing & version history</h3>
@forelse($versions->take(5) as $asset)
<article data-nm-asset="{{ $asset->id }}"><div class="nm-between"><strong>{{ $asset->alt_text?:$asset->original_name }}</strong><span class="nm-status">{{ strtoupper($asset->metadata['publication']??($asset->slot===$slot?'published':'draft')) }} · {{ strtoupper($asset->status) }}</span></div><p>{{ number_format($asset->size_bytes/1048576,1) }} MiB · Asset {{ $asset->id }}</p>
@if($asset->status==='ready')
<details><summary>Preview this version</summary><video controls muted playsinline preload="none" poster="{{ $asset->posterUrl() }}" src="{{ app(\App\Services\NationalMediaService::class)->stream($asset) }}"></video></details>
@if($asset->slot!==$slot && $asset->derivatives->firstWhere('variant','stream-safe'))
<form method="POST" action="{{ route('national.media.publish',$asset->id) }}">@csrf
<button class="as-cta" type="submit">Publish this version</button></form>
@endif
@if($asset->slot===$slot)
<form method="POST" action="{{ route('national.media.unpublish',$asset->id) }}">@csrf
<button type="submit">Return to draft</button></form>
@endif
@elseif(($asset->metadata['playback']['state']??'')==='failed')
<p role="alert">Processing failed. The existing live video has not changed. Upload a compatible MP4 and retry.</p>
@else
<p data-nm-processing role="status">Processing browser-ready MP4 and poster. It will sync publicly automatically when ready.</p>
@endif
</article>
@empty
<p>No uploaded versions yet.</p>
@endforelse
</div></section>
