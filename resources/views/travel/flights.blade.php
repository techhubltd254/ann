@extends('layouts.app')

@section('title', $origin->city . ' → ' . $destination->city . ' — Book Flight + Hotel + Transfer')

@section('content')
<div class="pt-20" x-data="{
    inv: null, invPrice: 0, room: null, roomPrice: 0, nights: 2, transfer: null, transferPrice: 0, passengers: 1,
    total() { return (this.invPrice * this.passengers) + (this.roomPrice * this.nights) + this.transferPrice; }
}">
    <div class="bg-gradient-to-r from-[#0EA5E9] to-[#0B1E57] py-10">
        <div class="max-w-6xl mx-auto px-5">
            <a href="{{ route('travel.index') }}" class="text-gray-600 hover:text-gray-900 text-sm mb-2 inline-block">← All destinations</a>
            <h1 class="text-3xl font-black text-gray-900" data-split>{{ $origin->city }} → {{ $destination->city }}</h1>
            <div class="flex flex-wrap items-center gap-3 mt-2">
                <span class="text-gray-700 text-sm">{{ \Carbon\Carbon::parse($date)->format('l, d M Y') }}</span>
                <form method="GET" class="flex items-center gap-2">
                    <input type="hidden" name="to" value="{{ $destination->iata_code }}">
                    <input type="date" name="date" value="{{ $date }}" min="{{ now()->toDateString() }}" onchange="this.form.submit()"
                           class="h-9 px-3 rounded-xl bg-white/20 border border-gray-200 text-gray-900 text-sm outline-none [color-scheme:dark]">
                </form>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('travel.book') }}" class="max-w-6xl mx-auto px-5 py-10">
        @csrf
        <input type="hidden" name="inventory_id" :value="inv">
        <input type="hidden" name="room_id" :value="room">
        <input type="hidden" name="transfer_id" :value="transfer">
        <input type="hidden" name="nights" :value="nights">

        {{-- STEP 1: pick a flight --}}
        <h2 class="text-xl font-black text-gray-900 mb-4" data-split>1 · Pick your flight</h2>
        <div class="space-y-3 mb-10">
            @forelse($flights as $f)
            <button type="button" @click="inv = {{ $f->inventory_id }}; invPrice = {{ $f->price }}"
                    class="w-full bg-white border-2 rounded-2xl p-5 text-left transition-all"
                    :class="inv === {{ $f->inventory_id }} ? 'border-[#0B1E57] shadow-lg shadow-[#0B1E57]/10' : 'border-gray-200 hover:border-gray-300'">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-11 h-11 rounded-xl bg-[#0EA5E9]/10 flex items-center justify-center font-black text-[#0EA5E9] text-xs">{{ $f->iata_code }}</div>
                        <div>
                            <div class="font-black text-gray-900">{{ $f->airline_name }} <span class="text-gray-300 font-medium">·</span> <span class="text-gray-400 text-sm font-medium">{{ $f->flight_number }}</span></div>
                            <div class="text-xs text-gray-400">{{ $f->aircraft_type }} · {{ $f->duration_minutes }} min</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-6">
                        <div class="text-center"><div class="font-black text-gray-900">{{ substr($f->departure_time, 0, 5) }}</div><div class="text-[10px] text-gray-400">{{ $origin->iata_code }}</div></div>
                        <div class="text-gray-300">→</div>
                        <div class="text-center"><div class="font-black text-gray-900">{{ substr($f->arrival_time, 0, 5) }}</div><div class="text-[10px] text-gray-400">{{ $destination->iata_code }}</div></div>
                        <div class="text-right">
                            <div class="font-black text-gray-900 text-lg">KES {{ number_format($f->price) }}</div>
                            <div class="text-[10px] {{ $f->available_seats <= 5 ? 'text-[#F59E0B] font-bold' : 'text-gray-400' }}">{{ $f->available_seats }} seats left</div>
                        </div>
                    </div>
                </div>
            </button>
            @empty
            <div class="bg-white border border-gray-200 rounded-2xl p-10 text-center text-gray-400">No flights on this date — pick another day above.</div>
            @endforelse
        </div>

        <div x-show="inv" x-cloak x-transition>
            {{-- STEP 2: hotel (optional) --}}
            <h2 class="text-xl font-black text-gray-900 mb-4" data-split>2 · Add a place to stay <span class="text-sm font-medium text-gray-400">(optional)</span></h2>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-4">
                @foreach($hotels as $h)
                    @foreach($h->rooms as $r)
                    <button type="button" @click="room = {{ $r->id }}; roomPrice = {{ $r->price_per_night }}"
                            class="bg-white border-2 rounded-2xl p-4 text-left transition-all"
                            :class="room === {{ $r->id }} ? 'border-[#0B1E57]' : 'border-gray-200 hover:border-gray-300'">
                        <div class="text-[10px] font-bold text-[#F59E0B] uppercase">{{ $h->star_rating }}★ {{ $h->name }}</div>
                        <div class="font-bold text-gray-900 text-sm mt-1">{{ $r->name }}</div>
                        <div class="text-xs text-gray-400">Sleeps {{ $r->max_guests }}</div>
                        <div class="font-black text-gray-900 mt-2">KES {{ number_format($r->price_per_night) }}<span class="text-[10px] text-gray-400 font-medium">/night</span></div>
                    </button>
                    @endforeach
                @endforeach
            </div>
            <div class="flex items-center gap-3 mb-10" x-show="room">
                <label class="text-sm text-gray-500">Nights:</label>
                <input type="number" x-model.number="nights" min="1" max="30" class="w-20 h-10 px-3 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm">
                <button type="button" @click="room = null; roomPrice = 0" class="text-xs text-gray-400 hover:text-[#901C1E]" data-magnetic>✕ remove hotel</button>
            </div>

            {{-- STEP 3: cab allocation --}}
            <h2 class="text-xl font-black text-gray-900 mb-4" data-split>3 · Airport transfer <span class="text-sm font-medium text-gray-400">(optional — we allocate the cab)</span></h2>
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-10">
                @foreach($transfers as $t)
                <button type="button" @click="transfer = {{ $t->id }}; transferPrice = {{ $t->price }}"
                        class="bg-white border-2 rounded-2xl p-4 text-left transition-all"
                        :class="transfer === {{ $t->id }} ? 'border-[#0B1E57]' : 'border-gray-200 hover:border-gray-300'">
                    <div class="text-2xl mb-2">{{ $t->vehicle_type === 'helicopter' ? '🚁' : ($t->vehicle_type === 'van' ? '🚐' : '🚗') }}</div>
                    <div class="font-bold text-gray-900 text-sm capitalize">{{ $t->vehicle_type }}</div>
                    <div class="text-xs text-gray-400">{{ $t->provider_name }} · seats {{ $t->capacity }}</div>
                    <div class="font-black text-gray-900 mt-2">KES {{ number_format($t->price) }}</div>
                </button>
                @endforeach
            </div>

            {{-- STEP 4: passenger details + pay --}}
            <h2 class="text-xl font-black text-gray-900 mb-4" data-split>4 · Your details</h2>
            <div class="bg-white border border-gray-200 rounded-2xl p-6 mb-8">
                <div class="grid sm:grid-cols-2 gap-3">
                    <input type="text" name="name" required placeholder="Lead passenger name *" class="h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none">
                    <input type="number" name="passengers" x-model.number="passengers" min="1" max="9" required placeholder="Passengers" class="h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none">
                    <input type="email" name="email" required placeholder="Email for receipts *" class="h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none">
                    <input type="tel" name="phone" required placeholder="Phone (M-Pesa) *" class="h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none">
                </div>
            </div>
        </div>

        {{-- Sticky total bar --}}
        <div x-show="inv" x-cloak class="sticky bottom-4 z-40">
            <div class="bg-[#0EA5E9] rounded-2xl px-6 py-4 flex items-center justify-between shadow-2xl">
                <div>
                    <div class="text-[10px] text-gray-400 uppercase tracking-widest">Package total</div>
                    <div class="text-2xl font-black text-gray-900">KES <span x-text="total().toLocaleString()"></span></div>
                </div>
                <button type="submit" class="h-12 px-8 rounded-xl bg-[#F59E0B] text-[#07090F] font-black text-sm hover:bg-[#d97706] transition-all active:scale-95">
                    Pay &amp; Book ✈️
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
