@extends('layouts.app')

@section('title', $sectorInfo['title'] . ' — ' . $county->name . ' County')
@section('description', 'Explore ' . $sectorInfo['title'] . ' in ' . $county->name . ' County')

@section('content')
<div class="pt-20">
    <div class="bg-white border-b border-gray-200 py-12">
        <div class="max-w-7xl mx-auto px-5">
            <a href="{{ route('counties.show', $county->slug) }}" class="inline-flex items-center gap-1.5 text-[#5A6480] hover:text-gray-900 text-sm mb-4 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                {{ $county->name }} County
            </a>
            <div class="flex items-center gap-5">
                <div class="w-16 h-16 bg-[#901C1E]/20 border border-[#901C1E]/30 rounded-2xl flex items-center justify-center shrink-0">
                    <span class="text-3xl">{{ $sectorInfo['icon'] }}</span>
                </div>
                <div>
                    <h1 class="text-3xl font-black text-gray-900" data-split>{{ $sectorInfo['title'] }}</h1>
                    <p class="text-[#5A6480] mt-1 text-sm">{{ $items->count() }} registered entities · {{ $county->name }} County</p>
                </div>
            </div>
            <p class="text-[#5A6480] text-sm mt-4 max-w-xl">{{ $sectorInfo['desc'] }}</p>
        </div>
    </div>
    <div class="max-w-7xl mx-auto px-5 py-10">
        @if($items->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            @foreach($items as $e)
            <div class="group bg-white rounded-2xl overflow-hidden border border-gray-200 hover:border-[#FFCD05]/40 hover:shadow-md transition-all">
                <div class="h-40 overflow-hidden bg-gradient-to-br from-[#F9FAFB] to-gray-100 relative">
                    {{-- media slot: fills once the county admin uploads counties/{slug}/{route}/{id}.jpeg --}}
                    <div class="absolute inset-0 flex items-center justify-center text-4xl text-gray-300">{{ $sectorInfo['icon'] }}</div>
                    <img src="{{ media('counties/' . $county->slug . '/' . $sector . '/' . $e->id . '.jpeg') }}"
                         alt="{{ $e->name }}" loading="lazy"
                         class="relative w-full h-full object-cover bg-[#F9FAFB] group-hover:scale-105 transition-transform duration-500"
                         onerror="this.remove()">
                    @if($e->category)
                    <span class="absolute top-2.5 left-3 text-[10px] font-bold px-2 py-0.5 rounded-full bg-black/40 text-white/90 backdrop-blur-sm capitalize">{{ $e->category }}</span>
                    @endif
                </div>
                <div class="p-4">
                    <div class="font-bold text-gray-900 text-sm leading-snug">{{ $e->name }}</div>
                    @if($e->description)
                    <p class="text-gray-500 text-xs leading-relaxed mt-1.5 line-clamp-2">{{ $e->description }}</p>
                    @endif
                    <div class="flex flex-wrap gap-x-3 gap-y-1 mt-2.5 text-[11px] text-gray-400">
                        @if($e->location)<span>📍 {{ $e->location }}</span>@endif
                        @if(!empty($e->entry_fee))<span class="font-bold text-kicc-gold">KES {{ number_format($e->entry_fee) }}</span>@endif
                        @if(!empty($e->contact))<span>☎ {{ $e->contact }}</span>@endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <div class="mt-8">
            {{ $items->links() }}
        </div>
        @else
        <div class="text-center py-16 text-[#5A6480]">
            <span class="text-4xl block mb-3">📂</span>
            <p class="text-sm">No entities registered in this sector yet.</p>
        </div>
        @endif
    </div>
</div>
@endsection