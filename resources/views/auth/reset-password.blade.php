@extends('layouts.app')

@section('title', 'Reset Password — KICC Platform')
@section('content')
<div class="min-h-screen flex items-center justify-center px-5 pt-20 py-12">
    <div class="w-full max-w-md" data-reveal>
        <div class="text-center mb-8">
            <img src="{{ media('kicc/kicc-logo.png') }}" alt="KICC" class="h-12 w-auto mx-auto mb-3 brightness-0 invert" style="filter: brightness(0) invert(1);">
            <h1 class="text-2xl font-black text-gray-900" data-split>Set New Password</h1>
            <p class="text-gray-400 text-sm mt-2">Enter your new password below</p>
        </div>

        <div class="bg-white border border-gray-100 rounded-2xl p-6 md:p-7">
            @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-600 rounded-xl px-4 py-3 mb-4 text-sm">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token ?? request('token') }}">
                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Email Address</label>
                        <input type="email" name="email" value="{{ old('email', request('email')) }}" required
                            class="w-full bg-[#F9FAFB] border border-gray-200 focus:border-[#F59E0B]/60 rounded-xl px-4 py-2.5 text-sm text-gray-900 outline-none transition-colors">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">New Password</label>
                        <input type="password" name="password" required minlength="8"
                            class="w-full bg-[#F9FAFB] border border-gray-200 focus:border-[#F59E0B]/60 rounded-xl px-4 py-2.5 text-sm text-gray-900 outline-none transition-colors">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Confirm Password</label>
                        <input type="password" name="password_confirmation" required minlength="8"
                            class="w-full bg-[#F9FAFB] border border-gray-200 focus:border-[#F59E0B]/60 rounded-xl px-4 py-2.5 text-sm text-gray-900 outline-none transition-colors">
                    </div>
                </div>
                <button type="submit"
                    class="w-full inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 mt-6 px-8 text-base h-14 rounded-xl bg-[#901C1E] text-white hover:bg-[#7b1618]" data-magnetic>
                    Reset Password
                </button>
            </form>
        </div>
    </div>
</div>
@endsection