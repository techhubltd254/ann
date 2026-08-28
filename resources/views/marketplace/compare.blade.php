@extends('layouts.app')
@section('title','Compare Products — KICC Marketplace')
@section('content')
<div class="pt-20 max-w-6xl mx-auto px-5 py-10">
<h1 class="text-2xl font-black text-gray-900 mb-6">📊 Compare Products</h1>
@if($products->count() < 2)
<div class="text-center py-20 text-gray-400"><p>Select at least 2 products to compare.</p><a href="{{ route('marketplace.index') }}" class="inline-block mt-4 text-[#046bd2] font-bold">Browse products</a></div>
@else
<div class="overflow-x-auto"><table class="w-full border-collapse bg-white rounded-2xl overflow-hidden">
<thead><tr class="bg-gray-50">@foreach($products as $p)<th class="p-4 text-center"><div class="aspect-square w-32 mx-auto bg-gray-100 rounded-xl overflow-hidden mb-2">@if($p->images->first())<x-fast-image :src="$p->images->first()->url" :alt="$p->name" :width="320" :quality="70" class="w-full h-full" />@else<div class="w-full h-full flex items-center justify-center text-gray-300 text-2xl font-black">{{ $p->name[0] }}</div>@endif</div><div class="font-bold text-sm text-gray-900">{{ $p->name }}</div><div class="text-[#046bd2] font-black text-sm">KES {{ number_format($p->variants->min('price') ?? 0) }}</div></th>@endforeach</tr></thead>
<tbody>
<tr class="border-t border-gray-200"><td colspan="{{ $products->count() }}" class="p-2 text-xs text-gray-500 bg-gray-50 font-bold">Details</td></tr>
<tr class="border-t"><td class="p-3 text-xs text-gray-400 font-bold" colspan="1">Category</td>@foreach($products as $p)<td class="p-3 text-xs text-center">{{ $p->category->name ?? 'N/A' }}</td>@endforeach</tr>
<tr class="border-t"><td class="p-3 text-xs text-gray-400 font-bold" colspan="1">County</td>@foreach($products as $p)<td class="p-3 text-xs text-center">{{ $p->county->name ?? 'N/A' }}</td>@endforeach</tr>
<tr class="border-t"><td class="p-3 text-xs text-gray-400 font-bold" colspan="1">Description</td>@foreach($products as $p)<td class="p-3 text-xs text-center">{{ Str::limit($p->short_description, 80) }}</td>@endforeach</tr>
<tr class="border-t"><td class="p-3 text-xs text-gray-400 font-bold" colspan="1">Stock</td>@foreach($products as $p)<td class="p-3 text-xs text-center">{{ $p->variants->sum('stock') }} units</td>@endforeach</tr>
</tbody></table></div>
@endif
</div>@endsection