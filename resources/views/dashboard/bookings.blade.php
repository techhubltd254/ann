@extends('layouts.app')

@section('title', 'My Bookings')
@section('description', 'View your booth and ticket bookings.')

@section('content')
<div class="bg-gradient-to-br from-amber-50 to-orange-100 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <a href="{{ route('dashboard.index') }}" class="text-amber-600 hover:text-amber-700 mb-4 inline-block">&larr; Dashboard</a>
        <h1 class="text-4xl font-bold text-gray-900 mb-2">My Bookings</h1>
        <p class="text-lg text-gray-600">Your booth and ticket bookings.</p>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    @if($bookings->count() > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-6 py-3 text-sm font-semibold text-gray-600">Exhibition</th>
                    <th class="text-left px-6 py-3 text-sm font-semibold text-gray-600">Type</th>
                    <th class="text-left px-6 py-3 text-sm font-semibold text-gray-600">Status</th>
                    <th class="text-left px-6 py-3 text-sm font-semibold text-gray-600">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($bookings as $booking)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <a href="{{ route('exhibitions.show', $booking->exhibition->slug) }}" class="font-medium text-amber-600 hover:text-amber-700">{{ $booking->exhibition->name }}</a>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ ucfirst($booking->booking_type) }}</td>
                    <td class="px-6 py-4">
                        <span class="text-xs font-medium px-2 py-1 rounded {{ $booking->status === 'confirmed' ? 'bg-green-50 text-green-600' : ($booking->status === 'pending' ? 'bg-yellow-50 text-yellow-600' : 'bg-gray-50 text-gray-500') }}">
                            {{ ucfirst($booking->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $booking->created_at->format('M d, Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $bookings->links() }}</div>
    @else
    <div class="text-center py-16 bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="text-5xl mb-4">🎟️</div>
        <h3 class="text-xl font-semibold text-gray-600 mb-2">No bookings yet</h3>
        <p class="text-gray-500">Browse exhibitions to book a booth or purchase tickets.</p>
        <a href="{{ route('exhibitions.index') }}" class="mt-4 inline-block bg-amber-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-amber-700">Browse Exhibitions</a>
    </div>
    @endif
</div>
@endSection
