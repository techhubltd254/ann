@extends('layouts.app')
@section('title', 'Live Events — KICC')
@section('content')
<div class="pt-20 max-w-7xl mx-auto px-5">
    <div class="flex items-center gap-3 mb-6">
        <span class="h-px w-8 bg-kicc-gold"></span>
        <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">Live Events</span>
        <span class="h-px flex-1 bg-gray-200"></span>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($channels as $c)
        <a href="{{ route('livestreams.show', $c->slug) }}" class="group bg-white border border-gray-200 rounded-2xl overflow-hidden hover:shadow-lg hover:-translate-y-0.5 transition-all block">
            <div class="relative h-44 bg-[#0A1024] flex items-center justify-center">
                @if($c->poster_url)<img src="{{ $c->poster_url }}" alt="" class="w-full h-full object-cover opacity-60">@endif
                <span class="absolute text-4xl">▶️</span>
                @if($c->is_live)<span class="absolute top-3 left-3 text-[10px] font-bold px-2 py-0.5 rounded-full bg-[#901C1E] text-white animate-pulse">● LIVE</span>@endif
                <span class="absolute bottom-3 right-3 text-[10px] font-bold px-2 py-0.5 rounded-full bg-black/50 text-white/90 capitalize">{{ $c->camera }}</span>
            </div>
            <div class="p-4">
                <div class="font-bold text-gray-900 text-sm group-hover:text-[#901C1E] transition-colors">{{ $c->name }}</div>
                <div class="text-gray-400 text-xs mt-1 capitalize">{{ $c->access_tier }} access</div>
            </div>
        </a>
        @empty
        <div class="col-span-full bg-white border border-gray-200 rounded-2xl p-14 text-center text-gray-400">No live channels right now.</div>
        @endforelse
    </div>
</div>
@endsection
