@extends('layouts.app')
@section('title','Start Auction — KICC Marketplace')
@section('content')
<div class="pt-20 max-w-2xl mx-auto px-5 py-10"><h1 class="text-2xl font-black text-gray-900 mb-6">Start an Auction</h1>
<form method="POST" action="{{ route('auctions.store') }}" class="bg-white border border-gray-200 rounded-2xl p-6 space-y-4">
@csrf
<div><label class="text-xs font-bold text-gray-600">Product</label>
<select name="product_id" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm" required>
<option value="">Select a product...</option>
@foreach(\App\Models\Marketplace\Product::where('user_id', auth()->id())->active()->get() as $p)
<option value="{{ $p->id }}">{{ $p->name }}</option>
@endforeach
</select></div>
<div class="grid grid-cols-2 gap-4"><div><label class="text-xs font-bold text-gray-600">Starting Bid (KES)</label><input type="number" name="starting_bid" min="1" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm" required></div>
<div><label class="text-xs font-bold text-gray-600">Increment (KES)</label><input type="number" name="increment" min="1" value="100" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm" required></div></div>
<div class="grid grid-cols-2 gap-4"><div><label class="text-xs font-bold text-gray-600">Starts At</label><input type="datetime-local" name="starts_at" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm" required></div>
<div><label class="text-xs font-bold text-gray-600">Ends At</label><input type="datetime-local" name="ends_at" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm" required></div></div>
<div><label class="text-xs font-bold text-gray-600">Reserve Price (optional)</label><input type="number" name="reserve_price" min="0" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm"></div>
<button type="submit" class="w-full bg-[#046bd2] text-white font-bold py-3 rounded-xl">Create Auction</button>
</form></div>@endsection