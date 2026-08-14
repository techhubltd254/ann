@extends('layouts.app')

@section('title', 'Complete Registration')
@section('description', 'Set up your KICC account.')

@section('content')
<div class="min-h-[60vh] flex items-center justify-center py-12 px-4">
    <div class="max-w-md w-full" data-reveal>
        <div class="bg-white rounded-2xl p-8 border border-gray-200 card-hover">
            <h1 class="text-2xl font-black text-gray-900 text-center mb-2" data-split>Complete Your Account</h1>
            <p class="text-center text-[#5A6480] text-sm mb-6">Email <strong class="text-kicc-gold">{{ $email }}</strong> verified. Set up your profile.</p>

            <form method="POST" action="{{ route('register.complete') }}" class="space-y-4" x-data="{ loading: false }" @submit="loading = true">
                @csrf
                <div>
                    <label class="block text-sm font-bold text-gray-900 mb-2">Full Name <span class="text-[#901C1E]">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-gray-900 placeholder:text-[#5A6480] outline-none focus:ring-2 focus:ring-kicc-gold/60 transition-all" required>
                    @error('name')<p class="text-[#e86f71] text-xs mt-1.5">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-900 mb-2">Phone <span class="text-gray-400 font-normal">(optional — for M-Pesa receipts)</span></label>
                    <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="+254 7XX XXX XXX" class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-gray-900 placeholder:text-[#5A6480] outline-none focus:ring-2 focus:ring-kicc-gold/60 transition-all">
                    @error('phone')<p class="text-[#e86f71] text-xs mt-1.5">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-900 mb-2">Account Type <span class="text-[#901C1E]">*</span></label>
                    <select name="account_type" required class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-gray-900 outline-none focus:ring-2 focus:ring-kicc-gold/60 transition-all">
                        <option value="">— Choose account type —</option>
                        <option value="exhibitor" {{ old('account_type') === 'exhibitor' ? 'selected' : '' }}>Private Exhibitor / Business (sell products, property, restaurant, services)</option>
                        <option value="individual" {{ old('account_type') === 'individual' ? 'selected' : '' }}>Individual / Tourist</option>
                        <option value="sme" {{ old('account_type') === 'sme' ? 'selected' : '' }}>SME / Company</option>
                        <option value="school" {{ old('account_type') === 'school' ? 'selected' : '' }}>School / Institution</option>
                    </select>
                    @error('account_type')<p class="text-[#e86f71] text-xs mt-1.5">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-900 mb-2">Password <span class="text-[#901C1E]">*</span></label>
                    <input type="password" name="password" class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-gray-900 placeholder:text-[#5A6480] outline-none focus:ring-2 focus:ring-kicc-gold/60 transition-all" required minlength="8">
                    @error('password')<p class="text-[#e86f71] text-xs mt-1.5">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-900 mb-2">Confirm Password <span class="text-[#901C1E]">*</span></label>
                    <input type="password" name="password_confirmation" class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-gray-900 placeholder:text-[#5A6480] outline-none focus:ring-2 focus:ring-kicc-gold/60 transition-all" required>
                </div>
                <button type="submit" :disabled="loading" class="w-full h-12 rounded-xl bg-[#901C1E] text-gray-900 font-bold hover:bg-[#7b1618] transition-colors active:scale-[0.98] disabled:opacity-60 inline-flex items-center justify-center gap-2" data-magnetic>
                    <svg x-show="loading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                    <span x-text="loading ? 'Creating your account…' : 'Create Account'"></span>
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
