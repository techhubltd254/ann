@extends('layouts.app')

@section('title', 'Your Cart — KICC Marketplace')

@section('content')
<div class="pt-20 max-w-4xl mx-auto px-5 py-10">
    <h1 class="text-3xl font-black text-[#0B1E57] mb-8">Your Cart</h1>

    @if($cart->items->count())
    <div class="space-y-4 mb-8">
        @foreach($cart->items as $item)
        @php $product = $item->variant->product; @endphp
        <div class="bg-white rounded-2xl border border-[#0B1E57]/8 p-5 flex items-center gap-5">
            <img src="{{ $product->image_url }}" alt="" class="w-16 h-16 rounded-xl object-cover bg-[#F9FAFB]"
                 onerror="this.src='{{ asset('storage/kicc/kicc-logo.png') }}'">
            <div class="flex-1">
                <h3 class="font-bold text-[#0B1E57]">{{ $product->name }}</h3>
                <p class="text-sm text-[#5A6480]">{{ $item->variant->name }}</p>
                <p class="text-sm font-bold text-kicc-gold mt-1">KES {{ number_format($item->unit_price) }}</p>
            </div>
            <form method="POST" action="{{ route('cart.update', $item) }}" class="flex items-center gap-2">
                @csrf @method('PATCH')
                <input type="number" name="quantity" value="{{ $item->quantity }}" min="0" max="99"
                       class="w-16 bg-[#F9FAFB] border border-[#0B1E57]/10 rounded-lg px-2 py-2 text-center text-sm text-[#0B1E57] outline-none focus:ring-1 focus:ring-kicc-gold"
                       onchange="this.form.submit()">
            </form>
            <div class="text-right w-28">
                <div class="font-black text-[#0B1E57]">KES {{ number_format($item->unit_price * $item->quantity) }}</div>
            </div>
            <form method="POST" action="{{ route('cart.destroy', $item) }}">
                @csrf @method('DELETE')
                <button class="text-[#0B1E57]/20 hover:text-[#901C1E] transition-colors" title="Remove">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </form>
        </div>
        @endforeach
    </div>

    <div class="bg-white rounded-2xl border border-[#0B1E57]/8 p-6">
        <div class="flex justify-between items-center mb-6">
            <span class="text-[#5A6480]">Subtotal ({{ $cart->item_count }} items)</span>
            <span class="text-2xl font-black text-kicc-gold">KES {{ number_format($cart->subtotal) }}</span>
        </div>
        <a href="{{ route('checkout.index') }}" class="block w-full text-center inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-8 text-base h-14 rounded-xl bg-[#901C1E] text-[#0B1E57] hover:bg-[#7b1618]">Proceed to Checkout</a>
        <a href="{{ route('marketplace.index') }}" class="block text-center text-sm text-kicc-gold font-bold mt-4 hover:underline">Continue shopping</a>
    </div>
    @else
    <div class="text-center py-20">
        <div class="w-16 h-16 rounded-2xl mx-auto mb-4 flex items-center justify-center bg-[#0B1E57]/5">
            <svg class="w-8 h-8 text-[#5A6480]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        </div>
        <h3 class="text-[#0B1E57] font-bold mb-2">Your cart is empty</h3>
        <p class="text-[#5A6480] mb-6">Browse products from Kenya's 47 counties.</p>
        <a href="{{ route('marketplace.index') }}" class="inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-8 text-base h-14 rounded-xl bg-[#901C1E] text-[#0B1E57] hover:bg-[#7b1618]">Explore Marketplace</a>
    </div>
    @endif
</div>
@endSection