@extends('layouts.app')

@section('title', 'Reservation ' . $booking->booking_reference . ' — ' . $venue->name)

@section('content')
<div class="pt-28 pb-16">
    <div class="max-w-2xl mx-auto px-5">

        @if(session('success'))
            <div class="mb-5 rounded-xl border border-green-300 bg-green-50 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif

        <div class="text-center mb-8">
            <div class="w-16 h-16 rounded-full {{ $booking->status === 'confirmed' ? 'bg-green-100' : 'bg-[#FFCD05]/20' }} flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 {{ $booking->status === 'confirmed' ? 'text-green-600' : 'text-[#0B0B0B]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h1 class="text-2xl font-black text-gray-900">
                {{ $booking->status === 'confirmed' ? 'Reservation confirmed' : 'Reservation request received' }}
            </h1>
            <p class="text-gray-500 mt-2 text-sm">
                {{ $booking->status === 'confirmed'
                    ? 'Your deposit has been recorded. Our events team will be in touch with the final schedule.'
                    : 'Our events team will confirm availability and send you the deposit invoice.' }}
            </p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-5 pb-4 border-b border-gray-100">
                <span class="text-xs text-gray-400 uppercase tracking-wider font-bold">Reference</span>
                <span class="font-black text-lg text-[#B3261E]">{{ $booking->booking_reference }}</span>
            </div>

            <div class="space-y-2.5 text-sm">
                <div class="flex justify-between"><span class="text-gray-400">Venue</span><span class="font-semibold text-gray-900">{{ $venue->name }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">Event type</span><span class="font-semibold text-gray-900">{{ str_replace('_', ' ', $booking->event_type) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">Date</span><span class="font-semibold text-gray-900">{{ $booking->event_date?->format('d M Y') }}@if($booking->event_end_date && $booking->event_end_date->ne($booking->event_date)) – {{ $booking->event_end_date->format('d M Y') }}@endif</span></div>
                @if($booking->expected_guests)
                <div class="flex justify-between"><span class="text-gray-400">Guests</span><span class="font-semibold text-gray-900">{{ number_format($booking->expected_guests) }}</span></div>
                @endif
                @if($booking->total_quote)
                <div class="flex justify-between border-t border-gray-100 pt-2.5"><span class="text-gray-400">Estimated total</span><span class="font-black text-[#0B0B0B]">KES {{ number_format($booking->total_quote) }}</span></div>
                @endif
                @if($booking->deposit_amount)
                <div class="flex justify-between"><span class="text-gray-400">Deposit due (30%)</span><span class="font-semibold text-gray-900">KES {{ number_format($booking->deposit_amount) }}</span></div>
                @endif
                <div class="flex justify-between"><span class="text-gray-400">Status</span>
                    <span class="font-semibold {{ $booking->status === 'confirmed' ? 'text-green-600' : 'text-amber-600' }}">{{ ucfirst($booking->status) }}</span>
                </div>
            </div>
        </div>

        @if($booking->status !== 'confirmed')
        <form method="POST" action="{{ route('venues.booking.pay', [$venue->slug, $booking->id]) }}"
              class="mt-6 bg-white rounded-2xl border border-gray-200 p-6 space-y-4">
            @csrf
            <h2 class="font-bold text-gray-900">Pay the deposit</h2>
            <label class="block text-sm">
                <span class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">M-Pesa / payment reference</span>
                <input type="text" name="payment_reference" placeholder="e.g. QK12ABCD34"
                       class="w-full h-11 px-3 rounded-xl bg-[#FFFFFF] border border-gray-200 text-sm">
            </label>
            <button class="h-11 px-6 rounded-xl bg-[#B3261E] text-white text-sm font-black hover:opacity-90">
                Confirm deposit payment
            </button>
        </form>
        @endif

        <div class="mt-6 flex flex-wrap gap-3">
            <a href="{{ route('venues.show', $venue->slug) }}"
               class="h-11 px-5 inline-flex items-center rounded-xl border border-gray-300 text-sm font-bold text-gray-800 hover:bg-gray-50">
                Back to venue
            </a>
            <a href="{{ route('venues.my-bookings') }}"
               class="h-11 px-5 inline-flex items-center rounded-xl bg-[#0B0B0B] text-white text-sm font-black hover:opacity-90">
                My reservations
            </a>
        </div>
    </div>
</div>
@endsection
