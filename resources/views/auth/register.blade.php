@extends('layouts.app')

@section('title', 'Register')
@section('description', 'Create your KICC account.')

@section('content')
<div class="min-h-[60vh] flex items-center justify-center py-12">
    <div class="max-w-md w-full mx-4">
        <div class="bg-white rounded-xl p-8 shadow-sm border border-gray-100">
            <h1 class="text-2xl font-bold text-center mb-2">Create Account</h1>
            <p class="text-center text-gray-500 text-sm mb-6">Enter your phone number to get a verification code.</p>

            <form method="POST" action="{{ route('register.send-code') }}">
                @csrf
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                    <div class="flex">
                        <span class="inline-flex items-center px-3 bg-gray-100 border border-r-0 border-gray-300 rounded-l-lg text-gray-500 text-sm">+254</span>
                        <input type="text" name="phone" value="{{ old('phone') }}" placeholder="712345678" class="flex-1 px-4 py-2 border border-gray-300 rounded-r-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500" required>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Enter without the leading 0. e.g. 712345678</p>
                    @error('phone')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="w-full bg-amber-600 text-white py-2.5 rounded-lg font-medium hover:bg-amber-700">Send Verification Code</button>
            </form>

            <p class="text-center text-sm text-gray-500 mt-6">
                Already have an account? <a href="{{ route('login') }}" class="text-amber-600 hover:text-amber-700 font-medium">Sign In</a>
            </p>
        </div>
    </div>
</div>
@endSection
