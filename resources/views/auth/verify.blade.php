@extends('layouts.app')

@section('title', 'Verify Phone')
@section('description', 'Enter the verification code sent to your phone.')

@section('content')
<div class="min-h-[60vh] flex items-center justify-center py-12">
    <div class="max-w-md w-full mx-4">
        <div class="bg-white rounded-xl p-8 shadow-sm border border-gray-100">
            @if(session('message'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4 text-sm">{{ session('message') }}</div>
            @endif

            <h1 class="text-2xl font-bold text-center mb-2">Verify Your Phone</h1>
            <p class="text-center text-gray-500 text-sm mb-6">Enter the 6-digit code sent to <strong>{{ $phone }}</strong></p>

            <form method="POST" action="{{ route('register.verify') }}">
                @csrf
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Verification Code</label>
                    <input type="text" name="code" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="000000" class="w-full px-4 py-3 text-center text-2xl tracking-widest border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500" required autocomplete="off">
                    @error('code')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="w-full bg-amber-600 text-white py-2.5 rounded-lg font-medium hover:bg-amber-700">Verify Code</button>
            </form>

            <div class="text-center mt-4">
                <p class="text-sm text-gray-500">
                    Didn't receive it?
                    <a href="{{ route('register') }}" class="text-amber-600 hover:text-amber-700 font-medium">Try again</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endSection
