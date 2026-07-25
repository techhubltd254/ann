@extends('layouts.app')

@section('title', 'Complete Registration')
@section('description', 'Set up your KICC account.')

@section('content')
<div class="min-h-[60vh] flex items-center justify-center py-12 px-4">
    <div class="max-w-md w-full" data-reveal>
        <div class="bg-[#0D1220] rounded-2xl p-8 border border-white/8">
            <h1 class="text-2xl font-black text-white text-center mb-2">Complete Your Account</h1>
            <p class="text-center text-white/40 text-sm mb-6">Phone <strong class="text-kicc-gold">{{ $phone }}</strong> verified. Set up your profile.</p>

            <form method="POST" action="{{ route('register.complete') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-bold text-white mb-2">Full Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="w-full h-11 px-4 rounded-xl bg-[#141B2E] border border-white/10 text-white placeholder:text-white/25 outline-none focus:ring-2 focus:ring-kicc-gold/60 transition-all" required>
                    @error('name')<p class="text-[#e86f71] text-xs mt-1.5">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-bold text-white mb-2">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="w-full h-11 px-4 rounded-xl bg-[#141B2E] border border-white/10 text-white placeholder:text-white/25 outline-none focus:ring-2 focus:ring-kicc-gold/60 transition-all" required>
                    @error('email')<p class="text-[#e86f71] text-xs mt-1.5">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-bold text-white mb-2">Account Type</label>
                    <select name="account_type" class="w-full h-11 px-4 rounded-xl bg-[#141B2E] border border-white/10 text-white outline-none focus:ring-2 focus:ring-kicc-gold/60 transition-all">
                        <option value="individual">Individual / Tourist</option>
                        <option value="sme">SME / Exhibitor</option>
                        <option value="school">School / Institution</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-white mb-2">Password</label>
                    <input type="password" name="password" class="w-full h-11 px-4 rounded-xl bg-[#141B2E] border border-white/10 text-white placeholder:text-white/25 outline-none focus:ring-2 focus:ring-kicc-gold/60 transition-all" required minlength="8">
                    @error('password')<p class="text-[#e86f71] text-xs mt-1.5">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-bold text-white mb-2">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="w-full h-11 px-4 rounded-xl bg-[#141B2E] border border-white/10 text-white placeholder:text-white/25 outline-none focus:ring-2 focus:ring-kicc-gold/60 transition-all" required>
                </div>
                <button type="submit" class="w-full h-12 rounded-xl bg-kicc-gold text-[#07090F] font-bold hover:bg-[#e6b904] transition-colors active:scale-[0.98]">Create Account</button>
            </form>
        </div>
    </div>
</div>
@endsection
