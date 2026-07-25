@extends('layouts.app')

@section('title', 'My Profile')
@section('description', 'Update your KICC account profile.')

@section('content')
<div class="bg-[#0D1220] border-b border-white/8 py-12">
    <div class="max-w-7xl mx-auto px-5" data-reveal>
        <a href="{{ route('dashboard.index') }}" class="text-kicc-gold hover:underline text-sm mb-4 inline-block">&larr; Dashboard</a>
        <h1 class="text-3xl md:text-4xl font-black text-white mb-2">My Profile</h1>
        <p class="text-white/50">Manage your account details.</p>
    </div>
</div>

<div class="max-w-3xl mx-auto px-5 py-10">
    @if(session('success'))
    <div class="bg-emerald-500/15 border border-emerald-500/25 text-emerald-400 px-5 py-3 rounded-xl mb-6 text-sm" data-reveal>{{ session('success') }}</div>
    @endif

    <div class="bg-[#0D1220] rounded-2xl p-6 md:p-8 border border-white/8" data-reveal>
        <form method="POST" action="{{ route('dashboard.profile.update') }}" class="space-y-5">
            @csrf
            <div>
                <label class="block text-sm font-bold text-white mb-2">Name</label>
                <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}"
                       class="w-full h-12 px-4 rounded-xl bg-[#141B2E] border border-white/10 text-white placeholder:text-white/25 outline-none focus:ring-2 focus:ring-kicc-gold/60 transition-all" required>
                @error('name')<p class="text-[#e86f71] text-xs mt-1.5">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-bold text-white mb-2">Email</label>
                <input type="email" value="{{ auth()->user()->email }}"
                       class="w-full h-12 px-4 rounded-xl bg-[#141B2E]/50 border border-white/5 text-white/40 outline-none" disabled>
                <p class="text-xs text-white/30 mt-1.5">Email cannot be changed.</p>
            </div>
            <div>
                <label class="block text-sm font-bold text-white mb-2">Phone</label>
                <input type="text" name="phone" value="{{ old('phone', auth()->user()->phone) }}"
                       class="w-full h-12 px-4 rounded-xl bg-[#141B2E] border border-white/10 text-white placeholder:text-white/25 outline-none focus:ring-2 focus:ring-kicc-gold/60 transition-all">
                @error('phone')<p class="text-[#e86f71] text-xs mt-1.5">{{ $message }}</p>@enderror
            </div>
            <button type="submit" data-magnetic
                    class="inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-8 text-sm h-12 rounded-xl bg-kicc-gold text-[#07090F] hover:bg-[#e6b904] active:scale-[0.98]">
                Save Changes
            </button>
        </form>
    </div>
</div>
@endsection
