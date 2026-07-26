@extends('layouts.app')

@section('title', 'Register — KICC Platform')
@section('content')
<div class="min-h-screen flex items-center justify-center px-5 pt-20">
    <div class="w-full max-w-md" data-reveal>
        <div class="text-center mb-8">
            <img src="{{ media('kicc/kicc-logo.png') }}" alt="KICC" class="h-14 w-auto mx-auto mb-4 brightness-0 invert" style="filter: brightness(0) invert(1);">
            <h1 class="text-2xl font-black text-white">Create your account</h1>
            <p class="text-white/40 text-sm mt-2">Join Kenya's premier exhibition platform</p>
        </div>
        <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6 md:p-7">
            <form method="POST" action="{{ route('register.complete') }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-white/30 uppercase tracking-wider mb-1.5">Full name</label>
                        <input type="text" name="name" required class="w-full bg-[#141B2E] border border-white/10 focus:border-kicc-gold/60 rounded-xl px-4 py-2.5 text-sm text-white placeholder:text-white/25 outline-none transition-colors">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-white/30 uppercase tracking-wider mb-1.5">Phone number</label>
                        <input type="tel" name="phone" required class="w-full bg-[#141B2E] border border-white/10 focus:border-kicc-gold/60 rounded-xl px-4 py-2.5 text-sm text-white placeholder:text-white/25 outline-none transition-colors">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-white/30 uppercase tracking-wider mb-1.5">Email (optional)</label>
                        <input type="email" name="email" class="w-full bg-[#141B2E] border border-white/10 focus:border-kicc-gold/60 rounded-xl px-4 py-2.5 text-sm text-white placeholder:text-white/25 outline-none transition-colors">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-white/30 uppercase tracking-wider mb-1.5">Password</label>
                        <input type="password" name="password" required class="w-full bg-[#141B2E] border border-white/10 focus:border-kicc-gold/60 rounded-xl px-4 py-2.5 text-sm text-white placeholder:text-white/25 outline-none transition-colors">
                    </div>
                </div>
                <button type="submit" data-magnetic class="w-full inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 mt-6 px-8 text-base h-14 rounded-xl bg-[#901C1E] text-white hover:bg-[#7b1618] active:scale-[0.97]">Create Account</button>
            </form>
            <div class="mt-6 pt-5 border-t border-white/8 text-center">
                <a href="{{ route('login') }}" class="text-kicc-gold text-sm font-bold hover:underline">Already have an account? Sign in</a>
            </div>
        </div>
    </div>
</div>
@endsection