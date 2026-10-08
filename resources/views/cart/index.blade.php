@extends('layouts.app')

@section('title', 'Your Cart — KICC Marketplace')

@section('content')
<div class="pt-20 max-w-4xl mx-auto px-5 py-10">
    <h1 class="text-3xl font-black text-gray-900 mb-8" data-reveal>Your Cart</h1>

    @if($cart->items->count())
    <div class="space-y-4 mb-8">
        @foreach($cart->items as $item)
        @if($item->isExperience() && $item->itemable)
            @php $booking = $item->itemable; $summary = $booking->displaySummary(); @endphp
            <div class="bg-white rounded-2xl border border-gray-100 p-5 flex mobile-stack items-center gap-5 card-hover" data-reveal>
                <div class="w-16 h-16 rounded-xl bg-[#0b0b0b]/10 flex items-center justify-center text-2xl shrink-0"></div>
                <div class="flex-1 min-w-0">
                    <div class="font-bold text-gray-900 text-sm">Experience: {{ $summary['destination_name'] }}</div>
                    <div class="text-gray-400 text-xs mt-0.5">
                        {{ $summary['origin'] }} → {{ $summary['destination_name'] }} · {{ $summary['dates'] }}
                    </div>
                    <div class="flex flex-wrap gap-2 mt-1.5">
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $summary['guests'] }} {{ Str::plural('guest', $summary['guests']) }}</span>
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-600"> {{ $summary['transport_out'] }}</span>
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-600"> {{ $summary['transport_back'] }}</span>
                    </div>
                </div>
                <div class="text-right shrink-0">
                    <div class="font-black text-kicc-gold text-sm">KES {{ $summary['total'] }}</div>
                </div>
                <form method="POST" action="{{ route('experience.remove', $booking) }}" onsubmit="return confirm('Remove this experience booking?')">
                    @csrf
                    <button class="text-gray-400 hover:text-[#e86f71] transition-colors p-1" title="Remove">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </form>
            </div>
        @elseif($item->variant)
            @php $product = $item->variant->product; @endphp
            <div class="bg-white rounded-2xl border border-gray-100 p-5 flex mobile-stack items-center gap-5 card-hover" data-reveal>
                <img src="{{ $product->image_url }}" alt="" class="w-16 h-16 rounded-xl object-cover bg-gray-50" loading="lazy" decoding="async" onerror="this.src='{{ asset('storage/kicc/kicc-logo.png') }}'">
                <div class="flex-1 min-w-0">
                    <a href="{{ route('marketplace.show', $product->slug) }}" class="font-bold text-gray-900 hover:text-kicc-gold transition-colors text-sm">{{ $product->name }}</a>
                    <div class="text-gray-400 text-xs mt-0.5">{{ $item->variant->name ?? 'Standard' }} × {{ $item->quantity }}</div>
                </div>
                <div class="text-right shrink-0">
                    <div class="font-black text-kicc-gold text-sm">KES {{ number_format($item->unit_price * $item->quantity) }}</div>
                    @if($item->unit_price > 0)<div class="text-gray-400 text-[10px]">KES {{ number_format($item->unit_price) }} ea</div>@endif
                </div>
                <form method="POST" action="{{ route('cart.destroy', $item) }}" class="inline" onsubmit="return confirm('Remove this item?')">
                    @csrf @method('DELETE')
                    <button class="text-gray-400 hover:text-[#e86f71] transition-colors p-1" title="Remove">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </form>
            </div>
        @endif
        @endforeach
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 p-6 card-hover" data-reveal>
        <div class="flex justify-between items-center mb-4">
            <span class="font-bold text-gray-900 text-lg">Total</span>
            <span class="font-black text-kicc-gold text-2xl">KES {{ number_format($cart->items->sum(fn($i) => $i->unit_price * $i->quantity)) }}</span>
        </div>
        <a href="{{ route('checkout.index') }}" data-magnetic
           class="w-full inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-8 text-base h-14 rounded-xl bg-[#b3261e] text-gray-900 hover:bg-[#7b1618] active:scale-[0.97]">
            Proceed to Checkout
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
        </a>
    </div>
    @else
    <div class="text-center py-20 bg-white rounded-2xl border border-gray-100 card-hover" data-reveal>
        <div class="w-16 h-16 rounded-2xl mx-auto mb-4 flex items-center justify-center bg-gray-50">
            <svg class="w-8 h-8 text-kicc-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        </div>
        <h3 class="text-gray-900 font-bold mb-2">Your cart is empty</h3>
        <p class="text-gray-400 text-sm mb-6">Browse the marketplace to add products.</p>
        <a href="{{ route('marketplace.index') }}" data-magnetic class="inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-6 text-sm h-11 rounded-xl bg-kicc-gold text-[#07090F] hover:bg-[#FFCD05]">Browse Products</a>
    </div>
    @endif
</div>
@push('styles')
<style>
/* Responsive touch targets */
@media (max-width: 640px) {
    .nav-link { padding: 0.625rem 0.75rem; font-size: 0.75rem; }
    .h1-responsive { font-size: 1.75rem !important; line-height: 1.2 !important; }
    .h2-responsive { font-size: 1.5rem !important; }
    .section-padding { padding-top: 2.5rem !important; padding-bottom: 2.5rem !important; }
    .sticky-sidebar { position: relative !important; top: auto !important; }
    .mobile-full { width: 100% !important; }
    .touch-target { min-height: 44px; min-width: 44px; }
}
@media (max-width: 768px) {
    .md-hidden { display: none !important; }
    .mobile-stack { flex-direction: column !important; }
    .mobile-text-center { text-align: center !important; }
}
</style>
@endpush
@endsection