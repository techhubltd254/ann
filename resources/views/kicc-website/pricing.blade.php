@extends('layouts.app')
@section('title', 'Pricing Guideline — KICC')
@section('content')
<div class="pt-20 max-w-4xl mx-auto px-5 py-10 prose prose-sm max-w-none">
    <h1>Pricing Guideline</h1>
    <p>KICC offers competitive rates for its world-class facilities. For detailed pricing, please contact our sales team at <strong>sales@kicc.co.ke</strong> or call <strong>(+254) 20 3261000</strong>.</p>
    <p>Pricing varies based on venue, duration, capacity requirements, and additional services (catering, AV, event planning).</p>
    <h2>Venue Capacity Overview</h2>
    <div class="overflow-x-auto mt-4"><table class="w-full text-sm"><thead><tr class="bg-gray-50"><th class="p-3 text-left">Venue</th><th class="p-3 text-left">Capacity</th><th class="p-3 text-left">Type</th></tr></thead><tbody>
    @foreach(App\Models\Venue::orderBy('name')->get() as $v)<tr class="border-b border-gray-100"><td class="p-3 font-semibold">{{ $v->name }}</td><td class="p-3">{{ number_format($v->capacity ?? 0) }}</td><td class="p-3 capitalize">{{ $v->venue_type ?? '—' }}</td></tr>@endforeach
    </tbody></table></div>
    <p class="mt-6"><a href="{{ route('kicc.event-booking') }}" class="inline-block h-11 px-6 rounded-xl bg-[#046bd2] text-white text-sm font-bold hover:bg-[#045cb4]">Request a Quote</a></p>
</div>
@endsection