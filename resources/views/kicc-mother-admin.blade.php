@extends('layouts.nexora')

@section('title', 'KICC Mother Admin — Nexora Control')

@php $accent = '#901C1E'; @endphp

@section('content')
<div class="flex h-screen overflow-hidden" x-data="{ tab: '{{ $tab ?? 'overview' }}', drawer: null, setTab(t) { this.tab = t; history.replaceState(null,'','?tab='+t); } }">

    <aside class="glass-nav flex flex-col w-64 shrink-0 z-30 overflow-y-auto">
        <div class="flex items-center gap-3 h-16 px-5 border-b border-white/5 shrink-0">
            <img src="{{ media('kicc/kicc-logo.png') }}" alt="KICC" class="w-9 h-9 rounded-xl object-contain bg-white/10 p-1.5">
            <div>
                <div class="text-white font-bold text-sm leading-tight">KICC</div>
                <div class="text-[#FFCD05] text-[9px] font-bold tracking-[0.2em] uppercase">Global Exhibition Admin</div>
            </div>
        </div>
        <div class="flex-1 px-3 py-4 space-y-6 scrollbar-hide">
            @foreach($navItems as $item)
            <div class="space-y-1">
                <a href="{{ route('kicc.admin', ['tab' => $item['tab']]) }}"
                   class="sidebar-link"
                   :class="tab === '{{ $item['tab'] }}' ? 'sidebar-link-active' : 'sidebar-link-inactive'">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/></svg>
                    <span>{{ $item['label'] }}</span>
                </a>
            </div>
            @endforeach
            <div class="px-3 pt-4 border-t border-white/5 space-y-1">
                <a href="{{ route('kicc.admin', ['tab' => 'counties']) }}" class="sidebar-link sidebar-link-inactive"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg><span>County Portals</span></a>
                <a href="{{ route('national.admin') }}" class="sidebar-link sidebar-link-inactive"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg><span>National Government</span></a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="sidebar-link sidebar-link-inactive w-full"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg><span>Logout</span></button></form>
            </div>
        </div>
    </aside>

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="glass-header h-16 px-6 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2 text-xs text-zinc-500">
                <span>KICC Mother Admin</span>
                <span>/</span>
                <span class="text-rose-400 font-medium">{{ ucfirst($tab) }}</span>
            </div>
            <div class="flex items-center gap-3">
                @if(session('success'))
                <span class="text-[11px] text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-3 py-1.5 rounded-lg">{{ session('success') }}</span>
                @endif
                <div class="flex items-center gap-2.5">
                    <div class="text-xs text-zinc-200 font-medium">{{ Auth::user()?->name ?? 'Admin' }}</div>
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-red-500 to-rose-600 flex items-center justify-center text-white font-bold text-xs">K</div>
                </div>
            </div>
        </header>
        <main class="flex-1 overflow-y-auto p-6 scrollbar-hide">
            @isset($errors) @if($errors->any())<div class="mb-4 px-5 py-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">{{ $errors->first() }}</div>@endif @endisset

            {{--  OVERVIEW  --}}
            @if($tab === 'overview')
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <x-nexora-kpi title="Counties" :value="number_format($stats['counties'])" growth="47 active portals" color="indigo" :sparkline="[44,45,46,46,47,47,47,47,47,47,47,47]" />
                <x-nexora-kpi :title="'Users'" :value="number_format($stats['users'])" :growth="$stats['exhibitors'] . ' exhibitors'" color="emerald" />
                <x-nexora-kpi :title="'Products'" :value="number_format($stats['products'])" :growth="$stats['orders'] . ' orders'" color="amber" />
                <x-nexora-kpi title="Total Revenue" :value="'KES ' . number_format($stats['payments'] ?: 0)" :growth="'Released: ' . number_format($stats['releasedEscrow'] ?: 0)" color="emerald" />
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
                <x-nexora-kpi title="Pipeline Registrations" :value="number_format($stats['pipelineCount'] ?: 0)" :growth="$stats['pipelineLicences'] . ' licences'" color="sky" />
                <x-nexora-kpi title="Institutions" :value="number_format($stats['institutionCount'] ?: 0)" :growth="$stats['ministries'] . ' ministries'" color="violet" />
                <x-nexora-kpi title="Venues" :value="number_format($stats['venueCount'] ?: 0)" growth="KICC &amp; county venues" color="indigo" />
                <x-nexora-kpi title="Escrow Volume" :value="'KES ' . number_format($stats['escrowTotal'] ?: 0)" :growth="$stats['releasedCount'] . ' released'" color="red" />
                <x-nexora-kpi title="Live Streams" :value="$streamStats['live'] . ' LIVE'" :growth="$streamStats['total'] . ' total'" color="rose" />
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
                <div class="glass-card rounded-2xl p-5">
                    <h3 class="text-sm font-bold text-white mb-3">User Growth</h3>
                    <div class="space-y-2 text-xs text-zinc-400">
                        <div class="flex justify-between"><span>Total Users</span><span class="font-semibold text-white">{{ number_format($stats['users']) }}</span></div>
                        <div class="flex justify-between"><span>Exhibitors</span><span class="font-semibold text-white">{{ $stats['exhibitors'] }}</span></div>
                        <div class="flex justify-between"><span>Trade Board Users</span><span class="font-semibold text-white">{{ $stats['tradeBoards'] ?? 0 }}</span></div>
                        <div class="flex justify-between"><span>Ministries</span><span class="font-semibold text-white">{{ $stats['ministries'] }}</span></div>
                        <div class="flex justify-between"><span>Agencies</span><span class="font-semibold text-white">{{ $stats['agencies'] }}</span></div>
                    </div>
                    <div class="border-t border-white/5 my-2"></div>
                    <div class="flex items-center justify-center gap-4">
                        <div class="text-center"><div class="text-2xl font-bold text-emerald-400">{{ number_format($stats['todayUsers']) }}</div><div class="text-xs text-zinc-500">Today</div></div>
                        <div class="text-center"><div class="text-2xl font-bold text-amber-400">{{ number_format($stats['weekUsers']) }}</div><div class="text-xs text-zinc-500">This Week</div></div>
                    </div>
                </div>

                <div class="glass-card rounded-2xl p-5">
                    <h3 class="text-sm font-bold text-white mb-3">Platform Activity</h3>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between"><span class="text-zinc-400">Marketplace Orders</span><span class="font-semibold text-white">{{ $stats['orders'] }}</span></div>
                        <div class="flex justify-between"><span class="text-zinc-400">Products Listed</span><span class="font-semibold text-white">{{ $stats['products'] }}</span></div>
                        <div class="flex justify-between"><span class="text-zinc-400">Escrow Transactions</span><span class="font-semibold text-white">{{ $stats['escrowHeld'] }} held</span></div>
                        <div class="flex justify-between"><span class="text-zinc-400">Total Revenue (Payments)</span><span class="font-semibold text-white">KES {{ number_format($stats['payments'] ?: 0) }}</span></div>
                        <div class="flex justify-between"><span class="text-zinc-400">Released Escrow</span><span class="font-semibold text-white">KES {{ number_format($stats['releasedEscrow'] ?: 0) }}</span></div>
                        <div class="flex justify-between"><span class="text-zinc-400">Search Analytics</span><span class="font-semibold text-white">{{ number_format($stats['searchAnalyticsCount'] ?: 0) }} searches</span></div>
                    </div>
                </div>

                <div class="glass-card rounded-2xl p-5">
                    <h3 class="text-sm font-bold text-white mb-3">Pipeline Overview</h3>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between"><span class="text-zinc-400">Total Pipelines</span><span class="font-semibold text-white">{{ $stats['pipelineCount'] ?? 0 }}</span></div>
                        <div class="flex justify-between"><span class="text-zinc-400">Active Licences</span><span class="font-semibold text-white">{{ $stats['pipelineLicences'] ?? 0 }}</span></div>
                        <div class="flex justify-between"><span class="text-zinc-400">Live Streams</span><span class="font-semibold text-white">{{ $streamStats['live'] }}/{{ $streamStats['total'] }}</span></div>
                        <div class="flex justify-between"><span class="text-zinc-400">Viewer Count</span><span class="font-semibold text-white">{{ number_format($streamStats['viewers']) }}</span></div>
                        <div class="flex justify-between"><span class="text-zinc-400">Experience Bookings</span><span class="font-semibold text-white">{{ $experienceStats['total'] }}</span></div>
                        <div class="flex justify-between"><span class="text-zinc-400">Experience Revenue</span><span class="font-semibold text-white">KES {{ number_format($experienceStats['revenue'] ?: 0) }}</span></div>
                    </div>
                    @if(($stats['topSectors'] ?? collect())->isNotEmpty())
                    <div class="border-t border-white/5 my-2"></div>
                    <div class="text-[10px] text-zinc-500">Top Pipeline Sectors</div>
                    <div class="flex flex-wrap gap-1 mt-1">
                        @foreach($stats['topSectors'] as $ts)
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">{{ $ts->sector ?? $ts['sector'] }} ({{ $ts->c ?? $ts['c'] }})</span>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                <a href="{{ route('kicc.admin', ['tab' => 'counties']) }}" class="kpi-card flex items-start gap-3 p-4">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-600 flex items-center justify-center text-white font-bold text-xs">47</div>
                    <div><div class="font-bold text-white text-sm">County Admins</div><div class="text-[10px] text-zinc-400">Manage all 47 county portals</div></div>
                </a>
                <a href="{{ route('kicc.admin', ['tab' => 'national']) }}" class="kpi-card flex items-start gap-3 p-4">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-sky-500 to-blue-600 flex items-center justify-center text-white font-bold text-xs">N</div>
                    <div><div class="font-bold text-white text-sm">National Government</div><div class="text-[10px] text-zinc-400">{{ $stats['ministries'] }} ministries</div></div>
                </a>
                <a href="{{ route('kicc.admin', ['tab' => 'exhibitors']) }}" class="kpi-card flex items-start gap-3 p-4">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white font-bold text-xs">E</div>
                    <div><div class="font-bold text-white text-sm">Exhibitors</div><div class="text-[10px] text-zinc-400">{{ $stats['exhibitors'] }} registered</div></div>
                </a>
            </div>

            <div class="glass-card rounded-2xl p-5">
                <h3 class="font-bold text-white text-sm mb-3">Artisan Console</h3>
                <form method="POST" action="{{ route('kicc.admin.artisan') }}" class="flex gap-2">
                    @csrf
                    <select name="command" class="flex-1">
                        <option value="">Select a command…</option>
                        @foreach(['cache:clear','config:clear','route:clear','view:clear','optimize:clear','migrate','schedule:run','queue:restart','db:seed --class=MurangaLiveInstitutionsSeeder --force','db:seed --class=MurangaAllSectorsSeeder --force'] as $cmd)
                        <option value="{{ $cmd }}">{{ $cmd }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn-primary">Run</button>
                </form>
            </div>
            @endif

            {{--  PORTALS  --}}
            @if($tab === 'portals')
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-white mb-1">Sub-Portals</h1>
                <p class="text-zinc-400 text-sm">Access all administration tiers from a single hub</p>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="glass-card rounded-2xl p-6 border-2 border-rose-500/30">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-rose-500 to-rose-600 flex items-center justify-center text-white font-bold text-sm">K</div>
                        <div><div class="font-bold text-white text-lg">KICC Mother Admin</div><div class="text-xs text-zinc-400">You are here</div></div>
                    </div>
                    <div class="text-xs text-zinc-500 leading-relaxed">Platform owner's god-mode: counties, institutions, national govt, exhibitors, orders, escrow, pipelines, live events, users, venues, analytics and every system setting.</div>
                </div>
                <a href="{{ route('kicc.admin', ['tab' => 'counties']) }}" class="glass-card rounded-2xl p-6 group hover:brightness-110 transition-all">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white font-bold text-sm">47</div>
                        <div><div class="font-bold text-white text-lg">County Portals</div><div class="text-xs text-zinc-400">47 counties — trade boards</div></div>
                    </div>
                    <div class="text-xs text-zinc-500 leading-relaxed">Each county has its own professional admin: content management, hero videos, 4D video upload, sector mapping, images, marketplace products, advertising, pricing and reports.</div>
                </a>
                <a href="{{ route('national.admin') }}" class="glass-card rounded-2xl p-6 group hover:brightness-110 transition-all">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-sky-500 to-blue-600 flex items-center justify-center text-white font-bold text-sm">N</div>
                        <div><div class="font-bold text-white text-lg">National Government</div><div class="text-xs text-zinc-400">{{ $stats['ministries'] }} ministries &middot; {{ $stats['agencies'] }} agencies</div></div>
                    </div>
                    <div class="text-xs text-zinc-500 leading-relaxed">National-level administration: manage ministries, government agencies, national hero video, and county classification for pipeline activation.</div>
                </a>
                <a href="{{ route('exhibitor.admin') }}" class="glass-card rounded-2xl p-6 group hover:brightness-110 transition-all">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white font-bold text-sm">E</div>
                        <div><div class="font-bold text-white text-lg">Private Exhibitors</div><div class="text-xs text-zinc-400">{{ $stats['exhibitors'] }} registered exhibitors</div></div>
                    </div>
                    <div class="text-xs text-zinc-500 leading-relaxed">Individual and SME portals: exhibitor onboarding, subscription plans, booth management, marketplace listings, show booking and trade board access.</div>
                </a>
                <a href="{{ route('kicc.admin', ['tab' => 'institutions']) }}" class="glass-card rounded-2xl p-6 group hover:brightness-110 transition-all">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white font-bold text-sm">I</div>
                        <div><div class="font-bold text-white text-lg">Institution Admin</div><div class="text-xs text-zinc-400">{{ $stats['institutionCount'] ?? 0 }} institutions</div></div>
                    </div>
                    <div class="text-xs text-zinc-500 leading-relaxed">County institutions dashboard: manage institution profile, sector mapping, production chain, team, products, videos, analytics and sync status.</div>
                </a>
                <a href="{{ route('kicc.admin', ['tab' => 'venues']) }}" class="glass-card rounded-2xl p-6 group hover:brightness-110 transition-all">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-cyan-500 to-sky-600 flex items-center justify-center text-white font-bold text-sm">V</div>
                        <div><div class="font-bold text-white text-lg">Venue Management</div><div class="text-xs text-zinc-400">{{ $stats['venueCount'] ?? 0 }} venues</div></div>
                    </div>
                    <div class="text-xs text-zinc-500 leading-relaxed">KICC &amp; county venues: manage exhibition halls, event spaces, capacity, pricing, amenities, cover images and availability.</div>
                </a>
            </div>
            @endif

            {{--  COUNTIES (all 47)  --}}
            @if($tab === 'counties')
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="font-bold text-white text-lg">All 47 Counties</h3>
                        <p class="text-zinc-500 text-sm">Each county has its own Muranga-style admin. Click to manage, upload hero video/thumbnail.</p>
                    </div>
                    <span class="text-xs font-bold px-3 py-1 rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/20">{{ $counties->count() }} counties</span>
                </div>
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    @foreach($counties as $c)
                    <div class="glass-card rounded-2xl overflow-hidden hover-scale">
                        <a href="{{ route('county.admin.pro', $c->slug) }}" class="block relative aspect-[16/9] bg-gradient-to-br from-[#0A1024] to-[#1a1a2e] overflow-hidden">
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
                            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-3">
                                <div class="text-white font-bold text-sm">{{ $c->name }}</div>
                                <div class="text-white/70 text-[10px]">{{ $c->product_count }} products · {{ $c->institution_count }} institutions · KES {{ number_format($c->trade_volume) }}</div>
                            </div>
                        </a>
                        <div class="p-3 flex items-center justify-between gap-2">
                            <a href="{{ route('county.admin.pro', $c->slug) }}" class="text-[10px] font-bold px-3 py-1.5 rounded-lg bg-rose-500/10 text-rose-400 hover:bg-rose-500/20 transition-all shrink-0">Open Admin →</a>
                            <form method="POST" action="{{ route('kicc.admin.county.hero', $c->slug) }}" enctype="multipart/form-data" class="flex items-center gap-1.5">
                                @csrf
                                <input type="file" name="video" accept="video/mp4,video/webm" class="text-[9px] text-zinc-400 w-24">
                                <button class="text-[10px] font-bold px-2.5 py-1.5 rounded-lg bg-white/5 text-zinc-300 hover:bg-white/10 transition-all" title="Upload hero video">Upload</button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{--  INSTITUTIONS  --}}
            @if($tab === 'institutions')
            <div>
                <h3 class="font-bold text-white text-lg mb-4">All Institutions ({{ $institutions->count() }})</h3>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($institutions as $inst)
                    <a href="{{ route('institution.admin', $inst->slug) }}"
                       class="flex items-center gap-3 glass-card rounded-xl px-4 py-3 hover:border-rose-500/30 transition-all group">
                        <div class="w-9 h-9 rounded-lg bg-rose-500/10 flex items-center justify-center font-black text-rose-400 text-xs shrink-0">{{ substr($inst->name, 0, 2) }}</div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-white text-sm truncate">{{ $inst->name }}</div>
                            <div class="text-[10px] text-zinc-500">{{ $inst->county?->name }} · {{ count($inst->products ?? []) }} products</div>
                        </div>
                        <svg class="w-4 h-4 text-zinc-600 group-hover:text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            {{--  VENUES  --}}
            @if($tab === 'venues')
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-white text-lg">Venues ({{ $adminVenues->total() }})</h3>
                    <button @click="drawer = 'venue-form'; $nextTick(() => document.getElementById('venue-name')?.focus())" class="text-xs font-bold px-3 py-1.5 rounded-lg bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 transition-all">+ Add Venue</button>
                </div>
                <div class="table-kicc-wrap">
                    <table class="table-kicc">
                        <thead><tr><th>Name</th><th>Type</th><th>Capacity</th><th>Institution</th><th></th></tr></thead>
                        <tbody>
                        @foreach($adminVenues as $v)
                        <tr>
                            <td class="font-semibold text-white">{{ $v->name }}</td>
                            <td><span class="text-zinc-400 text-xs">{{ $v->venue_type ?? '—' }}</span></td>
                            <td class="text-zinc-400">{{ $v->capacity ?? '—' }}</td>
                            <td class="text-zinc-400 text-xs">{{ $v->institution?->name ?? '—' }}</td>
                            <td><a href="{{ route('venues.show', $v->slug) }}" class="text-rose-400 text-xs hover:underline">View</a></td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $adminVenues->onEachSide(1)->links() }}</div>
            </div>

            {{--  Venue quick-create drawer  --}}
            <div x-show="drawer === 'venue-form'" x-cloak x-transition
                 class="fixed inset-0 z-50 flex justify-end" @click.self="drawer = null">
                <div class="w-full max-w-lg bg-[#111827] border-l border-zinc-800 p-6 overflow-y-auto" @click.stop>
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-white font-bold text-lg">New Venue</h3>
                        <button @click="drawer = null" class="text-zinc-400 hover:text-white p-2">✕</button>
                    </div>
                    <form method="POST" action="{{ route('kicc.admin.venue.store') }}" class="space-y-4">
                        @csrf
                        <div><label class="label-kicc text-zinc-300">Name</label><input id="venue-name" name="name" required class="input-kicc bg-zinc-800 border-zinc-700 text-white"></div>
                        <div><label class="label-kicc text-zinc-300">Type</label>
                            <select name="venue_type" class="input-kicc bg-zinc-800 border-zinc-700 text-white">
                                <option value="">— Select —</option>
                                <option>Plenary Hall</option><option>Theatre</option><option>Meeting Room</option><option>Boardroom</option><option>Outdoor</option><option>VIP/Events</option>
                            </select>
                        </div>
                        <div><label class="label-kicc text-zinc-300">Capacity</label><input type="number" name="capacity" class="input-kicc bg-zinc-800 border-zinc-700 text-white"></div>
                        <div><label class="label-kicc text-zinc-300">Description</label><textarea name="description" rows="3" class="input-kicc bg-zinc-800 border-zinc-700 text-white"></textarea></div>
                        <button type="submit" class="btn-kicc btn-kicc-primary w-full justify-center">Create Venue</button>
                    </form>
                </div>
            </div>
            @endif

            {{--  NATIONAL  --}}
            @if($tab === 'national')
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-white text-lg">National Government Portals</h3>
                    <a href="{{ route('national.admin') }}" class="text-xs font-bold text-sky-400 hover:underline">Open National Admin →</a>
                </div>
                <div class="grid md:grid-cols-2 gap-4">
                    @foreach($ministries as $m)
                    <div class="glass-card rounded-2xl p-6">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-black text-xs" style="background: {{ $m->color ?: '#0EA5E9' }}">{{ $m->code }}</div>
                                <div>
                                    <div class="font-bold text-white">{{ $m->name }}</div>
                                    <div class="text-[10px] text-zinc-500">{{ $m->agencies->count() }} agencies</div>
                                </div>
                            </div>
                        </div>
                        <a href="{{ route('national.site', $m->slug) }}" class="text-[10px] font-bold px-3 py-1.5 rounded-lg bg-sky-500/10 text-sky-400 hover:bg-sky-500/20 transition-all">View Public Site →</a>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{--  EXHIBITORS  --}}
            @if($tab === 'exhibitors')
            <div>
                <h3 class="font-bold text-white text-lg mb-5">Private Exhibitors ({{ $exhibitors->count() }})</h3>
                <div class="grid md:grid-cols-2 gap-3">
                    @foreach($exhibitors as $e)
                    <div class="flex items-center gap-4 glass-card rounded-xl px-5 py-4">
                        <div class="w-11 h-11 rounded-full bg-gradient-to-br from-emerald-600 to-emerald-500 flex items-center justify-center text-white font-black text-sm shrink-0">{{ strtoupper(substr($e->name, 0, 2)) }}</div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-white text-sm">{{ $e->name }}</div>
                            <div class="text-xs text-zinc-500">{{ $e->email }} · {{ $e->county?->name ?? 'N/A' }} · {{ $e->product_count }} products</div>
                        </div>
                        <div class="flex gap-2 shrink-0">
                            <a href="{{ route('exhibitor.site', \Illuminate\Support\Str::slug($e->name)) }}" class="text-[10px] font-bold px-2.5 py-1.5 rounded-lg bg-white/5 text-zinc-300 hover:bg-white/10 transition-all">Storefront</a>
                            <a href="{{ route('exhibitor.admin') }}" class="text-[10px] font-bold px-2.5 py-1.5 rounded-lg bg-sky-500/10 text-sky-400 hover:bg-sky-500/20 transition-all">Admin</a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{--  PROVIDERS  --}}
            @if($tab === 'providers')
            <div class="grid lg:grid-cols-2 gap-6">
                <div class="glass-card rounded-2xl p-6" style="max-height:600px; overflow-y:auto;">
                    <h3 class="font-bold text-white mb-5">Certified Providers ({{ $providers->count() }})</h3>
                    @foreach($providers as $p)
                    <div class="flex items-center gap-4 py-3 border-b border-white/5 last:border-0">
                        <div class="w-10 h-10 rounded-full bg-sky-500/20 flex items-center justify-center text-sky-400 font-black text-xs shrink-0">{{ strtoupper(substr($p->name, 0, 2)) }}</div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-white text-sm">{{ $p->name }}</div>
                            <div class="text-xs text-zinc-500">{{ $p->email }} · {{ ($p->metadata['provider_type'] ?? 'provider') }}</div>
                        </div>
                        @if($p->metadata['approved'] ?? false)
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400">CERTIFIED</span>
                        @else
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-400">PENDING</span>
                        @endif
</div>
                    @endforeach
                </div>
                <div class="glass-card rounded-2xl p-6" style="max-height:600px; overflow-y:auto;">
                    <h3 class="font-bold text-white mb-5">Certification Queue ({{ $pendingServices->count() }})</h3>
                    @forelse($pendingServices as $s)
                    <div class="flex items-center justify-between py-3 border-b border-white/5 last:border-0">
                        <div>
                            <div class="font-semibold text-white text-sm">{{ $s['label'] }}</div>
                            <div class="text-xs text-zinc-500">KES {{ number_format($s['price']) }} · {{ $s['table'] }}</div>
                        </div>
                        <form method="POST" action="{{ route('kicc.admin.approve', [$s['table'], $s['id']]) }}">@csrf
                            <button class="text-[10px] font-bold px-3 py-1.5 rounded-lg bg-emerald-500/20 text-emerald-400 hover:bg-emerald-500/30">CERTIFY </button>
                        </form>
                    </div>
                    @empty
                    <p class="text-zinc-500 text-sm py-6 text-center">Queue clear — nothing awaiting certification.</p>
                    @endforelse
                </div>
            </div>
            @endif

            {{--  ORDERS  --}}
            @if($tab === 'orders')
            <div class="glass-card rounded-2xl p-6" style="max-height:600px; overflow-y:auto;">
                <h3 class="font-bold text-white mb-5">All Orders</h3>
                @forelse($orders as $o)
                <div class="py-4 border-b border-white/5 last:border-0">
                    <div class="flex items-center justify-between mb-2">
                        <div class="font-semibold text-white text-sm">{{ $o->order_number }}</div>
                        <div class="text-xs text-zinc-500">{{ $o->created_at?->format('d M Y H:i') }}</div>
                    </div>
                    @foreach($o->items as $item)
                    <div class="flex justify-between text-xs text-zinc-500 py-1"><span>{{ $item->product_name }} × {{ $item->quantity }}</span><span class="font-bold text-white">KES {{ number_format($item->total) }}</span></div>
                    @endforeach
                    <div class="mt-2 flex gap-2">
                        <span class="text-[10px] font-bold px-2 py-1 rounded-full bg-white/5 text-zinc-400">{{ strtoupper($o->payment_status ?? 'pending') }}</span>
                        <span class="text-[10px] font-bold px-2 py-1 rounded-full bg-white/5 text-zinc-400">{{ strtoupper($o->fulfillment_status ?? 'unfulfilled') }}</span>
                    </div>
                </div>
                @empty
                <p class="text-zinc-500 text-sm py-6 text-center">No orders yet.</p>
                @endforelse
            </div>
            @endif

            {{--  ESCROW  --}}
            @if($tab === 'escrow')
            <div class="glass-card rounded-2xl p-6" style="max-height:600px; overflow-y:auto;">
                <h3 class="font-bold text-white mb-5">All Escrow Transactions</h3>
                @forelse($escrows as $e)
                <div class="flex items-center justify-between py-3 border-b border-white/5 last:border-0">
                    <div>
                        <div class="font-semibold text-white text-sm">{{ $e->escrow_id }}</div>
                        <div class="text-xs text-zinc-500">{{ $e->buyer?->name ?? 'Guest' }} → {{ $e->seller?->name }} · {{ $e->created_at?->format('d M Y') }}</div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="text-right">
                            <div class="font-black text-white">KES {{ number_format($e->amount) }}</div>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $e->status === 'released' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-amber-500/10 text-amber-400' }}">{{ strtoupper($e->status) }}</span>
                        </div>
                        @if($e->status === 'held')
                        <form method="POST" action="{{ route('kicc.admin.escrow.release', $e->id) }}" onsubmit="return confirm('Release KES {{ number_format($e->amount) }} to {{ $e->seller?->name }}?')">@csrf<button class="text-[10px] font-bold px-2.5 py-1.5 rounded-lg bg-rose-500/20 text-rose-400 hover:bg-rose-500/30">RELEASE</button></form>
                        @endif
</div>
                </div>
                @empty
                <p class="text-zinc-500 text-sm py-6 text-center">No escrow transactions yet.</p>
                @endforelse
            </div>
            @endif

            {{--  EXPERIENCES  --}}
            @if($tab === 'experiences')
            <div class="glass-card rounded-2xl p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="font-bold text-white text-lg">Experience Bookings</h3>
                    <div class="flex gap-3 text-xs">
                        <span class="text-zinc-400">Total: <strong class="text-white">{{ $experienceStats['total'] }}</strong></span>
                        <span class="text-amber-400">Pending: <strong>{{ $experienceStats['pending'] }}</strong></span>
                        <span class="text-emerald-400">Confirmed: <strong>{{ $experienceStats['confirmed'] }}</strong></span>
                        <span class="text-rose-400">Cancelled: <strong>{{ $experienceStats['cancelled'] }}</strong></span>
                        <span class="text-[#FFCD05]">Revenue: <strong>KES {{ number_format($experienceStats['revenue']) }}</strong></span>
                    </div>
                </div>
                <div class="overflow-x-auto" style="max-height:500px; overflow-y:auto;">
                    <table class="w-full text-xs">
                        <thead><tr class="text-zinc-500 border-b border-white/5">
                            <th class="text-left py-2 pr-3 font-semibold">Ref</th>
                            <th class="text-left py-2 pr-3 font-semibold">User</th>
                            <th class="text-left py-2 pr-3 font-semibold">Destination</th>
                            <th class="text-left py-2 pr-3 font-semibold">Origin</th>
                            <th class="text-left py-2 pr-3 font-semibold">Dates</th>
                            <th class="text-left py-2 pr-3 font-semibold">Transport</th>
                            <th class="text-left py-2 pr-3 font-semibold">Total</th>
                            <th class="text-left py-2 font-semibold">Status</th>
                        </tr></thead>
                        <tbody class="divide-y divide-white/5">
                        @forelse($experienceBookings as $b)
                        <tr class="hover:bg-white/5 transition">
                            <td class="py-2.5 pr-3 font-mono text-zinc-300 text-[10px]">{{ $b->booking_reference }}</td>
                            <td class="py-2.5 pr-3 text-white">{{ $b->user?->name ?? '—' }}</td>
                            <td class="py-2.5 pr-3 text-white">{{ $b->destination?->name ?? '—' }}</td>
                            <td class="py-2.5 pr-3 text-zinc-400">{{ $b->origin_location ?? $b->originCounty?->name ?? '—' }}</td>
                            <td class="py-2.5 pr-3 text-zinc-400">{{ $b->departure_date?->format('M d') }} – {{ $b->return_date?->format('M d') }}</td>
                            <td class="py-2.5 pr-3">
                                <span class="text-[10px] px-2 py-0.5 rounded-full {{ $b->transport_mode === 'road' ? 'bg-emerald-500/20 text-emerald-400' : ($b->transport_mode === 'train' ? 'bg-blue-500/20 text-blue-400' : ($b->transport_mode === 'air+rail' ? 'bg-purple-500/20 text-purple-400' : 'bg-zinc-500/20 text-zinc-400')) }}">
                                    {{ $b->transport_mode ?? '—' }}
                                </span>
                            </td>
                            <td class="py-2.5 pr-3 font-mono text-white font-bold">KES {{ number_format($b->grand_total) }}</td>
                            <td class="py-2.5">
                                <span class="text-[10px] font-bold px-2.5 py-1 rounded-full
                                    {{ $b->status === 'confirmed' ? 'bg-emerald-500/20 text-emerald-400' : '' }}
                                    {{ $b->status === 'pending' ? 'bg-amber-500/20 text-amber-400' : '' }}
                                    {{ $b->status === 'cancelled' ? 'bg-rose-500/20 text-rose-400' : '' }}">
                                    {{ $b->status }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="py-8 text-center text-zinc-500 text-sm">No experience bookings yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex gap-3 text-[10px] text-zinc-500">
                    <span> Road</span>
                    <span> Train</span>
                    <span> Air/Rail</span>
                    <span>| Prices include rating × season × distance multipliers</span>
                </div>
            </div>
            @endif

            {{--  LIVE EVENTS  --}}
            @if($tab === 'live_events')
            <div class="glass-card rounded-2xl p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="font-bold text-white text-lg">Live Events Management</h3>
                    <a href="{{ route('streams.create') }}" class="text-[11px] font-bold px-3 py-1.5 rounded-lg bg-[#901C1E] text-white hover:bg-[#7b1618] transition-all">+ New Stream</a>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
                    <div class="rounded-xl bg-white/5 border border-white/10 p-3">
                        <div class="text-2xl font-black text-white">{{ $streamStats['total'] }}</div>
                        <div class="text-[10px] text-zinc-400 mt-0.5">Total</div>
                    </div>
                    <div class="rounded-xl bg-red-500/10 border border-red-500/20 p-3">
                        <div class="text-2xl font-black text-red-400">{{ $streamStats['live'] }}</div>
                        <div class="text-[10px] text-zinc-400 mt-0.5">Live Now</div>
                    </div>
                    <div class="rounded-xl bg-amber-500/10 border border-amber-500/20 p-3">
                        <div class="text-2xl font-black text-amber-400">{{ $streamStats['idle'] }}</div>
                        <div class="text-[10px] text-zinc-400 mt-0.5">Scheduled</div>
                    </div>
                    <div class="rounded-xl bg-white/5 border border-white/10 p-3">
                        <div class="text-2xl font-black text-zinc-400">{{ $streamStats['ended'] }}</div>
                        <div class="text-[10px] text-zinc-400 mt-0.5">Ended</div>
                    </div>
                    <div class="rounded-xl bg-blue-500/10 border border-blue-500/20 p-3">
                        <div class="text-2xl font-black text-blue-400">{{ number_format($streamStats['viewers']) }}</div>
                        <div class="text-[10px] text-zinc-400 mt-0.5">Total Viewers</div>
                    </div>
                </div>

                <div class="overflow-x-auto" style="max-height:400px; overflow-y:auto;">
                    <table class="w-full text-xs">
                        <thead><tr class="text-zinc-500 border-b border-white/5">
                            <th class="text-left py-2 pr-3 font-semibold">Stream</th>
                            <th class="text-left py-2 pr-3 font-semibold">Status</th>
                            <th class="text-left py-2 pr-3 font-semibold">Exhibition</th>
                            <th class="text-left py-2 pr-3 font-semibold">County</th>
                            <th class="text-left py-2 pr-3 font-semibold">Viewers</th>
                            <th class="text-left py-2 pr-3 font-semibold">Actions</th>
                        </tr></thead>
                        <tbody class="divide-y divide-white/5">
                        @forelse($streams as $stream)
                        <tr class="hover:bg-white/5 transition">
                            <td class="py-2.5 pr-3">
                                <div class="text-white font-semibold">{{ $stream->name }}</div>
                                @if($stream->hls_url)<div class="text-[9px] text-zinc-500 mt-0.5 font-mono">HLS ready</div>@endif
                            </td>
                            <td class="py-2.5 pr-3">
                                @if($stream->isLive())
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-red-500/20 text-red-400 flex items-center gap-1.5 w-fit">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>LIVE
                                </span>
                                @elseif($stream->status === 'idle')
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-400">Scheduled</span>
                                @else
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-white/10 text-zinc-400">Ended</span>
                                @endif
                            </td>
                            <td class="py-2.5 pr-3 text-zinc-400">{{ $stream->exhibition?->name ?? '—' }}</td>
                            <td class="py-2.5 pr-3 text-zinc-400">{{ $stream->county?->name ?? '—' }}</td>
                            <td class="py-2.5 pr-3 text-white font-bold">{{ number_format($stream->viewer_count) }}</td>
                            <td class="py-2.5">
                                <div class="flex gap-2">
                                    <a href="{{ route('streams.show', $stream) }}" class="text-[10px] font-bold px-2 py-1 rounded-lg bg-white/10 text-zinc-300 hover:bg-white/20">View</a>
                                    @if($stream->isLive())
                                    <form method="POST" action="{{ route('streams.end', $stream) }}" onsubmit="return confirm('End this stream?')">
                                        @csrf
                                        <button class="text-[10px] font-bold px-2 py-1 rounded-lg bg-red-500/20 text-red-400 hover:bg-red-500/30">End</button>
                                    </form>
                                    @elseif($stream->status === 'idle')
                                    <form method="POST" action="{{ route('streams.go-live', $stream) }}">
                                        @csrf
                                        <button class="text-[10px] font-bold px-2 py-1 rounded-lg bg-emerald-500/20 text-emerald-400 hover:bg-emerald-500/30">Go Live</button>
                                    </form>
                                    @endif
                                    <form method="POST" action="{{ route('streams.destroy', $stream) }}" onsubmit="return confirm('Delete permanently?')">
                                        @csrf @method('DELETE')
                                        <button class="text-[10px] font-bold px-2 py-1 rounded-lg bg-white/10 text-zinc-500 hover:bg-red-500/20 hover:text-red-400">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="py-8 text-center text-zinc-500 text-sm">No streams yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{--  USERS  --}}
            @if($tab === 'users')
            <div class="glass-card rounded-2xl p-6" style="max-height:600px; overflow-y:auto;">
                <h3 class="font-bold text-white mb-5">Platform Users</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead><tr class="text-zinc-500 border-b border-white/5">
                            <th class="text-left py-3 pr-4 font-semibold">Name</th><th class="text-left py-3 pr-4 font-semibold">Email</th><th class="text-left py-3 pr-4 font-semibold">Type</th><th class="text-left py-3 font-semibold">Roles</th>
                        </tr></thead>
                        <tbody class="divide-y divide-white/5">
                        @foreach($users as $u)
                        <tr class="hover:bg-white/5 transition">
                            <td class="py-2.5 pr-4 font-semibold text-white">{{ $u->name }}</td>
                            <td class="py-2.5 pr-4 text-zinc-400 text-xs">{{ $u->email }}</td>
                            <td class="py-2.5 pr-4 text-zinc-500 text-xs">{{ $u->account_type }}</td>
                            <td class="py-2.5 text-zinc-500 text-xs">{{ $u->roles->pluck('name')->implode(', ') ?: '—' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{--  EXHIBITOR REQUESTS (custom/premium setups)  --}}
            @if($tab === 'exh_requests')
            <div class="glass-card rounded-2xl p-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold text-white text-lg">📋 Exhibitor Setup Requests</h3>
                    <span class="text-xs text-zinc-400">{{ count($exhRequests) }} pending</span>
                </div>
                <table class="w-full text-xs">
                    <thead>
                        <tr class="text-zinc-500 uppercase tracking-wider text-[10px] font-bold">
                            <th class="px-3 py-2 text-left">Exhibitor</th><th class="px-3 py-2 text-left">County</th>
                            <th class="px-3 py-2 text-left">Business Type</th><th class="px-3 py-2 text-center">Complexity</th>
                            <th class="px-3 py-2 text-left">Tagline</th><th class="px-3 py-2 text-left">Requested</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($exhRequests as $u)
                        @php $m = $u->metadata ?? []; @endphp
                        <tr class="border-t border-white/5 hover:bg-white/5 transition-all">
                            <td class="px-3 py-2 font-semibold text-white">{{ $u->name }}</td>
                            <td class="px-3 py-2 text-zinc-400">{{ $u->county?->name ?? '—' }}</td>
                            <td class="px-3 py-2 text-zinc-400">{{ $m['business_type_label'] ?? $m['business_type'] ?? '—' }}</td>
                            <td class="px-3 py-2 text-center">
                                @if($m['complexity'] === 'premium')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-rose-500/15 text-rose-400 font-bold">🎬 Premium Shoot</span>
                                @elseif($m['complexity'] === 'custom')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-sky-500/15 text-sky-400 font-bold">🎨 Custom Admin</span>
                                @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-400 font-bold">🚀 Simple</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-zinc-500 max-w-[200px] truncate">{{ $m['tagline'] ?? '' }}</td>
                            <td class="px-3 py-2 text-zinc-500">{{ substr($u->created_at ?? '', 0, 10) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-zinc-500">No pending exhibitor requests. All fully onboarded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <p class="text-[10px] text-zinc-500 mt-3">Premium and Custom requests are also sent to the N8n automation workflow for team notification.</p>
            </div>
            @endif

            {{--  HERO MEDIA  --}}
            @if($tab === 'hero_media')
            <div class="glass-card rounded-2xl p-6 max-w-2xl">
                <h3 class="font-bold text-white text-sm mb-4">Landing Page Hero Video</h3>
                @if($heroAsset)
                <div class="aspect-video bg-black rounded-xl overflow-hidden mb-4">
                    <video autoplay muted loop playsinline class="w-full h-full object-cover">
                        <source src="{{ $heroAsset->mp4Url() ?? $heroAsset->url() }}" type="video/mp4">
                    </video>
                </div>
                <div class="text-xs text-zinc-400 mb-4">Current: {{ $heroAsset->original_name }} ({{ number_format($heroAsset->size_bytes / 1024 / 1024, 1) }} MB)</div>
                <form method="POST" action="{{ route('kicc.admin.hero.delete') }}" class="inline" onsubmit="return confirm('Remove hero video?')">
                    @csrf
                    <button class="btn-danger text-xs">Delete Video</button>
                </form>
                @else
                <div class="aspect-video bg-white/5 rounded-xl flex items-center justify-center mb-4">
                    <div class="text-center text-zinc-500"><svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg><div class="text-sm font-semibold">No hero video</div></div>
                </div>
                @endif
                <form method="POST" action="{{ route('kicc.admin.hero.upload') }}" enctype="multipart/form-data" class="flex gap-3 mt-4">
                    @csrf
                    <input type="file" name="video" accept="video/mp4,video/webm" required class="flex-1">
                    <button class="btn-primary">Upload</button>
                </form>
            </div>
            @endif

            {{--  PACKAGES (Exhibitor Subscriptions)  --}}
            @if($tab === 'packages')
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="font-bold text-white text-lg">KICC Exhibitor Packages</h3>
                        <p class="text-zinc-500 text-sm">Subscription tiers shown on every county page & marketplace</p>
                    </div>
                    <a href="{{ route('packages.index') }}" target="_blank" class="text-xs font-bold px-3 py-1.5 rounded-lg bg-rose-500/10 text-rose-400 hover:bg-rose-500/20 transition-all">View Public Packages →</a>
                </div>
                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    @foreach($plans as $p)
                    <div class="glass-card rounded-2xl p-5 {{ $p->slug === 'exhibitor-pro' ? 'border-2 border-[#FFCD05]/40' : '' }}">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] font-bold uppercase tracking-widest text-zinc-400">{{ $p->name }}</span>
                            @if($p->slug === 'exhibitor-pro')
                            <span class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-[#FFCD05]/20 text-[#FFCD05]">POPULAR</span>
@endif
</div>
                        <div class="text-2xl font-black text-white">KES {{ number_format($p->price) }}<span class="text-xs text-zinc-500 font-medium">/mo</span></div>
                        <div class="text-[10px] text-zinc-500 mt-1">{{ $p->max_booths >= 999 ? 'Unlimited' : $p->max_booths }} booth{{ $p->max_booths > 1 ? 's' : '' }} · {{ $p->is_active ? 'Active' : 'Hidden' }}</div>
                        <div class="mt-3 text-[10px] text-zinc-400 leading-relaxed">{{ $p->description ?? 'Exhibitor subscription tier' }}</div>
                    </div>
                    @endforeach
                </div>
                <div class="glass-card rounded-2xl p-6">
                    <h4 class="font-bold text-white text-sm mb-4">Manage All Tiers ({{ $allPlans->count() }})</h4>
                    <div class="space-y-3">
                        @foreach($allPlans as $p)
                        <form method="POST" action="{{ route('kicc.admin.plan.update', $p->id) }}" class="flex flex-wrap items-end gap-3 p-4 rounded-xl bg-white/5 border border-white/10">
                            @csrf
                            <div class="flex-1 min-w-[120px]">
                                <label class="text-[9px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Name</label>
                                <input name="name" value="{{ $p->name }}" class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white">
                            </div>
                            <div class="w-28">
                                <label class="text-[9px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Price KES</label>
                                <input name="price" type="number" min="0" value="{{ $p->price }}" class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white">
                            </div>
                            <div class="w-24">
                                <label class="text-[9px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Booths</label>
                                <input name="max_booths" type="number" min="1" value="{{ $p->max_booths }}" class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white">
                            </div>
                            <div class="flex items-center gap-2 pb-2">
                                <input type="checkbox" name="is_active" value="1" {{ $p->is_active ? 'checked' : '' }} class="rounded border-zinc-600 bg-zinc-800" id="plan-active-{{ $p->id }}">
                                <label for="plan-active-{{ $p->id }}" class="text-[10px] text-zinc-400">Active</label>
                            </div>
                            <div class="flex-1 min-w-[200px]">
                                <label class="text-[9px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Description</label>
                                <input name="description" value="{{ $p->description }}" class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white">
                            </div>
                            <button class="px-4 py-2 rounded-lg bg-rose-500/20 text-rose-400 hover:bg-rose-500/30 text-xs font-bold">Save</button>
                        </form>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            @if($tab === 'analytics')
            @include('dashboards.analytics-tab', [
                'analytics' => $analytics ?? [],
                'analyticsUsers' => $analyticsUsers ?? [],
                'analyticsOrders' => $analyticsOrders ?? [],
                'analyticsEscrows' => $analyticsEscrows ?? [],
                'analyticsSearch' => $analyticsSearch ?? [],
                'analyticsGrowth' => $analyticsGrowth ?? [],
            ])
            @endif

            @include('partials.integration-tab')
            @include('partials.pipeline-creator-tab')
            @include('partials.earnings-tab')
            @include('partials.licence-queue-tab')
            @include('partials.pipeline-settings-tab')
            @include('partials.search-analytics-tab')
            @include('partials.cache-tab')

            @if($tab === 'pool')
            <div class="p-6 space-y-6">
                <h2 class="text-xl font-bold text-white">Selling Pool</h2>
                <p class="text-zinc-400 text-sm">All proceeds from every sector, county, and pipeline accrue into one pool. Monthly payouts flow back weighted by contribution &times; quality.</p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                    <div class="bg-zinc-800/80 rounded-xl p-5 border border-zinc-700">
                        <div class="text-zinc-400 text-xs uppercase tracking-wide">Pool Balance</div>
                        <div class="text-2xl font-bold text-white mt-1">KES {{ number_format($poolBalance ?? 0) }}</div>
                        @if($pool ?? false)
                        <div class="text-xs text-zinc-500 mt-1">Holdback {{ $pool->holdback_pct ?? 10 }}% &middot; Equalisation {{ $pool->equalisation_pct ?? 0.5 }}%</div>
                        @endif
                    </div>
                    <div class="bg-zinc-800/80 rounded-xl p-5 border border-zinc-700">
                        <div class="text-zinc-400 text-xs uppercase tracking-wide">Pending Distributions</div>
                        <div class="text-2xl font-bold text-white mt-1">{{ $poolPendingDistributions?->count() ?? 0 }}</div>
                        <div class="text-xs text-zinc-500 mt-1">Awaiting Mother Admin approval</div>
                    </div>
                    <div class="bg-zinc-800/80 rounded-xl p-5 border border-zinc-700">
                        <div class="text-zinc-400 text-xs uppercase tracking-wide">Current Period</div>
                        <div class="text-2xl font-bold text-white mt-1">{{ now()->format('Y-m') }}</div>
                        <div class="text-xs text-zinc-500 mt-1">Contributions tracked daily</div>
                    </div>
                </div>

                <div class="bg-zinc-800/80 rounded-xl border border-zinc-700 p-5 mt-6">
                    <h3 class="text-lg font-semibold text-white mb-3">Top Contributors This Period</h3>
                    @if(($poolPeriodContributions ?? collect())->isNotEmpty())
                    <table class="w-full text-sm text-zinc-300">
                        <thead><tr class="text-left border-b border-zinc-700"><th class="pb-2">County</th><th class="pb-2">Contribution</th></tr></thead>
                        <tbody>
                        @foreach($poolPeriodContributions as $contrib)
                        @php $county = \App\Models\County::find($contrib->county_id); @endphp
                        <tr class="border-b border-zinc-700/50"><td class="py-2">{{ $county?->name ?? 'County #'.$contrib->county_id }}</td><td class="py-2">KES {{ number_format($contrib->total) }}</td></tr>
                        @endforeach
                        </tbody>
                    </table>
                    @else
                    <p class="text-zinc-500 text-sm">No contributions recorded yet. Contributions appear when escrows are released.</p>
                    @endif
                </div>

                <div class="bg-zinc-800/80 rounded-xl border border-zinc-700 p-5 mt-6">
                    <h3 class="text-lg font-semibold text-white mb-3">Pending Distributions</h3>
                    @if(($poolPendingDistributions ?? collect())->isNotEmpty())
                    <table class="w-full text-sm text-zinc-300">
                        <thead><tr class="text-left border-b border-zinc-700"><th class="pb-2">Period</th><th class="pb-2">Beneficiary</th><th class="pb-2">Amount</th><th class="pb-2">Status</th></tr></thead>
                        <tbody>
                        @foreach($poolPendingDistributions as $d)
                        <tr class="border-b border-zinc-700/50">
                            <td class="py-2">{{ $d->period_id }}</td>
                            <td class="py-2">Entity #{{ $d->beneficiary_id }}</td>
                            <td class="py-2">KES {{ number_format($d->amount) }}</td>
                            <td class="py-2"><span class="px-2 py-0.5 rounded text-xs bg-yellow-900/50 text-yellow-300">{{ $d->status }}</span></td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                    @else
                    <p class="text-zinc-500 text-sm">No pending distributions. Run <code class="text-zinc-400 bg-zinc-700 px-1 rounded">php artisan pool:distribute</code> after contributions accrue.</p>
                    @endif
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                    <div class="bg-zinc-800/80 rounded-xl border border-zinc-700 p-5">
                        <h3 class="text-sm font-semibold text-white mb-2">Distribution Formula</h3>
                        <p class="text-xs text-zinc-400 leading-relaxed">weight = contribution&alpha; &times; quality&beta; (default &alpha;=0.7, &beta;=0.3). Holdback {{ $pool?->holdback_pct ?? 10 }}% funds dispute reversals. Equalisation {{ $pool?->equalisation_pct ?? 0.5 }}% earmarked for foundational/anchor counties per Art. 204(1) precedent.</p>
                    </div>
                    <div class="bg-zinc-800/80 rounded-xl border border-zinc-700 p-5">
                        <h3 class="text-sm font-semibold text-white mb-2">Quality Scoring</h3>
                        <p class="text-xs text-zinc-400 leading-relaxed">Computed from: delivery rate (25%), dispute record (20%), trust grade (15%), data completeness (15%), review standing (15%), media presence (10%). Run <code class="text-zinc-400 bg-zinc-700 px-1 rounded">php artisan pool:quality-scores</code> to update.</p>
                    </div>
                </div>
            </div>
            @endif

            @if($tab === 'pipelines')
            <div class="p-6 space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-white">Pipeline Management</h2>
                        <p class="text-zinc-400 text-sm mt-1">{{ $pipelineTotal }} revenue pipelines — 50 parents + 152 subsectors · 87 per the catalog</p>
                    </div>
                    <div class="text-right">
                        <a href="{{ route('kicc.admin', ['tab' => 'pipelines', 'pipeline_q' => '', 'pipeline_sector' => '']) }}" class="text-xs text-[#046bd2] hover:underline">Reset</a>
                    </div>
                </div>

                {{-- Status breakdown --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3">
                    <div class="bg-zinc-800/80 rounded-xl p-3 border border-zinc-700">
                        <div class="text-2xl font-bold text-white">{{ $pipelineTotal }}</div>
                        <div class="text-xs text-zinc-400">Total</div>
                    </div>
                    @foreach($pipelineStatusBreakdown as $ps)
                    <div class="bg-zinc-800/80 rounded-xl p-3 border border-zinc-700">
                        <div class="text-2xl font-bold text-{{ $ps->status === 'built' ? 'emerald' : ($ps->status === 'licence_gated' ? 'amber' : ($ps->status === 'blocked' ? 'rose' : 'sky')) }}-400">{{ $ps->c }}</div>
                        <div class="text-xs text-zinc-400">{{ ucwords(str_replace('_', ' ', $ps->status)) }}</div>
                    </div>
                    @endforeach
                </div>

                {{-- Search + sector filter --}}
                <div class="flex flex-col sm:flex-row gap-3">
                    <form method="GET" action="{{ route('kicc.admin') }}" class="flex flex-1 gap-2">
                        <input type="hidden" name="tab" value="pipelines">
                        <input type="text" name="pipeline_q" value="{{ request('pipeline_q') }}" placeholder="Search pipeline code or name…"
                            class="flex-1 bg-zinc-900/60 border border-zinc-700 rounded-lg px-3 py-2 text-sm text-white placeholder:text-zinc-500 outline-none focus:border-kicc-gold">
                        <select name="pipeline_sector" class="bg-zinc-900/60 border border-zinc-700 rounded-lg px-3 py-2 text-sm text-white outline-none">
                            <option value="">All sectors</option>
                            @foreach($pipelineSectors as $sector)
                            <option value="{{ $sector->sector }}" @if(request('pipeline_sector') == $sector->sector) selected @endif>{{ ucfirst($sector->sector) }} ({{ $sector->c }})</option>
                            @endforeach
                        </select>
                        <button type="submit" class="bg-kicc-gold text-gray-900 text-sm font-bold px-4 py-2 rounded-lg hover:bg-yellow-300 transition">Filter</button>
                    </form>
                </div>

                {{-- Pipeline table --}}
                <div class="bg-zinc-800/40 rounded-xl border border-zinc-700/50 overflow-hidden">
                    <table class="w-full text-sm">
                        <thead><tr class="bg-zinc-700/40 text-left text-zinc-300 text-xs uppercase tracking-wide">
                            <th class="px-4 py-3">Code</th>
                            <th class="px-4 py-3">Pipeline</th>
                            <th class="px-4 py-3">Sector</th>
                            <th class="px-4 py-3">Phase</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Economics</th>
                            <th class="px-4 py-3">Regulators</th>
                        </tr></thead>
                        <tbody>
                        @forelse($pipelines as $p)
                        <tr class="border-t border-zinc-700/40 text-zinc-300 hover:bg-zinc-700/20">
                            <td class="px-4 py-2.5 font-mono text-[#046bd2] font-semibold">{{ $p->code }}</td>
                            <td class="px-4 py-2.5 text-white">{{ \Illuminate\Support\Str::title(str_replace('-', ' ', $p->slug)) }}</td>
                            <td class="px-4 py-2.5">{{ ucfirst($p->sector) }}</td>
                            <td class="px-4 py-2.5">P{{ $p->phase }}</td>
                            <td class="px-4 py-2.5">
                                @php
                                $badgeColor = $p->status === 'built' ? 'bg-emerald-900/50 text-emerald-300'
                                    : ($p->status === 'licence_gated' ? 'bg-amber-900/50 text-amber-300'
                                    : ($p->status === 'blocked' ? 'bg-rose-900/50 text-rose-300'
                                    : ($p->status === 'partial' ? 'bg-sky-900/50 text-sky-300'
                                    : 'bg-zinc-700/50 text-zinc-300')));
                                @endphp
                                <span class="px-2 py-0.5 rounded text-xs {{ $badgeColor }}">{{ ucwords(str_replace('_',' ',$p->status)) }}</span>
                            </td>
                            <td class="px-4 py-2.5 text-xs text-zinc-400">
                                @php
                                $eco = json_decode($p->economics ?? '{}', true);
                                $rate = $eco['take_rate_pct'] ?? ($eco['take_rate'] ?? '');
                                if (is_array($rate)) $rate = min($rate) . '-' . max($rate) . '%';
                                echo $rate ?: ($eco['model'] ?? '—');
                                @endphp
                            </td>
                            <td class="px-4 py-2.5 text-xs text-zinc-400">
                                @php
                                $regs = json_decode($p->regulators ?? '[]', true);
                                echo implode(', ', array_map('ucfirst', $regs ?: [])) ?: '—';
                                @endphp
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-zinc-500">No pipelines match your filter.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                @if($pipelines->hasPages())
                <div class="flex justify-center mt-4">
                    {{ $pipelines->links() }}
                </div>
                @endif
            </div>
            @endif
        </main>
    </div>
</div>
@endsection