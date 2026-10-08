@extends('layouts.app')
@section('title', 'Visitor Facilities — KICC')
@section('content')
<div class="pt-20 max-w-4xl mx-auto px-5 py-10">
    <h1 class="text-3xl font-black text-gray-900 mb-6">Visitor Facilities</h1>
    @if(isset($page) && $page->content)
        <div class="prose prose-sm max-w-none">{!! $page->content !!}</div>
    @else
        <p class="text-gray-400">Content coming soon.</p>
    @endif
    <a href="{{ route('kicc.event-booking') }}" class="inline-block mt-6 h-11 px-6 rounded-xl bg-[#0B0B0B] text-white text-sm font-bold hover:bg-[#0B0B0B] transition-all">Book an Event</a>
</div>
@endsection