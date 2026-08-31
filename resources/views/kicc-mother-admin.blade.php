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
            @if($errors->any())<div class="mb-4 px-5 py-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">{{ $errors->first() }}</div>@endif

            {{-- ═══════════ OVERVIEW ═══════════ --}}
            @if($tab === 'overview')
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <x-nexora-kpi title="Counties" :value="number_format($stats['counties'])" growth="47 active trade boards" color="indigo" :sparkline="[44,45,46,46,47,47,47,47,47,47,47,47]" />
                <x-nexora-kpi :title="'Users'" :value="number_format($stats['users'])" :growth="$stats['exhibitors'] . ' exhibitors'" color="emerald" />
                <x-nexora-kpi :title="'Products'" :value="number_format($stats['products'])" :growth="$stats['orders'] . ' orders'" color="amber" />
                <x-nexora-kpi title="Escrow" :value="'KES ' . number_format($stats['escrowTotal'])" :growth="'Held: ' . number_format($stats['escrowHeld'])" color="red" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                <a href="{{ route('kicc.admin', ['tab' => 'counties']) }}" class="kpi-card flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-600 flex items-center justify-center text-white font-bold text-sm">47</div>
                    <div><div class="font-bold text-white">County Admins</div><div class="text-xs text-zinc-400">All 47 counties — content, sectors, images, 4D video, ads</div></div>
                </a>
                <a href="{{ route('kicc.admin', ['tab' => 'national']) }}" class="kpi-card flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-sky-500 to-blue-600 flex items-center justify-center text-white font-bold text-sm">N</div>
                    <div><div class="font-bold text-white">National Government</div><div class="text-xs text-zinc-400">Ministries & agencies management</div></div>
                </a>
                <a href="{{ route('kicc.admin', ['tab' => 'exhibitors']) }}" class="kpi-card flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white font-bold text-sm">E</div>
                    <div><div class="font-bold text-white">Exhibitors</div><div class="text-xs text-zinc-400">{{ $stats['exhibitors'] }} registered exhibitors</div></div>
                </a>
            </div>

            <div class="glass-card rounded-2xl p-5">
                <h3 class="font-bold text-white text-sm mb-3">🛠 Artisan Console</h3>
                <form method="POST" action="{{ route('kicc.admin.artisan') }}" class="flex gap-2">
                    @csrf
                    <select name="command" class="flex-1">
                        <option value="">Select a command…</option>
                        @foreach(['cache:clear','config:clear','route:clear','view:clear','optimize:clear','migrate','schedule:run','queue:restart'] as $cmd)
                        <option value="{{ $cmd }}">{{ $cmd }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn-primary">Run</button>
                </form>
            </div>
            @endif

            {{-- ═══════════ PORTALS ═══════════ --}}
            @if($tab === 'portals')
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="kpi-card border-2 border-rose-500/40"><div class="font-bold text-white">🏛 KICC Mother Admin</div><div class="text-xs text-zinc-400 mt-1">You are here</div></div>
                <a href="{{ route('kicc.admin', ['tab' => 'counties']) }}" class="kpi-card"><div class="font-bold text-white">🗺 County Portals</div><div class="text-xs text-zinc-400 mt-1">47 counties — trade boards</div></a>
                <a href="{{ route('national.admin') }}" class="kpi-card"><div class="font-bold text-white">🏛 National Government</div><div class="text-xs text-zinc-400 mt-1">Ministries & agencies</div></a>
                <a href="{{ route('exhibitor.admin') }}" class="kpi-card"><div class="font-bold text-white">👤 Private Exhibitors</div><div class="text-xs text-zinc-400 mt-1">Individual & SME portals</div></a>
            </div>
            @endif

            {{-- ═══════════ COUNTIES (all 47) ═══════════ --}}
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

            {{-- ═══════════ INSTITUTIONS ═══════════ --}}
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

            {{-- ═══════════ NATIONAL ═══════════ --}}
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

            {{-- ═══════════ EXHIBITORS ═══════════ --}}
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

            {{-- ═══════════ PROVIDERS ═══════════ --}}
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
                            <button class="text-[10px] font-bold px-3 py-1.5 rounded-lg bg-emerald-500/20 text-emerald-400 hover:bg-emerald-500/30">CERTIFY ✓</button>
                        </form>
                    </div>
                    @empty
                    <p class="text-zinc-500 text-sm py-6 text-center">Queue clear — nothing awaiting certification.</p>
                    @endforelse
                </div>
            </div>
            @endif

            {{-- ═══════════ ORDERS ═══════════ --}}
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

            {{-- ═══════════ ESCROW ═══════════ --}}
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

            {{-- ═══════════ EXPERIENCES ═══════════ --}}
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
                    <span>🚗 Road</span>
                    <span>🚆 Train</span>
                    <span>✈️ Air/Rail</span>
                    <span>| Prices include rating × season × distance multipliers</span>
                </div>
            </div>
            @endif

            {{-- ═══════════ USERS ═══════════ --}}
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

            {{-- ═══════════ HERO MEDIA ═══════════ --}}
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

            {{-- ═══════════ PACKAGES (Exhibitor Subscriptions) ═══════════ --}}
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
            @include('dashboards.analytics-tab', ['analytics' => $analytics ?? []])
            @endif
        </main>
    </div>
</div>
@endsection