@extends('layouts.app')
@section('title', 'Careers — KICC')
@section('content')
<div class="pt-20 max-w-4xl mx-auto px-5 py-10">
    <h1 class="text-2xl font-black text-gray-900 mb-2">Careers at KICC</h1>
    <p class="text-gray-500 text-sm mb-6">Join Africa's premier convention centre team.</p>
    @if(session('success'))<div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-4 py-3 mb-4 text-sm">{{ session('success') }}</div>@endif
    @forelse($jobs as $j)
    <div class="bg-white border border-gray-200 rounded-2xl p-5 mb-4 card-hover">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div><h3 class="font-bold text-gray-900 text-lg">{{ $j->title }}</h3>
                <div class="flex flex-wrap gap-3 text-xs text-gray-400 mt-1">
                    @if($j->department)<span>{{ $j->department }}</span>@endif
                    @if($j->location)<span>📍 {{ $j->location }}</span>@endif
                    <span class="px-2 py-0.5 rounded bg-gray-100 text-gray-600 font-bold">{{ ucfirst(str_replace('_',' ',$j->type)) }}</span>
                </div>
                @if($j->description)<p class="text-gray-600 text-sm mt-2">{{ $j->description }}</p>@endif
            </div>
            <a href="{{ route('kicc.jobs.show', $j->id) }}" class="h-10 px-5 rounded-xl bg-[#046bd2] text-white text-sm font-bold hover:bg-[#045cb4] transition-all">Apply</a>
        </div>
        @if($j->closing_date)<div class="mt-3 text-xs text-amber-600">Closes: {{ $j->closing_date->format('M d, Y') }}</div>@endif
    </div>
    @empty
    <div class="text-center py-12 text-gray-400"><div class="text-4xl mb-3">💼</div><p>No open positions right now. Check back later.</p></div>
    @endforelse
</div>
@endsection