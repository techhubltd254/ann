@extends('layouts.blank')

@section('title', 'KICC Overall Admin — Platform Control')

@section('content')
@php $accent = '#F59E0B'; @endphp
<div class="flex min-h-screen bg-[#F9FAFB]">
    {{-- Sidebar --}}
    <div class="w-56 bg-white border-r border-gray-200 flex flex-col shrink-0 min-h-screen overflow-y-auto">
        <div class="flex items-center gap-3 px-4 border-b border-gray-200 h-16 shrink-0">
            <div class="flex items-center gap-2">
                <img src="{{ media('kicc/kicc-logo.png') }}" alt="KICC" class="h-7 w-auto" style="filter: brightness(0) invert(1);">
                <div class="text-[#FFCD05] text-[9px] font-black tracking-[0.15em] uppercase">Overall<br>Admin</div>
            </div>
        </div>
        <div class="flex-1 py-3 overflow-y-auto">
            @foreach($navItems as $item)
            <a href="{{ route('kicc.admin', ['tab' => $item['tab']]) }}"
               class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-semibold transition-all {{ $tab === $item['tab'] ? 'text-gray-900 border-r-2' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-800' }}"
               style="{{ $tab === $item['tab'] ? 'background: '.$accent.'44; border-color: '.$accent : '' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/></svg>
                <span class="truncate text-xs">{{ $item['label'] }}</span>
            </a>
            @endforeach
        </div>
        <div class="px-4 py-3 border-t border-gray-200 text-[10px] text-gray-400 uppercase tracking-widest shrink-0">Jump to Portal</div>
        <a href="{{ route('kicc.admin', ['tab' => 'portals']) }}" class="px-4 py-2 text-xs text-gray-400 hover:text-gray-700 transition-colors flex items-center justify-between group">
            <span> All Portals</span>
            <span class="text-[#F59E0B] opacity-0 group-hover:opacity-100 transition">&nearr;</span>
        </a>
        <a href="{{ route('kicc.admin', ['tab' => 'counties']) }}" class="px-4 py-2 text-xs text-gray-400 hover:text-gray-700 transition-colors flex items-center justify-between group">
            <span> Counties (47)</span>
            <span class="text-[#F59E0B] opacity-0 group-hover:opacity-100 transition">&nearr;</span>
        </a>
        <a href="{{ route('national.admin') }}" class="px-4 py-2 text-xs text-gray-400 hover:text-gray-700 transition-colors flex items-center justify-between group">
            <span> National Govt</span>
            <span class="text-[#F59E0B] opacity-0 group-hover:opacity-100 transition">&nearr;</span>
        </a>
        <a href="{{ route('exhibitor.admin') }}" class="px-4 py-2 text-xs text-gray-400 hover:text-gray-700 transition-colors flex items-center justify-between group">
            <span> Exhibitors</span>
            <span class="text-[#F59E0B] opacity-0 group-hover:opacity-100 transition">&nearr;</span>
        </a>
        <a href="{{ route('marketplace.index') }}" class="px-4 py-2 text-xs text-gray-400 hover:text-gray-700 transition-colors flex items-center justify-between group">
            <span> Marketplace</span>
            <span class="text-[#F59E0B] opacity-0 group-hover:opacity-100 transition">&nearr;</span>
        </a>
        <a href="{{ route('kicc.admin', ['tab' => 'institutions']) }}" class="px-4 py-2 text-xs text-gray-400 hover:text-gray-700 transition-colors flex items-center justify-between group">
            <span> Institutions</span>
            <span class="text-[#F59E0B] opacity-0 group-hover:opacity-100 transition">&nearr;</span>
        </a>
        <a href="/" class="flex items-center gap-3 px-4 py-4 border-t border-gray-200 mt-2 text-gray-400 hover:text-gray-700 text-xs transition-colors shrink-0">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            <span>Exit to Site</span>
        </a>
    </div>

    {{-- Main --}}
    <div class="flex-1 flex flex-col overflow-hidden">
        <div class="bg-white border-b border-gray-200 px-6 h-16 flex items-center justify-between shrink-0">
            <div>
                <div class="font-black text-gray-900 text-sm">KICC Platform Control Center</div>
                <div class="text-[10px] font-bold uppercase tracking-widest" style="color: {{ $accent }}">Overall Administrator — All Tiers</div>
            </div>
        </div>
        <div class="flex-1 overflow-y-auto p-6">

            @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-5 py-3 mb-6 text-sm">{{ session('success') }}</div>
            @endif

            {{--  OVERVIEW  --}}
            @if($tab === 'overview')
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900">{{ $stats['counties'] }}</div><div class="text-xs text-gray-400 mt-1">County Exhibitors</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900">{{ $stats['ministries'] }}/{{ $stats['agencies'] }}</div><div class="text-xs text-gray-400 mt-1">Ministries / Agencies</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900">{{ $stats['exhibitors'] }}</div><div class="text-xs text-gray-400 mt-1">Private Exhibitors</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900">{{ $stats['tradeBoards'] }}</div><div class="text-xs text-gray-400 mt-1">County Trade Boards</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900">{{ $stats['products'] }}</div><div class="text-xs text-gray-400 mt-1">Products Live</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900">{{ $stats['orders'] }}</div><div class="text-xs text-gray-400 mt-1">Orders</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black" style="color: {{ $accent }}">KES {{ number_format($stats['escrowTotal']) }}</div><div class="text-xs text-gray-400 mt-1">Escrow Volume</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-amber-600">KES {{ number_format($stats['escrowHeld']) }}</div><div class="text-xs text-gray-400 mt-1">Held in Escrow</div></div>
            </div>
            <div class="grid md:grid-cols-3 gap-4">
                <a href="{{ route('kicc.admin', ['tab' => 'counties']) }}" class="bg-white border border-gray-200 rounded-2xl p-6 hover:shadow-md transition-all"><div class="font-bold text-gray-900 mb-1">County Tier</div><p class="text-xs text-gray-500">47 independent county websites &amp; trade boards.</p></a>
                <a href="{{ route('kicc.admin', ['tab' => 'exhibitors']) }}" class="bg-white border border-gray-200 rounded-2xl p-6 hover:shadow-md transition-all"><div class="font-bold text-gray-900 mb-1">Private Exhibitor Tier</div><p class="text-xs text-gray-500">Business storefronts with escrow-protected sales.</p></a>
                <a href="{{ route('kicc.admin', ['tab' => 'national']) }}" class="bg-white border border-gray-200 rounded-2xl p-6 hover:shadow-md transition-all"><div class="font-bold text-gray-900 mb-1">National Government Tier</div><p class="text-xs text-gray-500">Ministries &amp; agencies under the national pavilion.</p></a>
            </div>
            @endif

            {{--  SUB-PORTALS  --}}
            @if($tab === 'portals')
            <div>
                <h3 class="font-bold text-gray-900 mb-6">All Platform Portals — enter any tier</h3>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {{-- KICC Admin --}}
                    <a href="{{ route('kicc.admin') }}" class="group bg-white rounded-2xl border-2 border-gray-200 hover:border-[#F59E0B] p-6 text-center transition-all hover:shadow-xl card-hover">
                        <div class="w-14 h-14 bg-[#F59E0B]/10 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:bg-[#F59E0B]/20 transition-colors">
                            <svg class="w-7 h-7 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <h2 class="font-black text-gray-900 text-lg mb-2" data-split>KICC Overall Admin</h2>
                        <p class="text-gray-500 text-sm leading-relaxed">Full platform control — all tiers, counties, orders, escrow, users.</p>
                        <div class="mt-4 text-[#F59E0B] text-xs font-bold uppercase tracking-widest">CURRENT</div>
                    </a>
                    {{-- National --}}
                    <a href="{{ route('national.admin') }}" class="group bg-white rounded-2xl border-2 border-gray-200 hover:border-[#0EA5E9] p-6 text-center transition-all hover:shadow-xl card-hover">
                        <div class="w-14 h-14 bg-[#0EA5E9]/10 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:bg-[#0EA5E9]/20 transition-colors">
                            <svg class="w-7 h-7 text-[#0EA5E9]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <h2 class="font-black text-gray-900 text-lg mb-2" data-split>National Government</h2>
                        <p class="text-gray-500 text-sm">Ministries &amp; agencies exhibitor portal.</p>
                        <div class="mt-4 text-[#0EA5E9] text-xs font-bold uppercase tracking-widest">ENTER &nearr;</div>
                    </a>
                    {{-- County (all 47) --}}
                    <a href="{{ route('kicc.admin', ['tab' => 'counties']) }}" class="group bg-white rounded-2xl border-2 border-gray-200 hover:border-[#0b0b0b] p-6 text-center transition-all hover:shadow-xl card-hover">
                        <div class="w-14 h-14 bg-[#0b0b0b]/10 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:bg-[#0b0b0b]/20 transition-colors">
                            <svg class="w-7 h-7 text-[#0b0b0b]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <h2 class="font-black text-gray-900 text-lg mb-2" data-split>County Portal (47)</h2>
                        <p class="text-gray-500 text-sm">Each county has its own admin — content, images, prices.</p>
                        <div class="mt-4 text-[#0b0b0b] text-xs font-bold uppercase tracking-widest">BROWSE COUNTIES &nearr;</div>
                    </a>
                    {{-- Exhibitor --}}
                    <a href="{{ route('exhibitor.admin') }}" class="group bg-white rounded-2xl border-2 border-gray-200 hover:border-[#38BDF8] p-6 text-center transition-all hover:shadow-xl card-hover">
                        <div class="w-14 h-14 bg-[#38BDF8]/10 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:bg-[#38BDF8]/20 transition-colors">
                            <svg class="w-7 h-7 text-[#38BDF8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        </div>
                        <h2 class="font-black text-gray-900 text-lg mb-2" data-split>Private Exhibitors</h2>
                        <p class="text-gray-500 text-sm">Business storefronts with escrow-protected sales.</p>
                        <div class="mt-4 text-[#38BDF8] text-xs font-bold uppercase tracking-widest">ENTER &nearr;</div>
                    </a>
                    {{-- Provider --}}
                    <a href="{{ route('provider.admin') }}" class="group bg-white rounded-2xl border-2 border-gray-200 hover:border-[#0EA5E9] p-6 text-center transition-all hover:shadow-xl card-hover">
                        <div class="w-14 h-14 bg-[#0EA5E9]/10 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:bg-[#0EA5E9]/20 transition-colors">
                            <svg class="w-7 h-7 text-[#0EA5E9]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        </div>
                        <h2 class="font-black text-gray-900 text-lg mb-2" data-split>Travel Providers</h2>
                        <p class="text-gray-500 text-sm">Airlines, hotels &amp; cab companies manage services.</p>
                        <div class="mt-4 text-[#0EA5E9] text-xs font-bold uppercase tracking-widest">ENTER &nearr;</div>
                    </a>
                </div>
            </div>
            @endif

            {{--  COUNTIES  --}}
            @if($tab === 'counties')
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="font-bold text-gray-900 text-lg">All 47 Counties</h3>
                        <p class="text-gray-400 text-sm">Each county has its own Muranga-style admin. Click to manage, upload hero video/thumbnail.</p>
                    </div>
                    <span class="text-xs font-bold px-3 py-1 rounded-full bg-[#F59E0B]/10 text-[#F59E0B]">{{ $counties->count() }} counties</span>
                </div>
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    @foreach($counties as $c)
                    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden hover:shadow-md transition-all group">
                        {{-- Hero video / thumbnail --}}
                        <a href="{{ route('county.admin.pro', $c->slug) }}" class="block relative aspect-[16/9] bg-gradient-to-br from-[#0b0b0b] to-[#1a1a2e] overflow-hidden">
                            @if($c->hero_video_url)
                            <video autoplay muted loop playsinline preload="metadata" class="w-full h-full object-cover" onerror="this.style.display='none'">
                                <source src="{{ $c->hero_video_url }}" type="video/mp4">
                            </video>
                            @elseif($c->hero_thumbnail)
                            <img src="{{ $c->hero_thumbnail }}" alt="{{ $c->name }}" class="w-full h-full object-cover">
                            @else
                            <div class="w-full h-full flex items-center justify-center">
                                <span class="text-4xl font-black text-white/20">{{ substr($c->name, 0, 2) }}</span>
                            </div>
                            @endif
                            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-3">
                                <div class="text-white font-bold text-sm">{{ $c->name }}</div>
                                <div class="text-white/70 text-[10px]">{{ $c->product_count }} products · {{ $c->institution_count }} institutions</div>
                            </div>
                        </a>
                        {{-- Actions --}}
                        <div class="p-3 flex items-center justify-between gap-2">
                            <a href="{{ route('county.admin.pro', $c->slug) }}" class="text-[10px] font-bold px-3 py-1.5 rounded-lg bg-[#F59E0B]/10 text-[#F59E0B] hover:bg-[#F59E0B]/20 transition-all shrink-0">Open Admin →</a>
                            <div class="flex gap-1.5 shrink-0">
                                <form method="POST" action="{{ route('kicc.admin.county.hero', $c->slug) }}" enctype="multipart/form-data" class="flex items-center gap-1.5">
                                    @csrf
                                    <input type="file" name="video" accept="video/mp4,video/webm" class="text-[9px] text-gray-400 w-24">
                                    <button class="text-[10px] font-bold px-2.5 py-1.5 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-all" title="Upload hero video">Upload</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{--  EXHIBITORS  --}}
            @if($tab === 'exhibitors')
            <div>
                <h3 class="font-bold text-gray-900 mb-5">Private Exhibitors ({{ $exhibitors->count() }})</h3>
                <div class="grid md:grid-cols-2 gap-3">
                    @foreach($exhibitors as $e)
                    <div class="flex items-center gap-4 bg-white border border-gray-200 rounded-xl px-5 py-4 hover:shadow-md transition-all">
                        <div class="w-11 h-11 rounded-full bg-gradient-to-br from-[#2D6A4F] to-[#40916C] flex items-center justify-center text-white font-black text-sm shrink-0">{{ strtoupper(substr($e->name, 0, 2)) }}</div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-gray-900 text-sm">{{ $e->name }}</div>
                            <div class="text-xs text-gray-400">{{ $e->email }} &middot; {{ $e->county?->name ?? 'N/A' }} &middot; {{ $e->product_count }} products</div>
                        </div>
                        <div class="flex gap-2 shrink-0">
                            <a href="{{ route('exhibitor.site', \Illuminate\Support\Str::slug($e->name)) }}" class="text-[10px] font-bold px-2.5 py-1.5 rounded-lg bg-gray-100 text-gray-600 hover:bg-[#38BDF8]/10 hover:text-[#38BDF8] transition-all">Storefront</a>
                            <a href="{{ route('exhibitor.admin') }}" class="text-[10px] font-bold px-2.5 py-1.5 rounded-lg bg-[#38BDF8]/10 text-[#38BDF8] hover:bg-[#38BDF8]/20 transition-all">Admin</a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{--  INSTITUTIONS  --}}
            @if($tab === 'institutions')
            <div>
                <h3 class="font-bold text-gray-900 mb-5">All Institutions ({{ $institutions->count() }})</h3>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($institutions as $inst)
                    <a href="{{ route('institution.admin', $inst->slug) }}"
                       class="flex items-center gap-3 bg-white border border-gray-200 rounded-xl px-4 py-3 hover:border-[#F59E0B]/40 hover:shadow-md transition-all group">
                        <div class="w-9 h-9 rounded-lg bg-[#F59E0B]/10 flex items-center justify-center font-black text-[#F59E0B] text-xs shrink-0">{{ substr($inst->name, 0, 2) }}</div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-gray-900 text-sm truncate">{{ $inst->name }}</div>
                            <div class="text-[10px] text-gray-400">{{ $inst->county?->name }} · {{ count($inst->products ?? []) }} products</div>
                        </div>
                        <svg class="w-4 h-4 text-gray-300 group-hover:text-[#F59E0B] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            {{--  NATIONAL  --}}
            @if($tab === 'national')
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-gray-900">National Government Portals</h3>
                    <a href="{{ route('national.admin') }}" class="text-xs font-bold text-[#F59E0B] hover:underline">Open National Admin &nearr;</a>
                </div>
                <div class="grid md:grid-cols-2 gap-4">
                    @foreach($ministries as $m)
                    <div class="bg-white border border-gray-200 rounded-2xl p-6 hover:shadow-md transition-all">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-gray-900 font-black text-xs" style="background: {{ $m->color ?: '#0EA5E9' }}">{{ $m->code }}</div>
                                <div>
                                    <div class="font-bold text-gray-900">{{ $m->name }}</div>
                                    <div class="text-[10px] text-gray-400">{{ $m->agencies->count() }} agencies</div>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2 mt-3">
                            <a href="{{ route('national.site', $m->slug) }}" class="text-[10px] font-bold px-3 py-1.5 rounded-lg bg-gray-100 text-gray-600 hover:bg-[#0EA5E9]/10 hover:text-[#0EA5E9] transition-all">View Public Site &nearr;</a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{--  PROVIDERS  --}}
            @if($tab === 'providers')
            <div class="grid lg:grid-cols-2 gap-6">
                <div class="bg-white border border-gray-200 rounded-2xl p-6">
                    <h3 class="font-bold text-gray-900 mb-5">Certified Providers ({{ $providers->count() }})</h3>
                    @foreach($providers as $p)
                    <div class="flex items-center gap-4 py-3 border-b border-gray-100 last:border-0">
                        <div class="w-10 h-10 rounded-full bg-[#0EA5E9] flex items-center justify-center text-gray-900 font-black text-xs shrink-0">{{ strtoupper(substr($p->name, 0, 2)) }}</div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-gray-900 text-sm">{{ $p->name }}</div>
                            <div class="text-xs text-gray-400">{{ $p->email }} · {{ ($p->metadata['provider_type'] ?? 'provider') }}</div>
                        </div>
                        @if($p->metadata['approved'] ?? false)
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600">CERTIFIED</span>
                        @else
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-50 text-amber-600">PENDING</span>
                        @endif
                    </div>
                    @endforeach
                </div>
                <div class="bg-white border border-gray-200 rounded-2xl p-6">
                    <h3 class="font-bold text-gray-900 mb-5">Certification Queue ({{ $pendingServices->count() }})</h3>
                    @forelse($pendingServices as $s)
                    <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                        <div>
                            <div class="font-semibold text-gray-900 text-sm">{{ $s['label'] }}</div>
                            <div class="text-xs text-gray-400">KES {{ number_format($s['price']) }} · {{ $s['table'] }}</div>
                        </div>
                        <form method="POST" action="{{ route('kicc.admin.approve', [$s['table'], $s['id']]) }}">@csrf
                            <button class="text-[10px] font-bold px-3 py-1.5 rounded-lg text-gray-900 bg-emerald-600 hover:bg-emerald-700">CERTIFY </button>
                        </form>
                    </div>
                    @empty
                    <p class="text-gray-400 text-sm py-6 text-center">Queue clear — nothing awaiting certification.</p>
                    @endforelse
                </div>
            </div>
            @endif

            {{--  ORDERS  --}}
            @if($tab === 'orders')
            <div class="bg-white border border-gray-200 rounded-2xl p-6" style="max-height:600px; overflow-y:auto;">
                <h3 class="font-bold text-gray-900 mb-5">All Orders</h3>
                <div style="max-height:500px; overflow-y:auto;">
                @forelse($orders as $o)
                <div class="py-4 border-b border-gray-100 last:border-0">
                    <div class="flex items-center justify-between mb-2">
                        <div class="font-semibold text-gray-900 text-sm">{{ $o->order_number }}</div>
                        <div class="text-xs text-gray-400">{{ $o->created_at?->format('d M Y H:i') }}</div>
                    </div>
                    @foreach($o->items as $item)
                    <div class="flex justify-between text-xs text-gray-500 py-1"><span>{{ $item->product_name }} × {{ $item->quantity }}</span><span class="font-bold text-gray-900">KES {{ number_format($item->total) }}</span></div>
                    @endforeach
                    <div class="mt-2 flex gap-2">
                        <span class="text-[10px] font-bold px-2 py-1 rounded-full bg-gray-100 text-gray-600">{{ strtoupper($o->payment_status ?? 'pending') }}</span>
                        <span class="text-[10px] font-bold px-2 py-1 rounded-full bg-gray-100 text-gray-600">{{ strtoupper($o->fulfillment_status ?? 'unfulfilled') }}</span>
                    </div>
                </div>
                @empty
                <p class="text-gray-400 text-sm py-6 text-center">No orders yet.</p>
                @endforelse
            </div>
            @endif

            {{--  ESCROW  --}}
            @if($tab === 'escrow')
            <div class="bg-white border border-gray-200 rounded-2xl p-6" style="max-height:600px; overflow-y:auto;">
                <h3 class="font-bold text-gray-900 mb-5">All Escrow Transactions</h3>
                <div style="max-height:500px; overflow-y:auto;">
                @forelse($escrows as $e)
                <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                    <div>
                        <div class="font-semibold text-gray-900 text-sm">{{ $e->escrow_id }}</div>
                        <div class="text-xs text-gray-400">{{ $e->buyer?->name ?? 'Guest' }} &rarr; {{ $e->seller?->name }} &middot; {{ $e->created_at?->format('d M Y') }}</div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="text-right">
                            <div class="font-black text-gray-900">KES {{ number_format($e->amount) }}</div>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $e->status === 'released' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600' }}">{{ strtoupper($e->status) }}</span>
                        </div>
                        @if($e->status === 'held')
                        <form method="POST" action="{{ route('kicc.admin.escrow.release', $e->id) }}" onsubmit="return confirm('Release KES {{ number_format($e->amount) }} to {{ $e->seller?->name }}?')">@csrf<button class="text-[10px] font-bold px-2.5 py-1.5 rounded-lg text-gray-900" style="background: {{ $accent }}">RELEASE</button></form>
                        @endif
                    </div>
                </div>
                @empty
                <p class="text-gray-400 text-sm py-6 text-center">No escrow transactions yet.</p>
                @endforelse
            </div>
            @endif

            {{--  USERS  --}}
            @if($tab === 'users')
            <div class="bg-white border border-gray-200 rounded-2xl p-6" style="max-height:600px;">
                <h3 class="font-bold text-gray-900 mb-5">Platform Users</h3>
                 <div class="overflow-x-auto overflow-y-auto" style="max-height:480px;">
                    <table class="w-full text-sm">
                        <thead><tr class="text-left text-[10px] uppercase tracking-widest text-gray-400 border-b border-gray-100">
                            <th class="pb-3 pr-4">Name</th><th class="pb-3 pr-4">Email</th><th class="pb-3 pr-4">Type</th><th class="pb-3">Roles</th>
                        </tr></thead>
                        <tbody>
                        @foreach($users as $u)
                        <tr class="border-b border-gray-50 last:border-0">
                            <td class="py-2.5 pr-4 font-semibold text-gray-900">{{ $u->name }}</td>
                            <td class="py-2.5 pr-4 text-gray-500 text-xs">{{ $u->email }}</td>
                            <td class="py-2.5 pr-4 text-gray-500 text-xs">{{ $u->account_type }}</td>
                            <td class="py-2.5 text-gray-500 text-xs">{{ $u->roles->pluck('name')->implode(', ') ?: '—' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{--  HERO MEDIA  --}}
            @if($tab === 'hero_media')
            <div class="grid md:grid-cols-2 gap-6">
                <div class="bg-white border border-gray-200 rounded-2xl p-6">
                    <h3 class="font-bold text-gray-900 mb-4">Landing Page Hero Video</h3>
                    @if($heroAsset)
                    <div class="aspect-video bg-black rounded-xl overflow-hidden mb-4">
                        <video autoplay muted loop playsinline class="w-full h-full object-cover">
                            <source src="{{ $heroAsset->mp4Url() ?? $heroAsset->url() }}" type="video/mp4">
                        </video>
                    </div>
                    <p class="text-xs text-gray-500 mb-4">Current video: {{ $heroAsset->original_name }}</p>
                    <form method="POST" action="{{ route('kicc.admin.hero.delete') }}" onsubmit="return confirm('Delete the hero video?')">
                        @csrf
                        <button class="px-4 py-2 rounded-lg bg-red-50 text-red-600 text-xs font-bold hover:bg-red-100">Delete Video</button>
                    </form>
                    @else
                    <div class="aspect-video bg-gray-100 rounded-xl mb-4 flex items-center justify-center">
                        <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    </div>
                    @endif
                    <form method="POST" action="{{ route('kicc.admin.hero.upload') }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <input type="file" name="video" accept="video/mp4,video/webm" required>
                        <button class="w-full px-4 py-2 rounded-xl bg-[#F59E0B] text-white text-sm font-bold hover:bg-[#D98A00]">Upload New Hero Video</button>
                    </form>
                </div>
                <div class="bg-white border border-gray-200 rounded-2xl p-6">
                    <h3 class="font-bold text-gray-900 mb-4">Quick Actions</h3>
                    <div class="space-y-3">
                        <a href="{{ route('marketplace.index') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition-all">
                            <span class="text-2xl"></span>
                            <div><div class="font-semibold text-sm">Marketplace</div><div class="text-xs text-gray-400">Browse all products</div></div>
                        </a>
                        <a href="{{ route('counties.index') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition-all">
                            <span class="text-2xl"></span>
                            <div><div class="font-semibold text-sm">All Counties</div><div class="text-xs text-gray-400">47 county portals</div></div>
                        </a>
                        <a href="{{ route('national-government.index') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition-all">
                            <span class="text-2xl"></span>
                            <div><div class="font-semibold text-sm">National Government</div><div class="text-xs text-gray-400">Ministries & agencies</div></div>
                        </a>
                        <a href="{{ route('institutions', 'kakuzi-plc') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition-all">
                            <span class="text-2xl"></span>
                            <div><div class="font-semibold text-sm">Institutions</div><div class="text-xs text-gray-400">Kakuzi, Guka's, MUT & more</div></div>
                        </a>
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>
</div>
@endsection
