@extends('layouts.app')

@section('title', 'Verify Login')
@section('description', 'Enter the verification code sent to your phone.')

@section('content')
<div class="min-h-[60vh] flex items-center justify-center py-12 px-4">
    <div class="max-w-md w-full" data-reveal>
        <div class="bg-white rounded-2xl p-8 border border-gray-200 card-hover">
            @if(session('message'))
            <div class="bg-emerald-500/15 border border-emerald-500/25 text-emerald-400 px-4 py-3 rounded-xl mb-4 text-sm">{{ session('message') }}</div>
            @endif

            <h1 class="text-2xl font-black text-gray-900 text-center mb-2" data-split>Verify Login</h1>
            <p class="text-center text-[#5A6480] text-sm mb-6">Enter the code sent to <strong class="text-kicc-gold">{{ $phone }}</strong></p>

            <form method="POST" action="{{ route('login.code.verify') }}">
                @csrf
                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-900 mb-2">Verification Code</label>
                    <input type="text" name="code" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="000000" class="w-full px-4 py-3 text-center text-2xl tracking-widest bg-[#F9FAFB] border border-gray-200 rounded-xl text-gray-900 placeholder:text-gray-900/20 outline-none focus:ring-2 focus:ring-kicc-gold/60 transition-all" required autocomplete="off">
                    @error('code')<p class="text-[#e86f71] text-xs mt-1.5">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="w-full h-12 rounded-xl bg-kicc-gold text-[#07090F] font-bold hover:bg-[#FFCD05] transition-colors active:scale-[0.98]" data-magnetic>Verify & Sign In</button>
            </form>

            <p class="text-center text-sm text-[#5A6480] mt-4">
                <a href="{{ route('login') }}" class="text-kicc-gold hover:underline font-semibold" data-magnetic>Back to login</a>
            </p>
        </div>
    </div>
</div>
@endsection
