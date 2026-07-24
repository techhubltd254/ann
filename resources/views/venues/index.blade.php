@extends('layouts.app')

@section('title', 'Venues')

@section('content')
<div class="bg-gradient-to-br from-amber-50 to-cream py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">Venues</h1>
        <p class="text-lg text-gray-600">Browse our world-class exhibition and convention venues.</p>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    @if($venues->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($venues as $venue)
        <a href="{{ route('venues.show', $venue->slug) }}" class="block bg-white rounded-xl overflow-hidden shadow-sm border border-gray-100 hover:shadow-md hover:border-amber-300 transition-all group">
            @if($venue->cover_image)
            <img src="{{ $venue->cover_image }}" alt="{{ $venue->name }}" class="w-full h-48 object-cover">
            @else
            <div class="w-full h-48 bg-gradient-to-br from-blue-200 to-indigo-300 flex items-center justify-center text-4xl group-hover:scale-110 transition-transform">📍</div>
            @endif
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-2 group-hover:text-amber-600">{{ $venue->name }}</h3>
                @if($venue->city)<p class="text-sm text-gray-500 mb-3">{{ $venue->city }}{{ $venue->county ? ', ' . $venue->county : '' }}</p>@endif
                <div class="flex flex-wrap gap-2 mb-4">
                    <span class="text-xs bg-gray-100 px-2 py-1 rounded">{{ ucfirst($venue->venue_type) }}</span>
                    @if($venue->capacity)<span class="text-xs bg-gray-100 px-2 py-1 rounded">Capacity: {{ number_format($venue->capacity) }}</span>@endif
                </div>
                @if($venue->amenities)
                <div class="flex flex-wrap gap-1 mb-4">
                    @foreach(collect($venue->amenities)->take(3) as $amenity)
                    <span class="text-xs text-amber-600 bg-amber-50 px-2 py-1 rounded">{{ $amenity }}</span>
                    @endforeach
                </div>
                @endif
                <p class="text-gray-600 text-sm">{{ Str::limit($venue->description, 100) }}</p>
            </div>
        </a>
        @endforeach
    </div>
    <div class="mt-8">
        {{ $venues->links() }}
    </div>
    @else
    <div class="text-center py-16">
        <div class="text-5xl mb-4">📍</div>
        <h3 class="text-xl font-semibold text-gray-600 mb-2">No venues yet</h3>
        <p class="text-gray-500">Venues will appear here once added.</p>
    </div>
    @endif
</div>
@endSection
