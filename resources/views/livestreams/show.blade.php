@extends('layouts.app')
@section('title', $channel->name . ' — Live')
@section('content')
<div class="pt-20 max-w-5xl mx-auto px-5">
    <a href="{{ route('livestreams.index') }}" class="text-sm text-gray-500 hover:text-gray-900">&larr; All live channels</a>
    <h1 class="text-2xl font-black text-gray-900 mt-2 mb-4">{{ $channel->name }}</h1>
    <div class="rounded-2xl overflow-hidden bg-black border border-gray-200">
        <video controls autoplay muted playsinline class="w-full aspect-video" poster="{{ $channel->poster_url }}">
            <source src="{{ $channel->stream_url }}" type="video/mp4">
            Your browser does not support the video tag.
        </video>
    </div>
    <div class="flex items-center gap-3 mt-3 text-xs text-gray-500">
        @if($channel->is_live)<span class="font-bold text-[#b3261e]"> LIVE</span>@endif
        <span class="capitalize">{{ $channel->camera }} · {{ $channel->access_tier }} access</span>
    </div>
</div>
@endsection
