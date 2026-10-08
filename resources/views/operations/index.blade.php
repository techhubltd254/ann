@extends('layouts.app')

@section('title', 'Platform Operations — KICC')

@section('content')
<div class="max-w-7xl mx-auto px-5 py-10">
    <div class="mb-10" data-reveal>
        <div class="flex items-center gap-3 mb-3">
            <span class="h-px w-8 bg-kicc-gold"></span>
            <span class="text-kicc-gold text-xs font-bold uppercase tracking-[0.2em]">Platform Operations</span>
        </div>
        <h1 class="text-4xl font-black text-gray-900 tracking-tight mb-3" data-split>Operations & <span class="text-kicc-gold">Management</span></h1>
        <p class="text-[#5A6480] max-w-2xl text-sm leading-relaxed">Advertising campaigns, shipping logistics, and content management for the platform.</p>
    </div>

    <div class="grid lg:grid-cols-3 gap-5">
        {{-- Advertising --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-6 card-hover" data-tilt="5" data-reveal>
            <div class="tilt-glare"></div>
            <div class="w-12 h-12 bg-[#FFCD05]/10 rounded-2xl flex items-center justify-center mb-4">
                <svg class="w-6 h-6 text-kicc-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
            </div>
            <h2 class="text-lg font-black text-gray-900 mb-1" data-split>Advertising</h2>
            <p class="text-xs text-gray-400 mb-4">Campaigns, creatives, and impression tracking</p>
            <div class="space-y-2 text-sm">
                @forelse($campaigns ?? [] as $c)
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="font-semibold text-gray-700 text-sm">{{ $c->name }}</span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border {{ $c->is_active ? 'text-emerald-400 bg-emerald-500/15 border-emerald-500/25' : 'text-[#5A6480] bg-sky-50 border-gray-200' }}">{{ $c->is_active ? 'Active' : 'Paused' }}</span>
                </div>
                @empty
                <p class="text-[#5A6480] text-sm">No campaigns yet</p>
                @endforelse
            </div>
        </div>

        {{-- Logistics --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-6 card-hover" data-tilt="5" data-reveal data-reveal-delay="90">
            <div class="tilt-glare"></div>
            <div class="w-12 h-12 bg-[#FFCD05]/10 rounded-2xl flex items-center justify-center mb-4">
                <svg class="w-6 h-6 text-kicc-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
            </div>
            <h2 class="text-lg font-black text-gray-900 mb-1" data-split>Logistics</h2>
            <p class="text-xs text-gray-400 mb-4">Courier partners, shipping zones and rates</p>
            <div class="space-y-3">
                @forelse($couriers ?? [] as $c)
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full {{ $c->is_active ? 'bg-emerald-400' : 'bg-[#2a2a2a]/20' }}"></span>
                    <span class="text-sm text-gray-700">{{ $c->name }}</span>
                </div>
                @empty
                <p class="text-[#5A6480] text-sm">No couriers set up</p>
                @endforelse
                <div class="pt-2 border-t border-gray-100">
                    <span class="text-xs text-gray-400">Shipping zones: {{ count($zones ?? []) }} configured</span>
                </div>
            </div>
        </div>

        {{-- SEO --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-6 card-hover" data-tilt="5" data-reveal data-reveal-delay="180">
            <div class="tilt-glare"></div>
            <div class="w-12 h-12 bg-[#FFCD05]/10 rounded-2xl flex items-center justify-center mb-4">
                <svg class="w-6 h-6 text-kicc-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
            </div>
            <h2 class="text-lg font-black text-gray-900 mb-1" data-split>SEO & Content</h2>
            <p class="text-xs text-gray-400 mb-4">Metadata, content pages, and sitemap</p>
            <div class="space-y-2 text-sm">
                @forelse($pages ?? [] as $p)
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-700 text-sm">{{ $p->title }}</span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border {{ $p->is_published ? 'text-emerald-400 bg-emerald-500/15 border-emerald-500/25' : 'text-[#5A6480] bg-sky-50 border-gray-200' }}">{{ $p->is_published ? 'Published' : 'Draft' }}</span>
                </div>
                @empty
                <p class="text-[#5A6480] text-sm">No content pages yet</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Automation Tree --}}
    <div class="mt-16">
        <div class="flex items-center gap-3 mb-8" data-reveal>
            <span class="h-px w-8 bg-kicc-gold"></span>
            <h2 class="text-2xl font-black text-gray-900 tracking-tight" data-split>n8n &amp; Zapier Automation Tree</h2>
            <span class="text-[10px] text-gray-400 font-mono bg-sky-50 border border-gray-200 px-2 py-0.5 rounded-full">Each sector independent · Hierarchical flow</span>
        </div>
        <div class="relative pl-8 border-l-2 border-[#FFCD05]/25 space-y-6">
            @foreach($automationTree['children'] as $sector)
            <div class="relative" data-reveal>
                <div class="absolute -left-10 mt-1.5 w-4 h-4 rounded-full {{ $loop->first ? 'bg-kicc-gold' : 'bg-[#2a2a2a]/20' }} border-2 border-[#07090F]"></div>
                <div class="bg-white rounded-2xl border border-gray-200 p-5 hover:border-kicc-gold/30 transition-all card-hover">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-kicc-gold">{{ $sector['type'] }}</span>
                            <h3 class="text-lg font-black text-gray-900 mt-0.5">{{ $sector['name'] }}</h3>
                        </div>
                        <span class="text-[10px] text-[#5A6480] bg-sky-50 border border-gray-200 px-3 py-1 rounded-full whitespace-nowrap">{{ $sector['schedule'] }}</span>
                    </div>
                    <p class="text-sm text-[#5A6480] mb-4">{{ $sector['description'] }}</p>

                    <div class="grid grid-cols-2 md:grid-cols-5 gap-2">
                        @foreach($sector['children'] as $step)
                        <div class="relative bg-[#F9FAFB] rounded-xl p-3 text-center border border-gray-100 hover:border-kicc-gold/30 transition-all card-hover">
                            <div class="text-[10px] text-kicc-gold font-bold mb-1 uppercase tracking-wider">{{ $step['type'] }}</div>
                            <div class="text-xs font-semibold text-[#5A6480]">{{ $step['name'] }}</div>
                            @if(!$loop->last)
                            <div class="hidden md:block absolute -right-3 top-1/2 -translate-y-1/2 text-gray-900/20">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>

                    @if($sector['file'] !== 'agentic-loop')
                    <div class="mt-3 text-[10px] text-[#5A6480] font-mono">Workflow: <code class="bg-sky-50 px-1.5 rounded">{{ $sector['file'] }}</code></div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
