@extends('layouts.app')
@section('title','Images · replace and delete — KICC')
@section('content')
<x-experience.head eyebrow="KICC publishing · R2 + database" title="Every image.<br>Your control." lead="Uploaded media stays assigned to its exact entity ID and slot. Replacing an image preserves that mapping. Deleting media does not delete the county, institution or listing." />
<section class="ex-page"><div class="wrap">
 <nav class="ex-bar" aria-label="Publishing"><a class="btn ghost" href="{{ route('kicc.admin') }}">Full admin</a><a class="btn ghost" href="{{ route('media.library') }}">All media and pipelines</a><a class="btn ghost" href="{{ route('admin.hierarchy') }}">County → Sector → Institution</a></nav>
 <form class="ex-bar" method="GET"><label>Find an image <input type="search" name="q" value="{{ request('q') }}" placeholder="County slug, file or slot"></label><button class="btn" type="submit">Search</button><span>{{ $assets->total() }} image rows</span></form>
 <details class="rb-panel" style="margin:28px 0"><summary>Add an image to an entity</summary>
  <form method="POST" action="{{ route('experience.images.store') }}" enctype="multipart/form-data" data-owner-image-form class="rb-fields" style="margin-top:24px">@csrf
   <label>Entity type<select name="owner_type" required>@foreach($choices as $type => $items)<option value="{{ $type }}">{{ ucfirst($type) }}</option>@endforeach</select></label>
   <label>Exact entity ID<select name="owner_id" required>@foreach($choices as $type => $items)@foreach($items as $item)<option value="{{ $item->id }}" data-owner-type="{{ $type }}">#{{ $item->id }} · {{ $item->name }}</option>@endforeach @endforeach</select></label>
   <label>Placement<select name="slot"><option value="fallback_image">Image when no own film is published</option><option value="hero_image">Hero image</option></select></label>
   <label>File · JPG, PNG, WebP, max 10 MiB<input type="file" name="image" accept="image/jpeg,image/png,image/webp" required></label>
   <label>Alternative text<input name="alt_text" maxlength="255"></label>
   <label><input type="checkbox" name="illustrative" value="1"> Illustrative, not verified as depicting this entity</label>
   <button type="submit" class="btn">Upload to R2 and assign</button>
  </form>
 </details>
 <div class="ex-grid">
 @foreach($assets as $asset)
  @php $illustrative = ($asset->metadata['illustrative'] ?? false) || str_contains($asset->path, '/image/fallback-'); @endphp
  <article class="ex-card" data-image-id="{{ $asset->id }}">
   <div class="ex-shot"><img src="{{ $asset->url() }}" alt="{{ $asset->alt_text ?: $asset->original_name }}" loading="lazy">@if($illustrative)<span class="ex-illustration-note">Illustrative · not verified location</span>@endif</div>
   <div class="ex-body"><div class="ex-meta">{{ class_basename($asset->owner_type) }} #{{ $asset->owner_id }} · {{ $asset->slot }}</div>
    <h3>{{ $asset->original_name ?: 'Image #'.$asset->id }}</h3><p style="overflow-wrap:anywhere">{{ $asset->path }}</p>
    <form method="POST" action="{{ route('experience.images.replace',$asset->id) }}" enctype="multipart/form-data" style="display:grid;gap:12px">@csrf
     <label>Replacement file<input type="file" name="image" accept="image/jpeg,image/png,image/webp" required></label>
     <label>Alternative text<input name="alt_text" value="{{ $asset->alt_text }}" maxlength="255"></label>
     <label><input type="checkbox" name="illustrative" value="1" @checked($illustrative)> Illustrative image</label>
     <button class="btn" type="submit">Replace this image</button>
    </form>
    <form method="POST" action="{{ route('experience.images.destroy',$asset->id) }}" data-confirm="Remove this image from its entity? The listing remains. Unshared R2 media will be deleted." style="margin-top:12px">@csrf @method('DELETE')<button class="btn ghost" type="submit">Delete this image</button></form>
   </div>
  </article>
 @endforeach
 </div><div class="ex-pager">{{ $assets->links() }}</div>
</div></section>
@endsection
