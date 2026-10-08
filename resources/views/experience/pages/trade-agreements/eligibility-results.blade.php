@extends('layouts.app')

@section('title', 'Eligibility Results — Export Checker')
@section('description', 'Trade agreements matching your export criteria.')

@section('content')
<div class="pt-20 max-w-6xl mx-auto px-5 py-10">
    <a href="{{ route('trade.eligibility') }}" class="inline-flex items-center gap-1.5 text-gray-500 hover:text-[#0B0B0B] text-sm mb-6 transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        New search
    </a>

    <h1 class="text-3xl font-black text-gray-900 mb-2" data-split>Eligibility Results</h1>
    <p class="text-gray-500 mb-8">
        Matching: <span class="font-bold text-[#0B0B0B]">{{ $appliedCategory ?: 'All products' }}</span>
        @if($appliedDestination) → <span class="font-bold text-[#0B0B0B]">{{ $appliedDestination }}</span>@endif
    </p>

    @if($matches->isEmpty())
    <div class="bg-white border border-gray-200 rounded-2xl p-10 text-center">
        <div class="text-4xl mb-3"></div>
        <h3 class="font-bold text-gray-900 mb-1">No matching agreements found</h3>
        <p class="text-gray-500 text-sm">Try a broader category, or check the full agreement list.</p>
        <a href="{{ route('trade.agreements.index') }}" class="inline-block mt-4 text-[#0B0B0B] font-bold hover:underline">View all agreements →</a>
    </div>
    @else
    <div class="space-y-4">
        @foreach($matches as $a)
        <div class="bg-white border border-gray-200 rounded-2xl p-6 card-hover">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-2 flex-wrap mb-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#0B0B0B]/10 text-[#0B0B0B]">{{ $a->bloc?->name ?? $a->agreement_type }}</span>
                        @if($a->effective_date)<span class="text-xs text-gray-400">Effective {{ $a->effective_date->format('M Y') }}</span>@endif
                    </div>
                    <h3 class="font-black text-gray-900 text-lg">{{ $a->title }}</h3>
                    <p class="text-gray-500 text-sm mt-1">{{ $a->summary }}</p>
                    @if($a->benefits && is_array($a->benefits) && count($a->benefits) > 0)
                    <ul class="mt-3 space-y-1">
                        @foreach(array_slice($a->benefits, 0, 4) as $b)
                        <li class="flex items-start gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 mt-0.5 shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            {{ $b }}
                        </li>
                        @endforeach
                    </ul>
                    @endif
                </div>
                <div class="shrink-0 flex flex-col gap-2">
                    <a href="{{ route('trade.export.apply', $a->slug) }}" class="px-5 py-2.5 rounded-xl bg-[#0B0B0B] text-white text-sm font-bold hover:bg-[#0B0B0B] transition-all text-center">Apply to Export</a>
                    <a href="{{ route('trade.agreements.show', $a->slug) }}" class="px-5 py-2 rounded-xl border border-gray-200 text-gray-600 text-sm font-bold hover:bg-gray-50 transition-all text-center">Details</a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection