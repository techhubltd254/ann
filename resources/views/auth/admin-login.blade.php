@extends('layouts.auth')

@section('title', 'Admin Sign In — KICC Platform')
@section('content')
<div class="fixed inset-0 pointer-events-none z-0" aria-hidden="true">
    <div class="absolute top-1/4 -left-20 w-96 h-96 bg-amber-500/10 rounded-full blur-3xl"></div>
    <div class="absolute bottom-1/4 -right-20 w-96 h-96 bg-yellow-600/10 rounded-full blur-3xl"></div>
</div>

<div class="relative z-10 flex items-center justify-center min-h-dvh px-4 py-12"
     x-data="{ showPassword: false }">
    <div class="w-full max-w-md">
        <div class="text-center mb-4">
            <div class="w-14 h-14 mx-auto rounded-xl bg-gradient-to-br from-amber-400 to-yellow-500 flex items-center justify-center mb-3 shadow-lg shadow-amber-500/20">
                <span class="text-2xl font-black text-[#07090F]">K</span>
            </div>
        </div>

        @if($errors->any())
        <div class="bg-red-500/10 border border-red-400/20 text-red-400 rounded-xl px-4 py-3 mb-4 text-sm">{{ $errors->first() }}</div>
        @endif

        <div class="backdrop-blur-2xl bg-white/5 border border-white/10 rounded-3xl shadow-2xl shadow-black/80 p-6 md:p-8">
            <div class="flex items-center justify-center gap-2 mb-3">
                <span class="px-2 py-0.5 rounded-md bg-amber-400/20 text-amber-400 text-[10px] font-semibold uppercase tracking-wider">Admin</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-white text-center mb-1">Admin <span class="text-amber-400">Sign In</span></h1>
            <p class="text-xs text-zinc-400 text-center mb-5">KICC Mother Admin &middot; National &middot; County</p>

            <form method="POST" action="{{ route('auth.admin-login') }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-1.5">Email</label>
                        <div class="relative flex items-center">
                            <svg class="absolute left-3.5 w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <rect x="2" y="5" width="18" height="2" rx="1"/><rect x="2" y="9" width="18" height="2" rx="1"/>
                                <path d="M2 13l8 2 2 4 4 0 6-2-8 2-2 4-4 0-6 2" stroke-width="1.5"/>
                            </svg>
                            <input type="email" name="login" value="{{ old('login') }}" required autofocus placeholder="admin@kicc.go.ke"
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
                </div>
                <button type="submit"
                        class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-amber-400 via-yellow-400 to-amber-500 text-[#07090F] font-semibold text-sm inline-flex items-center justify-center gap-2 shadow-lg shadow-amber-500/20 hover:brightness-110 active:scale-[0.98] transition-all mt-6">
                    Sign In
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
