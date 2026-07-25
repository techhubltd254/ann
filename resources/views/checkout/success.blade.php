@extends('layouts.app')

@section('title', 'Order Confirmed — KICC Marketplace')

@section('content')
<div class="max-w-2xl mx-auto px-5 py-16 text-center">
    <div class="w-20 h-20 bg-emerald-500/15 rounded-full flex items-center justify-center mx-auto mb-6 border border-emerald-500/25" data-reveal="zoom">
        <svg class="w-10 h-10 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
    </div>
    <h1 class="text-3xl font-black text-white tracking-tight mb-3" data-reveal>Order confirmed!</h1>
    <p class="text-white/40 mb-2" data-reveal>Order <strong class="text-white">{{ $order->order_number }}</strong></p>
    <p class="text-white/40 mb-8" data-reveal>Total <strong class="text-kicc-gold">KES {{ number_format($order->grand_total) }}</strong> · {{ $order->items->count() }} items</p>

    <div class="bg-[#0D1220] rounded-2xl border border-white/8 p-6 text-left mb-8" data-reveal>
        <h2 class="font-bold text-white mb-4">Items</h2>
        <div class="space-y-3">
            @foreach($order->items as $item)
            <div class="flex justify-between text-sm">
                <span class="text-white/70">{{ $item->product_name }} <span class="text-white/30">({{ $item->variant_name }}) × {{ $item->quantity }}</span></span>
                <span class="font-bold text-white">KES {{ number_format($item->total) }}</span>
            </div>
            @endforeach
        </div>
    </div>

    <p class="text-sm text-white/35 mb-8" data-reveal>Our team will contact you on the phone number provided to confirm payment via M-Pesa and arrange delivery.</p>

    <div class="flex justify-center gap-4" data-reveal>
        <a href="{{ route('marketplace.index') }}" data-magnetic class="inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-6 text-sm h-11 rounded-xl bg-kicc-gold text-[#07090F] hover:bg-[#e6b904]">Continue Shopping</a>
        <a href="{{ route('home') }}" data-magnetic class="inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-6 text-sm h-11 rounded-xl border border-white/25 text-white hover:bg-white/10">Back Home</a>
    </div>
</div>
@endsection
