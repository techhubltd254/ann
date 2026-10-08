@extends('layouts.admin-records')
@section('title', 'R2 objects with no admin row')

@section('content')
<div class="ra-wrap">
  <div class="ra-head">
    <div>
      <p class="ra-eyebrow">Publishing · media · reconciliation</p>
      <h1 class="ra-h1">R2 objects with no admin row</h1>
      <p class="ra-muted">Every object in the bucket, checked against the admin tables. Nothing here is deleted automatically.</p>
    </div>
    <a class="ra-btn ra-btn-ghost" href="{{ route('admin.media.index') }}">← Video &amp; media control</a>
  </div>

  @if(session('success'))<div class="rb-notice" role="status">{{ session('success') }}</div>@endif

  <div class="rb-grid">
    <div class="rb-panel"><div class="ed-figure">{{ $report['objects'] }}<small>objects in R2</small></div></div>
    <div class="rb-panel"><div class="ed-figure">{{ $report['referenced'] }}<small>pointed at by an admin row</small></div></div>
    <div class="rb-panel"><div class="ed-figure">{{ count($report['orphans']) }}<small>unreferenced</small></div></div>
    <div class="rb-panel"><div class="ed-figure">{{ $report['db_missing_count'] }}<small>admin rows whose object is gone</small></div></div>
  </div>

  <h2 class="ra-h2" style="margin-top:34px">Unreferenced objects by category</h2>
  <table class="rb-table">
    <thead><tr><th>Category</th><th>Count</th></tr></thead>
    <tbody>
      @foreach($report['orphan_groups'] as $g=>$n)
        <tr><td>{{ $g }}</td><td>{{ $n }}</td></tr>
      @endforeach
    </tbody>
  </table>

  <h2 class="ra-h2" style="margin-top:34px">Delete unreferenced objects (tick the categories)</h2>
  <form class="rb-panel gk-danger" method="POST" action="{{ route('admin.media.orphans.purge') }}">
    @csrf
    @foreach($report['orphan_groups'] as $g=>$n)
      <label class="rb-checkbox" style="margin-bottom:8px">
        <input type="checkbox" name="groups[]" value="{{ $g }}">
        <span>{{ $g }} — {{ $n }} object(s)</span>
      </label>
    @endforeach
    <label style="display:block;margin-top:14px">Type DELETE to confirm
      <input type="text" name="confirm" required placeholder="DELETE" style="max-width:220px">
    </label>
    <button class="ra-btn ra-btn-danger" type="submit" style="margin-top:14px">Delete selected categories from R2</button>
    <p class="ra-status">Objects still referenced by an admin row are never in this list.</p>
  </form>

  <h2 class="ra-h2" style="margin-top:34px">Admin rows whose object is gone ({{ $report['db_missing_count'] }})</h2>
  <form method="POST" action="{{ route('admin.media.prune') }}">
    @csrf
    <button class="ra-btn" type="submit">Prune these rows (no R2 object is touched)</button>
  </form>
  <div style="max-height:420px;overflow:auto;margin-top:16px">
    <table class="rb-table">
      <thead><tr><th>#</th><th>owner</th><th>slot</th><th>path</th><th>status</th></tr></thead>
      <tbody>
        @foreach($report['db_missing'] as $r)
          <tr><td>{{ $r->id }}</td><td>{{ class_basename($r->owner_type) }} #{{ $r->owner_id }}</td><td>{{ $r->slot }}</td><td>{{ $r->path }}</td><td>{{ $r->status }}</td></tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <h2 class="ra-h2" style="margin-top:34px">Every unreferenced object ({{ count($report['orphans']) }})</h2>
  <div style="max-height:420px;overflow:auto">
    <table class="rb-table"><tbody>
      @foreach($report['orphans'] as $o)<tr><td>{{ $o }}</td></tr>@endforeach
    </tbody></table>
  </div>
</div>
@endsection
