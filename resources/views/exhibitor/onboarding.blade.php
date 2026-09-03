@extends('layouts.app')

@section('title', 'Set Up Your Exhibitor Website — KICC')

@section('content')
<div class="min-h-screen flex items-center justify-center px-5 py-24"
     x-data="{ step: 1, business_type: '{{ old('business_type', '') }}', package_slug: '{{ old('package_slug', '') }}', loading: false }">
    <div class="w-full max-w-2xl" data-reveal>

        @if(session('success'))
        <div class="bg-emerald-500/15 border border-emerald-500/25 text-emerald-400 rounded-xl px-5 py-3 mb-6 text-sm">{{ session('success') }}</div>
        @endif
        @if($errors->any())
        <div class="bg-red-500/15 border border-red-500/25 text-red-400 rounded-xl px-5 py-3 mb-6 text-sm">{{ $errors->first() }}</div>
        @endif

        <div class="text-center mb-8">
            <img src="{{ media('kicc/kicc-logo.png') }}" alt="KICC" class="h-12 w-auto mx-auto mb-4" style="filter: brightness(0) invert(1);">
            <h1 class="text-3xl font-black text-gray-900" data-split>Set up your exhibitor website</h1>
            <p class="text-gray-400 text-sm mt-2">3 quick questions — your storefront is built instantly from your answers</p>
            {{-- Progress --}}
            <div class="flex items-center justify-center gap-2 mt-6">
                <template x-for="i in 3" :key="i">
                    <div class="h-1.5 rounded-full transition-all duration-300" :class="step >= i ? 'bg-[#FFCD05] w-10' : 'bg-gray-100 w-6'"></div>
                </template>
            </div>
        </div>

        <form method="POST" action="{{ route('exhibitor.onboarding.store') }}" @submit="loading = true">
            @csrf
            <input type="hidden" name="business_type" :value="business_type">
            <input type="hidden" name="package_slug" :value="package_slug">

            {{-- STEP 1: What do you sell? --}}
            <div x-show="step === 1" x-transition>
                <h2 class="text-gray-900 font-black text-lg mb-4 text-center" data-split>1 · What do you sell?</h2>
                <div class="grid sm:grid-cols-2 gap-3">
                    @foreach($businessTypes as $key => $t)
                    <button type="button" @click="business_type = '{{ $key }}'"
                            class="border-2 rounded-2xl p-5 text-left transition-all"
                            :class="business_type === '{{ $key }}' ? 'border-[#FFCD05] bg-[#FFCD05]/10' : 'border-gray-200 bg-white hover:border-gray-200'" data-magnetic>
                        <div class="text-2xl mb-2">{{ $t['icon'] }}</div>
                        <div class="font-black text-gray-900 text-sm">{{ $t['label'] }}</div>
                        <div class="text-gray-400 text-xs mt-1">{{ $t['hint'] }}</div>
                    </button>
                    @endforeach
                </div>
                <button type="button" @click="business_type && (step = 2)" :disabled="!business_type"
                        class="w-full mt-6 h-12 rounded-xl bg-[#901C1E] text-gray-900 font-bold hover:bg-[#7b1618] transition-all disabled:opacity-40" data-magnetic>Continue →</button>
            </div>

            {{-- STEP 2: Business details --}}
            <div x-show="step === 2" x-transition x-cloak>
                <h2 class="text-gray-900 font-black text-lg mb-4 text-center" data-split>2 · Your business details</h2>
                <div class="bg-white border border-gray-200 rounded-2xl p-6 space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Display name <span class="text-[#901C1E]">*</span></label>
                        <input type="text" name="display_name" value="{{ old('display_name', $user->name) }}" required placeholder="e.g. Westlands Studio Apartments"
                               class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-gray-900 text-sm placeholder:text-[#5A6480]/50 outline-none focus:ring-2 focus:ring-[#FFCD05]/60">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">County <span class="text-[#901C1E]">*</span></label>
                        <select name="county_id" required class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-gray-900 text-sm outline-none focus:ring-2 focus:ring-[#FFCD05]/60">
                            <option value="">— Choose county —</option>
                            @foreach($counties as $c)
                            <option value="{{ $c->id }}" {{ old('county_id', $user->county_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Tell buyers what you offer <span class="text-[#901C1E]">*</span></label>
                        <textarea name="tagline" required rows="2" placeholder="e.g. Furnished studio & 1-bedroom apartments in Westlands with 24/7 security"
                                  class="w-full px-4 py-3 rounded-xl bg-[#F9FAFB] border border-gray-200 text-gray-900 text-sm placeholder:text-[#5A6480]/50 outline-none focus:ring-2 focus:ring-[#FFCD05]/60">{{ old('tagline') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Business phone <span class="text-[#901C1E]">*</span></label>
                        <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" required placeholder="+254…"
                               class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-gray-900 text-sm placeholder:text-[#5A6480]/50 outline-none focus:ring-2 focus:ring-[#FFCD05]/60">
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="button" @click="step = 1" class="h-12 px-6 rounded-xl border border-gray-200 text-gray-500 font-bold hover:bg-gray-50 transition-all">← Back</button>
                    <button type="button" @click="step = 3" class="flex-1 h-12 rounded-xl bg-[#901C1E] text-gray-900 font-bold hover:bg-[#7b1618] transition-all" data-magnetic>Continue →</button>
                </div>
            </div>

            {{-- STEP 3: Package --}}
            <div x-show="step === 3" x-transition x-cloak>
                <h2 class="text-gray-900 font-black text-lg mb-4 text-center" data-split>3 · Choose your package</h2>
                <div class="grid sm:grid-cols-2 gap-3">
                    @foreach($packages as $p)
                    <button type="button" @click="package_slug = '{{ $p->slug }}'"
                            class="border-2 rounded-2xl p-5 text-left transition-all"
                            :class="package_slug === '{{ $p->slug }}' ? 'border-[#FFCD05] bg-[#FFCD05]/10' : 'border-gray-200 bg-white hover:border-gray-200'">
                        <div class="flex items-center justify-between mb-2">
                            <div class="font-black text-gray-900 text-sm">{{ $p->name }}</div>
                            <div class="text-[#FFCD05] font-black text-sm">KES {{ number_format($p->price) }}<span class="text-[9px] text-gray-400">/mo</span></div>
                        </div>
                        <div class="text-gray-400 text-xs leading-relaxed">{{ $p->max_booths >= 999 ? 'Unlimited listings' : $p->max_booths . ' listing' . ($p->max_booths > 1 ? 's' : '') }} · {{ $p->has_analytics ? 'Analytics' : 'Basic profile' }}</div>
                    </button>
                    @endforeach
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="button" @click="step = 2" class="h-12 px-6 rounded-xl border border-gray-200 text-gray-500 font-bold hover:bg-gray-50 transition-all">← Back</button>
                    <button type="submit" :disabled="!package_slug || loading"
                            class="flex-1 h-12 rounded-xl bg-[#FFCD05] text-[#07090F] font-black hover:bg-[#FFCD05] transition-all disabled:opacity-40 inline-flex items-center justify-center gap-2" data-magnetic>
                        <svg x-show="loading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                        <span x-text="loading ? 'Building your website…' : 'Build My Website '"></span>
                    </button>
                </div>
                <p class="text-gray-400 text-[11px] text-center mt-3">You can change your package any time from your portal.</p>
            </div>
        </form>
    </div>
</div>
@endsection
