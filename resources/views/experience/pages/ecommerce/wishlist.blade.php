@extends('layouts.app')
@section('title','My Wishlist — KICC Marketplace')
@section('content')
<div class="pt-20 max-w-6xl mx-auto px-5 py-10">
<div class="flex items-center justify-between mb-6"><h1 class="text-2xl font-black text-gray-900">My Wishlist</h1><span class="text-sm text-gray-400" id="wishlist-count">{{ $items->total() }} items</span></div>
@if($items->count())
<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
@foreach($items as $item)
@php $p = $item->wishlistable; @endphp
@if($p)
<a href="{{ route('marketplace.show', $p->slug) }}" class="group bg-white border border-gray-200 rounded-2xl overflow-hidden card-hover">
<div class="aspect-square bg-gray-50 overflow-hidden"><img src="{{ $p->image_url ?? tile_url('logo') }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy"></div>
<div class="p-4"><div class="font-bold text-gray-900 text-sm leading-snug line-clamp-2">{{ $p->name }}</div><div class="font-black text-[#0B0B0B] text-sm mt-1">KES {{ number_format($p->variants->min('price') ?? 0) }}</div></div>
</a>
@endif
@endforeach
</div>
{{ $items->links() }}
@else
<div class="text-center py-20 text-gray-400"><p class="text-lg">Your wishlist is empty.</p><a href="{{ route('marketplace.index') }}" class="inline-block mt-4 text-[#0B0B0B] font-bold">Browse products</a></div>
@endif
</div>
<script>document.querySelectorAll('[data-wishlist-btn]').forEach(b=>{b.addEventListener('click',async function(e){e.preventDefault();let r=await fetch('{{ route('wishlist.toggle') }}',{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({wishlistable_type:this.dataset.type,wishlistable_id:this.dataset.id})});let d=await r.json();this.classList.toggle('text-red-500');});});</script>
@endsection