@extends('layouts.blank')

@section('title', 'Exhibition Hall 3D Tour — KICC National Exhibition')

@push('styles')
<style>
  body { margin: 0; overflow: hidden; background: #0a0a12; }
  #back-link {
    position: fixed; top: 16px; left: 16px; z-index: 200;
    color: rgba(255,255,255,0.5); font-size: 13px; text-decoration: none;
    background: rgba(0,0,0,0.5); padding: 6px 14px; border-radius: 16px;
    backdrop-filter: blur(4px); font-family: system-ui, sans-serif;
  }
  #back-link:hover { color: #FFCD05; }
</style>
@endpush

@section('content')
<a id="back-link" href="{{ route('home') }}">← Back to Home</a>
<iframe src="{{ asset('3d/booth_viewer.html') }}" style="width:100vw;height:100vh;border:none;display:block;"></iframe>
@endsection
