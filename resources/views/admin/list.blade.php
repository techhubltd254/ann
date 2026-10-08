@extends('layouts.admin-records')
@section('title', $label.' administration — KICC')
@section('admin-content')
<div class="ra-head" style="margin-bottom:16px">
  <h2 class="ra-h2" style="margin:0">{{ $label }}</h2>
  <a href="{{ route('admin.create', $type) }}" class="ra-btn">Add record</a>
</div>

<form method="GET" class="ra-card" style="margin-bottom:18px">
  <label class="ra-field" style="margin:0">Search records
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Name contains…">
  </label>
  <button class="ra-btn ra-btn-ghost" style="margin-top:10px">Search</button>
</form>

<div class="ra-table-wrap">
  <table class="ra-table">
    <thead><tr><th>Record</th><th>Status</th><th>Parent</th><th>Media</th><th>Actions</th></tr></thead>
    <tbody>
    @forelse($records as $record)
      <tr>
        <td><strong>{{ $record->name }}</strong><br><span class="ra-status">{{ $record->slug }}</span></td>
        <td><span class="ra-pill {{ $record->status === 'published' ? 'ra-pill-ok' : 'ra-pill-draft' }}">{{ $record->status }}</span></td>
        <td class="ra-status">{{ $record->parent?->name ?? '—' }}</td>
        <td>{{ $record->media->count() }}</td>
        <td><a href="{{ route('admin.edit', $record) }}">Edit / media →</a></td>
      </tr>
    @empty
      <tr><td colspan="5">No records of this type yet. Create the first one.</td></tr>
    @endforelse
    </tbody>
  </table>
</div>
@include('partials.record-pagination', ['paginator' => $records])
@endsection
