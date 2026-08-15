@extends('layouts.app')
@section('title', 'Places To Stay — KICC')
@section('content')
<div class="pt-20 max-w-4xl mx-auto px-5 py-10 prose prose-sm max-w-none">
    <h1>Places To Stay</h1>
    <div>{!! $page->content ?? '<p>Content coming soon. For inquiries, contact us at info@kicc.co.ke or call (+254) 20 3261000.</p>' !!}</div>
    <a href="{{ route('kicc.event-booking') }}" class="inline-block mt-6 h-11 px-6 rounded-xl bg-[#046bd2] text-white text-sm font-bold hover:bg-[#045cb4] transition-all">Book an Event</a>
</div>
@endSection
