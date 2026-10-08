{{-- One experience tile: media window, metadata, title, copy, action, admin hook. --}}
@props([
  'href'=>null,'media'=>null,'tag'=>null,'tagTone'=>null,'meta'=>null,
  'title'=>null,'copy'=>null,'action'=>'Open','admin'=>null,'adminHref'=>null,
  'initials'=>null,'mediaType'=>'image','poster'=>null,'illustrative'=>false,'mediaNote'=>null,
])
<article class="ex-card">
  <div class="ex-shot">
    @if($mediaNote)<span class="ex-illustration-note">{{ $mediaNote }}</span>@endif
    @if($illustrative)<span class="ex-illustration-note">Illustrative · not verified county imagery</span>@endif
    @if($tag)<span class="ex-tag {{ $tagTone==='red'?'ex-tag--red':'' }}">{{ $tag }}</span>@endif
    @if($media && $mediaType==='video')
      <video controls muted loop playsinline preload="metadata" data-ex-preview @if($poster) poster="{{ $poster }}" @endif>
        <source src="{{ $media }}" type="{{ str_contains($media,'.m3u8') ? 'application/vnd.apple.mpegurl' : 'video/mp4' }}">
      </video>
    @elseif($media)
      <img src="{{ $media }}" alt="{{ $title }}" loading="lazy">
    @else
      <div class="rb-fallback"><div><span>{{ $initials ?: 'KC' }}</span><p>No owner media is available for this listing.</p></div></div>
    @endif
  </div>
  <div class="ex-body">
    @if($meta)<div class="ex-meta">{{ $meta }}</div>@endif
    <h3>{{ $title }}</h3>
    @if($copy)<p>{{ $copy }}</p>@endif
    @if($href)<a class="ex-go" href="{{ $href }}">{{ $action }}</a>@endif
    @if($admin)<a class="ex-admin" href="{{ $adminHref }}">{{ $admin }}</a>@endif
  </div>
</article>
