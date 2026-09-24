@extends('layouts.app')

@section('title', 'Forgot Password — KICC Platform')
@section('content')
<div class="min-h-screen flex items-center justify-center px-5 pt-20 py-12">
    <div class="w-full max-w-md" data-reveal>
        <div class="text-center mb-8">
            <img src="{{ media('kicc/kicc-logo.png') }}" alt="KICC" class="h-12 w-auto mx-auto mb-3 brightness-0 invert" style="filter: brightness(0) invert(1);">
            <h1 class="text-2xl font-black text-gray-900" data-split>Forgot Password</h1>
            <p class="text-gray-400 text-sm mt-2">Enter your email and we'll send you a reset link</p>
        </div>

        <div class="bg-white border border-gray-100 rounded-2xl p-6 md:p-7">
            @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-600 rounded-xl px-4 py-3 mb-4 text-sm">{{ $errors->first() }}</div>
            @endif
            @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-600 rounded-xl px-4 py-3 mb-4 text-sm">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Email Address</label>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus
                            class="w-full bg-[#F9FAFB] border border-gray-200 focus:border-[#F59E0B]/60 rounded-xl px-4 py-2.5 text-sm text-gray-900 outline-none transition-colors">
                    </div>
                </div>
                <button type="submit"
                    class="w-full inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 mt-6 px-8 text-base h-14 rounded-xl bg-[#901C1E] text-white hover:bg-[#7b1618]" data-magnetic>
                    Send Reset Link
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="{{ route('login') }}" class="text-xs text-[#0EA5E9] font-semibold hover:underline">Back to Sign In</a>
            </div>
        </div>
    </div>
</div>
@endsection