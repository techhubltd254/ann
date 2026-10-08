@extends('layouts.app')
@section('title','Auctions — KICC Marketplace')
@section('content')
<div class="pt-20 max-w-6xl mx-auto px-5 py-10">
<div class="flex items-center justify-between mb-6"><h1 class="text-2xl font-black text-gray-900"> Live Auctions</h1><a href="{{ route('auctions.create') }}" class="text-sm font-bold text-[#0B0B0B]">Start an Auction</a></div>
@forelse($active as $auction)
<a href="{{ route('auctions.show', $auction->id) }}" class="block bg-white border border-gray-200 rounded-2xl p-4 mb-3 card-hover">
<div class="flex items-center gap-4">
@if($auction->product->images->first())<img src="{{ $auction->product->images->first()->url ?? '' }}" class="w-16 h-16 rounded-xl object-cover">@endif
<div class="flex-1"><div class="font-bold text-gray-900">{{ $auction->product->name }}</div><div class="text-xs text-gray-400">by {{ $auction->seller->name ?? 'Seller' }}</div></div>
<div class="text-right"><div class="font-black text-[#0B0B0B]">KES {{ number_format($auction->current_bid) }}</div><div class="text-xs text-gray-400">{{ $auction->bids->count() }} bids</div></div>
<div class="text-xs px-3 py-1 bg-orange-100 text-orange-700 rounded-full font-bold">Active</div>
</div>
</a>
@empty
<div class="text-center py-20 text-gray-400"><p>No active auctions.</p><a href="{{ route('auctions.create') }}" class="inline-block mt-4 text-[#0B0B0B] font-bold">Start one</a></div>
@endforelse
{{ $active->links() }}</div>@endsection