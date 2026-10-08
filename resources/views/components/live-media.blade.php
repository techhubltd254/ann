@props(['record','type','override'=>null])
@php $m=$override??\App\Support\LiveExperienceMedia::resolve($record,$type); @endphp
<div class="tm-window" data-media-window>
@if($m['video']??null)<video muted loop playsinline preload="metadata" data-preview aria-label="{{ $record->name }} preview" @if($m['poster']??null) poster="{{ $m['poster'] }}" @endif><source src="{{ $m['video'] }}"></video><button class="rb-play" type="button" data-open-film data-film-src="{{ $m['video'] }}" data-film-title="{{ $record->name }}">Play experience →</button>
@elseif($m['poster']??null)<img src="{{ $m['poster'] }}" alt="{{ $record->name }}" loading="lazy">
@else<div class="rb-fallback"><div><span>{{ strtoupper(substr($record->name,0,2)) }}</span><p>No owner media is available for this listing.</p></div></div>@endif</div>
