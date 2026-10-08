@extends('layouts.app')

@section('title', 'Booking Confirmed — ' . $product->name)

@section('content')
<div class="pt-28 pb-16">
    <div class="max-w-xl mx-auto px-5 text-center">
        <div class="w-16 h-16 rounded-full bg-[#0B0B0B]/10 flex items-center justify-center mx-auto mb-5">
            <svg class="w-8 h-8 text-[#0B0B0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h1 class="text-2xl font-black text-gray-900">Booking request received</h1>
        <p class="text-gray-500 mt-2">The seller will contact you on <span class="font-semibold text-gray-900">{{ $booking->customer_phone }}</span> to confirm payment and delivery.</p>

        <div class="mt-8 bg-white rounded-2xl border border-gray-200 p-6 text-left">
            <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-100">
                <span class="text-xs text-gray-400 uppercase tracking-wider font-bold">Reference</span>
                <span class="text-[#FFCD05] font-black text-lg">{{ $booking->reference }}</span>
            </div>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-gray-400">Product</span><span class="font-semibold text-gray-900">{{ $product->name }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">Quantity</span><span class="font-semibold text-gray-900">{{ $booking->quantity }} {{ $product->unit }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">Total</span><span class="font-black text-[#0B0B0B]">KES {{ number_format($booking->total) }}</span></div>
            </div>
        </div>

        <a href="{{ route('counties.show', $county->slug) }}" class="inline-flex items-center justify-center gap-2 mt-8 h-12 px-6 rounded-xl bg-[#B3261E] text-white text-sm font-black hover:bg-[#B3261E] transition-all">
            Back to {{ $county->name }}
        </a>
    </div>
</div>
@endsection
