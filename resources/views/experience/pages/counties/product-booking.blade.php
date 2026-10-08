@extends('layouts.app')

@section('title', 'Book ' . $product->name . ' — ' . $county->name)

@section('content')
<div class="pt-28 pb-16">
    <div class="max-w-xl mx-auto px-5">
        <a href="{{ route('counties.show', $county->slug) }}" class="inline-flex items-center gap-1.5 text-gray-500 hover:text-[#B3261E] text-sm mb-6 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to {{ $county->name }}
        </a>

        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
            @if($product->image_url)
            <div class="h-48 overflow-hidden bg-gray-100">
                <x-fast-image :src="$product->image_url" :alt="$product->name" :width="640" :quality="75" class="w-full h-full" />
            </div>
            @endif
            <div class="p-6">
                <span class="text-[10px] font-bold text-[#0B0B0B] uppercase tracking-widest">{{ $county->name }} · {{ $product->category }}</span>
                <h1 class="text-xl font-black text-gray-900 mt-1">{{ $product->name }}</h1>
                <p class="text-gray-500 text-sm mt-2">{{ $product->description }}</p>
                <div class="mt-4 flex items-baseline gap-2">
                    <span class="text-2xl font-black text-[#0B0B0B]">KES {{ number_format($product->price) }}</span>
                    <span class="text-gray-400 text-sm">/ {{ $product->unit }}</span>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('county.product.booking.store', [$county->slug, $product->id]) }}" class="mt-6 bg-white rounded-2xl border border-gray-200 p-6" x-data="{ qty: 1, price: {{ $product->price }} }" x-init="$watch('qty', v => qty = Math.max(1, Math.min(100, parseInt(v) || 1)))">
            @csrf
            <h2 class="font-bold text-gray-900 mb-4">Your booking details</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Full name *</label>
                    <input type="text" name="customer_name" required class="w-full h-11 px-4 rounded-xl bg-[#FFFFFF] border border-gray-200 text-sm outline-none focus:ring-2 focus:ring-[#0B0B0B]/40" placeholder="John Kamau">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Email *</label>
                    <input type="email" name="customer_email" required class="w-full h-11 px-4 rounded-xl bg-[#FFFFFF] border border-gray-200 text-sm outline-none focus:ring-2 focus:ring-[#0B0B0B]/40" placeholder="john@example.com">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Phone (M-Pesa) *</label>
                    <input type="tel" name="customer_phone" required class="w-full h-11 px-4 rounded-xl bg-[#FFFFFF] border border-gray-200 text-sm outline-none focus:ring-2 focus:ring-[#0B0B0B]/40" placeholder="+254 712 345 678">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Quantity *</label>
                    <div class="flex items-center gap-3">
                        <button type="button" @click="qty = Math.max(1, qty - 1)" class="w-10 h-10 rounded-xl border border-gray-200 hover:bg-gray-50 font-bold text-gray-600">−</button>
                        <input type="number" name="quantity" x-model="qty" min="1" max="100" required class="w-20 h-10 px-3 rounded-xl bg-[#FFFFFF] border border-gray-200 text-center text-sm outline-none focus:ring-2 focus:ring-[#0B0B0B]/40">
                        <button type="button" @click="qty = Math.min(100, qty + 1)" class="w-10 h-10 rounded-xl border border-gray-200 hover:bg-gray-50 font-bold text-gray-600">+</button>
                        <span class="text-sm text-gray-500">× KES {{ number_format($product->price) }}</span>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Notes</label>
                    <textarea name="notes" rows="3" class="w-full px-4 py-3 rounded-xl bg-[#FFFFFF] border border-gray-200 text-sm outline-none focus:ring-2 focus:ring-[#0B0B0B]/40" placeholder="Delivery instructions, preferred pickup time…"></textarea>
                </div>
            </div>

            <div class="mt-6 pt-6 border-t border-gray-100 flex items-center justify-between">
                <div>
                    <div class="text-xs text-gray-400">Total due</div>
                    <div class="text-xl font-black text-[#0B0B0B]" x-text="'KES ' + (qty * price).toLocaleString()">KES {{ number_format($product->price) }}</div>
                </div>
                <button type="submit" class="h-12 px-6 rounded-xl bg-[#B3261E] text-white text-sm font-black hover:bg-[#B3261E] transition-all active:scale-[0.98]">Place Booking</button>
            </div>
        </form>
    </div>
</div>
@endsection
