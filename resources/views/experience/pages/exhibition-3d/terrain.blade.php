@extends('layouts.blank')

@section('title', 'Interactive Terrain Explorer — Kenya Circuit — KICC')

@push('styles')
<style>
  body { margin: 0; overflow: hidden; background: #FFFFFF; }
</style>
@endpush

@section('content')
<iframe src="{{ asset('3d/terrain_explorer.html') }}" style="width:100vw;height:100vh;border:none;display:block;"></iframe>
@endsection