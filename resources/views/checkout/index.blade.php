@extends('layouts.app')

@section('title', 'Checkout — KICC Marketplace')

@section('content')
<div class="pt-20 max-w-5xl mx-auto px-5 py-10">
    <h1 class="text-3xl font-black text-gray-900 mb-8" data-reveal>Checkout</h1>

    <div class="grid lg:grid-cols-5 gap-6">
        <form method="POST" action="{{ route('checkout.store') }}" class="lg:col-span-3 bg-white border border-gray-100 rounded-2xl p-6 md:p-7" data-reveal>
            @csrf
            <h2 class="text-lg font-black text-gray-900 mb-6" data-split>Delivery details</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Full name</label>
                    <input type="text" name="name" required
                           class="w-full bg-gray-50 border border-gray-200 focus:border-kicc-gold/60 rounded-xl px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Phone (M-Pesa)</label>
                    <input type="tel" name="phone" required
                           class="w-full bg-gray-50 border border-gray-200 focus:border-kicc-gold/60 rounded-xl px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Email</label>
                    <input type="email" name="email"
                           class="w-full bg-gray-50 border border-gray-200 focus:border-kicc-gold/60 rounded-xl px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">County</label>
                    <input type="text" name="county" required
                           class="w-full bg-gray-50 border border-gray-200 focus:border-kicc-gold/60 rounded-xl px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Town</label>
                    <input type="text" name="town" required
                           class="w-full bg-gray-50 border border-gray-200 focus:border-kicc-gold/60 rounded-xl px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 outline-none transition-colors">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Address</label>
                    <input type="text" name="address" required
                           class="w-full bg-gray-50 border border-gray-200 focus:border-kicc-gold/60 rounded-xl px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 outline-none transition-colors">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Order notes</label>
                    <textarea name="notes" rows="2"
                              class="w-full bg-gray-50 border border-gray-200 focus:border-kicc-gold/60 rounded-xl px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 outline-none transition-colors resize-none"></textarea>
                </div>
            </div>

            <div class="mt-6 pt-5 border-t border-gray-100">
                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-3">Payment Method</label>
                <div class="space-y-2">
                    <label class="flex items-center gap-3 bg-white border border-gray-200 rounded-xl px-4 py-3 cursor-pointer hover:border-[#046bd2]/30 transition-colors">
                        <input type="radio" name="payment_method" value="mpesa" checked class="accent-[#046bd2]">
                        <div><div class="font-bold text-sm text-gray-900">M-Pesa</div><div class="text-xs text-gray-400">Lipa Na M-Pesa — STK push to your phone</div></div>
                    </label>
                    <label class="flex items-center gap-3 bg-white border border-gray-200 rounded-xl px-4 py-3 cursor-pointer hover:border-[#046bd2]/30 transition-colors">
                        <input type="radio" name="payment_method" value="cod" class="accent-[#046bd2]">
                        <div><div class="font-bold text-sm text-gray-900">Cash on Delivery</div><div class="text-xs text-gray-400">Pay when you receive your order</div></div>
                    </label>
                </div>
            </div>

            @if(session('gift_card_code'))
            <div class="mt-4 bg-green-50 border border-green-200 rounded-xl px-4 py-3 flex items-center justify-between">
                <span class="text-sm text-green-700"> Gift card {{ session('gift_card_code') }} — KES {{ number_format(session('gift_card_balance')) }}</span>
                <a href="{{ route('gift-cards.remove') }}" class="text-xs text-red-500 font-bold">Remove</a>
            </div>
            @endif

            <button type="submit" data-magnetic
                    class="w-full inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 mt-6 px-8 text-base h-14 rounded-xl bg-[#b3261e] text-white hover:bg-[#7b1618] active:scale-[0.97]">Place Order</button>
        </form>

        <div class="lg:col-span-2 space-y-5">
            <div class="bg-white rounded-2xl border border-gray-100 p-6 card-hover" data-reveal>
                <h2 class="font-bold text-gray-900 mb-5 text-sm uppercase tracking-wider" data-split>Order Summary</h2>
                <div class="space-y-3 mb-6">
                    @foreach($cart->items as $item)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">{{ $item->variant->product->name }} <span class="text-gray-400">× {{ $item->quantity }}</span></span>
                        <span class="font-bold text-gray-900">KES {{ number_format($item->unit_price * $item->quantity) }}</span>
                    </div>
                    @endforeach
                </div>
                <div class="border-t border-gray-100 pt-4 space-y-2 text-sm">
                    <div class="flex justify-between text-gray-400"><span>Subtotal</span><span>KES {{ number_format($cart->subtotal) }}</span></div>
                    <div class="flex justify-between text-gray-400"><span>Delivery</span><span>Calculated after order</span></div>
                    <div class="flex justify-between text-lg font-black text-kicc-gold pt-2 border-t border-gray-100"><span>Total</span><span>KES {{ number_format($cart->subtotal) }}</span></div>
                </div>

                <div class="mt-4 space-y-2">
                    @php
                        $resolver = app(\App\Services\PipelineResolver::class);
                        $pipelineGroups = $cart->items->groupBy(fn($item) => $item->variant?->product
                            ? $resolver->forProduct($item->variant->product)
                            : 'A1');
                    @endphp
                    @foreach($pipelineGroups as $pCode => $pItems)
                        @php
                            $pTotal = $pItems->sum(fn($i) => $i->unit_price * $i->quantity);
                            $pFee = $resolver->feeRate($pCode);
                        @endphp
                        @include('components.pipeline-fee-breakdown', [
                            'pipelineCode' => $pCode,
                            'pipelineName' => $pCode . ' Pipeline',
                            'feeRate' => $pFee,
                            'subtotal' => $pTotal,
                            'isLocked' => \Illuminate\Support\Facades\DB::table('pipeline_registrations')
                                ->where('code', $pCode)->value('earning_locked') ?? false,
                        ])
                    @endforeach
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 p-6 text-sm text-gray-400 card-hover" data-reveal>
                <p class="leading-relaxed">Orders fulfilled within <strong class="text-gray-900 font-bold">3-5 business days</strong> via county courier partners.</p>
                <div class="mt-4 flex items-center gap-2 text-gray-400">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <span>Secured with M-Pesa & Stripe</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection