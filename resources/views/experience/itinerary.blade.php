@extends('layouts.app')
@section('title', 'AI-Generated Itinerary')
@section('content')
<div class="pt-20 max-w-4xl mx-auto px-5 py-10">
    <div class="flex items-center gap-3 mb-3">
        <div class="h-px w-8 bg-[#FFCD05]"></div>
        <span class="text-[#FFCD05] text-xs font-bold tracking-[0.2em] uppercase">AI-Powered</span>
    </div>
    <h1 class="text-3xl md:text-5xl font-black text-gray-900 leading-tight mb-2">Your {{ $context['county']?->name ?? '' }} Itinerary</h1>
    <p class="text-gray-500 mb-8">Curated by AI based on your interests and our recommendations</p>

    {{-- AI Itinerary Content --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-6 md:p-10 mb-8">
        @if($aiItinerary)
            <div class="prose max-w-none">
                {!! nl2br(e($aiItinerary)) !!}
            </div>
        @else
            <div class="text-center py-10 text-gray-500">
                <p class="text-lg">✨ Generating your personalized itinerary...</p>
                <p class="text-sm mt-2">This uses AI to plan your perfect trip based on the places you selected.</p>
            </div>
        @endif
    </div>

    {{-- Recommended Places --}}
    @if(!empty($context['correlations']['places_to_visit']))
    <div class="mb-8">
        <h2 class="text-xl font-bold mb-4">📍 Recommended Places</h2>
        <div class="grid md:grid-cols-2 gap-4">
            @foreach($context['correlations']['places_to_visit'] as $place)
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="font-semibold">{{ $place['name'] }}</p>
                <p class="text-sm text-gray-500">{{ Str::limit($place['description'] ?? '', 100) }}</p>
                @if(!empty($place['distance_km']))
                <p class="text-xs text-gray-400 mt-1">{{ $place['distance_km'] }} km from your starting point</p>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Places to Stay --}}
    @if(!empty($context['correlations']['places_to_stay']))
    <div class="mb-8">
        <h2 class="text-xl font-bold mb-4">🏨 Where to Stay</h2>
        <div class="grid md:grid-cols-2 gap-4">
            @foreach($context['correlations']['places_to_stay'] as $hotel)
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="font-semibold">{{ $hotel['name'] }}</p>
                <p class="text-sm text-gray-500">{{ Str::limit($hotel['description'] ?? '', 100) }}</p>
                @if(!empty($hotel['price_per_night']))
                <p class="text-sm font-medium text-green-600 mt-1">KES {{ number_format($hotel['price_per_night']) }}/night</p>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Actions --}}
    <div class="flex gap-4">
        <a href="{{ route('experience.plan', ['type' => $context['anchor_type'] ?? 'attraction', 'id' => $context['anchor_id'] ?? 0]) }}" class="bg-blue-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-blue-700">
            Build This Trip
        </a>
        <a href="{{ url()->previous() }}" class="bg-gray-100 text-gray-700 px-6 py-3 rounded-xl font-bold hover:bg-gray-200">
            Go Back
        </a>
    </div>
</div>
@endsection