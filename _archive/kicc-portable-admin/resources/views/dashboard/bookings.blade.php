@extends('layouts.app')

@section('title', 'My Bookings')
@section('description', 'View your booth and ticket bookings.')

@section('content')
<div class="bg-white border-b border-gray-200 py-12">
    <div class="max-w-7xl mx-auto px-5" data-reveal>
        <a href="{{ route('dashboard.index') }}" class="text-kicc-gold hover:underline text-sm mb-4 inline-block">&larr; Dashboard</a>
        <h1 class="text-3xl md:text-4xl font-black text-gray-900 mb-2">My Bookings</h1>
        <p class="text-[#5A6480]">Your booth and ticket bookings.</p>
    </div>
</div>

<div class="max-w-7xl mx-auto px-5 py-10">
    @if($bookings->count() > 0)
    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden" data-reveal>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-[#5A6480] text-[10px] uppercase tracking-wider border-b border-gray-200 bg-white/[0.02]">
                        <th class="text-left px-6 py-3">Exhibition</th>
                        <th class="text-left px-6 py-3">Type</th>
                        <th class="text-left px-6 py-3">Status</th>
                        <th class="text-left px-6 py-3">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bookings as $booking)
                    <tr class="border-b border-gray-100 hover:bg-sky-50 transition-colors">
                        <td class="px-6 py-4">
                            <a href="{{ route('exhibitions.show', $booking->exhibition->slug) }}" class="font-bold text-kicc-gold hover:underline">{{ $booking->exhibition->name }}</a>
                        </td>
                        <td class="px-6 py-4 text-[#5A6480]">{{ ucfirst($booking->booking_type) }}</td>
                        <td class="px-6 py-4">
                            <span class="text-[10px] font-bold px-2.5 py-1 rounded-full border {{ $booking->status === 'confirmed' ? 'text-emerald-400 bg-emerald-500/15 border-emerald-500/25' : ($booking->status === 'pending' ? 'text-[#FFCD05] bg-[#FFCD05]/15 border-[#FFCD05]/25' : 'text-[#5A6480] bg-sky-50 border-gray-200') }}">
                                {{ ucfirst($booking->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-[#5A6480] text-xs">{{ $booking->created_at->format('M d, Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $bookings->links() }}</div>
    @else
    <div class="text-center py-16 bg-white rounded-2xl border border-gray-200" data-reveal>
        <div class="text-5xl mb-4">🎟️</div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">No bookings yet</h3>
        <p class="text-[#5A6480] text-sm">Browse exhibitions to book a booth or purchase tickets.</p>
        <a href="{{ route('exhibitions.index') }}" data-magnetic class="mt-5 inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-6 text-sm h-11 rounded-xl bg-kicc-gold text-[#07090F] hover:bg-[#e6b904]">Browse Exhibitions</a>
    </div>
    @endif
</div>
@endsection
