@extends('layouts.auth')

@section('title', 'Register — KICC Platform')
@section('content')
<div class="fixed inset-0 pointer-events-none z-0" aria-hidden="true">
    <div class="absolute top-1/4 -left-20 w-96 h-96 bg-amber-500/10 rounded-full blur-3xl"></div>
    <div class="absolute bottom-1/4 -right-20 w-96 h-96 bg-yellow-600/10 rounded-full blur-3xl"></div>
</div>

<div class="relative z-10 flex items-center justify-center min-h-dvh px-4 py-12">
    <div class="w-full max-w-md">
        <div class="text-center mb-4">
            <div class="w-14 h-14 mx-auto rounded-xl bg-gradient-to-br from-amber-400 to-yellow-500 flex items-center justify-center mb-3 shadow-lg shadow-amber-500/20">
                <img src="{{ asset('kicc-logo.png') }}" alt="KICC" class="h-10 w-10 object-contain">
            </div>
        </div>

        <div class="flex border-b border-white/10 mb-6">
            <a href="{{ route('login') }}" class="flex-1 py-2.5 px-4 rounded-t-xl bg-transparent text-zinc-400 text-sm font-semibold hover:text-white transition-all">Sign In</a>
            <button class="flex-1 py-2.5 px-4 rounded-t-xl bg-white/10 text-white text-sm font-semibold transition-all">Create Account</button>
        </div>

        @if($errors->any())
        <div class="bg-red-500/10 border border-red-400/20 text-red-400 rounded-xl px-4 py-3 mb-4 text-sm">{{ $errors->first() }}</div>
        @endif
        @if(session('message'))
        <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl px-4 py-3 mb-4 text-sm">{{ session('message') }}</div>
        @endif

        <div class="backdrop-blur-2xl bg-white/5 border border-white/10 rounded-3xl shadow-2xl shadow-black/80 p-6 md:p-8">
            {{-- Step indicator --}}
            <div class="flex items-center justify-center gap-2 mb-4">
                <span class="w-2 h-2 rounded-full bg-amber-400 shadow-sm shadow-amber-400/50"></span>
                <span class="w-4 h-0.5 border-t border-dashed border-zinc-600"></span>
                <span class="w-2 h-2 rounded-full bg-zinc-600"></span>
                <span class="w-4 h-0.5 border-t border-dashed border-zinc-600"></span>
                <span class="w-2 h-2 rounded-full bg-zinc-600"></span>
            </div>

            <h1 class="text-2xl font-bold tracking-tight text-white text-center mb-1">Create <span class="text-amber-400">Account</span></h1>
            <p class="text-xs text-zinc-400 text-center mb-5">Join Kenya's premier exhibition platform</p>

            <form method="POST" action="{{ route('register.send-code') }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-1.5">Email Address</label>
                        <div class="relative flex items-center">
                            <svg class="absolute left-3.5 w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <rect x="2" y="5" width="18" height="2" rx="1"/><rect x="2" y="9" width="18" height="2" rx="1"/>
                                <path d="M2 13l8 2 2 4 4 0 6-2-8 2-2 4-4 0-6 2" stroke-width="1.5"/>
                            </svg>
                            <input type="email" name="email" value="{{ old('email') }}" required placeholder="you@example.com"
                                   class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-amber-400/80 focus:ring-1 focus:ring-amber-400/50 transition-all">
                        </div>
                        <p class="text-[11px] text-zinc-500 mt-1.5">We'll email you a 6-digit verification code.</p>
                    </div>
                </div>
                <div class="cf-turnstile" data-site-key="__TURNSTILE_SITE_KEY__"></div>
                <input type="hidden" name="cf-turnstile-response" value="__TURNSTILE_RESPONSE__">
                <button type="submit"
                        class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-amber-400 via-yellow-400 to-amber-500 text-[#0B0B0B] font-semibold text-sm inline-flex items-center justify-center gap-2 shadow-lg shadow-amber-500/20 hover:brightness-110 active:scale-[0.98] transition-all mt-6">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l7 0 0 3-9 3-9 6-2 6-2 9-7 9-7 12l-3 0"/></svg>
                    Send Verification Code
                </button>
            </form>

            <div class="mt-5">
                <div class="relative flex items-center justify-center">
                    <div class="border-t border-white/10 w-full"></div>
                    <span class="px-3 text-[11px] text-zinc-400 uppercase tracking-wider">or</span>
                    <div class="border-t border-white/10 w-full"></div>
                </div>
                <div class="grid grid-cols-2 gap-3 mt-3">
                    <a href="{{ route('auth.google') }}"
                       class="flex items-center justify-center gap-2 py-2 px-3 rounded-xl bg-white/5 border border-white/10 text-xs font-medium text-zinc-200 hover:bg-white/10 hover:border-white/20 transition-all">
                        <svg class="w-4 h-4" viewBox="0 0 24 24"><path fill="#0B0B0B" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/><path fill="#0B0B0B" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FFCD05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#B3261E" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                        Google
                    </a>
                    <button disabled
                            class="flex items-center justify-center gap-2 py-2 px-3 rounded-xl bg-white/5 border border-white/10 text-xs font-medium text-zinc-500 opacity-50 cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M8 8h8l0 8h-8l0-8" stroke-width="2"/></svg>
                        Apple <span class="text-[10px] opacity-60">Coming soon</span>
                    </button>
                </div>
            </div>
        </div>

        <p class="text-center text-xs text-zinc-400 mt-5">
            Already have an account? <a href="{{ route('login') }}" class="text-amber-400 font-semibold hover:underline">Sign In</a>
        </p>
    </div>
</div>
@endsection
