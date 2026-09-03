@extends('layouts.app')
@section('title', 'Search Kenya — KICC Platform')
@section('description', 'Search products, attractions, accommodations, and trade agreements across Kenya.')

@section('content')
<div class="pt-20">
    <div class="bg-white border-b border-gray-200 py-10">
        <div class="max-w-7xl mx-auto px-5">
            <h1 class="text-3xl font-black text-gray-900" data-split>Search Kenya</h1>
            <p class="text-gray-500 mt-1 text-sm">Products, attractions, accommodations, trade agreements — across all 47 counties.</p>
            <form method="GET" action="{{ route('search.index') }}" class="mt-6">
                <div class="flex flex-wrap gap-3 items-end">
                    <div class="flex-1 min-w-[200px]">
                        <input type="text" name="q" value="{{ $q }}" placeholder="Search anything…" class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none focus:ring-2 focus:ring-[#046bd2]/40">
                    </div>
                    <select name="type" class="h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none">
                        <option value="all">All</option><option value="products" {{ $type === 'products' ? 'selected' : '' }}>Products</option>
                        <option value="attractions" {{ $type === 'attractions' ? 'selected' : '' }}>Attractions</option>
                        <option value="accommodations" {{ $type === 'accommodations' ? 'selected' : '' }}>Accommodations</option>
                        <option value="agreements" {{ $type === 'agreements' ? 'selected' : '' }}>Trade Agreements</option>
                    </select>
                    <select name="county" class="h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none">
                        <option value="">All counties</option>
                        @foreach($counties as $c)<option value="{{ $c->slug }}" {{ $county === $c->slug ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach
                    </select>
                    <button class="h-11 px-6 rounded-xl bg-[#046bd2] text-white font-bold text-sm hover:bg-[#045cb4] transition-all">Search</button>
                </div>
            </form>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-5 py-10">
        @if($q)
        <p class="text-sm text-gray-500 mb-6">{{ $total }} results for <strong class="text-gray-900">"{{ $q }}"</strong></p>
        @endif

        @if($results->isEmpty())
        <div class="text-center py-16 text-gray-400">
            <div class="text-4xl mb-3"></div>
            <p class="text-sm">No results found. Try a different search term or filters.</p>
        </div>
        @else
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($results as $r)
            @if($r['type'] === 'agreement')
            <a href="{{ $r['url'] }}" class="bg-white border border-gray-200 rounded-2xl p-5 hover:border-[#046bd2]/40 transition-all card-hover flex flex-col">
                <span class="text-[10px] font-bold text-[#046bd2] uppercase">Trade Agreement</span>
                <h3 class="font-bold text-gray-900 text-sm mt-1 leading-snug">{{ $r['name'] }}</h3>
                @if($r['description'])<p class="text-gray-500 text-xs mt-1 line-clamp-2">{{ $r['description'] }}</p>@endif
                @if($r['county'])<div class="mt-auto pt-3 text-xs text-gray-400">{{ $r['county'] }}</div>@endif
            </a>
            @else
            <a href="{{ $r['url'] }}" class="bg-white border border-gray-200 rounded-2xl overflow-hidden hover:border-[#046bd2]/40 transition-all card-hover group">
                <div class="h-36 bg-gray-100 overflow-hidden">
                    @if($r['image'])<img src="{{ $r['image'] }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" onerror="this.style.display='none'">@endif
                    <div class="h-full flex items-center justify-center text-2xl text-gray-300 {{ $r['image'] ? 'hidden' : '' }}">{{ $r['type'] === 'product' ? '' : ($r['type'] === 'attraction' ? '' : '') }}</div>
                </div>
                <div class="p-4">
                    <span class="text-[10px] font-bold text-[#046bd2] uppercase">{{ $r['type'] }}</span>
                    <h3 class="font-bold text-gray-900 text-sm mt-1 leading-snug">{{ $r['name'] }}</h3>
                    @if($r['description'])<p class="text-gray-500 text-xs mt-1 line-clamp-2">{{ $r['description'] }}</p>@endif
                    <div class="flex items-center justify-between mt-2">
                        @if($r['price'])<span class="font-black text-[#046bd2] text-sm">KES {{ number_format($r['price']) }}</span>@endif
                        @if($r['county'])<span class="text-xs text-gray-400">{{ $r['county'] }}</span>@endif
                        @if($r['rating'])<span class="text-amber-400 text-xs">{{ str_repeat('', $r['rating']) }}</span>@endif
                    </div>
                </div>
            </a>
            @endif
            @endforeach
        </div>
        @endif
    </div>
</div>
@endsection