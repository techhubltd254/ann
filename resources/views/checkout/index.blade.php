@extends('layouts.app')

@section('title', 'Checkout — KICC Marketplace')

@section('content')
<div class="pt-20 max-w-5xl mx-auto px-5 py-10">
    <h1 class="text-3xl font-black text-[#0B1E57] mb-8">Checkout</h1>

    <div class="grid lg:grid-cols-5 gap-8">
        <form method="POST" action="{{ route('checkout.store') }}" class="lg:col-span-3 bg-white border border-[#0B1E57]/10 rounded-2xl p-7">
            @csrf
            <h2 class="text-lg font-black text-[#0B1E57] mb-6">Delivery details</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-[10px] font-bold text-[#0B1E57]/35 uppercase tracking-wider mb-1.5">Full name</label>
                    <input type="text" name="name" required class="w-full bg-[#F9FAFB] border border-[#0B1E57]/10 focus:border-[#FFCD05]/50 rounded-xl px-4 py-2.5 text-sm text-[#0B1E57] outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-[#0B1E57]/35 uppercase tracking-wider mb-1.5">Phone (M-Pesa)</label>
                    <input type="tel" name="phone" required class="w-full bg-[#F9FAFB] border border-[#0B1E57]/10 focus:border-[#FFCD05]/50 rounded-xl px-4 py-2.5 text-sm text-[#0B1E57] outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-[#0B1E57]/35 uppercase tracking-wider mb-1.5">Email</label>
                    <input type="email" name="email" class="w-full bg-[#F9FAFB] border border-[#0B1E57]/10 focus:border-[#FFCD05]/50 rounded-xl px-4 py-2.5 text-sm text-[#0B1E57] outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-[#0B1E57]/35 uppercase tracking-wider mb-1.5">County</label>
                    <input type="text" name="county" required class="w-full bg-[#F9FAFB] border border-[#0B1E57]/10 focus:border-[#FFCD05]/50 rounded-xl px-4 py-2.5 text-sm text-[#0B1E57] outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-[#0B1E57]/35 uppercase tracking-wider mb-1.5">Town</label>
                    <input type="text" name="town" required class="w-full bg-[#F9FAFB] border border-[#0B1E57]/10 focus:border-[#FFCD05]/50 rounded-xl px-4 py-2.5 text-sm text-[#0B1E57] outline-none transition-colors">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-[10px] font-bold text-[#0B1E57]/35 uppercase tracking-wider mb-1.5">Address</label>
                    <input type="text" name="address" required class="w-full bg-[#F9FAFB] border border-[#0B1E57]/10 focus:border-[#FFCD05]/50 rounded-xl px-4 py-2.5 text-sm text-[#0B1E57] outline-none transition-colors">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-[10px] font-bold text-[#0B1E57]/35 uppercase tracking-wider mb-1.5">Order notes</label>
                    <textarea name="notes" rows="2" class="w-full bg-[#F9FAFB] border border-[#0B1E57]/10 focus:border-[#FFCD05]/50 rounded-xl px-4 py-2.5 text-sm text-[#0B1E57] outline-none transition-colors resize-none"></textarea>
                </div>
            </div>

            <div class="mt-6 pt-5 border-t border-[#0B1E57]/8">
                <div class="flex items-center gap-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl px-4 py-3 text-sm text-emerald-400">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    <span><strong>M-Pesa on delivery.</strong> You'll receive an STK push on the phone number above.</span>
                </div>
            </div>

            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 mt-6 px-8 text-base h-14 rounded-xl bg-[#901C1E] text-[#0B1E57] hover:bg-[#7b1618]">Place Order</button>
        </form>

        <div class="lg:col-span-2">
            <div class="bg-[#0B1E57] border border-[#0B1E57]/10 rounded-2xl p-6 sticky top-24">
                <h2 class="font-black text-[#0B1E57] mb-5">Order summary</h2>
                <div class="space-y-3 mb-6">
                    @foreach($cart->items as $item)
                    <div class="flex justify-between text-sm">
                        <span class="text-[#5A6480]">{{ $item->variant->product->name }} <span class="text-[#0B1E57]/35">× {{ $item->quantity }}</span></span>
                        <span class="font-bold text-[#0B1E57]">KES {{ number_format($item->unit_price * $item->quantity) }}</span>
                    </div>
                    @endforeach
                </div>
                <div class="border-t border-[#0B1E57]/10 pt-4 space-y-2 text-sm">
                    <div class="flex justify-between text-[#5A6480]"><span>Subtotal</span><span>KES {{ number_format($cart->subtotal) }}</span></div>
                    <div class="flex justify-between text-[#5A6480]"><span>Delivery</span><span>Calculated after order</span></div>
                    <div class="flex justify-between text-lg font-black text-[#0B1E57] pt-2 border-t border-[#0B1E57]/10"><span>Total</span><span>KES {{ number_format($cart->subtotal) }}</span></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endSection