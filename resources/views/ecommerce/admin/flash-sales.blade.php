@extends('layouts.app')
@section('title','Flash Sales Admin — KICC')
@section('content')
<div class="pt-20 max-w-5xl mx-auto px-5 py-10">
<div class="flex items-center justify-between mb-6"><h1 class="text-2xl font-black text-gray-900">⚡ Flash Sales</h1><button onclick="document.getElementById('new-sale-form').classList.toggle('hidden')" class="text-sm font-bold bg-[#046bd2] text-white px-4 py-2 rounded-xl">New Flash Sale</button></div>
<form id="new-sale-form" method="POST" class="hidden bg-white border border-gray-200 rounded-2xl p-5 mb-6 space-y-3">
@csrf
<div class="grid grid-cols-2 gap-4"><div><input type="text" name="title" placeholder="Sale title" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm" required></div>
<div><input type="number" name="discount_percent" placeholder="Discount %" min="0" max="100" step="1" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm" required></div></div>
<div class="grid grid-cols-2 gap-4"><div><label class="text-xs text-gray-500">Starts</label><input type="datetime-local" name="starts_at" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm" required></div>
<div><label class="text-xs text-gray-500">Ends</label><input type="datetime-local" name="ends_at" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm" required></div></div>
<div><textarea name="description" placeholder="Description" rows="2" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm"></textarea></div>
<button type="submit" class="bg-[#046bd2] text-white font-bold px-6 py-2 rounded-xl text-sm">Create</button>
</form>
@foreach($sales as $sale)
<div class="bg-white border border-gray-200 rounded-2xl p-4 mb-3">
<div class="flex items-center justify-between"><span class="font-bold text-gray-900">{{ $sale->title }}</span><span class="text-xs px-2 py-1 rounded-full {{ $sale->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">{{ $sale->is_active ? 'Active' : 'Inactive' }}</span></div>
<div class="text-xs text-gray-400 mt-1">{{ $sale->discount_percent }}% · {{ $sale->starts_at->format('d M H:i') }} → {{ $sale->ends_at->format('d M H:i') }}</div>
<div class="text-xs text-gray-400 mt-1">{{ $sale->products->count() }} products</div>
</div>
@endforeach
{{ $sales->links() }}</div>@endsection