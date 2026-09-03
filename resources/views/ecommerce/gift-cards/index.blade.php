@extends('layouts.app')
@section('title','Gift Cards — KICC Marketplace')
@section('content')
<div class="pt-20 max-w-4xl mx-auto px-5 py-10"><h1 class="text-2xl font-black text-gray-900 mb-6"> Gift Cards</h1>
<div class="bg-gradient-to-br from-[#046bd2] to-[#045cb4] rounded-3xl p-8 text-white mb-6">
<h2 class="text-xl font-black">Give the Gift of Choice</h2>
<p class="text-white/70 text-sm mt-2">KICC Gift Cards — redeemable across the entire marketplace.</p>
<form method="POST" action="{{ route('gift-cards.purchase') }}" class="mt-6 flex flex-col sm:flex-row gap-3">
@csrf
<div><label class="text-xs text-white/60">Amount (KES)</label><input type="number" name="amount" min="100" max="100000" step="50" value="1000" required class="w-full rounded-xl px-4 py-2.5 text-gray-900 text-sm font-bold"></div>
<div><label class="text-xs text-white/60">Quantity</label><input type="number" name="quantity" min="1" max="10" value="1" class="w-full rounded-xl px-4 py-2.5 text-gray-900 text-sm"></div>
<div class="self-end"><button type="submit" class="bg-[#FFCD05] text-[#07090F] font-bold px-6 py-2.5 rounded-xl text-sm">Buy Now</button></div>
</form>
</div>
<div class="bg-white border border-gray-200 rounded-2xl p-5"><h3 class="font-bold text-gray-900 mb-3">Redeem a Gift Card</h3>
<form method="POST" action="{{ route('gift-cards.apply') }}" class="flex gap-3">
@csrf
<input type="text" name="code" placeholder="KICC-XXXXXXXX" maxlength="13" class="flex-1 border border-gray-200 rounded-xl px-4 py-2.5 text-sm" required>
<button type="submit" class="bg-gray-900 text-white font-bold px-6 py-2.5 rounded-xl text-sm">Apply</button>
</form>
@if(session('gift_card_code'))<div class="mt-3 flex items-center justify-between bg-green-50 rounded-xl px-4 py-2"><span class="text-sm text-green-700"> {{ session('gift_card_code') }} applied</span><a href="{{ route('gift-cards.remove') }}" class="text-xs text-red-500">Remove</a></div>@endif
</div>
</div>@endsection