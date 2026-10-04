@extends('layouts.blank')

@section('title', 'Admin Sign In')

@push('styles')
<style>
body { background: #0A1024; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
.admin-wrap{ background: rgba(0,0,0,0.55); backdrop-filter: blur(18px) saturate(1.3); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 2rem; width: 100%; max-width: 28rem; }
.admin-wrap a{ color: rgba(255,255,255,0.4); font-size: 12px; text-decoration: none; }
.admin-wrap a:hover{ color: #FFCD05; }
</style>
@endpush

<section class="min-h-dvh flex items-center justify-center px-5">
    <div class="admin-wrap p-6 md:p-8">
        <form method="POST" action="{{ route('auth.admin-login') }}" class="space-y-4" x-data="{}">
            @csrf
            <div class="text-center mb-4">
                <div class="w-12 h-12 mx-auto rounded-xl bg-gradient-to-br from-[#F59E0B] to-[#D97706] flex items-center justify-center mb-3">
                    <span class="text-2xl">K</span>
                </div>
                <h1 class="text-white font-bold text-lg">Admin Sign In</h1>
                <p class="text-white/50 text-xs mt-1">KICC Mother Admin · National · County</p>
            </div>

            @if($errors->any())
            <div class="bg-white/10 border border-red-400/30 text-red-400 rounded-xl px-4 py-3 mb-4 text-sm">{{ $errors->first() }}</div>
            @endif

            <div>
                <label for="login" class="block text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-1.5">Email</label>
                <input id="login" type="email" name="login" value="{{ old('login') }}" required autofocus
                       class="w-full bg-white/5 border border-white/10 focus:border-[#F59E0B]/60 rounded-xl px-4 py-2.5 text-sm text-white placeholder:text-zinc-500 outline-none transition-colors"
                       placeholder="admin@kicc.go.ke">
            </div>
            <div>
                <label for="password" class="block text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-1.5">Password</label>
                <div class="relative">
                <input id="password" type="password" name="password" required
                       class="w-full bg-white/5 border border-white/10 focus:border-[#F59E0B]/60 rounded-xl px-4 py-2.5 pr-10 text-sm text-white placeholder:text-zinc-500 outline-none transition-colors"
                       style="min-height:44px" placeholder="Enter your password"
                       x-ref="pw">
                <button type="button" @click="pw.type = pw.type === 'password' ? 'text' : 'password'"
                        class="absolute right-2 top-1/2 -translate-y-1/2 p-2 text-zinc-400 hover:text-zinc-200 rounded-lg hover:bg-white/10 transition-colors"
                        style="min-height:44px; min-width:44px" aria-label="Toggle password visibility">
                    <svg x-show="pw.type === 'password'" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5">
                        <path d="M15 12.5l2.5 0m-2.5 0"/>
                        <path d="M10 5.5l-2-1.5 3 0.5 1.5-1.5 1-3 1-1.5-0.5-3.5-1.5-5"/>
                        <path d="M19.5 5l1-1.5 2-1-1.5 1.5-.5-1.5-3 1-4.5 1-1"/>
                        <circle cx="12" cy="6" r="5"/><circle cx="20.5" cy="6" r="5"/>
                        <circle cx="12" cy="6" r="1.5" fill="currentColor"/><circle cx="20.5" cy="6" r="1.5" fill="currentColor"/>
                    </svg>
                    <svg x-show="pw.type === 'text'" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5">
                        <path d="M15 12.5l2.5 0m-2.5 0"/>
                        <path d="M10 5.5l-2-1.5 3 0.5 1.5-1.5 1-3 1-1.5-0.5-3.5-1.5-5"/>
                        <path d="M19.5 5l1-1.5 2-1-1.5 1.5-.5-1.5-3 1-4.5 1-1"/>
                        <circle cx="12" cy="6" r="5"/><circle cx="20.5" cy="6" r="5"/>
                        <line x1="3" y1="2" x2="21" y2="10" stroke-width="2" stroke="#901C1E"/>
                        <line x1="21" y1="2" x2="3" y2="10" stroke-width="2" stroke="#901C1E"/>
                    </svg>
                </button>
                </div>
            </div>

            <button type="submit"
                    class="w-full inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 mt-2 px-8 text-base h-14 rounded-xl bg-gradient-to-br from-[#F59E0B] to-[#D97706] text-[#07090F] hover:brightness-110 active:scale-[0.98]">
                Sign In
            </button>
        </form>
    </div>
</section>