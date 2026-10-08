@extends('layouts.admin-records')
@section('admin-content')
<div class="ra-card">
  <h2 class="ra-h2">Content, not a simulated dashboard.</h2>
  <p class="ra-muted" style="margin:14px 0 18px">Create records, upload owner footage, review the details, and publish the same database records the public pages read.</p>
  <p class="ra-status">{{ $mediaCount }} stored media files · {{ $newEnquiries }} new enquiries</p>
  <div class="ra-table-wrap" style="margin-top:18px">
    <table class="ra-table">
      <thead><tr><th>Module</th><th>Published</th><th>Draft</th><th>Open</th></tr></thead>
      <tbody>
      @foreach(config('kicc.types') as $type => $label)
        <tr>
          <td>{{ $label }}</td>
          <td>{{ $counts->where('type', $type)->where('status', 'published')->sum('total') }}</td>
          <td>{{ $counts->where('type', $type)->where('status', 'draft')->sum('total') }}</td>
          <td><a href="{{ route('admin.list', $type) }}">Manage →</a></td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
</div>
<div class="ra-notice">
  Records-based publishing is live against the same TiDB database the public pages read, with private media storage.
  Payments, inventory fulfilment, AI curation and video-to-3D reconstruction are not performed here.
  No simulated revenue or AI scores are shown.
</div>
@endsection
