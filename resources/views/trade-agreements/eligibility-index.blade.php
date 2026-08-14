@extends('layouts.app')

@section('title', 'Export Eligibility Checker — Kenya Trade')
@section('description', 'Check which trade agreements give duty-free market access for your product.')

@section('content')
<div class="pt-20">
    <div class="bg-gradient-to-r from-[#046bd2] to-[#045cb4] py-16">
        <div class="max-w-7xl mx-auto px-5">
            <div class="flex items-center gap-3 mb-4">
                <span class="h-px w-8 bg-[#FFCD05]"></span>
                <span class="text-[#FFCD05] text-xs font-bold tracking-[0.2em] uppercase">Export Eligibility</span>
            </div>
            <h1 class="text-4xl md:text-5xl font-black text-white leading-tight" data-split>Check Your Product's <span class="text-[#FFCD05]">Export Eligibility</span></h1>
            <p class="text-white/70 text-lg mt-3 max-w-2xl">Enter your product category and destination to see which trade agreements unlock duty-free market access.</p>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-5 py-12">
        <form method="POST" action="{{ route('trade.eligibility.check') }}" class="bg-white border border-gray-200 rounded-2xl p-6 card-hover">
            @csrf
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Product Category *</label>
                    <input type="text" name="product_category" value="{{ old('product_category') }}" required list="cat-suggestions" placeholder="e.g. Tea, Coffee, Textiles, Manufacturing" class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none focus:ring-2 focus:ring-[#046bd2]/40">
                    <datalist id="cat-suggestions">
                        @foreach($categories as $c)<option value="{{ $c->name }}">@endforeach
                        <option value="Agriculture"><option value="Tea"><option value="Coffee"><option value="Textiles"><option value="Manufacturing"><option value="Horticulture"><option value="Automotive"><option value="Pharmaceuticals">
                    </datalist>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Destination / Partner</label>
                    <input type="text" name="destination" value="{{ old('destination') }}" placeholder="e.g. UK, South Africa, any EAC country" class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none focus:ring-2 focus:ring-[#046bd2]/40">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Or filter by Trading Bloc</label>
                    <select name="trading_bloc_id" class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none">
                        <option value="">— Any bloc —</option>
                        @foreach($blocs as $b)<option value="{{ $b->id }}">{{ $b->name }} ({{ $b->code }})</option>@endforeach
                    </select>
                </div>
            </div>
            <button type="submit" class="mt-5 w-full h-12 rounded-xl bg-[#046bd2] text-white font-black text-sm hover:bg-[#045cb4] transition-all">Check Eligibility</button>
        </form>

        <div class="mt-8 text-center">
            <a href="{{ route('trade.agreements.index') }}" class="text-sm font-bold text-[#046bd2] hover:underline">Browse all agreements instead →</a>
        </div>
    </div>
</div>
@endsection