@extends('layouts.app')
@section('title', 'National Government of Kenya — Ministries & Agencies')
@section('description', 'Kenya\'s national government ministries, state departments and agencies.')

@section('content')
<div class="pt-20">
    <div class="bg-gradient-to-r from-[#046bd2] to-[#045cb4] py-16">
        <div class="max-w-7xl mx-auto px-5">
            <div class="flex items-center gap-3 mb-4">
                <span class="h-px w-8 bg-[#FFCD05]"></span>
                <span class="text-[#FFCD05] text-xs font-bold tracking-[0.2em] uppercase">National Government</span>
            </div>
            <h1 class="text-4xl md:text-5xl font-black text-white leading-tight" data-split>Kenya's <span class="text-[#FFCD05]">Government</span></h1>
            <p class="text-white/70 text-lg mt-3 max-w-2xl">Ministries, state departments and agencies powering Kenya's digital economy.</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-5 py-12">
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($ministries as $m)
            <a href="{{ route('national.site', $m->slug) }}" class="bg-white border border-gray-200 rounded-2xl p-6 hover:border-[#046bd2]/40 transition-all card-hover group">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-black text-sm" style="background: {{ $m->color ?: '#046bd2' }}">{{ $m->code ?? substr($m->name, 0, 3) }}</div>
                    <div>
                        <h3 class="font-black text-gray-900 text-sm group-hover:text-[#046bd2] transition-colors">{{ $m->name }}</h3>
                        <span class="text-xs text-gray-400">{{ $m->agencies->count() }} {{ Str::plural('agency', $m->agencies->count()) }}</span>
                    </div>
                </div>
                @if($m->description)<p class="text-gray-500 text-sm leading-relaxed line-clamp-2">{{ $m->description }}</p>@endif
                @if($m->agencies->isNotEmpty())
                <div class="mt-4 pt-4 border-t border-gray-100 space-y-1">
                    @foreach($m->agencies->take(3) as $a)
                    <div class="text-xs text-gray-400">· {{ $a->name }}</div>
                    @endforeach
                    @if($m->agencies->count() > 3)<div class="text-xs text-[#046bd2] font-semibold">+{{ $m->agencies->count() - 3 }} more</div>@endif
                </div>
                @endif
            </a>
            @empty
            <div class="col-span-3 text-center py-16 text-gray-400">
                <div class="text-4xl mb-3">🏛️</div>
                <p class="text-sm">No ministries listed yet.</p>
            </div>
            @endforelse
        </div>

        @if($agencies->isNotEmpty())
        <div class="mt-12">
            <h2 class="text-xl font-black text-gray-900 mb-4">All Agencies ({{ $stats['agencies'] }})</h2>
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
                @foreach($agencies as $a)
                <div class="bg-white border border-gray-200 rounded-xl p-4">
                    <div class="font-semibold text-gray-900 text-sm">{{ $a->name }}</div>
                    <div class="text-xs text-gray-400 mt-1">{{ $a->ministry?->name }}</div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection