@extends('layouts.app')
@section('title','Request for Quotation — KICC Marketplace')
@section('content')
<div class="pt-20 max-w-4xl mx-auto px-5 py-10">
<div class="flex items-center justify-between mb-6"><h1 class="text-2xl font-black text-gray-900">📋 My RFQs</h1><a href="{{ route('rfq.create') }}" class="text-sm font-bold bg-[#046bd2] text-white px-4 py-2 rounded-xl">New RFQ</a></div>
@forelse($quotes as $rfq)
<div class="bg-white border border-gray-200 rounded-2xl p-5 mb-3">
<div class="flex items-center justify-between"><div><span class="font-bold text-gray-900">{{ $rfq->rfq_number }}</span><span class="text-gray-400 text-xs ml-3">{{ $rfq->product_name }}</span></div><span class="text-xs font-bold px-2 py-1 rounded-full {{ $rfq->status === 'open' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">{{ $rfq->status }}</span></div>
<div class="text-sm text-gray-500 mt-2">Qty: {{ $rfq->quantity }} · Budget: KES {{ number_format($rfq->budget_min ?? 0) }} - {{ number_format($rfq->budget_max ?? 0) }}</div>
@if($rfq->quotes->count())<div class="mt-3 text-xs text-gray-400">{{ $rfq->quotes->count() }} quote(s) received</div>@endif
</div>
@empty
<div class="text-center py-20 text-gray-400"><p>No RFQs yet.</p><a href="{{ route('rfq.create') }}" class="inline-block mt-4 text-[#046bd2] font-bold">Create your first RFQ</a></div>
@endforelse
{{ $quotes->links() }}</div>@endsection