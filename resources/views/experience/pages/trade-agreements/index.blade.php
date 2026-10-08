@extends('layouts.app')

@section('title', 'Trade Agreements — Kenya')
@section('description', 'Kenya\'s bilateral, regional and multilateral trade agreements — benefits for counties and exporters.')

@section('content')
<div class="pt-20">
    <div class="bg-gradient-to-r from-[#0B0B0B] to-[#0B0B0B] py-16">
        <div class="max-w-7xl mx-auto px-5">
            <div class="flex items-center gap-3 mb-4">
                <span class="h-px w-8 bg-[#FFCD05]"></span>
                <span class="text-[#FFCD05] text-xs font-bold tracking-[0.2em] uppercase">Trade Agreements</span>
            </div>
            <h1 class="text-4xl md:text-5xl font-black text-white leading-tight" data-split>Kenya's <span class="text-[#FFCD05]">Trade Agreements</span></h1>
            <p class="text-white/70 text-lg mt-3 max-w-2xl">Bilateral, regional and multilateral agreements that open markets for Kenyan exporters across Africa and the world.</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-5 py-12">
        {{-- Feature cards --}}
        @if($featured->isNotEmpty())
        <div class="mb-12">
            <h2 class="text-xl font-black text-gray-900 mb-6" data-split>Featured Agreements</h2>
            <div class="grid md:grid-cols-3 gap-5">
                @foreach($featured as $a)
                <a href="{{ route('trade.agreements.show', $a->slug) }}" class="group bg-white rounded-2xl border border-gray-200 overflow-hidden hover:border-[#FFCD05]/40 transition-all card-hover">
                    <div class="p-6">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#0B0B0B]/10 text-[#0B0B0B]">{{ $a->bloc?->name ?? $a->agreement_type }}</span>
                        <h3 class="font-black text-gray-900 text-lg mt-3 leading-snug group-hover:text-[#0B0B0B] transition-colors">{{ $a->title }}</h3>
                        <p class="text-gray-500 text-sm mt-2 line-clamp-2">{{ $a->summary }}</p>
                        <div class="flex items-center gap-3 mt-4 text-xs text-gray-400">
                            @if($a->effective_date)<span>Effective {{ $a->effective_date->format('M Y') }}</span>@endif
                            <span class="uppercase tracking-wider">{{ $a->agreement_type }}</span>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- All agreements --}}
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <h2 class="text-xl font-black text-gray-900" data-split>All Trade Agreements</h2>
            <a href="{{ route('trade.blocs.index') }}" class="text-sm font-bold text-[#0B0B0B] hover:underline">View Trading Blocs →</a>
        </div>
        <div class="grid md:grid-cols-2 gap-4">
            @forelse($agreements as $a)
            <a href="{{ route('trade.agreements.show', $a->slug) }}" class="group bg-white rounded-2xl border border-gray-200 p-5 hover:border-[#0B0B0B]/40 transition-all card-hover">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#0B0B0B]/10 text-[#0B0B0B]">{{ $a->bloc?->name ?? $a->agreement_type }}</span>
                        <h3 class="font-bold text-gray-900 text-sm mt-2 leading-snug group-hover:text-[#0B0B0B] transition-colors">{{ $a->title }}</h3>
                        @if($a->summary)<p class="text-gray-500 text-xs mt-1 line-clamp-2">{{ $a->summary }}</p>@endif
                    </div>
                    <div class="text-right shrink-0">
                        @if($a->effective_date)<div class="text-xs text-gray-400">Effective</div><div class="text-sm font-bold text-gray-700">{{ $a->effective_date->format('M Y') }}</div>@endif
                    </div>
                </div>
            </a>
            @empty
            <div class="col-span-2 text-center py-12 text-gray-400">No trade agreements listed yet.</div>
            @endforelse
        </div>
        <div class="mt-8">{{ $agreements->links() }}</div>
    </div>
</div>
@endsection