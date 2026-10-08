@extends('layouts.app')

@section('title', 'Booking Receipt — ' . $groupRef)

@section('content')
<div class="pt-20 min-h-screen flex items-center justify-center px-5 py-12">
    <div class="w-full max-w-2xl" data-reveal>
        <div class="text-center mb-8">
            <div class="w-20 h-20 rounded-full bg-[#0b0b0b]/15 border-2 border-[#0b0b0b] flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-[#0b0b0b]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h1 class="text-3xl font-black text-gray-900 mb-2" data-split>Package Booked!</h1>
            <p class="text-gray-400 text-sm">Your receipts are below — payment confirmation follows via M-Pesa.</p>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl divide-y divide-white/8">
            <div class="p-5 flex justify-between items-center">
                <span class="text-gray-400 text-xs uppercase tracking-widest">Package reference</span>
                <span class="text-[#FFCD05] font-black text-lg">{{ $groupRef }}</span>
            </div>

            {{-- Flight --}}
            <div class="p-5">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="text-[10px] font-bold text-[#0EA5E9] uppercase tracking-widest mb-1"> Flight</div>
                        <div class="text-gray-900 font-bold">{{ $flightDetail->flight_number ?? '' }}</div>
                        <div class="text-gray-400 text-xs mt-1">Ref {{ $flight->booking_reference }} · PNR {{ $flight->pnr_code }} · {{ $flight->passenger_count }} pax</div>
                    </div>
                    <div class="text-gray-900 font-black">KES {{ number_format($flight->total) }}</div>
                </div>
            </div>

            {{-- Hotel --}}
            @if($hotel)
            <div class="p-5">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="text-[10px] font-bold text-[#F59E0B] uppercase tracking-widest mb-1"> Hotel</div>
                        <div class="text-gray-900 font-bold">{{ $hotelDetail->name ?? '' }}</div>
                        <div class="text-gray-400 text-xs mt-1">Ref {{ $hotel->booking_reference }} · {{ $hotel->check_in }} → {{ $hotel->check_out }} · {{ $hotel->guest_count }} guests</div>
                    </div>
                    <div class="text-gray-900 font-black">KES {{ number_format($hotel->total) }}</div>
                </div>
            </div>
            @endif

            {{-- Transfer --}}
            @if($transfer)
            <div class="p-5">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="text-[10px] font-bold text-[#0b0b0b] uppercase tracking-widest mb-1">{{ ($transferDetail->vehicle_type ?? '') === 'helicopter' ? '' : '' }} Transfer ({{ $transferDetail->vehicle_type ?? '' }})</div>
                        <div class="text-gray-900 font-bold">{{ $transferDetail->provider_name ?? '' }}</div>
                        <div class="text-gray-400 text-xs mt-1">Ref {{ $transfer->booking_reference }} · status: {{ $transfer->status }}</div>
                    </div>
                    <div class="text-gray-900 font-black">KES {{ number_format($transfer->total) }}</div>
                </div>
            </div>
            @endif

            <div class="p-5 flex justify-between items-center bg-gray-50">
                <span class="text-gray-900 font-bold">Package total</span>
                <span class="text-[#0b0b0b] font-black text-2xl">KES {{ number_format($total) }}</span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 mt-6">
            <a href="{{ route('travel.index') }}" class="h-11 inline-flex items-center justify-center rounded-xl bg-[#b3261e] text-gray-900 text-sm font-bold hover:bg-[#7b1618] transition-all" data-magnetic>+ Add another destination</a>
            <a href="{{ route('counties.index') }}" class="h-11 inline-flex items-center justify-center rounded-xl border border-gray-200 text-gray-600 text-sm font-bold hover:bg-gray-50 transition-all card-hover">Explore counties</a>
        </div>
    </div>
</div>
@endsection
