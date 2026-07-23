@extends('layouts.app')

@section('title', $venue->name)
@section('description', $venue->description)

@section('content')
<div class="bg-gradient-to-br from-blue-50 to-indigo-100 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <a href="{{ route('venues.index') }}" class="text-blue-600 hover:text-blue-700 mb-4 inline-block">&larr; All Venues</a>
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
            <div>
                <h1 class="text-4xl font-bold text-gray-900 mb-2">{{ $venue->name }}</h1>
                @if($venue->city)<p class="text-lg text-gray-600">{{ $venue->city }}{{ $venue->county ? ', ' . $venue->county : '' }}</p>@endif
                <div class="flex flex-wrap gap-3 mt-4">
                    <span class="bg-white px-3 py-1 rounded-lg text-sm shadow-sm">{{ ucfirst($venue->venue_type) }}</span>
                    @if($venue->capacity)<span class="bg-white px-3 py-1 rounded-lg text-sm shadow-sm">Capacity: {{ number_format($venue->capacity) }}</span>@endif
                    @if($venue->contact_info && $venue->contact_info['phone'] ?? null)<span class="bg-white px-3 py-1 rounded-lg text-sm shadow-sm">{{ $venue->contact_info['phone'] }}</span>@endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    @if($venue->cover_image)
    <img src="{{ $venue->cover_image }}" alt="{{ $venue->name }}" class="w-full h-64 lg:h-96 object-cover rounded-xl mb-8">
    @else
    <div class="w-full h-64 bg-gradient-to-br from-blue-200 to-indigo-300 rounded-xl mb-8 flex items-center justify-center text-6xl">📍</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
            @if($venue->description)
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 mb-6">
                <h2 class="text-xl font-semibold mb-4">About This Venue</h2>
                <p class="text-gray-600 leading-relaxed">{{ $venue->description }}</p>
            </div>
            @endif

            @if($upcomingExhibitions && $upcomingExhibitions->count() > 0)
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <h2 class="text-xl font-semibold mb-4">Upcoming Exhibitions at {{ $venue->name }}</h2>
                <div class="space-y-4">
                    @foreach($upcomingExhibitions as $exhibition)
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div>
                            <h4 class="font-semibold">{{ $exhibition->name }}</h4>
                            <p class="text-sm text-gray-500">{{ $exhibition->start_date->format('M d, Y') }} - {{ $exhibition->end_date->format('M d, Y') }}</p>
                        </div>
                        <a href="{{ route('exhibitions.show', $exhibition->slug) }}" class="text-amber-600 font-medium text-sm hover:text-amber-700">Details &rarr;</a>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <div class="space-y-6">
            @if($venue->amenities && count($venue->amenities) > 0)
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <h3 class="font-semibold mb-4">Amenities</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach($venue->amenities as $amenity)
                    <span class="bg-blue-50 text-blue-700 px-3 py-1 rounded-lg text-sm">{{ $amenity }}</span>
                    @endforeach
                </div>
            </div>
            @endif

            @if($venue->contact_info)
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <h3 class="font-semibold mb-4">Contact Information</h3>
                <ul class="space-y-2 text-sm text-gray-600">
                    @if($venue->contact_info['phone'] ?? null)<li>📞 {{ $venue->contact_info['phone'] }}</li>@endif
                    @if($venue->contact_info['email'] ?? null)<li>✉️ {{ $venue->contact_info['email'] }}</li>@endif
                    @if($venue->contact_info['website'] ?? null)<li>🌐 {{ $venue->contact_info['website'] }}</li>@endif
                </ul>
            </div>
            @endif

            @if($venue->address)
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <h3 class="font-semibold mb-2">Address</h3>
                <p class="text-sm text-gray-600">{{ $venue->address }}, {{ $venue->city }}</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endSection
