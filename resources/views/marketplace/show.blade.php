@extends('layouts.app')

@section('title', $product->name . ' — KICC Marketplace')
@section('description', $product->short_description ?? Str::limit($product->description, 150))

@section('content')
<div class="max-w-7xl mx-auto px-6 lg:px-8 py-10">
    <a href="{{ route('marketplace.index') }}" class="inline-flex items-center gap-2 text-gray-500 hover:text-amber-600 mb-8 transition-colors text-sm font-medium">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Marketplace
    </a>

    <div class="grid lg:grid-cols-2 gap-12">
        {{-- Gallery --}}
        <div>
            <div class="rounded-3xl overflow-hidden bg-gray-50 border border-gray-100 shadow-sm">
                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full aspect-square object-cover"
                     onerror="this.src='{{ asset('storage/kicc/logo.png') }}'; this.classList.add('object-contain','p-12')">
            </div>
            @if($product->images->count() > 1)
            <div class="grid grid-cols-4 gap-3 mt-3">
                @foreach($product->images->take(4) as $img)
                <img src="{{ $img->image_url }}" alt="" class="rounded-xl aspect-square object-cover border border-gray-100">
                @endforeach
            </div>
            @endif
        </div>

        {{-- Details --}}
        <div>
            <div class="flex items-center gap-2 mb-3">
                @if($product->county)
                <a href="{{ route('counties.show', $product->county->slug) }}" class="text-xs font-medium text-amber-600 bg-amber-50 px-3 py-1 rounded-full hover:bg-amber-100">{{ $product->county->name }} County</a>
                @endif
                @if($product->category)
                <a href="{{ route('marketplace.index', ['category' => $product->category->slug]) }}" class="text-xs font-medium text-gray-600 bg-gray-100 px-3 py-1 rounded-full hover:bg-gray-200">{{ $product->category->name }}</a>
                @endif
            </div>
            <h1 class="text-3xl md:text-4xl font-extrabold text-gray-900 tracking-tight mb-4">{{ $product->name }}</h1>
            @if($product->short_description)
            <p class="text-lg text-gray-500 mb-6">{{ $product->short_description }}</p>
            @endif

            <form method="POST" action="{{ route('cart.add') }}">
                @csrf
                <div class="space-y-3 mb-6">
                    @foreach($product->variants as $i => $variant)
                    <label class="flex items-center justify-between border rounded-2xl px-5 py-4 cursor-pointer transition-all has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50/50 has-[:checked]:shadow-md border-gray-200">
                        <span class="flex items-center gap-3">
                            <input type="radio" name="variant_id" value="{{ $variant->id }}" {{ $i === 0 ? 'checked' : '' }} class="accent-amber-600 w-4 h-4">
                            <span>
                                <span class="block font-semibold text-gray-900">{{ $variant->name }}</span>
                                <span class="block text-xs text-gray-400">
                                    @if($variant->stock > 0) {{ $variant->stock }} in stock @else <span class="text-amber-600 font-medium">Out of stock</span> @endif
                                </span>
                            </span>
                        </span>
                        <span class="text-right">
                            <span class="block text-lg font-extrabold text-gray-900">KES {{ number_format($variant->price) }}</span>
                            @if($variant->compare_at_price && $variant->compare_at_price > $variant->price)
                            <span class="block text-xs text-gray-400 line-through">KES {{ number_format($variant->compare_at_price) }}</span>
                            @endif
                        </span>
                    </label>
                    @endforeach
                </div>

                <div class="flex items-center gap-4">
                    <input type="number" name="quantity" value="1" min="1" max="99"
                           class="w-20 border border-gray-200 rounded-xl px-3 py-3 text-center focus:outline-none focus:border-amber-500">
                    <button type="submit" class="btn-amber flex-1 text-center">Add to Cart</button>
                </div>
            </form>

            @if($product->description)
            <div class="mt-10 border-t border-gray-100 pt-8">
                <h2 class="text-lg font-bold text-gray-900 mb-3">About this product</h2>
                <div class="prose prose-sm text-gray-600 leading-relaxed">{!! nl2br(e($product->description)) !!}</div>
            </div>
            @endif

            <div class="mt-8 grid grid-cols-2 gap-4 text-sm">
                @if($product->sku)
                <div class="bg-gray-50 rounded-xl p-4"><span class="text-gray-400 block text-xs uppercase tracking-wider mb-1">SKU</span><span class="font-medium">{{ $product->sku }}</span></div>
                @endif
                @if($product->unit)
                <div class="bg-gray-50 rounded-xl p-4"><span class="text-gray-400 block text-xs uppercase tracking-wider mb-1">Sold per</span><span class="font-medium">{{ $product->unit }}</span></div>
                @endif
            </div>
        </div>
    </div>

    {{-- Related --}}
    @if($related->count())
    <div class="mt-20">
        <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight mb-6">You may also like</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
            @foreach($related as $rel)
            <a href="{{ route('marketplace.show', $rel->slug) }}" class="group bg-white rounded-2xl overflow-hidden border border-gray-100 shadow-sm hover:shadow-xl transition-all">
                <div class="aspect-square overflow-hidden bg-gray-50">
                    <img src="{{ $rel->image_url }}" alt="{{ $rel->name }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                         onerror="this.src='{{ asset('storage/kicc/logo.png') }}'; this.classList.add('object-contain','p-8')">
                </div>
                <div class="p-4">
                    <h3 class="font-semibold text-gray-900 text-sm line-clamp-2 group-hover:text-amber-600">{{ $rel->name }}</h3>
                    @if($rel->price !== null)
                    <div class="mt-1.5 font-extrabold text-gray-900">KES {{ number_format($rel->price) }}</div>
                    @endif
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endSection
