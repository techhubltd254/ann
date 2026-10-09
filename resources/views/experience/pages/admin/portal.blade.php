@extends('layouts.blank')

@section('title', 'Admin Portal — KICC Platform')

@section('content')
@php
    $u = auth()->user();
    $cards = [];

    if ($u?->hasRole('kicc_admin')) {
        $cards[] = [
            'href' => route('kicc.admin'), 'color' => '#B3261E', 'title' => 'KICC Overall Admin',
            'desc' => 'Full platform control — all tiers, all counties, orders, escrow release, users.',
            'tag' => 'FULL ACCESS',
            'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
        ];
    }
    if ($u?->hasAnyRole(['national_admin', 'kicc_admin'])) {
        $cards[] = [
            'href' => route('national.admin'), 'color' => '#0B0B0B', 'title' => 'National Government',
            'desc' => 'Ministries & agencies exhibitor portal — national pavilion and trade picture.',
            'tag' => 'NATIONAL EXHIBITOR',
            'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
        ];
    }
    if ($u?->hasAnyRole(['county_admin', 'kicc_admin'])) {
        $cards[] = [
            'href' => route('county.admin'), 'color' => '#B3261E', 'title' => 'County Exhibitor',
            'desc' => 'Your county is a website by itself — products, exhibitors, escrow revenue.',
            'tag' => 'COUNTY SCOPE',
            'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z',
        ];
    }
    if ($u?->hasAnyRole(['exhibitor', 'kicc_admin'])) {
        $cards[] = [
            'href' => route('exhibitor.admin'), 'color' => '#0B0B0B', 'title' => 'Private Exhibitor',
            'desc' => 'Your business storefront — products, orders, and escrow earnings.',
            'tag' => 'BUSINESS',
            'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
        ];
    }
    if ($u?->account_type === 'provider' || $u?->hasRole('kicc_admin')) {
        $cards[] = [
            'href' => route('provider.admin'), 'color' => '#0B0B0B', 'title' => 'Travel Provider',
            'desc' => 'Airline, hotel or cab company — services, prices, bookings & money flow.',
            'tag' => 'CERTIFIED PROVIDER',
            'icon' => 'M12 19l9 2-9-18-9 18 9-2zm0 0v-8',
        ];
    }
@endphp
<div class="min-h-screen bg-[#FFFFFF] flex items-center justify-center p-5">
    <div class="w-full max-w-5xl">
        <div class="text-center mb-10">
            <img src="{{ tile_url('logo') }}" alt="KICC" class="h-16 w-auto mx-auto mb-4" style="filter: brightness(0) invert(0);">
            <h1 class="text-3xl font-black text-gray-900" data-split>Admin Portal</h1>
            <p class="text-[#0B0B0B] mt-2">Four independent exhibitor tiers, one interconnected platform</p>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            @forelse($cards as $c)
            <a href="{{ $c['href'] }}" class="group bg-white rounded-2xl border-2 p-8 text-center transition-all hover:shadow-xl" style="border-color: {{ $c['color'] }}33" onmouseover="this.style.borderColor='{{ $c['color'] }}'" onmouseout="this.style.borderColor='{{ $c['color'] }}33'">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-5" style="background: {{ $c['color'] }}1a">
                    <svg class="w-8 h-8" style="color: {{ $c['color'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $c['icon'] }}"/></svg>
                </div>
                <h2 class="font-black text-gray-900 text-xl mb-2" data-split>{{ $c['title'] }}</h2>
                <p class="text-[#0B0B0B] text-sm leading-relaxed">{{ $c['desc'] }}</p>
                <div class="mt-4 text-xs font-bold uppercase tracking-widest" style="color: {{ $c['color'] }}">{{ $c['tag'] }}</div>
            </a>
            @empty
            <div class="md:col-span-2 bg-white rounded-2xl border border-gray-200 p-10 text-center text-[#0B0B0B] card-hover">
                Your account has no admin tier assigned. Contact KICC.
            </div>
            @endforelse
        </div>

        <div class="mt-8 text-center text-[#0B0B0B] text-xs">
            Signed in as <span class="font-semibold text-gray-900">{{ $u?->name ?? 'Guest' }}</span>
            — <a href="{{ route('home') }}" class="text-[#B3261E] hover:underline" data-magnetic>Back to site</a>
        </div>
    </div>
</div>
@endsection
