{{-- Shared experience page header. One shape, every tab. --}}
@props(['eyebrow','title','lead'=>null,'stats'=>[],'accent'=>null])
<section class="ex-hero">
  <div class="wrap">
    <div class="ex-eyebrow">{{ $eyebrow }}</div>
    <h1>{!! $title !!}</h1>
    @if($lead)<p class="ex-lead">{{ $lead }}</p>@endif
    @if(!empty($stats))
    <div class="ex-stats">
      @foreach($stats as $label=>$value)
        <span class="ex-stat"><b>{{ $value }}</b>{{ $label }}</span>
      @endforeach
    </div>
    @endif
  </div>
</section>
