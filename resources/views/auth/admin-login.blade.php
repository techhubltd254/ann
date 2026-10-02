@extends('layouts.blank')

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
        <form method="POST" action="{{ route('auth.admin-login') }}" class="space-y-4">
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
                <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-1.5">Email</label>
                <input type="email" name="login" value="{{ old('login') }}" required autofocus
                       class="w-full bg-white/5 border border-white/10 focus:border-[#F59E0B]/60 rounded-xl px-4 py-2.5 text-sm text-white placeholder:text-zinc-500 outline-none transition-colors"
                       placeholder="admin@kicc.go.ke">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-1.5">Password</label>
                <input type="password" name="password" required
                       class="w-full bg-white/5 border border-white/10 focus:border-[#F59E0B]/60 rounded-xl px-4 py-2.5 text-sm text-white placeholder:text-zinc-500 outline-none transition-colors"
                       style="min-height:44px" placeholder="Enter your password">
            </div>

            <button type="submit"
                    class="w-full inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 mt-2 px-8 text-base h-14 rounded-xl bg-gradient-to-br from-[#F59E0B] to-[#D97706] text-[#07090F] hover:brightness-110 active:scale-[0.98]">
                Sign In
            </button>
        </form>

        <div class="mt-5 text-center text-xs">
            <a href="{{ route('login') }}" class="text-zinc-400 hover:text-[#FFCD05] transition-colors">&larr; Public sign-in</a>
        </div>
    </div>
</section>