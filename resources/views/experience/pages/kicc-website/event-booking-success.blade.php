@extends('layouts.app')
@section('title', 'Event Booking Success — KICC')
@section('content')
<div class="pt-20 max-w-4xl mx-auto px-5 py-10">
    <h1 class="text-2xl font-black text-gray-900 mb-2">Event Booking Success</h1>
    <p class="text-gray-500 text-sm mb-6">Content coming soon. For inquiries, contact us at info@kicc.co.ke or call (+254) 20 3261000.</p>
    <a href="{{ route('kicc.event-booking') }}" class="inline-block h-11 px-6 rounded-xl bg-[#0B0B0B] text-white text-sm font-bold hover:bg-[#0B0B0B] transition-all">Book an Event</a>
</div>
@endsection
