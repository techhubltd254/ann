@extends('layouts.app')

@section('title', $ministry->name . ' — National Government of Kenya')

@section('content')
@php $color = $ministry->color ?: '#1890D7'; @endphp

{{-- Hero --}}
<div class="text-gray-900" style="background: {{ $color }}">
    <div class="max-w-6xl mx-auto px-5 py-14">
        <div class="flex items-center gap-3 mb-6">
            <img src="{{ media('kicc/kicc-logo.png') }}" alt="KICC" class="h-9 w-auto" style="filter: brightness(0) invert(1);">
            <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 border-l border-gray-200 pl-3">National Pavilion Website</span>
        </div>
        <div class="flex items-center gap-2 mb-4">
            <span class="text-[10px] font-bold uppercase tracking-widest px-2.5 py-1 rounded-full bg-gray-100">National Government of Kenya</span>
            <span class="text-[10px] font-bold uppercase tracking-widest px-2.5 py-1 rounded-full bg-gray-100">KICC National Exhibitor</span>
        </div>
        <h1 class="text-4xl font-black mb-3" data-split>{{ $ministry->name }}</h1>
        <p class="text-gray-600 max-w-2xl leading-relaxed">{{ $ministry->description }}</p>
    </div>
</div>

<div class="max-w-6xl mx-auto px-5 py-12">
    {{-- Agencies --}}
    <h2 class="text-xl font-black text-gray-900 mb-6" data-split>Agencies under this Ministry</h2>
    @if($ministry->agencies->isEmpty())
    <div class="bg-white border border-gray-200 rounded-2xl p-10 text-center text-gray-400">No agencies listed yet.</div>
    @else
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-12">
        @foreach($ministry->agencies as $a)
        @if($a->website)
        <a href="{{ $a->website }}" target="_blank" rel="noopener" class="bg-white border border-gray-200 rounded-2xl p-5 hover:shadow-md hover:-translate-y-0.5 transition-all group">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl shrink-0 flex items-center justify-center text-gray-900 font-black text-xs overflow-hidden" style="background: {{ $color }}">
                    @if($a->logo)<img src="{{ $a->logo }}" alt="" class="w-full h-full object-cover" loading="lazy">@else{{ $a->code }}@endif
                </div>
                <div class="min-w-0">
                    <div class="font-bold text-gray-900 text-sm mb-1 group-hover:text-[#901C1E] transition-colors">{{ $a->name }}</div>
                    <p class="text-xs text-gray-500 leading-relaxed">{{ \Illuminate\Support\Str::limit($a->description, 110) }}</p>
                    <div class="text-xs font-bold text-[#901C1E] mt-2 group-hover:underline">Visit website &nearr;</div>
                </div>
            </div>
        </a>
        @else
        <div class="bg-white border border-gray-200 rounded-2xl p-5">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl shrink-0 flex items-center justify-center text-gray-900 font-black text-xs overflow-hidden" style="background: {{ $color }}">
                    @if($a->logo)<img src="{{ $a->logo }}" alt="" class="w-full h-full object-cover" loading="lazy">@else{{ $a->code }}@endif
                </div>
                <div class="min-w-0">
                    <div class="font-bold text-gray-900 text-sm mb-1">{{ $a->name }}</div>
                    <p class="text-xs text-gray-500 leading-relaxed">{{ \Illuminate\Support\Str::limit($a->description, 110) }}</p>
                </div>
            </div>
        </div>
        @endif
        @endforeach
    </div>
    @endif

    {{-- Interconnection --}}
    <div class="grid md:grid-cols-3 gap-4">
        <a href="{{ route('counties.index') }}" class="bg-white border border-gray-200 rounded-2xl p-6 hover:shadow-md transition-all flex items-center justify-between">
            <div>
                <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Explore</div>
                <div class="font-bold text-gray-900">47 County Websites</div>
            </div><span class="text-gray-300 text-xl">&nearr;</span>
        </a>
        <a href="{{ route('marketplace.index') }}" class="bg-white border border-gray-200 rounded-2xl p-6 hover:shadow-md transition-all flex items-center justify-between">
            <div>
                <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Trade on</div>
                <div class="font-bold text-gray-900">National Marketplace</div>
            </div><span class="text-gray-300 text-xl">&nearr;</span>
        </a>
        <a href="{{ route('exhibitions.index') }}" class="bg-white border border-gray-200 rounded-2xl p-6 hover:shadow-md transition-all flex items-center justify-between">
            <div>
                <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Showcasing at</div>
                <div class="font-bold text-gray-900">KICC Exhibitions</div>
            </div><span class="text-gray-300 text-xl">&nearr;</span>
        </a>
    </div>
</div>
@endsection
