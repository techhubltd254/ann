@extends('layouts.auth')

@section('title', 'Complete Registration — KICC Platform')
@section('description', 'Set up your KICC account.')
@section('content')
<div class="fixed inset-0 pointer-events-none z-0" aria-hidden="true">
    <div class="absolute top-1/4 -left-20 w-96 h-96 bg-amber-500/10 rounded-full blur-[120px]"></div>
    <div class="absolute bottom-1/4 -right-20 w-96 h-96 bg-yellow-600/10 rounded-full blur-[140px]"></div>
    <div class="absolute w-[500px] h-[500px] border border-amber-500/15 rotate-45 rounded-[60px] opacity-40 shadow-[0_0_50px_rgba(245,158,11,0.05)]"></div>
</div>

<div class="relative z-10 flex items-center justify-center min-h-dvh px-4 py-12" x-data="{ loading: false }" @submit="loading = true">
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
            {{-- Step indicator --}}
            <div class="flex items-center justify-center gap-2 mb-4">
                <span class="w-2 h-2 rounded-full bg-amber-400 shadow-sm shadow-amber-400/50"></span>
                <span class="w-4 h-0.5 border-t border-solid border-amber-400/50"></span>
                <span class="w-2 h-2 rounded-full bg-amber-400 shadow-sm shadow-amber-400/50"></span>
                <span class="w-4 h-0.5 border-t border-solid border-amber-400/50"></span>
                <span class="w-2 h-2 rounded-full bg-amber-400 shadow-sm shadow-amber-400/50"></span>
            </div>

            <h1 class="text-2xl font-bold tracking-tight text-white text-center mb-1">Complete Your <span class="text-amber-400">Account</span></h1>
            <p class="text-xs text-zinc-400 text-center mb-5">Email <strong class="text-amber-400">{{ $email }}</strong> verified. Set up your profile.</p>

            <form method="POST" action="{{ route('register.complete') }}" @submit="loading = true">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-1.5">Full Name <span class="text-amber-400">*</span></label>
                        <div class="relative flex items-center">
                            <svg class="absolute left-3.5 w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <circle cx="12" cy="6" r="8"/><path d="M6 16Q10 14 14 14l4 2" stroke-width="1.5"/>
                                <circle cx="12" cy="6" r="8"/><path d="M6 14l8 2 3 4-3 4-3 0-8" stroke-width="1.5"/>
                            </svg>
                            <input type="text" name="name" value="{{ old('name') }}" required placeholder="Alex Johnson"
                                   class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-amber-400/80 focus:ring-1 focus:ring-amber-400/50 transition-all">
                        </div>
                        @error('name')<p class="text-red-400 text-xs mt-1.5">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-1.5">Phone <span class="text-zinc-500 font-normal">(optional — for M-Pesa receipts)</span></label>
                        <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="+254 7XX XXX XXX"
                               class="w-full px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-amber-400/80 focus:ring-1 focus:ring-amber-400/50 transition-all">
                        @error('phone')<p class="text-red-400 text-xs mt-1.5">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-1.5">Account Type <span class="text-amber-400">*</span></label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-center justify-center gap-2 py-2 px-3 rounded-xl bg-white/5 border border-white/10 text-xs text-zinc-300 cursor-pointer hover:bg-white/10 transition-all" @click="$event.target.querySelector('input').checked = true">
                                <input type="radio" name="account_type" value="exhibitor" class="sr-only" {{ old('account_type')==='exhibitor' ? 'checked' : '' }}>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M4 4l16 0 0 16-16 0 0-16"/><path d="M4 4l16 0 0 16-16 0 0-16"/></svg>
                                <span>Exhibitor</span>
                            </label>
                            <label class="flex items-center justify-center gap-2 py-2 px-3 rounded-xl bg-white/5 border border-white/10 text-xs text-zinc-300 cursor-pointer hover:bg-white/10 transition-all" @click="$event.target.querySelector('input').checked = true">
                                <input type="radio" name="account_type" value="individual" class="sr-only" {{ old('account_type')==='individual' ? 'checked' : '' }}>
                                <span>Individual</span>
                            </label>
                            <label class="flex items-center justify-center gap-2 py-2 px-3 rounded-xl bg-white/5 border border-white/10 text-xs text-zinc-300 cursor-pointer hover:bg-white/10 transition-all" @click="$event.target.querySelector('input').checked = true">
                                <input type="radio" name="account_type" value="sme" class="sr-only" {{ old('account_type')==='sme' ? 'checked' : '' }}>
                                <span>SME</span>
                            </label>
                            <label class="flex items-center justify-center gap-2 py-2 px-3 rounded-xl bg-white/5 border border-white/10 text-xs text-zinc-300 cursor-pointer hover:bg-white/10 transition-all" @click="$event.target.querySelector('input').checked = true">
                                <input type="radio" name="account_type" value="school" class="sr-only" {{ old('account_type')==='school' ? 'checked' : '' }}>
                                <span>School</span>
                            </label>
                        </div>
                        @error('account_type')<p class="text-red-400 text-xs mt-1.5">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-1.5">Password <span class="text-amber-400">*</span></label>
                        <input type="password" name="password" required minlength="8" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                               class="w-full px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-amber-400/80 focus:ring-1 focus:ring-amber-400/50 transition-all">
                        @error('password')<p class="text-red-400 text-xs mt-1.5">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-1.5">Confirm Password <span class="text-amber-400">*</span></label>
                        <input type="password" name="password_confirmation" required minlength="8" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                               class="w-full px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-amber-400/80 focus:ring-1 focus:ring-amber-400/50 transition-all">
                    </div>
                </div>
                <button type="submit" :disabled="loading"
                        class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-amber-400 via-yellow-400 to-amber-500 text-[#07090F] font-semibold text-sm inline-flex items-center justify-center gap-2 shadow-lg shadow-amber-500/20 hover:brightness-110 active:scale-[0.98] transition-all mt-6 disabled:opacity-60">
                    <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                    <span x-text="loading ? 'Creating your account&hellip;' : 'Create Account'"></span>
                    <svg x-show="!loading" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l7 0 0 3-9 3-9 6-2 6-2 9-7 9-7 12l-3 0"/></svg>
                </button>
            </form>
        </div>
    </div>
</div>
@endsection