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
                    @if(isset($liveStream) && $liveStream->isLive())
                    <span class="bg-red-500/15 border border-red-500/25 text-red-500 px-3 py-1 rounded-lg text-xs font-semibold flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>LIVE
                    </span>
                    @endif
                </div>
            </div>
            <div class="flex gap-2">
                @if(isset($liveStream) && $liveStream->isLive())
                <a href="{{ route('streams.show', $liveStream) }}" class="inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-6 text-sm h-11 rounded-xl bg-red-500 text-white hover:bg-red-600 shrink-0">
                    <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span> Watch Live
                </a>
                @endif
                @if($exhibition->status === 'published')
                <a href="#booking" class="inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-8 text-base h-14 rounded-xl bg-[#901C1E] text-gray-900 hover:bg-[#7b1618] active:scale-[0.97] shrink-0">Book a Booth</a>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-5 py-12">
    {{-- Tabs --}}
    @php
        $tabs = ['about' => 'About'];
        if (isset($liveStream) && $liveStream->hls_url) $tabs['live'] = 'Live Stream';
        if (isset($liveStream)) $tabs['chat'] = 'Chat';
        $tabs['booths'] = 'Booths & Tickets';
        $activeTab = request()->get('tab', 'about');
    @endphp

    <div class="flex gap-1 mb-8 overflow-x-auto pb-1" x-data="{ tab: '{{ $activeTab }}' }">
        @foreach($tabs as $key => $label)
        <button @click="tab = '{{ $key }}'; history.replaceState(null, '', '?tab={{ $key }}')"
                class="shrink-0 px-4 py-2 rounded-xl text-xs font-bold transition-all"
                :class="tab === '{{ $key }}' ? 'bg-[#0B1E57] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
            {{ $label }}
        </button>
        @endforeach
    </div>

    @foreach($tabs as $key => $label)
    <div x-show="tab === '{{ $key }}'" x-cloak>
        @if($key === 'about')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                @if($exhibition->cover_image)
                <div class="rounded-2xl overflow-hidden border border-gray-200" data-reveal>
                    <img src="{{ $exhibition->cover_image }}" alt="{{ $exhibition->name }}" class="w-full h-64 lg:h-96 object-cover">
                </div>
                @endif

                @if($exhibition->description)
                <div class="bg-white rounded-2xl p-6 border border-gray-200" data-reveal>
                    <h2 class="text-lg font-black text-gray-900 mb-4" data-split>About This Exhibition</h2>
                    <p class="text-[#5A6480] leading-relaxed text-sm">{{ $exhibition->description }}</p>
                </div>
                @endif

                @if($exhibition->sessions && $exhibition->sessions->count() > 0)
                <div class="bg-white rounded-2xl p-6 border border-gray-200" data-reveal>
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
                <div class="bg-white rounded-2xl p-6 border border-gray-200" data-reveal>
                    <h3 class="font-bold text-gray-900 mb-3 text-sm uppercase tracking-wider">Venue</h3>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('venues.show', $exhibition->venue->slug) }}" class="text-kicc-gold hover:underline font-bold">{{ $exhibition->venue->name }}</a>
                        @if($exhibition->venue->latitude && $exhibition->venue->longitude)
                        <a href="https://www.google.com/maps/search/?api=1&query={{ $exhibition->venue->latitude }},{{ $exhibition->venue->longitude }}" target="_blank" rel="noopener"
                           class="inline-flex items-center gap-1 text-[10px] text-[#5A6480] hover:text-[#901C1E] transition-colors" title="View on Google Maps">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.73 7 13 7 13s7-7.27 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5S14.5 7.62 14.5 9s-1.12 2.5-2.5 2.5z"/></svg>
                            Map
                        </a>
                        @else
                        <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($exhibition->venue->name . ', ' . ($exhibition->venue->city ?? '')) }}" target="_blank" rel="noopener"
                           class="inline-flex items-center gap-1 text-[10px] text-[#5A6480] hover:text-[#901C1E] transition-colors" title="Search on Google Maps">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.73 7 13 7 13s7-7.27 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5S14.5 7.62 14.5 9s-1.12 2.5-2.5 2.5z"/></svg>
                            Map
                        </a>
                        @endif
                    </div>
                    @if($exhibition->venue->city)<p class="text-xs text-[#5A6480] mt-1">{{ $exhibition->venue->city }}</p>@endif
                </div>
                @endif

                <x-booth-3d height="300px" />

                <div class="bg-white rounded-2xl p-6 border border-gray-200" data-reveal>
                    <h3 class="font-bold text-gray-900 mb-3 text-sm uppercase tracking-wider">Organizer</h3>
                    @if($exhibition->organizer_info)
                    <p class="text-xs text-[#5A6480]">{{ $exhibition->organizer_info['name'] ?? 'KICC' }}</p>
                    @if(isset($exhibition->organizer_info['email']))<p class="text-xs text-[#5A6480] mt-1">{{ $exhibition->organizer_info['email'] }}</p>@endif
                    @else
                    <p class="text-xs text-[#5A6480]">KICC Global Exhibitions</p>
                    @endif
                </div>
            </div>
        </div>

        @elseif($key === 'live' && isset($liveStream) && $liveStream->hls_url)
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <x-hls-player
                    :hls-url="$liveStream->hls_url"
                    :poster="$liveStream->thumbnail_url ?? null"
                    :title="$liveStream->name"
                    :autoplay="$liveStream->isLive()"
                    class="aspect-video"
                />
            </div>
            <div class="bg-white border border-gray-200 rounded-2xl p-5">
                <h3 class="font-bold text-gray-900 text-sm mb-2">{{ $liveStream->name }}</h3>
                @if($liveStream->description)<p class="text-gray-500 text-xs leading-relaxed">{{ $liveStream->description }}</p>@endif
                <div class="flex items-center gap-2 mt-4">
                    @if($liveStream->isLive())
                    <span class="text-[10px] font-bold flex items-center gap-1.5 text-red-500"><span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span> LIVE</span>
                    <span class="text-[10px] text-gray-400">{{ $liveStream->formattedViewerCount() }} watching</span>
                    @else
                    <span class="text-[10px] font-bold text-gray-400">OFFLINE</span>
                    @endif
                </div>
            </div>
        </div>

        @elseif($key === 'chat' && isset($liveStream))
        <div class="max-w-md mx-auto">
            <x-live-chat :live-stream-id="$liveStream->id" height="500px" />
        </div>

        @elseif($key === 'booths')
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div id="booking" class="bg-white rounded-2xl p-6 border border-gray-200" data-reveal>
                <h3 class="font-bold text-gray-900 mb-4 text-sm uppercase tracking-wider">Available Booths</h3>
                @if($exhibition->booths && $exhibition->booths->count() > 0)
                <div class="space-y-3 max-h-72 overflow-y-auto pr-1">
                    @foreach($exhibition->booths as $booth)
                    <div class="flex justify-between items-center pb-3 border-b border-gray-100 last:border-0 last:pb-0">
                        <div>
                            <p class="font-bold text-gray-900 text-sm">{{ $booth->booth_number }}@if($booth->name) — {{ $booth->name }}@endif</p>
                            <p class="text-xs text-gray-400">{{ ucfirst($booth->size) }} &middot; {{ ucfirst($booth->category) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-black text-kicc-gold text-sm">KES {{ number_format($booth->price) }}</p>
                            <p class="text-[10px] text-[#5A6480]">{{ $booth->max_quantity - $booth->booked_quantity }} left</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                @auth
                <a href="{{ route('dashboard.bookings') }}" class="mt-5 w-full inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-4 text-sm h-11 rounded-xl bg-kicc-gold text-[#07090F] hover:bg-[#FFCD05]">Book Your Booth</a>
                @else
                <a href="{{ route('login') }}" class="mt-5 w-full inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-4 text-sm h-11 rounded-xl bg-[#901C1E] text-gray-900 hover:bg-[#7b1618]">Sign In to Book</a>
                @endauth
                @else
                <p class="text-[#5A6480] text-sm">No booths available yet.</p>
                @endif
            </div>

            @if($exhibition->ticketTypes && $exhibition->ticketTypes->count() > 0)
            <div class="bg-white rounded-2xl p-6 border border-gray-200" data-reveal>
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
                <a href="{{ route('dashboard.bookings') }}" class="mt-5 w-full inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-4 text-sm h-11 rounded-xl bg-kicc-gold text-[#07090F] hover:bg-[#FFCD05]">Buy Tickets</a>
                @else
                <a href="{{ route('login') }}" class="mt-5 w-full inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-4 text-sm h-11 rounded-xl bg-[#901C1E] text-gray-900 hover:bg-[#7b1618]">Sign In to Buy</a>
                @endauth
            </div>
            @endif
        </div>
        @endif
    </div>
    @endforeach
</div>
@endsection
