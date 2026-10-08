@extends('layouts.app')
@section('title','RFQ Marketplace — KICC')
@section('content')
<div class="pt-20 max-w-4xl mx-auto px-5 py-10">
<h1 class="text-2xl font-black text-gray-900 mb-2"> RFQ Marketplace</h1>
<p class="text-gray-400 mb-6">Browse open requests and submit quotes to win business.</p>
<h2 class="font-bold text-gray-900 mb-3">Open Requests</h2>
@forelse($openRfqs as $rfq)
<div class="bg-white border border-gray-200 rounded-2xl p-5 mb-3">
<div class="flex items-start justify-between"><div><span class="font-bold text-gray-900">{{ $rfq->product_name }}</span><span class="text-gray-400 text-xs ml-3">Qty: {{ $rfq->quantity }}</span></div><span class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded-full font-bold">{{ $rfq->status }}</span></div>
<div class="text-sm text-gray-500 mt-1 line-clamp-2">{{ $rfq->specifications }}</div>
<div class="text-xs text-gray-400 mt-1">Budget: KES {{ number_format($rfq->budget_min ?? 0) }} - {{ number_format($rfq->budget_max ?? 0) }} · Deadline: {{ $rfq->deadline ?? 'N/A' }}</div>
<form method="POST" action="{{ route('rfq.quote', $rfq->id) }}" class="mt-3 flex gap-2">
@csrf
<input type="number" name="price" placeholder="Your price (KES)" step="1" class="flex-1 border border-gray-200 rounded-xl px-3 py-1.5 text-sm" required>
<textarea name="notes" placeholder="Notes..." rows="1" class="flex-1 border border-gray-200 rounded-xl px-3 py-1.5 text-sm"></textarea>
<button type="submit" class="bg-[#0B0B0B] text-white font-bold px-4 py-1.5 rounded-xl text-sm">Quote</button>
</form>
</div>
@empty
<p class="text-gray-400 text-center py-10">No open RFQs.</p>
@endforelse
@if($myQuotes->count())
<h2 class="font-bold text-gray-900 mt-8 mb-3">My Quotes</h2>
@foreach($myQuotes as $q)
<div class="bg-white border border-gray-200 rounded-2xl p-4 mb-2 flex justify-between items-center"><div><span class="font-bold text-sm text-gray-900">{{ $q->rfq->product_name }}</span><span class="text-gray-400 text-xs ml-3">{{ $q->rfq->buyer->name ?? 'Buyer' }}</span></div><div class="text-right"><span class="font-bold text-sm">KES {{ number_format($q->price) }}</span><span class="text-xs text-gray-400 ml-2 capitalize">{{ $q->status }}</span></div></div>
@endforeach
@endif
</div>@endsection