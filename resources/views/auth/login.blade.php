@extends('layouts.app')

@section('title', 'Sign In — KICC Platform')
@section('content')
<div class="min-h-screen flex items-center justify-center px-5 pt-20" x-data="{ step: 'select' }">
    <div class="w-full max-w-md" data-reveal>
        {{-- STEP 1: Choose admin type --}}
        <div x-show="step === 'select'" x-cloak>
            <div class="text-center mb-8">
                <img src="{{ media('kicc/kicc-logo.png') }}" alt="KICC" class="h-14 w-auto mx-auto mb-4 brightness-0 invert" style="filter: brightness(0) invert(1);">
                <h1 class="text-2xl font-black text-white">Sign in as</h1>
                <p class="text-white/40 text-sm mt-2">Choose your administrative access level</p>
            </div>
            <div class="space-y-3">
                <button @click="step = 'kicc'" class="w-full bg-[#0D1220] border border-white/10 hover:border-[#901C1E]/50 rounded-2xl p-5 text-left transition-all group">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-[#901C1E]/15 rounded-xl flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-[#e86f71]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <div>
                            <div class="font-black text-white group-hover:text-[#e86f71] transition-colors">KICC Admin</div>
                            <div class="text-white/35 text-xs mt-0.5">Full platform control — all counties, users, settings</div>
                        </div>
                    </div>
                </button>
                <button @click="step = 'national'" class="w-full bg-[#0D1220] border border-white/10 hover:border-[#0B1E57]/50 rounded-2xl p-5 text-left transition-all group">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-[#0B1E57]/15 rounded-xl flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-[#0B1E57]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <div>
                            <div class="font-black text-white group-hover:text-[#0B1E57] transition-colors">National Government</div>
                            <div class="text-white/35 text-xs mt-0.5">Ministries, agencies, national content</div>
                        </div>
                    </div>
                </button>
                <button @click="step = 'county'" class="w-full bg-[#0D1220] border border-white/10 hover:border-kicc-gold/40 rounded-2xl p-5 text-left transition-all group">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-[#FFCD05]/15 rounded-xl flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-kicc-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div>
                            <div class="font-black text-white group-hover:text-kicc-gold transition-colors">County Admin</div>
                            <div class="text-white/35 text-xs mt-0.5">Manage your county's content & revenue</div>
                        </div>
                    </div>
                </button>
            </div>
            <div class="mt-6 text-center">
                <a href="{{ route('login') }}" class="text-sm text-white/40 hover:text-kicc-gold font-semibold transition-colors">&larr; Standard user login</a>
            </div>
        </div>

        {{-- STEP 2: Login form (shared) --}}
        <div x-show="step !== 'select'" x-cloak>
            <div class="text-center mb-6">
                <button @click="step = 'select'" class="inline-flex items-center gap-1 text-white/40 hover:text-kicc-gold text-sm mb-4 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Back
                </button>
                <img src="{{ media('kicc/kicc-logo.png') }}" alt="KICC" class="h-14 w-auto mx-auto mb-4 brightness-0 invert" style="filter: brightness(0) invert(1);">
                <h1 class="text-2xl font-black text-white">
                    <span x-text="step === 'kicc' ? 'KICC Admin' : (step === 'national' ? 'National Government' : 'County Admin')"></span>
                </h1>
                <p class="text-white/40 text-sm mt-2">Enter your credentials to continue</p>
            </div>
            <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6 md:p-7">
                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <input type="hidden" name="admin_type" :value="step">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-white/30 uppercase tracking-wider mb-1.5">Email</label>
                            <input type="email" name="login" required class="w-full bg-[#141B2E] border border-white/10 focus:border-kicc-gold/60 rounded-xl px-4 py-2.5 text-sm text-white placeholder:text-white/25 outline-none transition-colors">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-white/30 uppercase tracking-wider mb-1.5">Password</label>
                            <input type="password" name="password" required class="w-full bg-[#141B2E] border border-white/10 focus:border-kicc-gold/60 rounded-xl px-4 py-2.5 text-sm text-white placeholder:text-white/25 outline-none transition-colors">
                        </div>
                    </div>
                    <button type="submit" data-magnetic class="w-full inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 mt-6 px-8 text-base h-14 rounded-xl bg-[#901C1E] text-white hover:bg-[#7b1618] active:scale-[0.97]">Sign In</button>
                </form>
                <div class="mt-6 pt-5 border-t border-white/8 text-center">
                    <a href="{{ route('register') }}" class="text-kicc-gold text-sm font-bold hover:underline">Don't have an account? Register</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection