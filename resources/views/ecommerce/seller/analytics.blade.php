@extends('layouts.app')
@section('title','Seller Analytics — KICC Marketplace')
@section('content')
<div class="pt-20 max-w-6xl mx-auto px-5 py-10">
<h1 class="text-2xl font-black text-gray-900 mb-6"> Seller Dashboard</h1>
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
<div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-[#046bd2]">KES {{ number_format($totalSales) }}</div><div class="text-xs text-gray-400">Total Sales</div></div>
<div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-[#046bd2]">{{ $totalOrders }}</div><div class="text-xs text-gray-400">Orders</div></div>
<div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-[#046bd2]">{{ $totalProducts }}</div><div class="text-xs text-gray-400">Products</div></div>
<div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-[#046bd2]">{{ $products->sum(fn($p)=>$p->variants->sum('stock')) }}</div><div class="text-xs text-gray-400">Total Stock</div></div>
</div>
<div class="grid md:grid-cols-2 gap-6">
<div class="bg-white border border-gray-200 rounded-2xl p-5"><h2 class="font-bold text-gray-900 mb-3">Top Products</h2>
@forelse($topProducts as $p)<div class="flex justify-between py-1 border-b border-gray-100 text-sm"><span>{{ $p->name }}</span><span class="text-gray-400">KS {{ number_format($p->variants->min('price') ?? 0) }}</span></div>@empty<p class="text-gray-400 text-sm">No products yet.</p>@endforelse
</div>
<div class="bg-white border border-gray-200 rounded-2xl p-5"><h2 class="font-bold text-gray-900 mb-3">Recent Orders</h2>
@forelse($orders->take(10) as $o)<div class="flex justify-between py-1 border-b border-gray-100 text-sm"><span class="text-gray-600">{{ $o->order_number }}</span><span class="font-bold">KES {{ number_format($o->grand_total) }}</span></div>@empty<p class="text-gray-400 text-sm">No orders yet.</p>@endforelse
</div></div></div>@endsection