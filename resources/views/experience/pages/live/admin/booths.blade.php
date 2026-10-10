@extends('layouts.nexora')
@section('title','Live booth management')
@section('content')<section class="as-card"><h1>Live booth management</h1><form method="GET"><label>Status<input name="status" value="{{ request('status') }}"></label><label>Search<input name="search" value="{{ request('search') }}"></label><button class="as-cta">Filter</button></form>@forelse($booths as $booth)<article class="as-card"><h2>{{ $booth->name??$booth->title??'Booth #'.$booth->id }}</h2><p>{{ $booth->status }}</p><p>Stream: {{ $booth->stream_status??'idle' }} · Authorization: {{ $booth->authorization?->status??'not authorized' }}</p>
<form method="POST" action="{{ route('live.admin.authorize',$booth->id) }}">@csrf<button class="as-cta">Authorize booth</button></form>
<form method="POST" action="{{ route('live.admin.terminate',$booth->id) }}">@csrf<input name="reason_note" placeholder="Reason"><button class="as-cta">Terminate booth session</button></form>
<form method="POST" action="{{ route('live.admin.api-key',$booth->id) }}">@csrf<button class="as-cta">Generate private studio API key</button></form>
</article>@empty<p>No live booths found.</p>@endforelse{{ $booths->links() }}</section>@endsection
