@extends('layouts.app')

@section('title', $exhibition->name)
@section('description', $exhibition->tagline ?? $exhibition->description)

@section('content')
<div class="bg-white border-b border-gray-200 py-12 relative overflow-hidden">
    <div class="absolute w-96 h-96 rounded-full bg-[#901C1E]/10 blur-3xl -top-20 right-0"></div>
    <div class="max-w-7xl mx-auto px-5 relative">
        <a href="{{ route('exhibitions.index') }}" class="text-[#5A6480] hover:text-kicc-gold text-sm mb-4 inline-block transition-colors" data-magnetic>&larr; Back to Exhibitions</a>
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4" data-reveal>
            <div>
                <h1 class="text-3xl md:text-4xl font-black text-gray-900 mb-2" data-split>{{ $exhibition->name }}</h1>
                @if($exhibition->tagline)
                <p class="text-lg text-[#5A6480]">{{ $exhibition->tagline }}</p>
                @endif
                <div class="flex flex-wrap gap-3 mt-4">
                    <span class="bg-[#1890D7]/8 border border-gray-200 text-[#5A6480] px-3 py-1 rounded-lg text-xs font-semibold">{{ $exhibition->start_date->format('M d, Y') }} - {{ $exhibition->end_date->format('M d, Y') }}</span>
                    @if($exhibition->county)
                    <a href="{{ route('counties.show', $exhibition->county->slug) }}" class="bg-[#1890D7]/8 border border-gray-200 text-[#5A6480] px-3 py-1 rounded-lg text-xs font-semibold hover:border-kicc-gold/40 hover:text-kicc-gold transition-colors">{{ $exhibition->county->name }}</a>
                    @endif
                    <span class="bg-emerald-500/15 border border-emerald-500/25 text-emerald-400 px-3 py-1 rounded-lg text-xs font-semibold">{{ ucfirst($exhibition->status) }}</span>
                </div>
            </div>
            @if($exhibition->status === 'published')
            <a href="#booking" data-magnetic class="inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-8 text-base h-14 rounded-xl bg-[#901C1E] text-gray-900 hover:bg-[#7b1618] active:scale-[0.97] shrink-0">Book a Booth</a>
            @endif
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-5 py-12">
    @if($exhibition->cover_image)
    <div class="rounded-2xl overflow-hidden mb-8 border border-gray-200 card-hover" data-reveal>
        <img src="{{ $exhibition->cover_image }}" alt="{{ $exhibition->name }}" class="w-full h-64 lg:h-96 object-cover">
    </div>
    @else
    <div class="w-full h-64 bg-white rounded-2xl mb-8 flex items-center justify-center text-6xl border border-gray-200 card-hover" data-reveal>🏛️</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            @if($exhibition->description)
            <div class="bg-white rounded-2xl p-6 border border-gray-200 card-hover" data-reveal>
                <h2 class="text-lg font-black text-gray-900 mb-4" data-split>About This Exhibition</h2>
                <p class="text-[#5A6480] leading-relaxed text-sm">{{ $exhibition->description }}</p>
            </div>
            @endif

            @if($exhibition->sessions && $exhibition->sessions->count() > 0)
            <div class="bg-white rounded-2xl p-6 border border-gray-200 card-hover" data-reveal>
                <h2 class="text-lg font-black text-gray-900 mb-4" data-split>Schedule</h2>
                <div class="space-y-4">
                    @foreach($exhibition->sessions as $session)
                    <div class="border-l-2 border-kicc-gold pl-4">
                        <div class="text-xs text-gray-400">{{ $session->start_time->format('M d, Y g:i A') }}</div>
                        <h4 class="font-bold text-gray-900 text-sm mt-0.5">{{ $session->name }}</h4>
                        @if($session->speaker)<p class="text-xs text-[#5A6480] mt-0.5">By {{ $session->speaker }}</p>@endif
                        @if($session->description)<p class="text-xs text-gray-400 mt-1">{{ $session->description }}</p>@endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <div class="space-y-6">
            @if($exhibition->venue)
            <div class="bg-white rounded-2xl p-6 border border-gray-200 card-hover" data-reveal>
                <h3 class="font-bold text-gray-900 mb-3 text-sm uppercase tracking-wider">Venue</h3>
                <a href="{{ route('venues.show', $exhibition->venue->slug) }}" class="text-kicc-gold hover:underline font-bold">{{ $exhibition->venue->name }}</a>
                @if($exhibition->venue->city)<p class="text-xs text-[#5A6480] mt-1">{{ $exhibition->venue->city }}</p>@endif
            </div>
            @endif

            <div id="booking" class="bg-white rounded-2xl p-6 border border-gray-200 card-hover" data-reveal>
                <h3 class="font-bold text-gray-900 mb-4 text-sm uppercase tracking-wider">Available Booths</h3>
                @if($exhibition->booths && $exhibition->booths->count() > 0)
                <div class="space-y-3 max-h-72 overflow-y-auto pr-1">
                    @foreach($exhibition->booths as $booth)
                    <div class="flex justify-between items-center pb-3 border-b border-gray-100 last:border-0 last:pb-0">
                        <div>
                            <p class="font-bold text-gray-900 text-sm">{{ $booth->booth_number }}@if($booth->name) — {{ $booth->name }}@endif</p>
                            <p class="text-xs text-gray-400">{{ ucfirst($booth->size) }} · {{ ucfirst($booth->category) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-black text-kicc-gold text-sm">KES {{ number_format($booth->price) }}</p>
                            <p class="text-[10px] text-[#5A6480]">{{ $booth->max_quantity - $booth->booked_quantity }} left</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                @auth
                <a href="{{ route('dashboard.bookings') }}" data-magnetic class="mt-5 w-full inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-4 text-sm h-11 rounded-xl bg-kicc-gold text-[#07090F] hover:bg-[#FFCD05]">Book Your Booth</a>
                @else
                <a href="{{ route('login') }}" class="mt-5 w-full inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-4 text-sm h-11 rounded-xl bg-[#901C1E] text-gray-900 hover:bg-[#7b1618]" data-magnetic>Sign In to Book</a>
                @endauth
                @else
                <p class="text-[#5A6480] text-sm">No booths available yet.</p>
                @endif
            </div>

            @if($exhibition->ticketTypes && $exhibition->ticketTypes->count() > 0)
            <div class="bg-white rounded-2xl p-6 border border-gray-200 card-hover" data-reveal>
                <h3 class="font-bold text-gray-900 mb-4 text-sm uppercase tracking-wider">Tickets</h3>
                <div class="space-y-3">
                    @foreach($exhibition->ticketTypes as $ticketType)
                    <div class="flex justify-between items-center pb-3 border-b border-gray-100 last:border-0 last:pb-0">
                        <div>
                            <p class="font-bold text-gray-900 text-sm">{{ $ticketType->name }}</p>
                            @if($ticketType->description)<p class="text-xs text-gray-400">{{ Str::limit($ticketType->description, 40) }}</p>@endif
                        </div>
                        <div class="text-right">
                            <p class="font-black text-kicc-gold text-sm">KES {{ number_format($ticketType->discount_price ?? $ticketType->price) }}</p>
                            @if($ticketType->discount_price)<p class="text-[10px] line-through text-[#5A6480]">KES {{ number_format($ticketType->price) }}</p>@endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @auth
                <a href="{{ route('dashboard.bookings') }}" data-magnetic class="mt-5 w-full inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-4 text-sm h-11 rounded-xl bg-kicc-gold text-[#07090F] hover:bg-[#FFCD05]">Buy Tickets</a>
                @else
                <a href="{{ route('login') }}" class="mt-5 w-full inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-4 text-sm h-11 rounded-xl bg-[#901C1E] text-gray-900 hover:bg-[#7b1618]" data-magnetic>Sign In to Buy</a>
                @endauth
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
