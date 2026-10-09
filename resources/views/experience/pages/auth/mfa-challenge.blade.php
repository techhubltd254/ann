@extends('layouts.auth')

@section('title', 'Two-Factor Authentication — KICC Platform')
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

        @if($errors->any())
        <div class="bg-red-500/10 border border-red-400/20 text-red-400 rounded-xl px-4 py-3 mb-4 text-sm">{{ $errors->first() }}</div>
        @endif

        <div class="backdrop-blur-2xl bg-white/5 border border-white/10 rounded-3xl shadow-2xl shadow-black/80 p-6 md:p-8">
            <h1 class="text-2xl font-bold tracking-tight text-white text-center mb-1">Two-Factor <span class="text-amber-400">Authentication</span></h1>
            <p class="text-xs text-zinc-400 text-center mb-5">Enter the 6-digit code from your authenticator app.</p>

            <form method="POST" action="{{ route('mfa.challenge.verify') }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-1.5">Authentication Code</label>
                        <input type="text" name="code" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="000000" autocomplete="off"
                               class="w-full px-4 py-3 text-center text-2xl tracking-widest bg-white/5 border border-white/10 rounded-xl text-white placeholder-zinc-500 focus:outline-none focus:border-amber-400/80 focus:ring-1 focus:ring-amber-400/50 transition-all">
                        @error('code')<p class="text-red-400 text-xs mt-1.5">{{ $message }}</p>@enderror
                    </div>
                </div>
                <button type="submit"
                        class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-amber-400 via-yellow-400 to-amber-500 text-[#0B0B0B] font-semibold text-sm inline-flex items-center justify-center gap-2 shadow-lg shadow-amber-500/20 hover:brightness-110 active:scale-[0.98] transition-all mt-6">
                    Verify
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
