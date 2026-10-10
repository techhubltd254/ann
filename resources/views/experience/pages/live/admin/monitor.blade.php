@extends('layouts.nexora')
@section('title','Live broadcast monitor')
@section('content')<section class="as-card"><h1>Live broadcast monitor</h1><p>Current native live booths. No synthetic live streams.</p>@forelse($liveBooths as $booth)<article><h2>{{ $booth->name??$booth->title??'Booth #'.$booth->id }}</h2><p>{{ $booth->status }} · {{ $booth->viewer_count??0 }} recorded viewers</p><p>Heartbeat: {{ $booth->heartbeat_health??'unknown' }} · {{ $booth->last_heartbeat??'not recorded' }}</p></article>@empty<p>No broadcasts are live.</p>@endforelse</section>@endsection
