@extends('layouts.app')

@section('title', $attraction->name . ' — Book Your Visit')
@section('description', 'Book ' . $attraction->name . ' in ' . $attraction->county->name . ' County — entry, transport, flights and dining packages.')

@section('content')
<div class="pt-20" x-data="{
    guests: 2, entry: {{ $entryFee }}, addons: {},
    prices: { transport: 3500, flight: 8500, helicopter: 45000, restaurant: 2000 },
    perGuest: { transport: false, flight: true, helicopter: true, restaurant: true },
    total() {
        let t = this.entry * this.guests;
        for (const k in this.addons) { if (this.addons[k]) t += this.perGuest[k] ? this.prices[k] * this.guests : this.prices[k]; }
        return t.toLocaleString();
    }
}">
    {{-- Hero --}}
    <div class="relative h-80 overflow-hidden">
        <img src="{{ $attraction->image_url ?? media('counties/' . $attraction->county->slug . '/tourism.jpeg') }}" alt="{{ $attraction->name }}"
             class="w-full h-full object-cover" onerror="this.onerror=null;this.src='{{ media('counties/' . $attraction->county->slug . '/hero.jpeg') }}'">
        <div class="absolute inset-0 bg-gradient-to-t from-[#07090F] via-[#07090F]/40 to-transparent"></div>
        <div class="absolute bottom-0 left-0 right-0 max-w-6xl mx-auto px-5 pb-8">
            <a href="{{ route('counties.sector', [$attraction->county->slug, 'tourism']) }}" class="inline-flex items-center gap-1.5 text-gray-500 hover:text-gray-900 text-sm mb-3 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                {{ $attraction->county->name }} Tourism
            </a>
            <h1 class="text-4xl font-black text-gray-900" data-split>{{ $attraction->name }}</h1>
            <div class="flex flex-wrap items-center gap-3 mt-2">
                <span class="text-[#0B1E57] text-xs font-bold uppercase tracking-widest">{{ $attraction->category ?? 'Attraction' }}</span>
                @if($attraction->latitude && $attraction->longitude)
                <a href="https://www.google.com/maps/search/?api=1&query={{ $attraction->latitude }},{{ $attraction->longitude }}" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-[#901C1E] transition-colors">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.73 7 13 7 13s7-7.27 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5S14.5 7.62 14.5 9s-1.12 2.5-2.5 2.5z"/></svg>
                    {{ $attraction->location ?? 'View on Map' }}
                </a>
                @elseif($attraction->location)
                <span class="text-gray-500 text-sm flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.73 7 13 7 13s7-7.27 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5S14.5 7.62 14.5 9s-1.12 2.5-2.5 2.5z"/></svg>
                    <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($attraction->location) }}" target="_blank" rel="noopener" class="hover:text-[#901C1E] transition-colors">{{ $attraction->location }}</a>
                </span>
                @endif
                @if($attraction->opening_hours)<span class="text-gray-500 text-sm"> {{ $attraction->opening_hours }}</span>@endif
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-5 py-10 grid lg:grid-cols-3 gap-8">
        {{-- Left: description + gallery --}}
        <div class="lg:col-span-2">
            @if($attraction->description)
            <p class="text-gray-600 leading-relaxed text-sm">{{ $attraction->description }}</p>
            @endif
            <div class="grid grid-cols-3 gap-3 mt-6">
                @foreach(['tourism', 'hero', 'culture'] as $g)
                <img src="{{ media('counties/' . $attraction->county->slug . '/' . $g . '.jpeg') }}" alt="" loading="lazy"
                     class="rounded-xl h-28 w-full object-cover" onerror="this.style.display='none'">
                @endforeach
            </div>

            {{-- Recommended: complete your day (based on your first choice) --}}
            @if($recommended->isNotEmpty())
            <h3 class="text-gray-900 font-black mt-10 mb-4">Complete your day in {{ $attraction->county->name }}</h3>
            <div class="grid sm:grid-cols-3 gap-4">
                @foreach($recommended as $r)
                <a href="{{ route('attractions.show', $r->id) }}" class="bg-gray-50 border border-gray-200 rounded-2xl p-4 hover:border-[#0B1E57]/40 transition-all group">
                    <div class="font-bold text-gray-900 text-sm group-hover:text-[#0B1E57] transition-colors">{{ $r->name }}</div>
                    <div class="text-gray-400 text-xs mt-1">Also bookable · from KES {{ number_format(($r->entry_fee ?: 500)) }}</div>
                </a>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Right: booking card --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl p-6 sticky top-24 shadow-2xl">
                <div class="flex items-baseline justify-between mb-1">
                    <div class="text-2xl font-black text-gray-900">KES {{ number_format($entryFee) }}</div>
                    <div class="text-xs text-gray-400">per guest entry</div>
                </div>
                <div class="text-[10px] font-bold uppercase tracking-widest text-[#0B1E57] mb-5">Instant booking · escrow protected</div>

                @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-600 rounded-xl px-4 py-2.5 mb-4 text-xs">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('attractions.book', $attraction->id) }}" class="space-y-3">
                    @csrf
                    <input type="text" name="name" required placeholder="Your full name *" class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none focus:ring-2 focus:ring-[#0B1E57]/60">
                    <input type="email" name="email" required placeholder="Email *" class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none focus:ring-2 focus:ring-[#0B1E57]/60">
                    <input type="tel" name="phone" required placeholder="Phone (M-Pesa) *" class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none focus:ring-2 focus:ring-[#0B1E57]/60">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-[10px] font-bold text-gray-400 uppercase">Visit date</label>
                            <input type="date" name="visit_date" required min="{{ date('Y-m-d') }}" class="w-full h-11 px-3 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none">
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-gray-400 uppercase">Guests</label>
                            <input type="number" name="ticket_count" x-model.number="guests" min="1" max="50" required class="w-full h-11 px-3 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none">
                        </div>
                    </div>

                    {{-- Suggested add-ons (optional) --}}
                    <div class="pt-2">
                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Suggested for this trip — optional</div>
                        <div class="space-y-2">
                            @foreach($addons as $key => $a)
                            <label class="flex items-start gap-3 border border-gray-200 rounded-xl p-3 cursor-pointer hover:border-[#0B1E57]/40 transition-all">
                                <input type="checkbox" name="addons[]" value="{{ $key }}" x-model="addons.{{ $key }}" class="mt-1 accent-[#0B1E57]">
                                <span class="flex-1">
                                    <span class="block text-xs font-bold text-gray-900">{{ $a['label'] }}</span>
                                    <span class="block text-[10px] text-gray-400">{{ $a['desc'] }}</span>
                                </span>
                                <span class="text-xs font-black text-gray-900 whitespace-nowrap">+{{ number_format($a['price']) }}{{ $a['per_guest'] ? '/pp' : '' }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="pt-3 border-t border-gray-100 flex items-center justify-between">
                        <span class="text-sm font-bold text-gray-500">Total</span>
                        <span class="text-xl font-black text-gray-900">KES <span x-text="total()"></span></span>
                    </div>
                    <button type="submit" class="w-full h-12 rounded-xl bg-[#0B1E57] text-white font-black text-sm hover:bg-[#0D2A7A] transition-all active:scale-[0.98]">
                        Book &amp; Pay
                    </button>
                    <p class="text-gray-400 text-[10px] text-center">M-Pesa · Card · Escrow protected until your visit</p>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
