@extends('layouts.app')

@section('title', 'Sign In — KICC Platform')
@section('content')
<div class="min-h-screen flex items-center justify-center px-5 pt-20 py-12"
     x-data="{
        step: 'portal',
        portal: null,
        loading: false,
        portals: [
            { id: 'kicc',     title: 'KICC Mother Admin',    desc: 'Platform-wide control — all counties, national, exhibitors', icon: '', color: 'from-[#F59E0B] to-[#D97706]' },
            { id: 'national', title: 'National Government',   desc: 'Ministries & agencies portal',                              icon: '', color: 'from-[#0EA5E9] to-[#0284C7]' },
            { id: 'exhibitor',title: 'Exhibitor',             desc: 'Your storefront & marketplace dashboard',                   icon: '', color: 'from-[#2D6A4F] to-[#40916C]' },
        ],
        select(p) { this.portal = p; this.step = 'form'; },
        back() { this.step = 'portal'; this.portal = null; }
     }">
    <div class="w-full max-w-4xl grid md:grid-cols-2 gap-6" data-reveal>

        {{-- LEFT: Portal Selector / Sign In --}}
        <div class="bg-white border border-gray-100 rounded-2xl p-6 md:p-8">
            <div class="text-center mb-6">
                <img src="{{ media('kicc/kicc-logo.png') }}" alt="KICC" class="h-12 w-auto mx-auto mb-3">
                <h1 class="text-xl font-black text-gray-900" data-split x-text="step === 'portal' ? 'Choose your portal' : 'Sign in to portal'"></h1>
                <p class="text-gray-400 text-sm mt-1" x-text="step === 'portal' ? 'Select where you are entering as' : portal ? 'Enter credentials for ' + portal.title : ''"></p>
            </div>

            @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-600 rounded-xl px-4 py-3 mb-4 text-sm">{{ $errors->first() }}</div>
            @endif

            {{-- STEP 1: Choose portal --}}
            <div x-show="step === 'portal'" x-cloak x-transition class="space-y-3">
                <template x-for="p in portals" :key="p.id">
                    <button type="button" @click="select(p)"
                            class="w-full flex items-center gap-4 p-4 rounded-2xl border-2 border-gray-200 hover:border-gray-400 transition-all text-left group">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br flex items-center justify-center text-2xl shrink-0"
                             :class="p.color">
                            <span x-text="p.icon"></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-bold text-gray-900 text-sm group-hover:text-gray-700" x-text="p.title"></div>
                            <div class="text-xs text-gray-400 mt-0.5" x-text="p.desc"></div>
                        </div>
                        <svg class="w-4 h-4 text-gray-300 group-hover:text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </template>
                <div class="text-center pt-2">
                    <a href="{{ route('register') }}" class="text-xs text-[#0EA5E9] font-semibold hover:underline">New? Create an exhibitor account →</a>
                </div>
            </div>

            {{-- STEP 2: Credentials --}}
            <form x-show="step === 'form'" x-cloak x-transition method="POST" action="{{ route('login') }}"
                  @submit="loading = true">
                @csrf
                <input type="hidden" name="admin_type" :value="portal.id">
                <div class="flex items-center gap-2 mb-5">
                    <button type="button" @click="back()" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition-colors" aria-label="Back to portal selection">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <span class="inline-flex items-center gap-2 text-xs font-bold text-gray-500">
                        <span class="text-base" x-text="portal.icon"></span>
                        <span x-text="portal.title"></span>
                    </span>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Email or Phone</label>
                        <input type="text" name="login" value="{{ old('login') }}" required autofocus
                               class="w-full bg-[#F9FAFB] border border-gray-200 focus:border-[#F59E0B]/60 rounded-xl px-4 py-2.5 text-sm text-gray-900 placeholder:text-[#5A6480]/50 outline-none transition-colors">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Password</label>
                        <input type="password" name="password" required
                               class="w-full bg-[#F9FAFB] border border-gray-200 focus:border-[#F59E0B]/60 rounded-xl px-4 py-2.5 text-sm text-gray-900 placeholder:text-[#5A6480]/50 outline-none transition-colors">
                    </div>
                </div>
                <button type="submit" :disabled="loading"
                        class="w-full inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 mt-6 px-8 text-base h-14 rounded-xl bg-[#901C1E] text-white hover:bg-[#7b1618] disabled:opacity-60" data-magnetic>
                    <svg x-show="loading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                    <span x-text="loading ? 'Signing you in…' : 'Sign In'"></span>
                </button>
            </form>

            {{-- Google Sign-In (exhibitor path) --}}
            <div class="mt-4" x-show="step === 'form' && portal.id === 'exhibitor'" x-cloak>
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
        </div>

        {{-- RIGHT: Portal info / Sign Up --}}
        <div class="bg-gradient-to-br from-[#0B1E57] to-[#0A1024] rounded-2xl p-6 md:p-8 flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-2 mb-4">
                    <span class="bg-[#FFCD05]/20 text-[#FFCD05] text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full">KICC Platform</span>
                    <span class="text-white/60 text-xs">One account, four portals</span>
                </div>
                <h2 class="text-2xl font-black text-white leading-tight" data-split>Enter where you<br>belong</h2>
                <p class="text-white/70 text-sm mt-3 leading-relaxed">
                    The KICC Mother Admin governs everything. Below it sit the 
                    <strong class="text-white">National Government</strong> and <strong class="text-white">Exhibitor</strong> storefronts.
                    County admins log in through the KICC Mother Admin portal.
                </p>
                <div class="mt-6 space-y-3">
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-white/5 border border-white/10">
                        <span class="text-xl"></span>
                        <div>
                            <div class="text-white text-sm font-bold">KICC Mother Admin</div>
                            <div class="text-white/50 text-xs">Counties, national, exhibitors, orders, escrow, users</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-white/5 border border-white/10">
                        <span class="text-xl"></span>
                        <div>
                            <div class="text-white text-sm font-bold">National Government</div>
                            <div class="text-white/50 text-xs">Ministries & agencies portal</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-white/5 border border-white/10">
                        <span class="text-xl"></span>
                        <div>
                            <div class="text-white text-sm font-bold">Exhibitor</div>
                            <div class="text-white/50 text-xs">Your storefront, products & orders</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-6 space-y-2">
                <a href="{{ route('register') }}" class="block w-full text-center py-3.5 rounded-xl bg-[#FFCD05] text-[#0B1E57] font-black text-sm hover:bg-[#ffe44d] transition-all active:scale-[0.98]">
                    Create Your Exhibitor Account →
                </a>
                <p class="text-white/40 text-[11px] text-center">Takes 2 minutes. 3 quick setup questions to build your website.</p>
            </div>
        </div>
    </div>
</div>
@endsection