@extends('layouts.app')
@section('title', 'Two-Factor Authentication')
@section('content')
<div class="min-h-[60vh] flex items-center justify-center py-12 px-4">
    <div class="max-w-md w-full" data-reveal>
        <div class="bg-white rounded-2xl p-8 border border-gray-200 card-hover">
            <h1 class="text-2xl font-black text-gray-900 text-center mb-2" data-split>Enter Your 2FA Code</h1>
            <p class="text-center text-[#5A6480] text-sm mb-6">Enter the 6-digit code from your authenticator app.</p>
            <form method="POST" action="{{ route('mfa.challenge.verify') }}">
                @csrf
                <input type="text" name="code" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="000000" class="w-full px-4 py-3 text-center text-2xl tracking-widest bg-[#F9FAFB] border border-gray-200 rounded-xl text-gray-900 outline-none focus:ring-2 focus:ring-[#046bd2]/60 transition-all" required autocomplete="off">
                @error('code')<p class="text-[#e86f71] text-xs mt-1.5">{{ $message }}</p>@enderror
                <button type="submit" class="mt-4 w-full h-12 rounded-xl bg-[#046bd2] text-white font-bold hover:bg-[#045cb4] transition-all">Verify</button>
            </form>
        </div>
    </div>
</div>
@endsection