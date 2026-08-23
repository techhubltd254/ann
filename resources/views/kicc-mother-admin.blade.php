@extends('layouts.nexora')

@section('title', 'KICC Mother Admin — Nexora Control')

@php $accent = '#901C1E'; @endphp

@section('content')
<div class="flex h-screen overflow-hidden" x-data="{ tab: '{{ $tab ?? 'overview' }}', drawer: null, setTab(t) { this.tab = t; history.replaceState(null,'','?tab='+t); } }">

    <aside class="glass-nav flex flex-col w-64 shrink-0 z-30 overflow-y-auto">
        <div class="flex items-center gap-3 h-16 px-5 border-b border-white/5 shrink-0">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-red-500 to-rose-600 flex items-center justify-center font-black text-white text-sm">K</div>
            <div>
                <div class="text-white font-bold text-sm leading-tight">Nexora</div>
                <div class="text-rose-400 text-[9px] font-bold tracking-[0.2em] uppercase">Mother Admin</div>
            </div>
        </div>
        <div class="flex-1 px-3 py-4 space-y-6 scrollbar-hide">
            @foreach($navItems as $item)
            <div class="space-y-1">
                <a href="{{ route('kicc.admin', ['tab' => $item['tab']]) }}"
                   @click.prevent="setTab('{{ $item['tab'] }}')"
                   class="sidebar-link"
                   :class="tab === '{{ $item['tab'] }}' ? 'sidebar-link-active' : 'sidebar-link-inactive'">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/></svg>
                    <span>{{ $item['label'] }}</span>
                </a>
            </div>
            @endforeach
            <div class="px-3 pt-4 border-t border-white/5 space-y-1">
                <a href="{{ route('county.admin') }}" class="sidebar-link sidebar-link-inactive"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg><span>County Portals</span></a>
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
                <span class="text-rose-400 font-medium" x-text="tab.charAt(0).toUpperCase()+tab.slice(1)"></span>
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

            @if($tab === 'overview')
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <x-nexora-kpi title="Counties" value="47" growth="47 active trade boards" color="indigo" :sparkline="[44,45,46,46,47,47,47,47,47,47,47,47]" />
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

            @if($tab === 'portals')
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="kpi-card border-2 border-rose-500/40"><div class="font-bold text-white">🏛 KICC Mother Admin</div><div class="text-xs text-zinc-400 mt-1">You are here</div></div>
                <a href="{{ route('county.admin') }}" class="kpi-card"><div class="font-bold text-white">🗺 County Portals</div><div class="text-xs text-zinc-400 mt-1">47 counties — trade boards</div></a>
                <a href="{{ route('national.admin') }}" class="kpi-card"><div class="font-bold text-white">🏛 National Government</div><div class="text-xs text-zinc-400 mt-1">Ministries & agencies</div></a>
                <a href="{{ route('exhibitor.admin') }}" class="kpi-card"><div class="font-bold text-white">👤 Private Exhibitors</div><div class="text-xs text-zinc-400 mt-1">Individual & SME portals</div></a>
            </div>
            @endif

            @if($tab === 'counties')
            <div class="flex gap-4 flex-wrap mb-6">@foreach([['Baringo','30','KES 145K'],['Bomet','36','KES 89K'],['Bungoma','47','KES 210K'],['Busia','39','KES 67K'],['Elgeyo-Marakwet','28','KES 112K'],['Embu','14','KES 95K'],['Garissa','7','KES 34K'],['Homa Bay','42','KES 78K']] as $c)<div class="kpi-card flex-1 min-w-[160px]"><div class="text-sm font-bold text-white">{{ $c[0] }}</div><div class="text-[10px] text-zinc-500">Board #{{ $c[1] }} · {{ $c[2] }}</div></div>@endforeach</div>
            @endif

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

            @if(in_array($tab, ['users','orders','exhibitors','providers','escrow','national']))
            <div class="glass-card rounded-2xl p-6 text-center">
                <div class="text-zinc-400 text-sm">{{ ucfirst($tab) }} management panel — coming soon in this view.</div>
            </div>
            @endif

            @if($tab === 'analytics')
            @include('dashboards.analytics-tab', ['analytics' => $analytics ?? []])
            @endif
        </main>
    </div>
</div>
@endsection