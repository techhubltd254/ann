@extends('layouts.app')
@section('title', 'Booking Confirmed')
@section('content')
<div class="pt-20 max-w-4xl mx-auto px-5 py-10">
    <div class="text-center mb-10">
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h1 class="text-3xl font-black text-gray-900">Experience Booked!</h1>
        <p class="text-gray-500 mt-2">Your {{ $context['anchor_name'] ?? 'experience' }} has been confirmed.</p>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200 p-6 md:p-8 mb-8">
        <h2 class="text-xl font-bold mb-6">Booking Summary</h2>

        <div class="grid md:grid-cols-2 gap-6 mb-6">
            <div>
                <p class="text-sm text-gray-500">Reference</p>
                <p class="font-bold text-lg">{{ $context['booking']['reference'] ?? $context['itinerary']['name'] ?? 'Draft' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Destination</p>
                <p class="font-bold">{{ $context['county']?->name ?? '' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Dates</p>
                <p class="font-bold">{{ $context['itinerary']['start_date'] ?? 'TBD' }} — {{ $context['itinerary']['end_date'] ?? 'TBD' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Duration</p>
                <p class="font-bold">{{ $context['itinerary']['days'] ?? 0 }} days</p>
            </div>
        </div>

        {{-- Selected Items --}}
        @if(!empty($context['selections']))
        <div class="border-t pt-6 mb-6">
            <h3 class="font-semibold mb-3">What's Included</h3>
            <div class="space-y-2">
                @foreach($context['selections'] as $item)
                <div class="flex justify-between text-sm">
                    <span>{{ $item['name'] ?? 'Item' }}</span>
                    @if(!empty($item['price']))<span class="font-medium">KES {{ number_format($item['price']) }}</span>@endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Day Plan --}}
        @if(!empty($context['itinerary']['day_plan']))
        <div class="border-t pt-6">
            <h3 class="font-semibold mb-3">Your Itinerary</h3>
            @foreach($context['itinerary']['day_plan'] as $day => $dayItems)
            <div class="mb-4">
                <p class="text-sm font-bold text-blue-600 mb-2">Day {{ $day }}</p>
                <div class="space-y-2">
                    @foreach($dayItems as $item)
                    <div class="flex items-center gap-3 text-sm bg-gray-50 rounded-lg p-3">
                        <span class="text-gray-400 w-12 text-xs">{{ $item['time'] ?? '' }}</span>
                        <span>{{ $item['name'] ?? '' }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Total --}}
        @if(!empty($context['itinerary']['total_estimated']))
        <div class="border-t pt-4 mt-4">
            <div class="flex justify-between text-lg font-bold">
                <span>Total</span>
                <span>KES {{ number_format($context['itinerary']['total_estimated']) }}</span>
            </div>
        </div>
        @endif
    </div>

    <div class="text-center">
        <a href="{{ route('experience.plan', ['type' => $context['anchor_type'] ?? 'attraction', 'id' => $context['anchor_id'] ?? 0]) }}" class="bg-blue-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-blue-700">
            Plan Another Experience
        </a>
    </div>
</div>
@endsection