@extends('layouts.app')
@section('title', 'Pricing Guideline')
@section('content')
<div class="pt-20 max-w-4xl mx-auto px-5 py-10">
    <div class="prose prose-sm max-w-none mb-6">{!! $page->content ?? '' !!}</div>
    <p>For detailed pricing, contact our sales team at <strong>sales@kicc.co.ke</strong> or call <strong>(+254) 20 3261000</strong>.</p>
    <div class="overflow-x-auto mt-4">
        <table class="w-full text-sm"><thead><tr class="bg-gray-50"><th class="p-3 text-left">Venue</th><th class="p-3 text-left">Capacity</th><th class="p-3 text-left">Type</th></tr></thead><tbody>
        @foreach($venues as $v)<tr class="border-b border-gray-100"><td class="p-3 font-semibold">{{ $v->name }}</td><td class="p-3">{{ number_format($v->capacity ?? 0) }}</td><td class="p-3 capitalize">{{ $v->venue_type ?? '—' }}</td></tr>@endforeach
        </tbody></table>
    </div>
    <a href="{{ route('kicc.event-booking') }}" class="inline-block mt-6 h-11 px-6 rounded-xl bg-[#0B0B0B] text-white text-sm font-bold hover:bg-[#0B0B0B]">Request a Quote</a>
</div>
@endsection