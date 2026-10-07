@props(['record','hero'=>false])
@php $assets=$record?->publicMedia??collect(); $asset=$assets->first(); @endphp
<div class="tm-window" data-media-window>@if($asset)
 @if($asset->isVideo())<video muted loop playsinline preload="metadata" data-preview aria-label="{{ $asset->title }}"><source src="{{ $asset->url() }}" type="{{ $asset->mime }}"></video><button type="button" class="rb-play" data-open-film data-film-src="{{ $asset->url() }}" data-film-title="{{ $asset->title }}">Play full experience →</button>@else<img src="{{ $asset->url() }}" alt="{{ $asset->description ?: $asset->title }}" loading="{{ $hero?'eager':'lazy' }}">@endif
 <span class="tm-badge">{{ $asset->title }}</span>
 @else<div class="rb-fallback"><div><span>{{ strtoupper(substr($record?->name??'KICC',0,2)) }}</span><p>Owner footage has not been published for this experience.</p></div></div>@endif</div>
