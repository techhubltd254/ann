@extends('layouts.app')

@section('title', $product->name . ' — KICC Marketplace')
@section('description', $product->short_description ?? Str::limit($product->description, 150))

@section('content')
<div class="pt-20 max-w-7xl mx-auto px-5 py-10">
    <a href="{{ route('marketplace.index') }}" class="inline-flex items-center gap-1.5 text-white/40 hover:text-white text-sm mb-6 transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Marketplace
    </a>

    <div class="grid lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
            <div class="rounded-2xl overflow-hidden h-80 bg-[#141B2E]">
                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover"
                     onerror="this.src='{{ asset('storage/kicc/kicc-logo.png') }}'">
            </div>
            @if($product->images->count() > 1)
            <div class="flex gap-2 mt-3">
                @foreach($product->images->take(4) as $img)
                <div class="w-20 h-14 rounded-xl overflow-hidden border-2 shrink-0 border-white/10">
                    <img src="{{ $img->url }}" alt="" class="w-full h-full object-cover">
                </div>
                @endforeach
            </div>
            @endif
            <div class="mt-8">
                <div class="flex items-center gap-2 mb-2">
                    @if($product->county)
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold tracking-wide border bg-[#FFCD05]/15 text-[#FFCD05] border-[#FFCD05]/30">{{ $product->county->name }} County</span>
                    @endif
                    @if($product->category)
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold tracking-wide border bg-white/10 text-white/60 border-white/10">{{ $product->category->name }}</span>
                    @endif
                </div>
                <h1 class="text-3xl font-black text-white">{{ $product->name }}</h1>
                @if($product->short_description)
                <p class="text-white/50 leading-relaxed text-sm mt-4">{{ $product->short_description }}</p>
                @endif
            </div>
        </div>

        <div>
            <div class="bg-[#0D1220] border border-white/10 rounded-2xl p-6 sticky top-24">
                <div class="font-black text-kicc-gold text-2xl">KES {{ number_format($product->price ?? 0) }}</div>
                <div class="text-white/35 text-sm">per {{ $product->unit ?? 'unit' }}</div>

                <div class="mt-6 space-y-3">
                    @foreach($product->variants->where('is_active', true) as $i => $variant)
                    <label class="flex items-center justify-between bg-[#141B2E] border border-white/10 rounded-xl px-4 py-3 cursor-pointer transition-all has-[:checked]:border-kicc-gold has-[:checked]:bg-[#FFCD05]/5">
                        <span class="flex items-center gap-3">
                            <input type="radio" name="variant" value="{{ $variant->id }}" {{ $i === 0 ? 'checked' : '' }} class="accent-kicc-gold">
                            <span class="text-sm font-semibold text-white">{{ $variant->name }}</span>
                        </span>
                        <span class="font-bold text-kicc-gold">KES {{ number_format($variant->price) }}</span>
                    </label>
                    @endforeach
                </div>

                <form method="POST" action="{{ route('cart.add') }}" class="mt-6 space-y-3">
                    @csrf
                    <input type="hidden" name="variant_id" value="{{ $product->variants->first()->id ?? '' }}">
                    <input type="number" name="quantity" value="1" min="1" max="99"
                           class="w-full h-11 rounded-xl bg-[#141B2E] border border-white/10 text-white/80 text-center outline-none focus:ring-1 focus:ring-kicc-gold">
                    <button type="submit" class="w-full inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-8 text-base h-14 rounded-xl bg-[#901C1E] text-white hover:bg-[#7b1618] active:scale-[0.97]">
                        Add to Cart
                    </button>
                </form>

                @if($product->description)
                <div class="mt-6 pt-5 border-t border-white/8">
                    <div class="text-xs text-white/35">Description</div>
                    <p class="text-white/50 text-sm mt-2 leading-relaxed">{{ $product->description }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    @if(isset($related) && $related->count() > 0)
    <div class="mt-20">
        <h2 class="text-2xl font-black text-white mb-6">You may also like</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($related as $rel)
            <a href="{{ route('marketplace.show', $rel->slug) }}" class="group bg-[#141B2E] rounded-2xl overflow-hidden border border-white/8 hover:border-[#FFCD05]/30 transition-all">
                <div class="aspect-square overflow-hidden bg-[#0D1220]">
                    <img src="{{ $rel->image_url }}" alt="{{ $rel->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                         onerror="this.src='{{ asset('storage/kicc/kicc-logo.png') }}'">
                </div>
                <div class="p-4">
                    <h3 class="font-bold text-white text-sm line-clamp-2">{{ $rel->name }}</h3>
                    <div class="font-black text-kicc-gold text-sm mt-1">KES {{ number_format($rel->price ?? 0) }}</div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endSection