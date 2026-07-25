@extends('layouts.app')

@section('title', $info['title'] . ' — ' . $county->name . ' County')
@section('description', 'Explore ' . $info['title'] . ' in ' . $county->name . ' County')

@section('content')
<div class="pt-20">
    <div class="bg-[#0D1220] border-b border-white/8 py-12">
        <div class="max-w-7xl mx-auto px-5">
            <a href="{{ route('counties.show', $county->slug) }}" class="inline-flex items-center gap-1.5 text-white/40 hover:text-white text-sm mb-4 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                {{ $county->name }} County
            </a>
            <div class="flex items-center gap-5">
                <div class="w-16 h-16 bg-[#901C1E]/20 border border-[#901C1E]/30 rounded-2xl flex items-center justify-center shrink-0">
                    <span class="text-3xl">{{ $info['icon'] ?? '📍' }}</span>
                </div>
                <div>
                    <h1 class="text-3xl font-black text-white">{{ $info['title'] }}</h1>
                    <p class="text-white/40 mt-1 text-sm">{{ $items->count() }} registered entities · {{ $county->name }} County</p>
                </div>
            </div>
            <p class="text-white/30 text-sm mt-4 max-w-xl">{{ $info['desc'] ?? '' }}</p>
        </div>
    </div>
    <div class="max-w-7xl mx-auto px-5 py-10">
        @if($items->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            @foreach($items as $e)
            <div class="group bg-[#0D1220] rounded-2xl overflow-hidden border border-white/8 hover:border-[#FFCD05]/40 transition-all">
                <div class="h-44 overflow-hidden bg-[#141B2E]">
                    @php $img = media("counties/" . $county->slug . "/" . $sector . ".jpeg"); @endphp
                    <img src="{{ $img }}" alt="{{ $e->name ?? $e->title ?? '' }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                         onerror="this.style.display='none'">
                </div>
                <div class="p-4">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold tracking-wide border bg-[#FFCD05]/15 text-[#FFCD05] border-[#FFCD05]/30">{{ $info['title'] }}</span>
                    <h3 class="font-bold text-white text-sm mt-2 leading-snug line-clamp-2">{{ $e->name ?? $e->name ?? '—' }}</h3>
                    @if(isset($e->description) || isset($e->details))
                    <p class="text-white/35 text-xs mt-1 line-clamp-2">{{ Str::limit($e->description ?? $e->details ?? '', 80) }}</p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-20">
            <div class="w-16 h-16 rounded-2xl mx-auto mb-4 flex items-center justify-center bg-white/5">
                <svg class="w-8 h-8 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </div>
            <h3 class="text-white font-bold mb-2">No entities found</h3>
            <p class="text-white/40">No {{ strtolower($info['title']) }} registered in {{ $county->name }} yet.</p>
        </div>
        @endif
    </div>
</div>
@endSection