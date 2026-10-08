@extends('layouts.app')
@section('title','New RFQ — KICC Marketplace')
@section('content')
<div class="pt-20 max-w-2xl mx-auto px-5 py-10"><h1 class="text-2xl font-black text-gray-900 mb-6">New Request for Quotation</h1>
<p class="text-gray-400 text-sm mb-6">Describe what you need and sellers will respond with competitive quotes.</p>
<form method="POST" action="{{ route('rfq.store') }}" class="bg-white border border-gray-200 rounded-2xl p-6 space-y-4">
@csrf
<div><label class="text-xs font-bold text-gray-600">Product Name</label><input type="text" name="product_name" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm" required></div>
<div class="grid grid-cols-2 gap-4"><div><label class="text-xs font-bold text-gray-600">Quantity</label><input type="number" name="quantity" min="1" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm" required></div>
<div><label class="text-xs font-bold text-gray-600">Deadline (optional)</label><input type="date" name="deadline" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm"></div></div>
<div><label class="text-xs font-bold text-gray-600">Specifications</label><textarea name="specifications" rows="4" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm"></textarea></div>
<div class="grid grid-cols-2 gap-4"><div><label class="text-xs font-bold text-gray-600">Budget Min (KES)</label><input type="number" name="budget_min" min="0" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm"></div>
<div><label class="text-xs font-bold text-gray-600">Budget Max (KES)</label><input type="number" name="budget_max" min="0" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm"></div></div>
<button type="submit" class="w-full bg-[#0B0B0B] text-white font-bold py-3 rounded-xl">Submit RFQ</button>
</form></div>@endsection