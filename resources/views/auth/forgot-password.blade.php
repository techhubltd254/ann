@extends('layouts.auth')

@section('title', 'Forgot Password — KICC Platform')
@section('content')
<div class="fixed inset-0 pointer-events-none z-0" aria-hidden="true">
    <div class="absolute top-1/4 -left-20 w-96 h-96 bg-amber-500/10 rounded-full blur-[120px]"></div>
    <div class="absolute bottom-1/4 -right-20 w-96 h-96 bg-yellow-600/10 rounded-full blur-[140px]"></div>
    <div class="absolute w-[500px] h-[500px] border border-amber-500/15 rotate-45 rounded-[60px] opacity-40 shadow-[0_0_50px_rgba(245,158,11,0.05)]"></div>
</div>

<div class="relative z-10 flex items-center justify-center min-h-dvh px-4 py-12">
    <div class="w-full max-w-md">
        <div class="text-center mb-4">
            <div class="w-14 h-14 mx-auto rounded-xl bg-gradient-to-br from-amber-400 to-yellow-500 flex items-center justify-center mb-3 shadow-lg shadow-amber-500/20">
                <span class="text-2xl font-black text-[#07090F]">K</span>
            </div>
        </div>

        @if($errors->any())
        <div class="bg-red-500/10 border border-red-400/20 text-red-400 rounded-xl px-4 py-3 mb-4 text-sm">{{ $errors->first() }}</div>
        @endif
        @if(session('success'))
        <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl px-4 py-3 mb-4 text-sm">{{ session('success') }}</div>
        @endif

        <div class="backdrop-blur-2xl bg-white/5 border border-white/10 rounded-3xl shadow-2xl shadow-black/80 p-6 md:p-8">
            <h1 class="text-2xl font-bold tracking-tight text-white text-center mb-1">Forgot <span class="text-amber-400">Password</span></h1>
            <p class="text-xs text-zinc-400 text-center mb-5">Enter your email and we'll send you a reset link</p>

            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-1.5">Email Address</label>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="name@example.com"
                               class="w-full px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-amber-400/80 focus:ring-1 focus:ring-amber-400/50 transition-all">
                    </div>
                </div>
                <button type="submit"
                        class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-amber-400 via-yellow-400 to-amber-500 text-[#07090F] font-semibold text-sm inline-flex items-center justify-center gap-2 shadow-lg shadow-amber-500/20 hover:brightness-110 active:scale-[0.98] transition-all mt-6">
                    Send Reset Link
                </button>
            </form>

            <p class="text-center text-xs text-zinc-400 mt-4">
                <a href="{{ route('login') }}" class="text-amber-400 font-semibold hover:underline">Back to Sign In</a>
            </p>
        </div>
    </div>
</div>
@endsection