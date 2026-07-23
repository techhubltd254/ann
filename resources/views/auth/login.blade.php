@extends('layouts.app')

@section('title', 'Login')
@section('description', 'Sign in to your KICC account.')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center py-12 px-4">
    <div class="max-w-md w-full">
        <div class="bg-white rounded-2xl p-8 shadow-lg border border-gray-100">
            <h1 class="text-2xl font-bold text-center mb-1">Welcome Back</h1>
            <p class="text-center text-gray-500 text-sm mb-6">Choose how you'd like to sign in</p>

            @error('login')<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-4 text-sm">{{ $message }}</div>@enderror
            @error('google')<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-4 text-sm">{{ $message }}</div>@enderror

            {{-- Google Login (Viewer) --}}
            <a href="{{ route('auth.google') }}"
               class="flex items-center justify-center gap-3 w-full bg-white border-2 border-gray-200 hover:border-gray-300 text-gray-700 py-3 rounded-xl font-medium transition-all mb-4 hover:shadow-md">
                <svg class="w-5 h-5" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                <span class="text-sm">Continue with Google</span>
            </a>

            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-200"></div></div>
                <div class="relative flex justify-center"><span class="bg-white px-4 text-sm text-gray-400">or sign in with email</span></div>
            </div>

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email or Phone</label>
                    <input type="text" name="login" value="{{ old('login') }}"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm"
                           required autofocus placeholder="email@example.com or 712345678">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm"
                           required placeholder="••••••••">
                </div>
                <button type="submit"
                        class="w-full bg-amber-500 text-white py-2.5 rounded-xl font-semibold hover:bg-amber-600 shadow-lg shadow-amber-500/25 transition-all text-sm">
                    Sign In
                </button>
            </form>

            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-200"></div></div>
                <div class="relative flex justify-center"><span class="bg-white px-4 text-xs text-gray-400 uppercase tracking-wider">or use code</span></div>
            </div>

            <form method="POST" action="{{ route('login.send-code') }}">
                @csrf
                <input type="hidden" name="login" value="{{ old('login') }}">
                <button type="submit"
                        class="w-full bg-white text-amber-600 border-2 border-amber-500 py-2.5 rounded-xl font-semibold hover:bg-amber-50 transition-all text-sm">
                    Sign In with Verification Code
                </button>
            </form>

            <p class="text-center text-sm text-gray-500 mt-6">
                Don't have an account? <a href="{{ route('register') }}" class="text-amber-600 hover:text-amber-700 font-medium">Register as Exhibitor</a>
            </p>
        </div>

        <div class="mt-4 text-center">
            <a href="{{ url('/admin') }}" class="text-xs text-gray-400 hover:text-gray-600 transition-colors">
                Admin Panel →
            </a>
        </div>
    </div>
</div>
@endSection
