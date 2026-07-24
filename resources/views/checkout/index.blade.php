@extends('layouts.app')

@section('title', 'Checkout — KICC Marketplace')

@section('content')
<div class="max-w-5xl mx-auto px-6 lg:px-8 py-12">
    <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight mb-8">Checkout</h1>

    <div class="grid lg:grid-cols-5 gap-8">
        {{-- Delivery form --}}
        <form method="POST" action="{{ route('checkout.store') }}" class="lg:col-span-3 bg-white rounded-2xl border border-gray-100 shadow-sm p-7">
            @csrf
            <h2 class="text-lg font-bold text-gray-900 mb-6">Delivery details</h2>
            @if($errors->any())
            <div class="bg-amber-50 border border-amber-200 text-amber-700 rounded-xl px-5 py-3 mb-6 text-sm">
                {{ $errors->first() }}
            </div>
            @endif
            <div class="grid sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Full name</label>
                    <input type="text" name="name" value="{{ old('name', auth()->user()?->name) }}" required
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone (M-Pesa)</label>
                    <input type="tel" name="phone" value="{{ old('phone', auth()->user()?->phone) }}" placeholder="07XXXXXXXX" required
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Email (optional)</label>
                    <input type="email" name="email" value="{{ old('email', auth()->user()?->email) }}"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">County</label>
                    <input type="text" name="county" value="{{ old('county') }}" required
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Town / Estate</label>
                    <input type="text" name="town" value="{{ old('town') }}" required
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-amber-500">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Delivery address</label>
                    <input type="text" name="address" value="{{ old('address') }}" required
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-amber-500">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Order notes (optional)</label>
                    <textarea name="notes" rows="2" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-amber-500">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="mt-8 border-t border-gray-100 pt-6">
                <h3 class="text-sm font-bold text-gray-900 mb-3">Payment</h3>
                <div class="flex items-center gap-3 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 text-sm text-emerald-800">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    <span><strong>M-Pesa on delivery confirmation.</strong> You'll receive an STK push on the phone number above. (Online STK push arrives in the payments phase.)</span>
                </div>
            </div>

            <button type="submit" class="btn-amber w-full text-center text-lg mt-8">Place Order — KES {{ number_format($cart->subtotal) }}</button>
        </form>

        {{-- Summary --}}
        <div class="lg:col-span-2">
            <div class="bg-charcoal rounded-2xl p-6 text-white sticky top-24">
                <h2 class="font-bold mb-5">Order summary</h2>
                <div class="space-y-3 mb-6">
                    @foreach($cart->items as $item)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-300">{{ $item->variant->product->name }} <span class="text-gray-500">× {{ $item->quantity }}</span></span>
                        <span class="font-medium">KES {{ number_format($item->unit_price * $item->quantity) }}</span>
                    </div>
                    @endforeach
                </div>
                <div class="border-t border-white/10 pt-4 space-y-2 text-sm">
                    <div class="flex justify-between text-gray-400"><span>Subtotal</span><span>KES {{ number_format($cart->subtotal) }}</span></div>
                    <div class="flex justify-between text-gray-400"><span>Delivery</span><span>Calculated after order</span></div>
                    <div class="flex justify-between text-lg font-extrabold text-white pt-2"><span>Total</span><span>KES {{ number_format($cart->subtotal) }}</span></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endSection
