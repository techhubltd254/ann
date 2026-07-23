@extends('layouts.app')

@section('title', 'My Dashboard')
@section('description', 'Your KICC dashboard — manage bookings, exhibitions, and profile.')

@section('content')
<div class="bg-gradient-to-br from-amber-50 to-orange-100 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl font-bold text-gray-900 mb-2">Welcome, {{ auth()->user()->name }}</h1>
        <p class="text-lg text-gray-600">Manage your exhibitions, bookings, and profile.</p>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
            <div class="text-3xl mb-3">🎟️</div>
            <h3 class="text-2xl font-bold text-gray-900">{{ $stats['total_bookings'] }}</h3>
            <p class="text-gray-600 text-sm">Total Bookings</p>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
            <div class="text-3xl mb-3">📅</div>
            <h3 class="text-2xl font-bold text-gray-900">{{ $stats['upcoming_bookings'] }}</h3>
            <p class="text-gray-600 text-sm">Upcoming Events</p>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
            <div class="text-3xl mb-3">🏛️</div>
            <h3 class="text-2xl font-bold text-gray-900">{{ $stats['exhibitions'] }}</h3>
            <p class="text-gray-600 text-sm">My Exhibitions</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <a href="{{ route('dashboard.exhibitions') }}" class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 hover:shadow-md hover:border-amber-300 transition-all group">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-lg group-hover:text-amber-600">Manage Exhibitions</h3>
                    <p class="text-sm text-gray-500 mt-1">View and manage your exhibitions and booths.</p>
                </div>
                <span class="text-2xl group-hover:translate-x-1 transition-transform">&rarr;</span>
            </div>
        </a>
        <a href="{{ route('dashboard.bookings') }}" class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 hover:shadow-md hover:border-amber-300 transition-all group">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-lg group-hover:text-amber-600">My Bookings</h3>
                    <p class="text-sm text-gray-500 mt-1">View your booth and ticket bookings.</p>
                </div>
                <span class="text-2xl group-hover:translate-x-1 transition-transform">&rarr;</span>
            </div>
        </a>
        <a href="{{ route('dashboard.profile') }}" class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 hover:shadow-md hover:border-amber-300 transition-all group">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-lg group-hover:text-amber-600">My Profile</h3>
                    <p class="text-sm text-gray-500 mt-1">Update your account details and preferences.</p>
                </div>
                <span class="text-2xl group-hover:translate-x-1 transition-transform">&rarr;</span>
            </div>
        </a>
    </div>
</div>
@endSection
