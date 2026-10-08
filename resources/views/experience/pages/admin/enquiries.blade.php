@extends('layouts.admin-records')
@section('admin-content')
<h2 class="ra-h2">Experience enquiries</h2>
@forelse($enquiries as $e)
  <article class="ra-card" style="margin-top:20px">
    <h3 class="ra-h3">{{ $e->record?->name ?? 'Unlinked record' }}</h3>
    <p class="ra-status">{{ $e->name }} · {{ $e->email }} · {{ $e->phone }} · {{ $e->created_at }}</p>
    <p style="font-size:14px;line-height:1.6;margin:16px 0">{{ $e->message }}</p>
    <form method="POST" action="{{ route('admin.enquiry.status', $e) }}">
      @csrf @method('PATCH')
      <label class="ra-field" style="max-width:240px">Status
        <select name="status">
          @foreach(['new', 'reviewed', 'closed'] as $s)<option @selected($e->status === $s)>{{ $s }}</option>@endforeach
        </select>
      </label>
      <button class="ra-btn ra-btn-ghost ra-btn-sm">Update status</button>
    </form>
  </article>
@empty
  <p class="ra-notice">No enquiries received yet.</p>
@endforelse
@include('partials.record-pagination', ['paginator' => $enquiries])
@endsection
