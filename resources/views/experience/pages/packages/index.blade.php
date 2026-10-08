@extends('layouts.app')

@section('title', 'Packages — Exhibit & Trade on KICC')
@section('description', 'Exhibitor and county packages on the KICC Digital Economy Platform — from a free listing to white-label enterprise.')

@section('content')
{{-- Hero --}}
<div class="relative bg-[#0B0B0B] overflow-hidden">
    <div class="absolute inset-0">
        <img src="{{ media('kicc/tower-night.jpg') }}" alt="" class="w-full h-full object-cover opacity-20">
    </div>
    <div class="relative max-w-7xl mx-auto px-5 py-16">
        <div class="flex items-center gap-3 mb-4">
            <img src="{{ media('kicc/kicc-logo.png') }}" alt="KICC" class="h-8 w-auto">
            <span class="text-[#FFCD05] text-xs font-bold uppercase tracking-[0.25em]">Packages</span>
        </div>
        <h1 class="text-4xl md:text-5xl font-black text-gray-900 tracking-tight" data-split>Sell, Exhibit &amp; <span class="text-[#FFCD05]">Trade</span></h1>
        <p class="text-gray-500 mt-3 max-w-xl">Every service on this platform is a package — pick yours and start selling under escrow protection today.</p>
    </div>
</div>

{{-- Exhibitor packages --}}
<div class="max-w-7xl mx-auto px-5 py-14">
    <div class="flex items-center gap-3 mb-8">
        <h2 class="text-2xl font-black text-gray-900" data-split>Exhibitor Packages</h2>
        <span class="h-px flex-1 bg-gray-200"></span>
        <span class="text-xs text-gray-400">For businesses &amp; traders</span>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
        @foreach($plans as $i => $p)
        @php $featured = $p->slug === 'exhibitor-pro'; @endphp
        <div class="relative bg-white rounded-2xl border {{ $featured ? 'border-[#B3261E] shadow-xl shadow-[#B3261E]/10' : 'border-gray-200' }} p-6 flex flex-col card-hover">
            @if($featured)
            <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-[#B3261E] text-gray-900 text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-full">Most Popular</span>
            @endif
            <div class="text-xs font-bold uppercase tracking-widest {{ $featured ? 'text-[#B3261E]' : 'text-gray-400' }} mb-2">{{ $p->name }}</div>
            <div class="mb-1">
                <span class="text-3xl font-black text-gray-900">KES {{ number_format($p->price) }}</span>
                <span class="text-sm text-gray-400">/mo</span>
            </div>
            <p class="text-xs text-gray-500 mb-5 leading-relaxed">{{ $p->description }}</p>
            <ul class="space-y-2 mb-6 flex-1">
                @foreach($p->features ?? [] as $f)
                <li class="flex items-start gap-2 text-xs text-gray-600">
                    <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 {{ $featured ? 'text-[#B3261E]' : 'text-[#0B0B0B]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    {{ $f }}
                </li>
                @endforeach
            </ul>
            <a href="{{ route('register') }}" class="block text-center py-2.5 rounded-xl text-sm font-bold transition-all {{ $featured ? 'bg-[#B3261E] text-gray-900 hover:bg-[#B3261E]' : 'border border-[#B3261E]/30 text-[#B3261E] hover:bg-[#B3261E]/5' }}" data-magnetic>
                {{ $p->price == 0 ? 'Start Free' : 'Get ' . $p->name }}
            </a>
        </div>
        @endforeach
    </div>
</div>

{{-- County packages --}}
<div class="bg-[#FFFFFF] border-y border-gray-100">
    <div class="max-w-7xl mx-auto px-5 py-14">
        <div class="flex items-center gap-3 mb-8">
            <h2 class="text-2xl font-black text-gray-900" data-split>County Packages</h2>
            <span class="h-px flex-1 bg-gray-200"></span>
            <span class="text-xs text-gray-400">For county governments &amp; corporates</span>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
            @foreach($countyPackages as $cp)
            <div class="bg-white rounded-2xl border border-gray-200 p-6 flex flex-col card-hover">
                <div class="text-xs font-bold uppercase tracking-widest text-[#B3261E] mb-2">{{ $cp['name'] }}</div>
                <div class="mb-1">
                    <span class="text-3xl font-black text-gray-900">KES {{ number_format($cp['price']) }}</span>
                    <span class="text-sm text-gray-400">/mo</span>
                </div>
                <div class="text-xs font-bold text-[#B3261E] mb-4">{{ $cp['slots'] }}</div>
                <ul class="space-y-2 mb-6 flex-1">
                    @foreach($cp['features'] as $f)
                    <li class="flex items-start gap-2 text-xs text-gray-600">
                        <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-[#B3261E]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        {{ $f }}
                    </li>
                    @endforeach
                </ul>
                <a href="{{ route('counties.index') }}" class="block text-center py-2.5 rounded-xl text-sm font-bold border border-[#B3261E]/30 text-[#B3261E] hover:bg-[#B3261E]/5 transition-all">Contact County Desk</a>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Assurance strip --}}
<div class="max-w-7xl mx-auto px-5 py-12">
    <div class="grid md:grid-cols-3 gap-4 text-center">
        <div class="bg-white border border-gray-200 rounded-2xl p-6">
            <div class="font-black text-gray-900 mb-1">Escrow Protected</div>
            <p class="text-xs text-gray-500">Buyer payments held in KICC escrow until delivery is confirmed.</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-2xl p-6">
            <div class="font-black text-gray-900 mb-1">70% County Revenue Share</div>
            <p class="text-xs text-gray-500">Counties earn from every trade on their pavilion.</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-2xl p-6">
            <div class="font-black text-gray-900 mb-1">Your Own Website</div>
            <p class="text-xs text-gray-500">Every exhibitor and county gets an independent public website.</p>
        </div>
    </div>
</div>
@endsection
