@extends('layouts.app')

@section('title', 'Sign In — KICC Platform')
@section('content')
<div class="min-h-screen flex items-center justify-center px-5 pt-20 py-12" x-data="{ staff: false }">
    <div class="w-full max-w-4xl grid md:grid-cols-2 gap-6" data-reveal>

        {{-- LEFT: Sign In --}}
        <div class="bg-white border border-gray-100 rounded-2xl p-6 md:p-8">
            <div class="text-center mb-6">
                <img src="{{ media('kicc/kicc-logo.png') }}" alt="KICC" class="h-12 w-auto mx-auto mb-3">
                <h1 class="text-xl font-black text-gray-900" data-split>Welcome back</h1>
                <p class="text-gray-400 text-sm mt-1">Sign in to manage your portal</p>
            </div>

            @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-600 rounded-xl px-4 py-3 mb-4 text-sm">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}" x-data="{ loading: false }" @submit="loading = true">
                @csrf
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

            {{-- Google Sign-In --}}
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

            <div class="mt-6 pt-5 border-t border-gray-100 text-center">
                <p class="text-xs text-gray-400 mb-2">Staff accounts are routed by role automatically</p>
                <button @click="staff = !staff" class="text-gray-400 hover:text-gray-500 text-xs transition-colors">
                    <span x-text="staff ? '▾ Hide staff roles' : '▸ Staff roles (KICC / National / County)'"></span>
                </button>
                <div x-show="staff" x-cloak x-transition class="mt-3">
                    <div class="grid grid-cols-3 gap-2 text-center text-[11px] font-bold">
                        <div class="border border-[#901C1E]/30 rounded-xl py-2 text-[#e86f71]">KICC Admin</div>
                        <div class="border border-[#7C3AED]/30 rounded-xl py-2 text-[#a78bfa]">National</div>
                        <div class="border border-[#F59E0B]/30 rounded-xl py-2 text-[#fbbf24]">County</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- RIGHT: Sign Up as Exhibitor --}}
        <div class="bg-gradient-to-br from-[#0EA5E9] to-[#0B1E57] rounded-2xl p-6 md:p-8 flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-2 mb-4">
                    <span class="bg-white/20 text-white text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full">New</span>
                    <span class="text-white/80 text-xs">Exhibitor onboarding</span>
                </div>
                <h2 class="text-2xl font-black text-white leading-tight" data-split>Start selling on the<br>KICC Marketplace</h2>
                <p class="text-white/80 text-sm mt-3 leading-relaxed">
                    Register as an exhibitor and get your own <strong class="text-white">personalized website &amp; storefront</strong> — 
                    post your products, manage orders, receive escrow-protected payments, and track everything from your dashboard.
                </p>
                <ul class="mt-5 space-y-2.5">
                    <li class="flex items-start gap-2.5 text-sm text-white/80">
                        <svg class="w-4 h-4 mt-0.5 shrink-0 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        <span>Your own <strong class="text-white">public exhibitor website</strong> at <code class="text-white/60 text-xs">/exhibitor/your-brand</code></span>
                    </li>
                    <li class="flex items-start gap-2.5 text-sm text-white/80">
                        <svg class="w-4 h-4 mt-0.5 shrink-0 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        <span>List products, apartments, restaurant menus or services</span>
                    </li>
                    <li class="flex items-start gap-2.5 text-sm text-white/80">
                        <svg class="w-4 h-4 mt-0.5 shrink-0 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        <span>Escrow-protected payments — buyer pays, you ship, funds released</span>
                    </li>
                    <li class="flex items-start gap-2.5 text-sm text-white/80">
                        <svg class="w-4 h-4 mt-0.5 shrink-0 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        <span>From a <strong class="text-white">clothes seller</strong> to a <strong class="text-white">Westlands apartment</strong> to <strong class="text-white">Villarosa Kempinski</strong> — every business gets a portal</span>
                    </li>
                </ul>
            </div>
            <div class="mt-6 space-y-2">
                <a href="{{ route('register') }}" class="block w-full text-center py-3.5 rounded-xl bg-white text-[#0B1E57] font-black text-sm hover:bg-white/90 transition-all active:scale-[0.98]">
                    Create Your Exhibitor Account →
                </a>
                <p class="text-white/50 text-[11px] text-center">Takes 2 minutes. 3 quick setup questions to build your website.</p>
            </div>
        </div>
    </div>
</div>
@endsection