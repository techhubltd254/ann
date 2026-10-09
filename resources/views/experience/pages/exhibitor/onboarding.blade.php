@extends('layouts.admin-theme', ['title' => 'Exhibitor', 'themeColor' => '#14B8A6', 'brandName' => 'Exhibitor', 'brandSub' => 'KICC Portal'])

@section('title', 'Set Up Your Exhibitor Website — KICC')

@section('content')
<div class="min-h-screen flex items-center justify-center px-5 py-24"
     x-data="{ step: 1, business_type: '{{ old('business_type', '') }}', package_slug: '{{ old('package_slug', '') }}', complexity: '{{ old('complexity', '') }}', loading: false }">
    <div class="w-full max-w-2xl" data-reveal>

        @if(session('success'))
        <div class="bg-emerald-500/15 border border-emerald-500/25 text-emerald-400 rounded-xl px-5 py-3 mb-6 text-sm">{{ session('success') }}</div>
        @endif
        @if($errors->any())
        <div class="bg-red-500/15 border border-red-500/25 text-red-400 rounded-xl px-5 py-3 mb-6 text-sm">{{ $errors->first() }}</div>
        @endif

        <div class="text-center mb-8">
            <img src="{{ media('kicc/kicc-logo.png') }}" alt="KICC" class="h-12 w-auto mx-auto mb-4" style="filter: brightness(0) invert(1);">
            <h1 class="text-3xl font-black text-white" data-split>Set up your exhibitor website</h1>
            <p class="text-gray-400 text-sm mt-2">4 quick questions — your storefront is built instantly from your answers</p>
            <div class="flex items-center justify-center gap-2 mt-6">
                <template x-for="i in 4" :key="i">
                    <div class="h-1.5 rounded-full transition-all duration-300" :class="step >= i ? 'bg-[#14B8A6] w-10' : 'bg-gray-100 w-6'"></div>
                </template>
            </div>
        </div>

        <form method="POST" action="{{ route('exhibitor.onboarding.store') }}" @submit="loading = true">
            @csrf
            <input type="hidden" name="business_type" :value="business_type">
            <input type="hidden" name="package_slug" :value="package_slug">
            <input type="hidden" name="complexity" :value="complexity">

            {{-- STEP 1: What do you sell? --}}
            <div x-show="step === 1" x-transition>
                <h2 class="text-white font-black text-lg mb-4 text-center" data-split>1 · What do you sell?</h2>
                <div class="grid sm:grid-cols-2 gap-3">
                    @foreach($businessTypes as $key => $t)
                    <button type="button" @click="business_type = '{{ $key }}'"
                            class="border-2 rounded-2xl p-5 text-left transition-all"
                            :class="business_type === '{{ $key }}' ? 'border-[#14B8A6] bg-[#14B8A6]/10' : 'border-white/10 glass-card hover:border-white/10'" data-magnetic>
                        <div class="text-2xl mb-2">{{ $t['icon'] }}</div>
                        <div class="font-black text-white text-sm">{{ $t['label'] }}</div>
                        <div class="text-gray-400 text-xs mt-1">{{ $t['hint'] }}</div>
                    </button>
                    @endforeach
                </div>
                <button type="button" @click="business_type && (step = 2)" :disabled="!business_type"
                        class="w-full mt-6 h-12 rounded-xl bg-[#14B8A6] text-white font-bold hover:bg-[#14B8A6] transition-all disabled:opacity-40" data-magnetic>Continue →</button>
            </div>

            {{-- STEP 2: Business details --}}
            <div x-show="step === 2" x-transition x-cloak>
                <h2 class="text-white font-black text-lg mb-4 text-center" data-split>2 · Your business details</h2>
                <div class="glass-card border border-white/10 rounded-2xl p-6 space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Display name <span class="text-[#14B8A6]">*</span></label>
                        <input type="text" name="display_name" value="{{ old('display_name', $user->name) }}" required placeholder="e.g. Westlands Studio Apartments"
                               class="w-full h-11 px-4 rounded-xl bg-zinc-900 border border-white/10 text-white text-sm placeholder:text-zinc-400/50 outline-none focus:ring-2 focus:ring-[#14B8A6]/60">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">County <span class="text-[#14B8A6]">*</span></label>
                        <select name="county_id" required class="w-full h-11 px-4 rounded-xl bg-zinc-900 border border-white/10 text-white text-sm outline-none focus:ring-2 focus:ring-[#14B8A6]/60">
                            <option value="">— Choose county —</option>
                            @foreach($counties as $c)
                            <option value="{{ $c->id }}" {{ old('county_id', $user->county_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Tagline <span class="text-[#14B8A6]">*</span></label>
                        <textarea name="tagline" required rows="2" placeholder="e.g. Premium furnished apartments in Westlands"
                                  class="w-full px-4 py-3 rounded-xl bg-zinc-900 border border-white/10 text-white text-sm placeholder:text-zinc-400/50 outline-none focus:ring-2 focus:ring-[#14B8A6]/60">{{ old('tagline') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Phone <span class="text-[#14B8A6]">*</span></label>
                        <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" required placeholder="+254…"
                               class="w-full h-11 px-4 rounded-xl bg-zinc-900 border border-white/10 text-white text-sm placeholder:text-zinc-400/50 outline-none focus:ring-2 focus:ring-[#14B8A6]/60">
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="button" @click="step = 1" class="h-12 px-6 rounded-xl border border-white/10 text-gray-500 font-bold hover:bg-gray-50 transition-all">← Back</button>
                    <button type="button" @click="step = 3" class="flex-1 h-12 rounded-xl bg-[#14B8A6] text-white font-bold hover:bg-[#14B8A6] transition-all" data-magnetic>Continue →</button>
                </div>
            </div>

            {{-- STEP 3: Choose your package --}}
            <div x-show="step === 3" x-transition x-cloak>
                <h2 class="text-white font-black text-lg mb-4 text-center" data-split>3 · Choose your package</h2>
                <div class="grid sm:grid-cols-2 gap-3">
                    @foreach($packages as $p)
                    <button type="button" @click="package_slug = '{{ $p->slug }}'"
                            class="border-2 rounded-2xl p-5 text-left transition-all"
                            :class="package_slug === '{{ $p->slug }}' ? 'border-[#14B8A6] bg-[#14B8A6]/10' : 'border-white/10 glass-card hover:border-white/10'">
                        <div class="flex items-center justify-between mb-2">
                            <div class="font-black text-white text-sm">{{ $p->name }}</div>
                            <div class="text-[#14B8A6] font-black text-sm">KES {{ number_format($p->price) }}<span class="text-[9px] text-gray-400">/mo</span></div>
                        </div>
                        <div class="text-gray-400 text-xs leading-relaxed">{{ $p->description }}</div>
                    </button>
                    @endforeach
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="button" @click="step = 2" class="h-12 px-6 rounded-xl border border-white/10 text-gray-500 font-bold hover:bg-gray-50 transition-all">← Back</button>
                    <button type="button" @click="package_slug && (step = 4)" :disabled="!package_slug"
                            class="flex-1 h-12 rounded-xl bg-[#14B8A6] text-white font-bold hover:bg-[#14B8A6] transition-all disabled:opacity-40" data-magnetic>Continue →</button>
                </div>
            </div>

            {{-- STEP 4: Complexity — Intelligent Decision Tree --}}
            <div x-show="step === 4" x-transition x-cloak>
                <h2 class="text-white font-black text-lg mb-4 text-center" data-split>4 · What level of setup do you need?</h2>
                <p class="text-gray-400 text-sm text-center mb-6">Choose the experience that best matches your needs</p>
                <div class="space-y-3">
                    @foreach($complexityLevels as $key => $cl)
                    <button type="button" @click="complexity = '{{ $key }}'"
                            class="w-full border-2 rounded-2xl p-5 text-left transition-all flex items-start gap-4"
                            :class="complexity === '{{ $key }}' ? 'border-[#14B8A6] bg-[#14B8A6]/10' : 'border-white/10 glass-card hover:border-white/10 hover:bg-gray-50'">
                        <div class="text-3xl shrink-0">{{ $cl['icon'] }}</div>
                        <div class="flex-1">
                            <div class="font-black text-white text-base">{{ $cl['label'] }}</div>
                            <div class="text-gray-400 text-sm mt-1 leading-relaxed">{{ $cl['desc'] }}</div>
                            @if($key === 'premium')
                            <div class="flex items-center gap-2 mt-2">
                                <span class="inline-flex items-center gap-1 text-[10px] bg-[#14B8A6]/10 text-[#14B8A6] font-bold px-2 py-0.5 rounded-full">📸 Professional shoot</span>
                                <span class="inline-flex items-center gap-1 text-[10px] bg-[#0B0B0B]/10 text-zinc-400 font-bold px-2 py-0.5 rounded-full">🎬 Video production</span>
                            </div>
                            @elseif($key === 'custom')
                            <div class="flex items-center gap-2 mt-2">
                                <span class="inline-flex items-center gap-1 text-[10px] bg-[#14B8A6]/10 text-[#14B8A6] font-bold px-2 py-0.5 rounded-full">🛠️ Personalized dashboard</span>
                            </div>
                            @else
                            <div class="flex items-center gap-2 mt-2">
                                <span class="inline-flex items-center gap-1 text-[10px] bg-[#0B0B0B]/10 text-zinc-400 font-bold px-2 py-0.5 rounded-full">⚡ Instant setup</span>
                            </div>
                            @endif
                        </div>
                    </button>
                    @endforeach
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="button" @click="step = 3" class="h-12 px-6 rounded-xl border border-white/10 text-gray-500 font-bold hover:bg-gray-50 transition-all">← Back</button>
                    <button type="submit" :disabled="!complexity || loading"
                            class="flex-1 h-12 rounded-xl bg-[#14B8A6] text-zinc-400 font-black hover:bg-[#14B8A6] transition-all disabled:opacity-40 inline-flex items-center justify-center gap-2" data-magnetic>
                        <svg x-show="loading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                        <span x-text="complexity === 'premium' ? 'Request Premium Setup →' : complexity === 'custom' ? 'Request Custom Setup →' : loading ? 'Building your website…' : 'Build My Website →'"></span>
                    </button>
                </div>
                <p class="text-gray-400 text-[11px] text-center mt-3">You can change your setup or package any time from your portal.</p>
            </div>
        </form>
    </div>
</div>
@endsection