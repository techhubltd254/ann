@extends('layouts.app')

@section('title', 'Your Cart — KICC Marketplace')

@section('content')
<div class="max-w-4xl mx-auto px-6 lg:px-8 py-12">
    <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight mb-8">Your Cart</h1>

    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-5 py-3 mb-6 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-amber-50 border border-amber-200 text-amber-700 rounded-xl px-5 py-3 mb-6 text-sm">{{ session('error') }}</div>
    @endif

    @if($cart->items->count())
    <div class="space-y-4 mb-8">
        @foreach($cart->items as $item)
        @php $product = $item->variant->product; @endphp
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-5">
            <img src="{{ $product->image_url }}" alt="" class="w-20 h-20 rounded-xl object-cover bg-gray-50"
                 onerror="this.src='{{ asset('storage/kicc/logo.png') }}'">
            <div class="flex-1">
                <h3 class="font-semibold text-gray-900">{{ $product->name }}</h3>
                <p class="text-sm text-gray-400">{{ $item->variant->name }}</p>
                <p class="text-sm font-bold text-gray-900 mt-1">KES {{ number_format($item->unit_price) }}</p>
            </div>
            <form method="POST" action="{{ route('cart.update', $item) }}" class="flex items-center gap-2">
                @csrf @method('PATCH')
                <input type="number" name="quantity" value="{{ $item->quantity }}" min="0" max="99"
                       class="w-16 border border-gray-200 rounded-lg px-2 py-2 text-center text-sm focus:outline-none focus:border-amber-500"
                       onchange="this.form.submit()">
            </form>
            <div class="text-right w-28">
                <div class="font-extrabold text-gray-900">KES {{ number_format($item->unit_price * $item->quantity) }}</div>
            </div>
            <form method="POST" action="{{ route('cart.destroy', $item) }}">
                @csrf @method('DELETE')
                <button class="text-gray-300 hover:text-amber-600 transition-colors" title="Remove">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </form>
        </div>
        @endforeach
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <div class="flex justify-between items-center mb-6">
            <span class="text-gray-500">Subtotal ({{ $cart->item_count }} items)</span>
            <span class="text-2xl font-extrabold text-gray-900">KES {{ number_format($cart->subtotal) }}</span>
        </div>
        <a href="{{ route('checkout.index') }}" class="btn-amber block text-center text-lg">Proceed to Checkout</a>
        <a href="{{ route('marketplace.index') }}" class="block text-center text-sm text-amber-600 font-medium mt-4 hover:text-amber-700">Continue shopping</a>
    </div>
    @else
    <div class="text-center py-20">
        <div class="w-16 h-16 bg-amber-50 rounded-2xl flex items-center justify-center mx-auto mb-5">
            <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        </div>
        <h3 class="text-xl font-bold text-gray-700 mb-2">Your cart is empty</h3>
        <p class="text-gray-500 mb-6">Browse products from Kenya's 47 counties.</p>
        <a href="{{ route('marketplace.index') }}" class="btn-amber inline-block">Explore Marketplace</a>
    </div>
    @endif
</div>
@endSection
