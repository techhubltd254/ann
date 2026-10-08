@extends('layouts.admin-records')
@section('admin-content')
<h2 class="ra-h2">Server audit history</h2>
<p class="ra-muted">Persisted create, update, publish, hierarchy and delete actions. This is not a tamper-proof compliance ledger.</p>
<div class="ra-table-wrap" style="margin-top:18px">
  <table class="ra-table">
    <thead><tr><th>Time</th><th>Action</th><th>Subject</th><th>User ID</th></tr></thead>
    <tbody>
    @forelse($events as $event)
      <tr><td>{{ $event->created_at }}</td><td>{{ $event->action }}</td><td>{{ $event->subject_id }}</td><td>{{ $event->user_id }}</td></tr>
    @empty
      <tr><td colspan="4">No audit events recorded yet.</td></tr>
    @endforelse
    </tbody>
  </table>
</div>
@include('partials.record-pagination', ['paginator' => $events])
@endsection
