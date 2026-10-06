@extends('layouts.app')

@section('title', 'Travel & Tourism — Kenya')
@section('description', 'Book flights, hotels and transfers across Kenya — escape the cold to a Kenyan summer.')

@section('content')
<div class="pt-20">
    {{-- Weather-aware hero: "someone in cold UK must see a Kenya summer" --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-[#0EA5E9] to-[#0B1E57]">
        <div class="absolute inset-0 opacity-15 bg-gradient-to-br from-white/10 to-white/5"></div>
        <div class="relative max-w-7xl mx-auto px-5 py-14">
            <div class="flex items-center gap-3 mb-3">
                <span class="text-gray-700 text-xs font-bold tracking-[0.2em] uppercase">Travel & Tourism</span>
                @if($weather['temp'] <= 14)
                <span class="bg-[#F59E0B] text-[#07090F] text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full"> Summer Escape</span>
                @endif
            </div>
            <h1 class="text-4xl md:text-5xl font-black text-gray-900 tracking-tight leading-[1.1]" data-split>
                @if($weather['temp'] <= 14)
                It's {{ $weather['temp'] }}°C in {{ $weather['city'] }} right now.<br>
                <span class="text-[#FFCD05]">Mombasa is 30°C. Book the flight.</span>
                @else
                Discover <span class="text-[#FFCD05]">Kenya</span>
                @endif
            </h1>
            <p class="text-gray-700 mt-3 max-w-xl">Flights + hotels + transfers in one booking — certified providers, escrow-protected, instant receipts.</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-5 py-12">
        {{-- DESTINATIONS — pick one, pick a day, fly --}}
        <div class="flex items-end justify-between mb-6">
            <div>
                <div class="flex items-center gap-3 mb-2"><span class="h-px w-8 bg-[#F59E0B]"></span><span class="text-[#F59E0B] text-xs font-bold tracking-[0.2em] uppercase">Fly somewhere warm</span></div>
                <h2 class="text-2xl font-black text-gray-900" data-split>Choose your destination</h2>
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-16">
            @foreach($destinations as $d)
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden hover:shadow-xl transition-all group card-hover" x-data="{ date: '{{ now()->addDays(7)->toDateString() }}' }">
                <div class="h-36 overflow-hidden relative bg-gray-100">
                    <div class="w-full h-full bg-gradient-to-br from-[#0A1024] to-[#1a1a2e] group-hover:scale-105 transition-transform duration-500"></div>
                    <div class="absolute top-3 left-3 bg-white backdrop-blur px-2.5 py-1 rounded-full text-[10px] font-black text-gray-900">{{ $d->iata_code }}</div>
                </div>
                <div class="p-5">
                    <div class="font-black text-gray-900 text-lg">{{ $d->city }}</div>
                    <div class="text-xs text-gray-400 mb-4">{{ $d->name }} · flights from <span class="font-bold text-[#0B1E57]">KES {{ number_format($d->from_price) }}</span></div>
                    <div class="flex gap-2">
                        <input type="date" x-model="date" min="{{ now()->toDateString() }}" class="flex-1 h-10 px-3 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none">
                        <a :href="'{{ route('travel.flights') }}?to={{ $d->iata_code }}&date=' + date" class="h-10 px-4 inline-flex items-center rounded-xl bg-[#901C1E] text-gray-900 text-xs font-black hover:bg-[#7b1618] transition-all">See Flights</a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Attractions (bookable) --}}
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-black text-gray-900" data-split>Top Attractions</h2>
            <span class="text-xs text-gray-400">All bookable online</span>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-16">
            @forelse($attractions->take(6) as $a)
            <div class="bg-white rounded-2xl overflow-hidden border border-gray-200 hover:border-[#0B1E57]/40 transition-all group card-hover">
                <div class="h-32 bg-[#F9FAFB] flex items-center justify-center overflow-hidden">
                    <span class="text-4xl text-gray-300">{{ $a->name[0] }}</span>
                </div>
                <div class="p-4">
                    <span class="text-[10px] font-bold text-[#0B1E57] uppercase tracking-widest">{{ $a->type }}</span>
                    <h3 class="font-bold text-gray-900 text-sm mt-1 leading-snug">{{ $a->name }}</h3>
                    <p class="text-gray-400 text-xs mt-1">{{ $a->city }}</p>
                </div>
            </div>
            @empty
            <div class="col-span-6 text-center py-10 text-gray-400">Attractions loading…</div>
            @endforelse
        </div>

        {{-- Certified provider strip --}}
        <div class="bg-[#0EA5E9] rounded-2xl p-8 text-center">
            <div class="text-[#FFCD05] text-xs font-black uppercase tracking-widest mb-2">Certified Providers</div>
            <p class="text-gray-600 text-sm max-w-2xl mx-auto">Every airline, hotel and cab company on this platform is government-certified and posts its own services &amp; prices. KICC facilitates — escrow protects every shilling until your trip is complete.</p>
            <div class="flex flex-wrap justify-center gap-3 mt-5 text-[11px] font-bold text-gray-400">
                <span class="px-3 py-1.5 rounded-full border border-gray-200">Kenya Airways</span>
                <span class="px-3 py-1.5 rounded-full border border-gray-200">Jambojet</span>
                <span class="px-3 py-1.5 rounded-full border border-gray-200">Safarilink</span>
                <span class="px-3 py-1.5 rounded-full border border-gray-200">Villarosa Kempinski</span>
                <span class="px-3 py-1.5 rounded-full border border-gray-200">Kenya Helicopter Rides</span>
            </div>
        </div>
    </div>
</div>
@endsection
