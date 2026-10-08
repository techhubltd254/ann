@props(['records', 'type'])
<div>
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
    <span class="ra-status">{{ $records->count() }} published experiences</span>
  </div>
  <div class="ra-media">
    @forelse($records as $r)
      <article class="ra-media-card">
        <x-record-media :record="$r" />
        <div class="ra-media-body">
          <p class="ra-status">{{ $r->payload['venue_type'] ?? $r->payload['category'] ?? ucfirst($r->type) }}</p>
          <h3 class="ra-h3">{{ $r->name }}</h3>
          <p class="ra-muted">{{ \Illuminate\Support\Str::limit($r->description, 170) }}</p>
          @if(isset($r->payload['price']))
            <p style="font-weight:700;margin-top:8px">KES {{ number_format($r->payload['price']) }}</p>
          @elseif(isset($r->payload['capacity']))
            <p style="font-weight:700;margin-top:8px">{{ number_format($r->payload['capacity']) }} seats</p>
          @endif
        </div>
      </article>
    @empty
      <p class="ra-notice">No {{ $type }} have been published yet.</p>
    @endforelse
  </div>
</div>
