@extends('layouts.app')

@section('title', 'Exhibitions')
@section('description', 'Browse exhibitions, trade shows, and events across Kenya')

@section('content')
<div class="bg-gradient-to-br from-amber-50 to-orange-100 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">Exhibitions & Trade Shows</h1>
        <p class="text-lg text-gray-600">Discover exhibitions, book booths, and purchase tickets across Kenya.</p>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    @if($exhibitions->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($exhibitions as $exhibition)
        <div class="bg-white rounded-xl overflow-hidden shadow-sm border border-gray-100 hover:shadow-md transition">
            @if($exhibition->cover_image)
            <img src="{{ $exhibition->cover_image }}" alt="{{ $exhibition->name }}" class="w-full h-48 object-cover">
            @else
            <div class="w-full h-48 bg-gradient-to-br from-amber-200 to-orange-300 flex items-center justify-center text-4xl">🏛️</div>
            @endif
            <div class="p-6">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm text-gray-500">{{ $exhibition->start_date->format('M d, Y') }} - {{ $exhibition->end_date->format('M d, Y') }}</span>
                    <span class="text-xs font-medium px-2 py-1 rounded {{ $exhibition->status === 'published' ? 'bg-green-50 text-green-600' : 'bg-gray-50 text-gray-500' }}">
                        {{ ucfirst($exhibition->status) }}
                    </span>
                </div>
                <h3 class="text-lg font-semibold mb-2">{{ $exhibition->name }}</h3>
                <p class="text-gray-600 text-sm mb-4">{{ Str::limit($exhibition->tagline ?? $exhibition->description, 120) }}</p>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">{{ $exhibition->booths_count ?? 0 }} booths</span>
                    <a href="{{ route('exhibitions.show', $exhibition->slug) }}" class="text-amber-600 font-medium text-sm hover:text-amber-700">View Details &rarr;</a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    <div class="mt-8">
        {{ $exhibitions->links() }}
    </div>
    @else
    <div class="text-center py-16">
        <div class="text-5xl mb-4">🏛️</div>
        <h3 class="text-xl font-semibold text-gray-600 mb-2">No exhibitions yet</h3>
        <p class="text-gray-500">Check back soon for upcoming exhibitions.</p>
    </div>
    @endif
</div>
@endsection
