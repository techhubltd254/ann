@extends('layouts.app')

@section('title', 'My Venue Reservations — KICC')

@section('content')
<div class="pt-28 pb-16">
    <div class="max-w-4xl mx-auto px-5">
        <div class="flex items-center gap-3 mb-6">
            <span class="h-px w-10 bg-kicc-gold"></span>
            <span class="text-kicc-gold text-xs font-semibold uppercase tracking-[0.25em]">Reservations</span>
        </div>
        <h1 class="text-3xl font-black text-gray-900 mb-8">My venue reservations</h1>

        @forelse($bookings as $booking)
            <div class="bg-white rounded-2xl border border-gray-200 p-5 mb-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="font-black text-gray-900">{{ $booking->venue?->name ?? 'Venue removed' }}</div>
                        <div class="text-xs text-gray-400 mt-0.5">{{ $booking->booking_reference }} · {{ str_replace('_', ' ', $booking->event_type) }}</div>
                        <div class="text-sm text-gray-600 mt-2">
                            {{ $booking->event_date?->format('d M Y') }}
                            @if($booking->expected_guests) · {{ number_format($booking->expected_guests) }} guests @endif
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="inline-block px-3 py-1 rounded-lg text-xs font-bold {{ $booking->status === 'confirmed' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                            {{ ucfirst($booking->status) }}
                        </span>
                        @if($booking->total_quote)
                            <div class="font-black text-gray-900 mt-2">KES {{ number_format($booking->total_quote) }}</div>
                        @endif
                    </div>
                </div>
                @if($booking->venue)
                    <a href="{{ route('venues.booking.confirm', [$booking->venue->slug, $booking->id]) }}"
                       class="inline-block mt-4 text-sm font-bold text-[#B3261E] hover:underline">View reservation →</a>
                @endif
            </div>
        @empty
            <div class="bg-white rounded-2xl border border-dashed border-gray-300 p-10 text-center">
                <p class="text-gray-500">You have no venue reservations yet.</p>
                <a href="{{ route('venues.index') }}" class="inline-block mt-4 h-11 px-5 leading-[2.75rem] rounded-xl bg-[#0B0B0B] text-white text-sm font-black">Browse venues</a>
            </div>
        @endforelse

        <div class="mt-6">{{ $bookings->links() }}</div>
    </div>
</div>
@endsection
