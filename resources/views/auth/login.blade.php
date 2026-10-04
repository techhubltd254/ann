@extends('layouts.app')

@section('title', 'Sign In — KICC Platform')
@section('content')
<div class="min-h-dvh flex items-center justify-center px-5 pt-20 py-12"
     x-data="{ loading: false, showPassword: false }">
    <div class="w-full max-w-md">

        <div class="bg-white border border-gray-100 rounded-2xl p-6 md:p-8">
            <div class="text-center mb-6">
                <img src="{{ media('kicc/kicc-logo.png') }}" alt="KICC" class="h-12 w-auto mx-auto mb-3">
                <h1 class="text-xl font-black text-gray-900" data-split>Sign In</h1>
                <p class="text-gray-400 text-sm mt-1">Sign in to your KICC National Exhibition account</p>
            </div>

            @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-600 rounded-xl px-4 py-3 mb-4 text-sm">{{ $errors->first() }}</div>
            @endif

            {{-- Sign-in form --}}
            <form method="POST" action="{{ route('login') }}"
                  @submit="loading = true">
                @csrf
                <input type="hidden" name="admin_type" value="public">
                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Email or Phone</label>
                        <input type="text" name="login" value="{{ old('login') }}" required autofocus
                               class="w-full bg-[#F9FAFB] border border-gray-200 focus:border-[#F59E0B]/60 rounded-xl px-4 py-2.5 text-sm text-gray-900 placeholder:text-[#5A6480]/50 outline-none transition-colors">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Password</label>
                        <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'" name="password" required
                                   class="w-full bg-[#F9FAFB] border border-gray-200 focus:border-[#F59E0B]/60 rounded-xl px-4 py-2.5 pr-10 text-sm text-gray-900 placeholder:text-[#5A6480]/50 outline-none transition-colors"
                                   style="min-height: 44px">
                            <button type="button" @click="showPassword = !showPassword"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors"
                                    style="min-height: 44px; min-width: 44px" aria-label="Toggle password visibility">
                                <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <circle cx="8" cy="6" r="6" stroke-width="1.5"/>
                                    <circle cx="8" cy="6" r="2" fill="currentColor"/>
                                    <circle cx="22" cy="6" r="6" stroke-width="1.5"/>
                                    <circle cx="22" cy="6" r="2" fill="currentColor"/>
                                    <path d="M6 16Q8 14 10 12l2 4Q4 18 6 20l2 2" stroke-width="1.5"/>
                                    <path d="M20 16Q22 14 16 12l2 4Q18 18 20 20l2 2" stroke-width="1.5"/>
                                </svg>
                                <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <circle cx="8" cy="6" r="6" stroke-width="1.5"/>
                                    <circle cx="22" cy="6" r="6" stroke-width="1.5"/>
                                    <path d="M6 16Q8 14 10 12l2 4Q4 18 6 20l2 2" stroke-width="1.5"/>
                                    <path d="M20 16Q22 14 16 12l2 4Q18 18 20 20l2 2" stroke-width="1.5"/>
                                    <line x1="3" y1="3" x2="13" y2="13" stroke="#901C1E" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <label class="flex items-center gap-2 text-gray-500 cursor-pointer" style="min-height: 44px">
                            <input type="checkbox" name="remember" value="1" class="rounded border-gray-300 accent-[#901C1E]" style="min-width: 20px; min-height: 20px">
                            Keep me signed in
                        </label>
                        <a href="{{ route('password.request') }}" class="text-[#0EA5E9] font-semibold hover:underline">Forgot password?</a>
                    </div>
                </div>
                <button type="submit" :disabled="loading"
                        class="w-full inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 mt-6 px-8 text-base h-14 rounded-xl bg-[#901C1E] text-white hover:bg-[#7b1618] disabled:opacity-60" data-magnetic>
                    <svg x-show="loading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                    <span x-text="loading ? 'Signing you in…' : 'Sign In'"></span>
                </button>
            </form>

            {{-- Google Sign-In (available to all public users) --}}
            <div class="mt-4">
                <div class="flex items-center gap-3 mb-4">
                    <span class="h-px flex-1 bg-gray-200"></span>
                    <span class="text-xs text-gray-400">or</span>
                    <span class="h-px flex-1 bg-gray-200"></span>
                </div>
                <a href="{{ route('auth.google') }}"
                   class="w-full inline-flex items-center justify-center gap-3 font-bold tracking-wide transition-all duration-200 px-8 text-base h-14 rounded-xl border border-gray-200 text-gray-900 hover:bg-gray-50 hover:border-gray-300 card-hover">
                    <svg class="w-5 h-5" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                    Sign in with Google
                </a>
            </div>

            <p class="text-center text-xs mt-6 text-gray-500">
                New to KICC? <a href="{{ route('register') }}" class="text-[#0EA5E9] font-semibold hover:underline">Create your account &rarr;</a>
            </p>
        </div>

    </div>
</div>
@endsection