@extends('layouts.app')

@section('title', 'Reserve ' . $venue->name . ' — KICC')

@section('content')
<div class="pt-28 pb-16">
    <div class="max-w-3xl mx-auto px-5">

        <a href="{{ route('venues.show', $venue->slug) }}"
           class="inline-flex items-center gap-1.5 text-gray-500 hover:text-[#B3261E] text-sm mb-6 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to {{ $venue->name }}
        </a>

        <div class="rounded-2xl border border-gray-200 overflow-hidden bg-white mb-6">
            @php $cover = $venue->cover_image ? media($venue->cover_image) : null; @endphp
            @if($cover)
                <div class="h-48 overflow-hidden bg-gray-100">
                    <img src="{{ $cover }}" alt="{{ $venue->name }}" class="w-full h-full object-cover">
                </div>
            @endif
            <div class="p-6">
                <span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">
                    {{ $venue->venue_type ?: 'Venue' }}@if($venue->city) · {{ $venue->city }}@endif
                </span>
                <h1 class="text-2xl font-black text-gray-900 mt-1">{{ $venue->name }}</h1>
                <p class="text-sm text-gray-500 mt-1">Capacity {{ number_format((int) $venue->capacity) }} guests</p>

                @if($venue->hero_video_url || $venue->conference_rate || $venue->exhibition_rate)
                <div class="mt-4 flex flex-wrap gap-3 text-xs">
                    @if($venue->conference_rate)
                        <span class="px-3 py-1.5 rounded-lg bg-[#FFFFFF] border border-gray-200 text-gray-700">Conference · KES {{ number_format($venue->conference_rate) }}/day</span>
                    @endif
                    @if($venue->exhibition_rate)
                        <span class="px-3 py-1.5 rounded-lg bg-[#FFFFFF] border border-gray-200 text-gray-700">Exhibition · KES {{ number_format($venue->exhibition_rate) }}/day</span>
                    @endif
                    @if($venue->concert_rate)
                        <span class="px-3 py-1.5 rounded-lg bg-[#FFFFFF] border border-gray-200 text-gray-700">Concert · KES {{ number_format($venue->concert_rate) }}/day</span>
                    @endif
                </div>
                @endif
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 rounded-xl border border-green-300 bg-green-50 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="mb-4 rounded-xl border border-red-300 bg-red-50 text-red-800 px-4 py-3 text-sm">
                <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        @if($quote)
        <div class="mb-6 rounded-2xl border border-[#FFCD05]/40 bg-[#0B0B0B] p-6 text-white">
            <div class="text-[10px] font-bold uppercase tracking-[0.25em] text-[#FFCD05] mb-3">Your estimate</div>
            @if($quote['on_request'])
                <p class="text-lg font-black">Price on request</p>
                <p class="text-white/60 text-sm mt-1">This venue has no published {{ str_replace('_', ' ', $quote['event_type']) }} rate yet. Send the request and our events team will quote you directly.</p>
            @else
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-white/60">Event type</span><span class="font-semibold">{{ str_replace('_', ' ', $quote['event_type']) }}</span></div>
                    <div class="flex justify-between"><span class="text-white/60">Days</span><span class="font-semibold">{{ $quote['days'] }}</span></div>
                    <div class="flex justify-between"><span class="text-white/60">Day rate</span><span class="font-semibold">KES {{ number_format($quote['rate']) }}</span></div>
                    <div class="flex justify-between border-t border-white/15 pt-2"><span class="text-white/60">Estimated total</span><span class="font-black text-[#FFCD05]">KES {{ number_format($quote['subtotal']) }}</span></div>
                    <div class="flex justify-between"><span class="text-white/60">Deposit (30%)</span><span class="font-semibold">KES {{ number_format($quote['deposit']) }}</span></div>
                </div>
            @endif
        </div>
        @endif

        <form method="POST" action="{{ route('venues.reserve', $venue->slug) }}"
              class="bg-white rounded-2xl border border-gray-200 p-6 space-y-5">
            @csrf
            <h2 class="font-bold text-gray-900">Reservation details</h2>

            <div class="grid sm:grid-cols-2 gap-4">
                <label class="block text-sm">
                    <span class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Event type *</span>
                    <select name="event_type" required class="w-full h-11 px-3 rounded-xl bg-[#FFFFFF] border border-gray-200 text-sm">
                        @foreach(['conference' => 'Conference', 'meeting' => 'Meeting', 'workshop' => 'Workshop', 'exhibition' => 'Exhibition', 'expo' => 'Expo', 'trade_show' => 'Trade show', 'concert' => 'Concert', 'show' => 'Show', 'performance' => 'Performance'] as $val => $label)
                            <option value="{{ $val }}" @selected(old('event_type', $quoteInput['event_type'] ?? '') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block text-sm">
                    <span class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Days</span>
                    <input type="number" name="days" min="1" max="60" value="{{ old('days', $quoteInput['days'] ?? 1) }}"
                           class="w-full h-11 px-3 rounded-xl bg-[#FFFFFF] border border-gray-200 text-sm">
                </label>

                <label class="block text-sm">
                    <span class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Start date *</span>
                    <input type="date" name="event_date" required value="{{ old('event_date', $quoteInput['event_date'] ?? '') }}"
                           class="w-full h-11 px-3 rounded-xl bg-[#FFFFFF] border border-gray-200 text-sm">
                </label>

                <label class="block text-sm">
                    <span class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">End date</span>
                    <input type="date" name="event_end_date" value="{{ old('event_end_date', $quoteInput['event_end_date'] ?? '') }}"
                           class="w-full h-11 px-3 rounded-xl bg-[#FFFFFF] border border-gray-200 text-sm">
                </label>

                <label class="block text-sm">
                    <span class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Expected guests</span>
                    <input type="number" name="expected_guests" min="1" value="{{ old('expected_guests', $quoteInput['expected_guests'] ?? '') }}"
                           class="w-full h-11 px-3 rounded-xl bg-[#FFFFFF] border border-gray-200 text-sm">
                </label>

                <label class="block text-sm">
                    <span class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Expected cars</span>
                    <input type="number" name="expected_cars" min="0" value="{{ old('expected_cars', $quoteInput['expected_cars'] ?? '') }}"
                           class="w-full h-11 px-3 rounded-xl bg-[#FFFFFF] border border-gray-200 text-sm">
                </label>

                <label class="block text-sm">
                    <span class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Security level</span>
                    <input type="text" name="security_level" value="{{ old('security_level', $quoteInput['security_level'] ?? '') }}"
                           class="w-full h-11 px-3 rounded-xl bg-[#FFFFFF] border border-gray-200 text-sm">
                </label>

                <label class="block text-sm">
                    <span class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Preferred layout</span>
                    <input type="text" name="preferred_layout" value="{{ old('preferred_layout', $quoteInput['preferred_layout'] ?? '') }}"
                           class="w-full h-11 px-3 rounded-xl bg-[#FFFFFF] border border-gray-200 text-sm">
                </label>
            </div>

            <label class="block text-sm">
                <span class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Additional facilities</span>
                <textarea name="additional_facilities" rows="2" class="w-full px-3 py-2 rounded-xl bg-[#FFFFFF] border border-gray-200 text-sm">{{ old('additional_facilities', $quoteInput['additional_facilities'] ?? '') }}</textarea>
            </label>

            <label class="block text-sm">
                <span class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Catering requirements</span>
                <textarea name="catering_requirements" rows="2" class="w-full px-3 py-2 rounded-xl bg-[#FFFFFF] border border-gray-200 text-sm">{{ old('catering_requirements', $quoteInput['catering_requirements'] ?? '') }}</textarea>
            </label>

            <label class="block text-sm">
                <span class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">AV requirements</span>
                <textarea name="av_requirements" rows="2" class="w-full px-3 py-2 rounded-xl bg-[#FFFFFF] border border-gray-200 text-sm">{{ old('av_requirements', $quoteInput['av_requirements'] ?? '') }}</textarea>
            </label>

            <div class="flex flex-wrap gap-5 text-sm text-gray-700">
                <label class="inline-flex items-center gap-2"><input type="checkbox" name="requires_live_coverage" value="1" @checked(old('requires_live_coverage'))> Live coverage</label>
                <label class="inline-flex items-center gap-2"><input type="checkbox" name="requires_ad_screens" value="1" @checked(old('requires_ad_screens'))> Ad screens</label>
                <label class="inline-flex items-center gap-2"><input type="checkbox" name="requires_fountains" value="1" @checked(old('requires_fountains'))> Fountains</label>
            </div>

            <label class="block text-sm">
                <span class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Special requests</span>
                <textarea name="special_requests" rows="3" class="w-full px-3 py-2 rounded-xl bg-[#FFFFFF] border border-gray-200 text-sm">{{ old('special_requests', $quoteInput['special_requests'] ?? '') }}</textarea>
            </label>

            <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" formaction="{{ route('venues.quote', $venue->slug) }}"
                        class="h-11 px-6 rounded-xl border border-gray-300 text-sm font-bold text-gray-800 hover:bg-gray-50">
                    Get estimate
                </button>
                <button type="submit"
                        class="h-11 px-6 rounded-xl bg-[#B3261E] text-white text-sm font-black hover:opacity-90">
                    Reserve this venue
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
