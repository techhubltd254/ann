@extends('layouts.nexora')
@section('title','Live broadcast analytics')
@section('content')<section class="as-card"><h1>Live broadcast analytics</h1><p>Native recorded activity; unavailable measurements are not invented.</p><pre style="white-space:pre-wrap">{{ json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre></section>@endsection
