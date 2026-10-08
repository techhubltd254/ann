@extends('layouts.app')
@section('title','Auction — '.$auction->product->name)
@section('content')
<div class="pt-20 max-w-4xl mx-auto px-5 py-10">
<div class="grid md:grid-cols-2 gap-6">
<div><div class="aspect-square bg-gray-50 rounded-3xl overflow-hidden border border-gray-200">@if($auction->product->images->first())<img src="{{ $auction->product->images->first()->url ?? '' }}" class="w-full h-full object-cover">@else<div class="w-full h-full flex items-center justify-center text-gray-300 text-4xl font-black">{{ $auction->product->name[0] }}</div>@endif</div></div>
<div><h1 class="text-2xl font-black text-gray-900">{{ $auction->product->name }}</h1>
<div class="mt-4 bg-gradient-to-br from-orange-50 to-red-50 border border-orange-200 rounded-2xl p-5">
<div class="flex items-center justify-between"><span class="text-sm text-gray-500">Current Bid</span><span class="text-2xl font-black text-[#0B0B0B]">KES {{ number_format($auction->current_bid) }}</span></div>
<div class="flex items-center justify-between mt-2"><span class="text-sm text-gray-500">Starting Bid</span><span class="font-bold">KES {{ number_format($auction->starting_bid) }}</span></div>
<div class="flex items-center justify-between mt-1"><span class="text-sm text-gray-500">Increment</span><span class="font-bold">KES {{ number_format($auction->increment) }}</span></div>
<div class="flex items-center justify-between mt-1"><span class="text-sm text-gray-500">Bids</span><span class="font-bold">{{ $auction->bids->count() }}</span></div>
<div class="flex items-center justify-between mt-1"><span class="text-sm text-gray-500">Ends</span><span class="font-bold">{{ $auction->ends_at->diffForHumans() }}</span></div>
</div>
@if($auction->status === 'active' && auth()->id() !== $auction->seller_id)
<form method="POST" action="{{ route('auctions.bid', $auction->id) }}" class="mt-4 flex gap-3">
@csrf
<input type="number" name="amount" step="1" min="{{ $auction->current_bid + $auction->increment }}" value="{{ $auction->current_bid + $auction->increment }}" class="flex-1 border border-gray-200 rounded-xl px-4 py-2.5 text-sm" required>
<button type="submit" class="bg-[#0B0B0B] text-white font-bold px-6 py-2.5 rounded-xl text-sm">Place Bid</button>
</form>
@endif
<div class="mt-6"><h3 class="font-bold text-gray-900 mb-2">Bid History</h3>
@forelse($auction->bids->sortByDesc('created_at') as $bid)
<div class="flex justify-between py-1 text-sm border-b border-gray-100"><span>{{ $bid->user->name ?? 'Anonymous' }}</span><span class="font-bold">KES {{ number_format($bid->amount) }}</span></div>
@empty<p class="text-gray-400 text-sm">No bids yet.</p>@endforelse
</div></div></div></div>@endsection