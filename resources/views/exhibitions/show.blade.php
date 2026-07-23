@extends('layouts.app')

@section('title', $exhibition->name)
@section('description', $exhibition->tagline ?? $exhibition->description)

@section('content')
<div class="bg-gradient-to-br from-amber-50 to-orange-100 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <a href="{{ route('exhibitions.index') }}" class="text-amber-600 hover:text-amber-700 mb-4 inline-block">&larr; Back to Exhibitions</a>
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
            <div>
                <h1 class="text-4xl font-bold text-gray-900 mb-2">{{ $exhibition->name }}</h1>
                @if($exhibition->tagline)
                <p class="text-xl text-gray-600">{{ $exhibition->tagline }}</p>
                @endif
                <div class="flex flex-wrap gap-3 mt-4">
                    <span class="bg-white px-3 py-1 rounded-lg text-sm shadow-sm">{{ $exhibition->start_date->format('M d, Y') }} - {{ $exhibition->end_date->format('M d, Y') }}</span>
                    @if($exhibition->county)
                    <a href="{{ route('counties.show', $exhibition->county->slug) }}" class="bg-white px-3 py-1 rounded-lg text-sm shadow-sm hover:bg-amber-50">{{ $exhibition->county->name }}</a>
                    @endif
                    <span class="bg-white px-3 py-1 rounded-lg text-sm shadow-sm">{{ ucfirst($exhibition->status) }}</span>
                </div>
            </div>
            @if($exhibition->status === 'published')
            <a href="#booking" class="bg-amber-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-amber-700">Book a Booth</a>
            @endif
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    @if($exhibition->cover_image)
    <img src="{{ $exhibition->cover_image }}" alt="{{ $exhibition->name }}" class="w-full h-64 lg:h-96 object-cover rounded-xl mb-8">
    @else
    <div class="w-full h-64 bg-gradient-to-br from-amber-200 to-orange-300 rounded-xl mb-8 flex items-center justify-center text-6xl">🏛️</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
            @if($exhibition->description)
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 mb-6">
                <h2 class="text-xl font-semibold mb-4">About This Exhibition</h2>
                <p class="text-gray-600 leading-relaxed">{{ $exhibition->description }}</p>
            </div>
            @endif

            @if($exhibition->sessions && $exhibition->sessions->count() > 0)
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 mb-6">
                <h2 class="text-xl font-semibold mb-4">Schedule</h2>
                <div class="space-y-4">
                    @foreach($exhibition->sessions as $session)
                    <div class="border-l-4 border-amber-500 pl-4">
                        <div class="text-sm text-gray-500">{{ $session->start_time->format('M d, Y g:i A') }}</div>
                        <h4 class="font-semibold">{{ $session->name }}</h4>
                        @if($session->speaker)<p class="text-sm text-gray-600">By {{ $session->speaker }}</p>@endif
                        @if($session->description)<p class="text-sm text-gray-500 mt-1">{{ $session->description }}</p>@endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <div class="space-y-6">
            @if($exhibition->venue)
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <h3 class="font-semibold mb-3">Venue</h3>
                <a href="{{ route('venues.show', $exhibition->venue->slug) }}" class="text-amber-600 hover:text-amber-700 font-medium">{{ $exhibition->venue->name }}</a>
                @if($exhibition->venue->city)<p class="text-sm text-gray-500">{{ $exhibition->venue->city }}</p>@endif
            </div>
            @endif

            <div id="booking" class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <h3 class="font-semibold mb-3">Available Booths</h3>
                @if($exhibition->booths && $exhibition->booths->count() > 0)
                <div class="space-y-3">
                    @foreach($exhibition->booths as $booth)
                    <div class="flex justify-between items-center pb-3 border-b border-gray-100 last:border-0 last:pb-0">
                        <div>
                            <p class="font-medium">{{ $booth->booth_number }}@if($booth->name) - {{ $booth->name }}@endif</p>
                            <p class="text-sm text-gray-500">{{ ucfirst($booth->size) }} | {{ ucfirst($booth->category) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-amber-600">KES {{ number_format($booth->price) }}</p>
                            <p class="text-sm text-gray-500">{{ $booth->max_quantity - $booth->booked_quantity }} left</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                @auth
                <a href="{{ route('dashboard.bookings') }}" class="mt-4 inline-block bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-amber-700">Book Your Booth</a>
                @else
                <a href="{{ route('login') }}" class="mt-4 inline-block bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-amber-700">Sign In to Book a Booth</a>
                @endauth
                @else
                <p class="text-gray-500 text-sm">No booths available yet.</p>
                @endif
            </div>

            @if($exhibition->ticketTypes && $exhibition->ticketTypes->count() > 0)
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <h3 class="font-semibold mb-3">Tickets</h3>
                <div class="space-y-3">
                    @foreach($exhibition->ticketTypes as $ticketType)
                    <div class="flex justify-between items-center pb-3 border-b border-gray-100 last:border-0 last:pb-0">
                        <div>
                            <p class="font-medium">{{ $ticketType->name }}</p>
                            @if($ticketType->description)<p class="text-sm text-gray-500">{{ $ticketType->description }}</p>@endif
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-amber-600">KES {{ number_format($ticketType->discount_price ?? $ticketType->price) }}</p>
                            @if($ticketType->discount_price)<p class="text-sm line-through text-gray-400">KES {{ number_format($ticketType->price) }}</p>@endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @auth
                <a href="{{ route('dashboard.bookings') }}" class="mt-4 inline-block bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-amber-700 text-center">Buy Tickets</a>
                @else
                <a href="{{ route('login') }}" class="mt-4 inline-block bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-amber-700 text-center">Sign In to Buy Tickets</a>
                @endauth
            </div>
            @endif
        </div>
    </div>
</div>
@endSection
