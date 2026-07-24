@extends('layouts.app')

@section('title', 'Order Confirmed — KICC Marketplace')

@section('content')
<div class="max-w-2xl mx-auto px-6 lg:px-8 py-16 text-center">
    <div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-6">
        <svg class="w-10 h-10 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
    </div>
    <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight mb-3">Order confirmed!</h1>
    <p class="text-gray-500 mb-2">Order <strong class="text-gray-900">{{ $order->order_number }}</strong></p>
    <p class="text-gray-500 mb-8">Total <strong class="text-amber-600">KES {{ number_format($order->grand_total) }}</strong> · {{ $order->items->count() }} items</p>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 text-left mb-8">
        <h2 class="font-bold text-gray-900 mb-4">Items</h2>
        <div class="space-y-3">
            @foreach($order->items as $item)
            <div class="flex justify-between text-sm">
                <span class="text-gray-700">{{ $item->product_name }} <span class="text-gray-400">({{ $item->variant_name }}) × {{ $item->quantity }}</span></span>
                <span class="font-medium">KES {{ number_format($item->total) }}</span>
            </div>
            @endforeach
        </div>
    </div>

    <p class="text-sm text-gray-500 mb-8">Our team will contact you on the phone number provided to confirm payment via M-Pesa and arrange delivery.</p>

    <div class="flex justify-center gap-4">
        <a href="{{ route('marketplace.index') }}" class="btn-amber">Continue Shopping</a>
        <a href="{{ route('home') }}" class="btn-outline !text-gray-700 !border-gray-300">Back Home</a>
    </div>
</div>
@endSection
