@extends('layouts.app')

@section('title', 'Trading Blocs — Kenya')
@section('description', 'Regional economic communities and trade blocs that Kenya participates in.')

@section('content')
<div class="pt-20">
    <div class="bg-gradient-to-r from-[#0B0B0B] to-[#0B0B0B] py-16">
        <div class="max-w-7xl mx-auto px-5">
            <div class="flex items-center gap-3 mb-4">
                <span class="h-px w-8 bg-[#FFCD05]"></span>
                <span class="text-[#FFCD05] text-xs font-bold tracking-[0.2em] uppercase">Trading Blocs</span>
            </div>
            <h1 class="text-4xl md:text-5xl font-black text-white leading-tight" data-split>Kenya's <span class="text-[#FFCD05]">Trading Blocs</span></h1>
            <p class="text-white/70 text-lg mt-3 max-w-2xl">Regional economic communities and multilateral trade organisations that shape Kenya's trade policy and market access.</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-5 py-12">
        <div class="grid md:grid-cols-2 gap-6">
            @forelse($blocs as $b)
            <a href="{{ route('trade.blocs.show', $b->slug) }}" class="group bg-white rounded-2xl border border-gray-200 overflow-hidden hover:border-[#0B0B0B]/40 transition-all card-hover">
                <div class="p-6">
                    <div class="flex items-center gap-4 mb-4">
                        @if($b->logo_url)
                        <img src="{{ $b->logo_url }}" alt="{{ $b->name }}" class="w-14 h-14 object-contain">
                        @else
                        <div class="w-14 h-14 rounded-xl bg-[#0B0B0B]/10 flex items-center justify-center text-2xl font-black text-[#0B0B0B]">{{ $b->code }}</div>
                        @endif
                        <div>
                            <h3 class="font-black text-gray-900 text-lg group-hover:text-[#0B0B0B] transition-colors">{{ $b->name }}</h3>
                            <span class="text-xs text-gray-400">{{ $b->code }}</span>
                        </div>
                    </div>
                    @if($b->description)<p class="text-gray-500 text-sm leading-relaxed line-clamp-3">{{ $b->description }}</p>@endif
                    <div class="flex items-center gap-4 mt-4 text-xs text-gray-400">
                        <span>{{ $b->agreements_count }} agreement{{ $b->agreements_count !== 1 ? 's' : '' }}</span>
                        @if($b->member_states)<span>{{ $b->member_states }} member states</span>@endif
                        @if($b->website)<span class="text-[#0B0B0B] group-hover:underline">Visit website →</span>@endif
                    </div>
                </div>
            </a>
            @empty
            <div class="col-span-2 text-center py-12 text-gray-400">No trading blocs listed yet.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection