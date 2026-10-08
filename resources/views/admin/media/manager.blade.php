@extends('layouts.admin-records')
@section('title', 'Video & media control')

@section('content')
<div class="ra-wrap">
  <div class="ra-head">
    <div>
      <p class="ra-eyebrow">Publishing · media</p>
      <h1 class="ra-h1">Video &amp; image control</h1>
      <p class="ra-muted">Every county, institution, KICC and the national portal owns its own rows. Bytes live in Cloudflare R2.</p>
    </div>
    <div class="wrapflex">
      <a class="ra-btn ra-btn-ghost" href="{{ route('admin.media.orphans') }}">R2 objects with no admin row →</a>
      <a class="ra-btn ra-btn-ghost" href="{{ route('admin.index') }}">← Publishing admin</a>
    </div>
  </div>

  @if(session('success'))<div class="rb-notice" role="status">{{ session('success') }}</div>@endif
  @if(isset($errors) && $errors->any())
    <div class="rb-errors" role="alert"><strong>Nothing was saved.</strong>
      <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
  @endif

  <div class="gk-toolbar">
    @foreach(['total'=>'All','county'=>'Counties','institution'=>'Institutions','kicc'=>'KICC','national'=>'National'] as $k=>$label)
      <a class="ra-pill {{ $scope===$k ? 'ready' : '' }}" href="{{ route('admin.media.index', array_filter(['scope'=>$k,'q'=>$q])) }}">{{ $label }} · {{ $counts[$k] ?? 0 }}</a>
    @endforeach
    <form method="GET" action="{{ route('admin.media.index') }}" class="gk-toolbar" style="margin:0">
      <div><label for="q">Search path / slot</label><input id="q" name="q" value="{{ $q }}" placeholder="counties/kilifi"></div>
      <button class="ra-btn" type="submit">Filter</button>
    </form>
  </div>

  <h2 class="ra-h2">Add a video or image to one entity</h2>
  <form class="rb-panel" method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data">
    @csrf
    <div class="rb-fields">
      <label>Owner scope
        <select name="scope" required>
          <option value="county">County</option>
          <option value="institution">Institution</option>
          <option value="kicc">KICC (the venue)</option>
          <option value="national">National Government portal</option>
        </select>
      </label>
      <label>Entity (id)
        <select name="owner_id" required>
          @foreach($targets as $t)
            <option value="{{ $t['owner_id'] }}" data-scope="{{ $t['scope'] }}">{{ $t['label'] }} — {{ $t['hint'] }} ({{ $t['count'] }} media)</option>
          @endforeach
        </select>
      </label>
      <label>Slot
        <select name="slot" required>
          @foreach($slots as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
        </select>
      </label>
      <label>File (mp4 / webm / mov / jpg / png / webp, ≤200 MB)
        <input type="file" name="file" required accept="video/mp4,video/webm,video/quicktime,image/jpeg,image/png,image/webp">
      </label>
      <label class="full">Alt text (used for accessibility and search)
        <input type="text" name="alt_text" maxlength="240" placeholder="Kilifi County — Bofa beach and fishing fleet">
      </label>
    </div>
    <button class="ra-btn" type="submit">Upload to R2 &amp; publish</button>
    <p class="ra-status">The row is only written once the object is confirmed present in R2.</p>
  </form>

  <h2 class="ra-h2" style="margin-top:38px">Media by entity — {{ $assets->total() }} row(s)</h2>
  <div class="gk-mgrid">
    @forelse($assets as $a)
      <article class="gk-mcard">
        <div class="gk-tile-media">
          @if($a->play_url)
            <video muted playsinline preload="metadata" controls poster="{{ $a->posterUrl() ?? '' }}">
              <source src="{{ $a->play_url }}">
            </video>
          @else
            <div class="rb-fallback"><span>{{ strtoupper(substr((string)$a->slot,0,2)) }}</span></div>
          @endif
          <span class="gk-badge {{ $a->in_r2 ? 'vid' : 'rep' }}">{{ $a->in_r2 ? 'in R2' : 'object missing' }}</span>
        </div>
        <div class="body">
          <span class="gk-pill {{ $a->status }}">{{ $a->status }}</span>
          <strong style="font-size:14px">{{ class_basename($a->owner_type) }} #{{ $a->owner_id }} · {{ $a->slot }}</strong>
          <span class="meta">{{ $a->path }}</span>
          <span class="meta">{{ $a->mime }} · {{ number_format(((int)$a->size_bytes)/1048576,1) }} MB · asset #{{ $a->id }}</span>
          <div class="row">
            <form method="POST" action="{{ route('admin.media.replace', $a) }}" enctype="multipart/form-data" style="display:flex;gap:8px;align-items:center">
              @csrf
              <input type="file" name="file" required accept="video/mp4,video/webm,video/quicktime,image/jpeg,image/png,image/webp" style="max-width:150px">
              <button class="ra-btn ra-btn-sm" type="submit">Replace</button>
            </form>
            <a class="ra-btn ra-btn-ghost ra-btn-sm" href="{{ route('admin.media.file', $a) }}" target="_blank" rel="noopener">Open</a>
            <form method="POST" action="{{ route('admin.media.destroy', $a) }}" onsubmit="return confirm('Delete asset #{{ $a->id }} and its R2 object(s)? This cannot be undone.')">
              @csrf @method('DELETE')
              <button class="ra-btn ra-btn-danger ra-btn-sm" type="submit">Delete + remove from R2</button>
            </form>
          </div>
        </div>
      </article>
    @empty
      <p class="ra-muted">No media rows for this filter.</p>
    @endforelse
  </div>

  <div class="rb-pagination">{{ $assets->links() }}</div>
</div>
@endsection
