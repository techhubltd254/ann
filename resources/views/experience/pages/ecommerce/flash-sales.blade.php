@extends('layouts.app')
@section('title','Flash Sales — KICC Marketplace')
@section('content')
<div class="pt-20 max-w-6xl mx-auto px-5 py-10">
<h1 class="text-2xl font-black text-gray-900 mb-2"> Flash Sales</h1>
<p class="text-gray-400 mb-6">Limited-time deals with massive discounts. Don't miss out!</p>

@forelse($active as $sale)
<div class="bg-gradient-to-r from-orange-50 to-red-50 border border-orange-200 rounded-3xl p-6 mb-6">
<div class="flex items-center justify-between mb-4"><h2 class="text-xl font-black text-gray-900">{{ $sale->title }}</h2><span class="text-sm font-bold text-red-600" x-data="{ end: '{{ $sale->ends_at }}' }" x-init="setInterval(()=>{let d=new Date(end)-new Date();if(d<=0)location.reload();else{document.getElementById('countdown-{{ $sale->id }}').innerText=Math.floor(d/3600000)+'h '+Math.floor((d%3600000)/60000)+'m '+Math.floor((d%60000)/1000)+'s'}},1000)"><span id="countdown-{{ $sale->id }}"></span></span></div>
<div class="grid grid-cols-2 md:grid-cols-4 gap-3">
@foreach($sale->products as $p)
<a href="{{ route('marketplace.show', $p->slug) }}" class="bg-white rounded-2xl border border-orange-100 p-3 card-hover">
<div class="aspect-square bg-gray-50 rounded-xl overflow-hidden mb-2"><img src="{{ $p->image_url ?? tile_url('logo') }}" class="w-full h-full object-cover" loading="lazy"></div>
<div class="text-xs font-bold text-gray-900 line-clamp-2">{{ $p->name }}</div>
<div class="flex items-center gap-2 mt-1"><span class="font-black text-red-600 text-sm">-{{ $sale->discount_percent }}%</span><span class="text-gray-400 text-xs line-through">KES {{ number_format($p->variants->min('price') ?? 0) }}</span></div>
</a>
@endforeach
</div>
</div>
@empty
<div class="text-center py-20 text-gray-400"><p class="text-lg">No active flash sales right now.</p><a href="{{ route('marketplace.index') }}" class="inline-block mt-4 text-[#0B0B0B] font-bold">Browse products</a></div>
@endforelse

@if($upcoming->count())
<h3 class="font-bold text-gray-900 mt-10 mb-4">Upcoming Flash Sales</h3>
<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
@foreach($upcoming as $sale)
<div class="bg-white border border-gray-200 rounded-2xl p-4 text-center"><div class="font-bold text-gray-900">{{ $sale->title }}</div><div class="text-xs text-gray-400 mt-1">Starts {{ $sale->starts_at->format('M d, H:i') }}</div><div class="text-xs text-[#0B0B0B] font-bold mt-2">{{ $sale->products->count() }} products</div></div>
@endforeach
</div>
@endif
</div>@endsection