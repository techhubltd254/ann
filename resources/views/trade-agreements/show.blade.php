@extends('layouts.app')

@section('title', $agreement->title . ' — Trade Agreements')
@section('description', $agreement->summary)

@section('content')
<div class="pt-20 max-w-7xl mx-auto px-5 py-10">
    <a href="{{ route('trade.agreements.index') }}" class="inline-flex items-center gap-1.5 text-gray-500 hover:text-[#046bd2] text-sm mb-6 transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        All Trade Agreements
    </a>

    <div class="grid lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#046bd2]/10 text-[#046bd2]">{{ $agreement->bloc?->name ?? $agreement->agreement_type }}</span>
            <h1 class="text-3xl font-black text-gray-900 mt-3 leading-tight" data-split>{{ $agreement->title }}</h1>
            @if($agreement->summary)
            <p class="text-gray-500 text-base mt-4 leading-relaxed">{{ $agreement->summary }}</p>
            @endif
            @if($agreement->content)
            <div class="mt-8 prose prose-sm max-w-none text-gray-600 leading-relaxed">{!! $agreement->content !!}</div>
            @endif
        </div>

        <div class="space-y-5">
            <div class="bg-white border border-gray-200 rounded-2xl p-5 sticky top-24">
                <h3 class="font-bold text-gray-900 text-sm mb-4">Agreement Details</h3>
                <div class="space-y-3 text-sm">
                    @if($agreement->agreement_type)
                    <div class="flex justify-between"><span class="text-gray-400">Type</span><span class="font-semibold text-gray-700 capitalize">{{ $agreement->agreement_type }}</span></div>
                    @endif
                    @if($agreement->partner_country)
                    <div class="flex justify-between"><span class="text-gray-400">Partner</span><span class="font-semibold text-gray-700">{{ $agreement->partner_country }}</span></div>
                    @endif
                    @if($agreement->signed_date)
                    <div class="flex justify-between"><span class="text-gray-400">Signed</span><span class="font-semibold text-gray-700">{{ $agreement->signed_date->format('M d, Y') }}</span></div>
                    @endif
                    @if($agreement->effective_date)
                    <div class="flex justify-between"><span class="text-gray-400">Effective</span><span class="font-semibold text-gray-700">{{ $agreement->effective_date->format('M d, Y') }}</span></div>
                    @endif
                    <div class="flex justify-between"><span class="text-gray-400">Status</span><span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $agreement->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ ucfirst($agreement->status) }}</span></div>
                </div>
                @if($agreement->document_pdf)
                <a href="{{ $agreement->document_pdf }}" target="_blank" class="mt-5 block text-center py-2.5 rounded-xl bg-[#046bd2] text-white text-sm font-bold hover:bg-[#045cb4] transition-all">Download PDF</a>
                @endif
                <a href="{{ route('trade.export.apply', $agreement->slug) }}" class="mt-5 block text-center py-2.5 rounded-xl bg-[#046bd2] text-white text-sm font-bold hover:bg-[#045cb4] transition-all">Apply to Export Under This Agreement</a>
                @if($agreement->bloc)
                <a href="{{ route('trade.blocs.show', $agreement->bloc->slug) }}" class="mt-2 block text-center py-2 rounded-xl border border-gray-200 text-gray-600 text-sm font-bold hover:bg-gray-50 transition-all">View {{ $agreement->bloc->name }}</a>
                @endif
            </div>

            @if($agreement->benefits && count($agreement->benefits) > 0)
            <div class="bg-white border border-gray-200 rounded-2xl p-5">
                <h3 class="font-bold text-gray-900 text-sm mb-3">Key Benefits</h3>
                <ul class="space-y-2">
                    @foreach($agreement->benefits as $b)
                    <li class="flex items-start gap-2 text-sm text-gray-600">
                        <svg class="w-4 h-4 mt-0.5 shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        {{ $b }}
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

            @if($agreement->sector_coverage && count($agreement->sector_coverage) > 0)
            <div class="bg-white border border-gray-200 rounded-2xl p-5">
                <h3 class="font-bold text-gray-900 text-sm mb-3">Sectors Covered</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach($agreement->sector_coverage as $s)
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600">{{ $s }}</span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    @if($related->isNotEmpty())
    <div class="mt-16">
        <h2 class="text-xl font-black text-gray-900 mb-6" data-split>Related Agreements</h2>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($related as $r)
            <a href="{{ route('trade.agreements.show', $r->slug) }}" class="bg-white border border-gray-200 rounded-2xl p-4 hover:border-[#046bd2]/40 transition-all card-hover">
                <span class="text-[10px] font-bold text-[#046bd2]">{{ $r->bloc?->name ?? $r->agreement_type }}</span>
                <h3 class="font-bold text-gray-900 text-sm mt-1 leading-snug">{{ $r->title }}</h3>
                <p class="text-gray-400 text-xs mt-1">{{ $r->effective_date?->format('M Y') ?? '—' }}</p>
            </a>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection