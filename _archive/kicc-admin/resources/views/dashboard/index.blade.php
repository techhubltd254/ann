@extends('layouts.app')

@section('title', 'My Dashboard')
@section('description', 'Your KICC dashboard — manage bookings, exhibitions, and profile.')

@section('content')
<div class="bg-white border-b border-gray-200 py-12 relative overflow-hidden">
    <div class="absolute w-72 h-72 rounded-full bg-[#FFCD05]/8 blur-3xl -top-16 right-10"></div>
    <div class="max-w-7xl mx-auto px-5 relative" data-reveal>
        <h1 class="text-3xl md:text-4xl font-black text-gray-900 mb-2">Welcome, <span class="text-kicc-gold">{{ auth()->user()->name }}</span></h1>
        <p class="text-[#5A6480]">Manage your exhibitions, bookings, and profile.</p>
    </div>
</div>

<div class="max-w-7xl mx-auto px-5 py-10">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-10">
        @foreach([['🎟️', $stats['total_bookings'], 'Total Bookings'], ['📅', $stats['upcoming_bookings'], 'Upcoming Events'], ['🏛️', $stats['exhibitions'], 'My Exhibitions']] as $i => $s)
        <div class="bg-white rounded-2xl p-6 border border-gray-200 card-hover" data-tilt="6" data-reveal data-reveal-delay="{{ $i * 90 }}">
            <div class="tilt-glare"></div>
            <div class="text-3xl mb-3">{{ $s[0] }}</div>
            <h3 class="text-3xl font-black text-gray-900"><span data-count="{{ $s[1] }}">0</span></h3>
            <p class="text-[#5A6480] text-sm mt-1">{{ $s[2] }}</p>
        </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach([
            ['dashboard.exhibitions', 'Manage Exhibitions', 'View and manage your exhibitions and booths.', '🏛️'],
            ['dashboard.bookings', 'My Bookings', 'View your booth and ticket bookings.', '🎟️'],
            ['dashboard.profile', 'My Profile', 'Update your account details and preferences.', '👤'],
            ['room3d.index', '3D Room Explorer', 'Upload photos and explore spaces in 3D.', '🏠'],
        ] as $i => $l)
        <a href="{{ route($l[0]) }}" class="bg-white rounded-2xl p-6 border border-gray-200 hover:border-kicc-gold/40 transition-all group card-hover block" data-reveal data-reveal-delay="{{ $i * 70 }}">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <span class="text-2xl">{{ $l[3] }}</span>
                    <div>
                        <h3 class="font-bold text-gray-900 group-hover:text-kicc-gold transition-colors">{{ $l[1] }}</h3>
                        <p class="text-sm text-[#5A6480] mt-0.5">{{ $l[2] }}</p>
                    </div>
                </div>
                <svg class="w-5 h-5 text-gray-900/20 group-hover:text-kicc-gold group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </div>
        </a>
        @endforeach
    </div>
</div>
@endsection
