@extends('layouts.app')
@section('title', 'Video Gallery — KICC')
@section('content')
<div class="pt-20 max-w-6xl mx-auto px-5 py-10">
    <h1 class="text-2xl font-black text-gray-900 mb-2">Video Gallery</h1>
    @if($videos->isEmpty())
    <p class="text-gray-400">No videos yet.</p>
    @else
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach($videos as $v)
        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
            <div class="aspect-video bg-gray-900 flex items-center justify-center text-white/30 text-4xl">▶</div>
            <div class="p-4"><h3 class="font-bold text-gray-900 text-sm">{{ $v->title }}</h3>@if($v->description)<p class="text-gray-500 text-xs mt-1">{{ $v->description }}</p>@endif</div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection