@props(['record', 'hero' => false])
@php
  $assets = $record?->publicMedia ?? collect();
  $asset = $assets->first();
@endphp
<div style="border:1px solid #e4e4e7;border-radius:12px;overflow:hidden;background:#fff">
  @if($asset)
    @if($asset->isVideo())
      <video src="{{ $asset->url() }}" controls preload="metadata" style="display:block;width:100%;height:200px;object-fit:cover;background:#f4f4f5"></video>
    @else
      <img src="{{ $asset->url() }}" alt="{{ $asset->description ?: $asset->title }}" loading="{{ $hero ? 'eager' : 'lazy' }}" style="display:block;width:100%;height:200px;object-fit:cover;background:#f4f4f5">
    @endif
    <div style="padding:11px 13px"><span class="ra-status">{{ $asset->title }}</span></div>
  @else
    <div style="display:flex;align-items:center;justify-content:center;height:200px;background:#fafafa;text-align:center;padding:16px">
      <p class="ra-muted">Owner footage has not been published for this experience.</p>
    </div>
  @endif
</div>
