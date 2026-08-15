@extends('layouts.app')
@section('title', 'KICC History')
@section('content')
<div class="pt-20 max-w-4xl mx-auto px-5 py-10">
    <div class="prose prose-sm max-w-none mb-8">{!! $page->content ?? '' !!}</div>
    <div class="grid sm:grid-cols-2 gap-4">
        @forelse($events as $e)
        <div class="bg-white border border-gray-200 rounded-2xl p-4">
            <div class="text-xs font-bold text-[#046bd2]">{{ $e->year }}</div>
            <div class="font-bold text-gray-900">{{ $e->title }}</div>
            @if($e->description)<div class="text-xs text-gray-500 mt-1">{{ $e->description }}</div>@endif
        </div>
        @empty <p class="text-gray-400 col-span-2">No timeline events yet.</p>
        @endforelse
    </div>
</div>
@endsection