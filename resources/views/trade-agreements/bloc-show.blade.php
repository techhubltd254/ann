@extends('layouts.app')

@section('title', $bloc->name . ' — Trading Blocs')
@section('description', $bloc->description ?? $bloc->name)

@section('content')
<div class="pt-20 max-w-7xl mx-auto px-5 py-10">
    <a href="{{ route('trade.blocs.index') }}" class="inline-flex items-center gap-1.5 text-gray-500 hover:text-[#046bd2] text-sm mb-6 transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        All Trading Blocs
    </a>

    <div class="grid lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
            <div class="flex items-center gap-4 mb-4">
                @if($bloc->logo_url)
                <img src="{{ $bloc->logo_url }}" alt="{{ $bloc->name }}" class="w-16 h-16 object-contain">
                @else
                <div class="w-16 h-16 rounded-xl bg-[#046bd2]/10 flex items-center justify-center text-3xl font-black text-[#046bd2]">{{ $bloc->code }}</div>
                @endif
                <div>
                    <h1 class="text-3xl font-black text-gray-900" data-split>{{ $bloc->name }}</h1>
                    <span class="text-sm text-gray-400">{{ $bloc->code }}</span>
                </div>
            </div>
            @if($bloc->description)
            <p class="text-gray-600 leading-relaxed mt-4">{{ $bloc->description }}</p>
            @endif

            @if($agreements->isNotEmpty())
            <h2 class="text-xl font-black text-gray-900 mt-10 mb-4" data-split>Agreements under {{ $bloc->code }}</h2>
            <div class="space-y-3">
                @foreach($agreements as $a)
                <a href="{{ route('trade.agreements.show', $a->slug) }}" class="block bg-white border border-gray-200 rounded-2xl p-5 hover:border-[#046bd2]/40 transition-all card-hover">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="font-bold text-gray-900 text-sm">{{ $a->title }}</h3>
                            @if($a->summary)<p class="text-gray-500 text-xs mt-1">{{ $a->summary }}</p>@endif
                        </div>
                        <span class="shrink-0 text-xs text-gray-400">{{ $a->effective_date?->format('M Y') ?? '—' }}</span>
                    </div>
                </a>
                @endforeach
            </div>
            @endif
        </div>

        <div class="space-y-5">
            <div class="bg-white border border-gray-200 rounded-2xl p-5 sticky top-24">
                <h3 class="font-bold text-gray-900 text-sm mb-4">Bloc Details</h3>
                <div class="space-y-3 text-sm">
                    @if($bloc->member_states)
                    <div>
                        <span class="text-gray-400 text-xs">Member States</span>
                        <p class="font-semibold text-gray-700 mt-0.5">{{ $bloc->member_states }}</p>
                    </div>
                    @endif
                    <div class="flex justify-between"><span class="text-gray-400">Agreements</span><span class="font-semibold text-gray-700">{{ $agreements->count() }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-400">Counties linked</span><span class="font-semibold text-gray-700">{{ $bloc->counties_count }}</span></div>
                </div>
                @if($bloc->website)
                <a href="{{ $bloc->website }}" target="_blank" class="mt-5 block text-center py-2.5 rounded-xl bg-[#046bd2] text-white text-sm font-bold hover:bg-[#045cb4] transition-all">Visit Website →</a>
                @endif
                <a href="{{ route('trade.agreements.index') }}" class="mt-2 block text-center py-2 rounded-xl border border-gray-200 text-gray-600 text-sm font-bold hover:bg-gray-50 transition-all">All Agreements</a>
            </div>
        </div>
    </div>
</div>
@endsection