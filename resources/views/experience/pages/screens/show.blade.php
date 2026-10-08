@extends('layouts.app')

@section('title', $screen->label . ' — KICC Exhibition Screens')

@push('styles')
<style>
    .vid-container { position: relative; width: 100%; background: #FFFFFF; }
    .vid-container video {
        width: 100%; max-height: 80vh; object-fit: contain;
        display: block; margin: 0 auto;
    }
</style>
@endpush

@section('content')
<div class="bg-gray-900 min-h-screen">
    <div class="max-w-5xl mx-auto px-6 lg:px-8 py-8">
        <a href="{{ route('screens.directory') }}" class="inline-flex items-center gap-2 text-gray-400 hover:text-white mb-6 transition-colors text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            <span>All Screens</span>
        </a>

        <div class="flex items-center gap-3 mb-6">
            <h1 class="text-2xl font-bold text-white" data-split>{{ $screen->label }}</h1>
            <span class="text-xs px-2 py-0.5 rounded-full bg-gray-700 text-gray-400 font-mono">{{ $screen->id }}</span>
        </div>

        <div class="vid-container rounded-xl overflow-hidden shadow-2xl border border-gray-700/50 mb-8">
            @if($screen->video_exists)
            <video controls autoplay muted loop playsinline>
                <source src="{{ $screen->video_url }}" type="video/mp4">
            </video>
            @else
            <div class="flex items-center justify-center h-64 text-gray-500">
                <div class="text-center">
                    <div class="text-4xl mb-3"></div>
                    <p class="text-lg">Video not yet generated</p>
                    <p class="text-sm text-gray-600 mt-1">Run <code class="text-amber-400">php artisan screen:generate {{ $screen->id }}</code></p>
                </div>
            </div>
            @endif
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-gray-800/50 rounded-xl p-4 border border-gray-700/50">
                <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Duration</div>
                <div class="text-white font-bold text-lg">{{ $screen->target_duration_sec }}s</div>
            </div>
            <div class="bg-gray-800/50 rounded-xl p-4 border border-gray-700/50">
                <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Location</div>
                <div class="text-white font-bold text-lg">{{ $screen->location ?: '—' }}</div>
            </div>
            @if($screen->video_size_mb)
            <div class="bg-gray-800/50 rounded-xl p-4 border border-gray-700/50">
                <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">File Size</div>
                <div class="text-white font-bold text-lg">{{ $screen->video_size_mb }} MB</div>
            </div>
            @endif
            <div class="bg-gray-800/50 rounded-xl p-4 border border-gray-700/50">
                <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Refresh</div>
                <div class="text-white font-bold text-lg">Every {{ $screen->refresh_interval_min }}min</div>
            </div>
        </div>

        @if($screen->county_id || $screen->sector_id)
        <div class="bg-gray-800/50 rounded-xl p-5 border border-gray-700/50">
            <h3 class="text-white font-semibold mb-3">Screen Configuration</h3>
            <div class="text-sm text-gray-400 space-y-2">
                @if($screen->county_id)
                <div><span class="text-gray-500">County:</span> <span class="text-amber-400">{{ Str::title($screen->county_id) }}</span></div>
                @endif
                @if($screen->sector_id)
                <div><span class="text-gray-500">Sector:</span> <span class="text-emerald-400">{{ Str::title($screen->sector_id) }}</span></div>
                @endif
                <div><span class="text-gray-500">Min images:</span> <span class="text-white">{{ $screen->min_images }}</span></div>
                <div><span class="text-gray-500">Max images:</span> <span class="text-white">{{ $screen->max_images }}</span></div>
                <div><span class="text-gray-500">Preset:</span> <span class="text-white">{{ $screen->preset_key }}</span></div>
            </div>
        </div>
        @endif

        {{--  ADVERTISE ON THIS SCREEN  --}}
        <div class="mt-8 bg-gradient-to-r from-[#0B0B0B]/20 to-[#0B0B0B]/20 border border-[#0B0B0B]/30 rounded-2xl p-6" x-data="{ adOpen: false, pkg: 'week' }">
            @if(session('success'))
            <div class="bg-emerald-500/15 border border-emerald-500/25 text-emerald-400 rounded-xl px-5 py-3 mb-5 text-sm">{{ session('success') }}</div>
            @endif
            @if($errors->any())
            <div class="bg-red-500/15 border border-red-500/25 text-red-400 rounded-xl px-5 py-3 mb-5 text-sm">{{ $errors->first() }}</div>
            @endif
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="text-[#0B0B0B] text-xs font-black uppercase tracking-widest mb-1">Advertise Here</div>
                    <h3 class="text-gray-900 font-black text-xl">Your brand on this screen</h3>
                    <p class="text-gray-400 text-sm mt-1">Seen by thousands of exhibition visitors at {{ $screen->location ?: 'KICC' }}.</p>
                </div>
                <button @click="adOpen = true" class="px-6 py-3 rounded-xl bg-[#FFCD05] text-[#0B0B0B] font-black text-sm hover:bg-[#B3261E] transition-all active:scale-95">Book This Space</button>
            </div>

            {{-- Booking modal --}}
            <div x-show="adOpen" x-cloak x-transition.opacity class="fixed inset-0 z-[100] flex items-center justify-center p-5" style="background: rgba(0,0,0,0.75); backdrop-filter: blur(4px);">
                <div @click.away="adOpen = false" class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl max-h-[90vh] overflow-y-auto" x-transition.scale>
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="font-black text-gray-900 text-lg">Book Ad Space</h3>
                            <p class="text-[#0B0B0B] text-xs mt-1">{{ $screen->label }} · {{ $screen->location }}</p>
                        </div>
                        <button @click="adOpen = false" class="text-[#0B0B0B] hover:text-gray-900 p-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('screens.advertise', $screen->id) }}" class="space-y-3">
                        @csrf
                        <div class="grid grid-cols-3 gap-2">
                            @foreach($adPackages as $key => $p)
                            <label class="cursor-pointer">
                                <input type="radio" name="package" value="{{ $key }}" class="peer sr-only" {{ $key === 'week' ? 'checked' : '' }}>
                                <div class="border-2 border-gray-200 peer-checked:border-[#0B0B0B] peer-checked:bg-[#0B0B0B]/5 rounded-xl p-3 text-center transition-all">
                                    <div class="text-[10px] font-bold text-gray-400 uppercase">{{ $p['label'] }}</div>
                                    <div class="text-sm font-black text-gray-900">KES {{ number_format($p['price']) }}</div>
                                </div>
                            </label>
                            @endforeach
                        </div>
                        <input type="text" name="business_name" required placeholder="Business / brand name *"
                               class="w-full h-11 px-4 rounded-xl bg-[#FFFFFF] border border-gray-200 text-gray-900 text-sm placeholder:text-[#0B0B0B] outline-none focus:ring-2 focus:ring-[#0B0B0B]/60">
                        <input type="email" name="email" required placeholder="Email address *"
                               class="w-full h-11 px-4 rounded-xl bg-[#FFFFFF] border border-gray-200 text-gray-900 text-sm placeholder:text-[#0B0B0B] outline-none focus:ring-2 focus:ring-[#0B0B0B]/60">
                        <input type="tel" name="phone" required placeholder="Phone number *"
                               class="w-full h-11 px-4 rounded-xl bg-[#FFFFFF] border border-gray-200 text-gray-900 text-sm placeholder:text-[#0B0B0B] outline-none focus:ring-2 focus:ring-[#0B0B0B]/60">
                        <input type="url" name="target_url" placeholder="Link when screen is tapped (optional)"
                               class="w-full h-11 px-4 rounded-xl bg-[#FFFFFF] border border-gray-200 text-gray-900 text-sm placeholder:text-[#0B0B0B] outline-none focus:ring-2 focus:ring-[#0B0B0B]/60">
                        <textarea name="message" rows="2" placeholder="What are you advertising?"
                                  class="w-full px-4 py-3 rounded-xl bg-[#FFFFFF] border border-gray-200 text-gray-900 text-sm placeholder:text-[#0B0B0B] outline-none focus:ring-2 focus:ring-[#0B0B0B]/60"></textarea>
                        <button type="submit" class="w-full h-12 rounded-xl bg-[#FFCD05] text-[#0B0B0B] font-black text-sm hover:bg-[#B3261E] transition-colors active:scale-[0.98]">
                            Reserve Slot &amp; Pay
                        </button>
                        <p class="text-[#0B0B0B] text-[11px] text-center">Slot goes live after payment confirmation. Artwork collected within 24h.</p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
