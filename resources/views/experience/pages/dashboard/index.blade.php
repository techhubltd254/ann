@extends('layouts.admin-theme', ['title' => 'My Dashboard', 'themeColor' => '#06B6D4', 'brandName' => 'My Account', 'brandSub' => 'KICC'])

@section('title', 'My Dashboard')
@section('description', 'Your KICC dashboard — manage bookings, exhibitions, and profile.')

@section('content')
<div class="bg-zinc-900 border-b border-white/10 py-12 relative overflow-hidden">
    <div class="absolute w-72 h-72 rounded-full bg-[#06B6D4]/8 blur-3xl -top-16 right-10"></div>
    <div class="max-w-7xl mx-auto px-5 relative" data-reveal>
        <h1 class="text-3xl md:text-4xl font-black text-white mb-2" data-split>Welcome, <span class="text-[#06B6D4]">{{ auth()->user()->name }}</span></h1>
        <p class="text-zinc-400">Manage your exhibitions, bookings, and profile.</p>
    </div>
</div>

<div class="max-w-7xl mx-auto px-5 py-10">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-10">
        @foreach([['', $stats['total_bookings'], 'Total Bookings'], ['', $stats['upcoming_bookings'], 'Upcoming Events'], ['', $stats['exhibitions'], 'My Exhibitions']] as $i => $s)
        <div class="glass-card rounded-2xl p-6 card-hover" data-tilt="6" data-reveal data-reveal-delay="{{ $i * 90 }}">
            <div class="tilt-glare"></div>
            <div class="text-3xl mb-3">{{ $s[0] }}</div>
            <h3 class="text-3xl font-black text-white"><span data-count="{{ $s[1] }}">0</span></h3>
            <p class="text-zinc-400 text-sm mt-1">{{ $s[2] }}</p>
        </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach([
            ['dashboard.exhibitions', 'Manage Exhibitions', 'View and manage your exhibitions and booths.', ''],
            ['dashboard.bookings', 'My Bookings', 'View your booth and ticket bookings.', ''],
            ['dashboard.profile', 'My Profile', 'Update your account details and preferences.', ''],
            ['room3d.index', '3D Room Explorer', 'Upload photos and explore spaces in 3D.', ''],
        ] as $i => $l)
        <a href="{{ route($l[0]) }}" class="glass-card rounded-2xl p-6 hover:border-[#06B6D4]/40 transition-all group card-hover block" data-reveal data-reveal-delay="{{ $i * 70 }}" data-magnetic>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <span class="text-2xl">{{ $l[3] }}</span>
                    <div>
                        <h3 class="font-bold text-white group-hover:text-[#06B6D4] transition-colors">{{ $l[1] }}</h3>
                        <p class="text-sm text-zinc-400 mt-0.5">{{ $l[2] }}</p>
                    </div>
                </div>
                <svg class="w-5 h-5 text-white/20 group-hover:text-[#06B6D4] group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </div>
        </a>
        @endforeach
    </div>
</div>
@endsection
