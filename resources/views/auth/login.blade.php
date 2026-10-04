@extends('layouts.auth')

@section('title', 'Sign In — KICC Platform')
@section('content')
<div class="fixed inset-0 pointer-events-none z-0" aria-hidden="true">
    <div class="absolute top-1/4 -left-20 w-96 h-96 bg-amber-500/10 rounded-full blur-[120px]"></div>
    <div class="absolute bottom-1/4 -right-20 w-96 h-96 bg-yellow-600/10 rounded-full blur-[140px]"></div>
    <div class="absolute w-[500px] h-[500px] border border-amber-500/15 rotate-45 rounded-[60px] opacity-40 shadow-[0_0_50px_rgba(245,158,11,0.05)]"></div>
</div>

<div class="relative z-10 flex items-center justify-center min-h-dvh px-4 py-12"
     x-data="{ loading: false, showPassword: false }">
    <div class="w-full max-w-md">
        <div class="text-center mb-4">
            <div class="w-14 h-14 mx-auto rounded-xl bg-gradient-to-br from-amber-400 to-yellow-500 flex items-center justify-center mb-3 shadow-lg shadow-amber-500/20">
                <span class="text-2xl font-black text-[#07090F]">K</span>
            </div>
        </div>

        <div class="flex border-b border-white/10 mb-6">
            <button class="flex-1 py-2.5 px-4 rounded-t-xl bg-white/10 text-white text-sm font-semibold transition-all">Sign In</button>
            <a href="{{ route('register') }}" class="flex-1 py-2.5 px-4 rounded-t-xl bg-transparent text-zinc-400 text-sm font-semibold hover:text-white transition-all">Create Account</a>
        </div>

        @if($errors->any())
        <div class="bg-red-500/10 border border-red-400/20 text-red-400 rounded-xl px-4 py-3 mb-4 text-sm">{{ $errors->first() }}</div>
        @endif

        <div class="backdrop-blur-2xl bg-white/5 border border-white/10 rounded-3xl shadow-2xl shadow-black/80 p-6 md:p-8">
            <h1 class="text-2xl font-bold tracking-tight text-white text-center mb-1">Welcome <span class="text-amber-400">Back</span></h1>
            <p class="text-xs text-zinc-400 text-center mb-5">Enter your credentials to access your secure account</p>

            <form method="POST" action="{{ route('login') }}" @submit="loading = true">
                @csrf
                <input type="hidden" name="admin_type" value="public">
                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-1.5">Email or Phone</label>
                        <div class="relative flex items-center">
                            <svg class="absolute left-3.5 w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <rect x="2" y="5" width="18" height="2" rx="1"/><rect x="2" y="9" width="18" height="2" rx="1"/>
                                <path d="M2 13l8 2 2 4 4 0 6-2-8 2-2 4-4 0-6 2" stroke-width="1.5"/>
                            </svg>
                            <input type="text" name="login" value="{{ old('login') }}" required autofocus placeholder="name@example.com"
                                   class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-amber-400/80 focus:ring-1 focus:ring-amber-400/50 transition-all">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-1.5">Password</label>
                        <div class="relative flex items-center">
                            <svg class="absolute left-3.5 w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <circle cx="12" cy="8" r="6"/><circle cx="12" cy="8" r="2"/>
                                <path d="M5 18Q7 16 10 14l3 3Q10 18 7 20l2 2" stroke-width="1.5"/>
                            </svg>
                            <input :type="showPassword ? 'text' : 'password'" name="password" required placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                                   class="w-full pl-10 pr-10 py-2.5 rounded-xl bg-white/5 border border-white/10 text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-amber-400/80 focus:ring-1 focus:ring-amber-400/50 transition-all">
                            <button type="button" @click="showPassword = !showPassword"
                                    class="absolute right-3.5 text-zinc-400 hover:text-white transition-colors" aria-label="Toggle password visibility">
                                <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <ellipse cx="8" cy="7" rx="5" ry="6"/><ellipse cx="17" cy="7" rx="5" ry="6"/>
                                    <circle cx="8" cy="7" r="1.5" fill="currentColor"/><circle cx="17" cy="7" r="1.5" fill="currentColor"/>
                                    <path d="M3 3Q6 2 8 2l4 0" stroke-width="1"/><path d="M20 3Q23 2 13 2l4 0" stroke-width="1"/>
                                </svg>
                                <svg x-show="showPassword" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <ellipse cx="8" cy="7" rx="5" ry="6"/><ellipse cx="17" cy="7" rx="5" ry="6"/>
                                    <path d="M3 3Q6 2 8 2l4 0" stroke-width="1"/><path d="M20 3Q23 2 13 2l4 0" stroke-width="1"/>
                                    <line x1="2" y1="2" x2="14" y2="13" stroke="#901C1E" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <label class="flex items-center gap-2 cursor-pointer text-zinc-300 select-none">
                            <input type="checkbox" name="remember" value="1" class="rounded accent-amber-400 bg-zinc-800 border-zinc-700" style="min-width:20px;min-height:20px">
                            Remember me
                        </label>
                        <a href="{{ route('password.request') }}" class="text-amber-400 hover:text-amber-300 transition-colors">Forgot Password?</a>
                    </div>
                </div>
                <button type="submit" :disabled="loading"
                        class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-amber-400 via-yellow-400 to-amber-500 text-[#07090F] font-semibold text-sm inline-flex items-center justify-center gap-2 shadow-lg shadow-amber-500/20 hover:brightness-110 active:scale-[0.98] transition-all mt-6 disabled:opacity-60">
                    <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                    <span x-text="loading ? 'Signing you in&hellip;' : 'Sign In'"></span>
                    <svg x-show="!loading" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M9 12l7 0 0 3-9 3-9 6-2 6-2 9-7 9-7 12l-3 0"/>
                    </svg>
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
                        <svg class="w-4 h-4" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                        Google
                    </a>
                    <button disabled
                            class="flex items-center justify-center gap-2 py-2 px-3 rounded-xl bg-white/5 border border-white/10 text-xs font-medium text-zinc-500 opacity-50 cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10"/><path d="M8 8h8l0 8h-8l0-8" stroke-width="2"/>
                        </svg>
                        Apple <span class="text-[10px] opacity-60">Coming soon</span>
                    </button>
                </div>
            </div>
        </div>

        <p class="text-center text-xs text-zinc-400 mt-5">
            New to KICC? <a href="{{ route('register') }}" class="text-amber-400 font-semibold hover:underline">Create your account &rarr;</a>
        </p>
    </div>
</div>
@endsection