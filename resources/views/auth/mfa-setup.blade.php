@extends('layouts.app')
@section('title', 'Set Up Two-Factor Authentication')
@section('content')
<div class="min-h-[60vh] flex items-center justify-center py-12 px-4">
    <div class="max-w-md w-full" data-reveal>
        <div class="bg-white rounded-2xl p-8 border border-gray-200 card-hover">
            <h1 class="text-2xl font-black text-gray-900 text-center mb-2" data-split>Two-Factor Authentication</h1>
            <p class="text-center text-[#5A6480] text-sm mb-6">Scan the QR code below with Google Authenticator or any TOTP app.</p>
            <div class="flex justify-center mb-6">{!! $qrCode !!}</div>
            <p class="text-center text-xs text-gray-400 mb-4">Or enter this key manually: <code class="bg-gray-100 px-2 py-0.5 rounded font-mono text-sm">{{ $secret }}</code></p>
            <form method="POST" action="{{ route('mfa.setup.confirm') }}">
                @csrf
                <label class="block text-sm font-bold text-gray-900 mb-2">Enter the 6-digit code from your app</label>
                <input type="text" name="code" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="000000" class="w-full px-4 py-3 text-center text-2xl tracking-widest bg-[#F9FAFB] border border-gray-200 rounded-xl text-gray-900 outline-none focus:ring-2 focus:ring-[#046bd2]/60 transition-all" required autocomplete="off">
                @error('code')<p class="text-[#e86f71] text-xs mt-1.5">{{ $message }}</p>@enderror
                <button type="submit" class="mt-4 w-full h-12 rounded-xl bg-[#046bd2] text-white font-bold hover:bg-[#045cb4] transition-all">Confirm & Enable</button>
            </form>
        </div>
    </div>
</div>
@endsection