@extends('layouts.app')

@section('title', 'My Profile')
@section('description', 'Update your KICC account profile.')

@section('content')
<div class="bg-gradient-to-br from-amber-50 to-orange-100 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <a href="{{ route('dashboard.index') }}" class="text-amber-600 hover:text-amber-700 mb-4 inline-block">&larr; Dashboard</a>
        <h1 class="text-4xl font-bold text-gray-900 mb-2">My Profile</h1>
        <p class="text-lg text-gray-600">Manage your account details.</p>
    </div>
</div>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
        <form method="POST" action="{{ route('dashboard.profile.update') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500" required>
                @error('name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" value="{{ auth()->user()->email }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50" disabled>
                <p class="text-xs text-gray-500 mt-1">Email cannot be changed.</p>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                <input type="text" name="phone" value="{{ old('phone', auth()->user()->phone) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                @error('phone')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="bg-amber-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-amber-700">Save Changes</button>
        </form>
    </div>
</div>
@endSection
