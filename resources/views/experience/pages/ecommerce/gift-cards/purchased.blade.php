@extends('layouts.app')
@section('title','Gift Cards Purchased — KICC Marketplace')
@section('content')
<div class="pt-20 max-w-4xl mx-auto px-5 py-10 text-center"><h1 class="text-2xl font-black text-gray-900 mb-4"> Gift Cards Ready!</h1>
<div class="bg-white border border-gray-200 rounded-3xl p-8">
@foreach($cards as $card)
<div class="bg-gradient-to-br from-[#0B0B0B] to-[#0B0B0B] rounded-2xl p-6 text-white mb-4">
<div class="text-xs text-white/60 uppercase tracking-widest">KICC Gift Card</div>
<div class="text-2xl font-black mt-2">{{ $card->code }}</div>
<div class="text-lg mt-2">KES {{ number_format($card->initial_balance) }}</div>
<div class="text-xs text-white/60 mt-2">Expires {{ $card->expires_at->format('M d, Y') }}</div>
</div>
@endforeach
<a href="{{ route('gift-cards.index') }}" class="inline-block text-[#0B0B0B] font-bold text-sm mt-4">Back to Gift Cards</a>
</div></div>@endsection