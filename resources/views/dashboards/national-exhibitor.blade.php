@extends('layouts.nexora')

@section('title', 'National Government Portal — Nexora Control')

@php $accent = '#1890D7'; @endphp

@section('content')
<div class="flex h-screen overflow-hidden" x-data="{ tab: '{{ $tab ?? 'overview' }}', drawer: null, setTab(t) { this.tab = t; history.replaceState(null,'','?tab='+t); } }">

    <aside class="glass-nav flex flex-col w-64 shrink-0 z-30 overflow-y-auto">
        <div class="flex items-center gap-3 h-16 px-5 border-b border-white/5 shrink-0">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-sky-500 to-blue-600 flex items-center justify-center font-black text-white text-sm"></div>
            <div>
                <div class="text-white font-bold text-sm leading-tight">National</div>
                <div class="text-sky-400 text-[9px] font-bold tracking-[0.2em] uppercase">Government Portal</div>
            </div>
        </div>
        <div class="flex-1 px-3 py-4 space-y-6 scrollbar-hide">
            @php $navItems = [
                'Overview' => ['tab'=>'overview','label'=>'Dashboard','icon'=>"M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"],
                'Management' => ['tab'=>'ministries','label'=>'Ministries','icon'=>"M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"],
                'Agencies' => ['tab'=>'agencies','label'=>'Agencies','icon'=>"M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857"],
                'Trade' => ['tab'=>'trade','label'=>'Trade Overview','icon'=>"M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"],
            ]; @endphp
            @foreach($navItems as $group => $item)
            <div class="space-y-1">
                <p class="px-3 text-[10px] font-semibold text-zinc-500 uppercase tracking-widest mt-4 {{ $loop->first ? '' : '' }}">{{ $loop->first ? 'Overview' : $group }}</p>
                <a href="{{ route('national.admin', ['tab' => $item['tab']]) }}"
                   @click.prevent="setTab('{{ $item['tab'] }}')"
                   class="sidebar-link"
                   :class="tab === '{{ $item['tab'] }}' ? 'sidebar-link-active' : 'sidebar-link-inactive'">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/></svg>
                    <span>{{ $item['label'] }}</span>
                </a>
            </div>
            @endforeach
            <a href="{{ route('logout') }}" onclick="event.preventDefault();document.getElementById('logout-form').submit()" class="sidebar-link sidebar-link-inactive mt-8">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                <span>Exit</span>
            </a>
            <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">@csrf</form>
        </div>
    </aside>

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="glass-header h-16 px-6 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2 text-xs text-zinc-500">
                <span>National Government</span>
                <span>/</span>
                <span class="text-sky-400 font-medium">{{ ucfirst($tab) }}</span>
            </div>
            <div class="flex items-center gap-3">
                @if(session('success'))
                <span class="text-[11px] text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-3 py-1.5 rounded-lg">{{ session('success') }}</span>
                @endif
                <div class="flex items-center gap-2.5">
                    <div class="text-xs text-zinc-200 font-medium">{{ Auth::user()?->name ?? 'National Admin' }}</div>
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-sky-500 to-blue-600 flex items-center justify-center text-white font-bold text-xs">{{ strtoupper(substr(Auth::user()?->name ?? 'N', 0, 2)) }}</div>
                </div>
            </div>
        </header>
        <main class="flex-1 overflow-y-auto p-6 scrollbar-hide">
            @if($errors->any())
            <div class="mb-4 px-5 py-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">{{ $errors->first() }}</div>
            @endif

            {{--  OVERVIEW  --}}
            <div x-show="tab === 'overview'" x-transition:enter.duration.200ms>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <x-nexora-kpi title="Ministries" :value="number_format($stats['ministries'])" color="sky" :sparkline="[2,3,4,4,5,5,6,7,7,8,9,9]" />
                    <x-nexora-kpi title="Agencies" :value="number_format($stats['agencies'])" color="indigo" :sparkline="[5,7,9,11,13,15,17,19,21,23,25,27]" />
                    <x-nexora-kpi title="Products Live" :value="number_format($stats['products'])" color="emerald" :sparkline="[10,15,25,35,45,60,75,90,110,130,145,160]" />
                    <x-nexora-kpi title="Trade Volume" :value="'KES ' . number_format($stats['tradeVolume'])" color="amber" :sparkline="[100,120,140,160,180,210,240,270,300,340,380,420]" />
                </div>
                <div class="glass-card rounded-2xl p-6">
                    <h3 class="text-sm font-bold text-white mb-3">National Exhibition Mandate</h3>
                    <p class="text-sm text-zinc-400 leading-relaxed">The National Government exhibits through its ministries and agencies. Each ministry operates an independent public website under the national pavilion, while this portal provides the consolidated view of the entire country's trade, counties and exhibitors on the KICC platform.</p>
                </div>
            </div>

            {{--  MINISTRIES  --}}
            <div x-show="tab === 'ministries'" x-cloak x-transition:enter.duration.200ms>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-white">Ministries ({{ $ministries->count() }})</h2>
                    <button @click="$el.nextElementSibling.classList.toggle('hidden')" class="btn-primary text-xs">+ Add Ministry</button>
                </div>
                <form method="POST" action="{{ route('national.admin.ministry.store') }}" x-cloak class="hidden glass-card rounded-2xl p-4 mb-4 space-y-2">@csrf
                    <div class="grid sm:grid-cols-2 gap-2"><input type="text" name="name" required placeholder="Ministry name"><input type="text" name="code" placeholder="Code"></div>
                    <input type="text" name="description" placeholder="Description">
                    <div class="grid sm:grid-cols-2 gap-2"><input type="url" name="website" placeholder="Website"><input type="email" name="contact_email" placeholder="Email"></div>
                    <button class="btn-primary">Create Ministry</button>
                </form>
                <div class="grid md:grid-cols-2 gap-4">
                    @foreach($ministries as $m)
                    <div class="glass-card rounded-2xl p-5">
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-black text-sm" style="background: {{ $m->color ?: '#1890D7' }}">{{ $m->code }}</div>
                            <div class="flex gap-2">
                                <button @click="$el.nextElementSibling.classList.toggle('hidden')" class="btn-ghost text-xs py-1 px-2"></button>
                                <a href="{{ route('national.site', $m->slug) }}" class="btn-ghost text-xs py-1 px-2">Website ↗</a>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('national.admin.ministry.update', $m->id) }}" x-cloak class="hidden space-y-2 mb-3">@csrf
                            <input type="text" name="name" value="{{ $m->name }}">
                            <input type="text" name="code" value="{{ $m->code }}">
                            <input type="text" name="description" value="{{ $m->description }}">
                            <button class="btn-primary">Save</button>
                        </form>
                        <div class="font-bold text-white text-sm mb-1">{{ $m->name }}</div>
                        <p class="text-xs text-zinc-400 leading-relaxed mb-2">{{ Str::limit($m->description, 140) }}</p>
                        <div class="text-[10px] font-bold uppercase tracking-widest text-zinc-500">{{ $m->agencies->count() }} agencies</div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{--  AGENCIES  --}}
            <div x-show="tab === 'agencies'" x-cloak x-transition:enter.duration.200ms>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-white">Agencies ({{ $agencies->count() }})</h2>
                    <button @click="$el.nextElementSibling.classList.toggle('hidden')" class="btn-primary text-xs">+ Add Agency</button>
                </div>
                <form method="POST" action="{{ route('national.admin.agency.store') }}" x-cloak class="hidden glass-card rounded-2xl p-4 mb-4 space-y-2">@csrf
                    <div class="grid sm:grid-cols-2 gap-2">
                        <select name="ministry_id" required>@foreach($ministries as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach</select>
                        <input type="text" name="name" required placeholder="Agency name">
                    </div>
                    <button class="btn-primary">Create Agency</button>
                </form>
                <div class="glass-card rounded-2xl p-5">
                    <table class="w-full text-xs">
                        <thead><tr class="text-zinc-500 border-b border-white/5">
                            <th class="text-left py-3 font-semibold">Code</th><th class="text-left py-3 font-semibold">Agency</th><th class="text-left py-3 font-semibold">Parent Ministry</th><th class="text-right py-3 font-semibold">Actions</th>
                        </tr></thead>
                        <tbody class="divide-y divide-white/5">
                        @foreach($agencies as $a)
                        <tr class="hover:bg-white/5 transition">
                            <td class="py-3"><span class="text-[10px] font-black px-2 py-1 rounded-md text-white" style="background: {{ $a->ministry?->color ?: '#1890D7' }}">{{ $a->code }}</span></td>
                            <td class="py-3 font-medium text-zinc-200">{{ $a->name }}</td>
                            <td class="py-3 text-zinc-500 text-xs">{{ $a->ministry?->name }}</td>
                            <td class="py-3 text-right">
                                <button @click="$el.nextElementSibling.classList.toggle('hidden')" class="btn-ghost text-xs py-1 px-1.5"></button>
                                <form method="POST" action="{{ route('national.admin.agency.update', $a->id) }}" x-cloak class="hidden mt-2 flex gap-2">@csrf
                                    <input type="hidden" name="ministry_id" value="{{ $a->ministry_id }}">
                                    <input type="text" name="name" value="{{ $a->name }}">
                                    <button class="btn-primary py-1">Save</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{--  TRADE  --}}
            <div x-show="tab === 'trade'" x-cloak x-transition:enter.duration.200ms>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <x-nexora-kpi title="Products (47 Counties)" :value="number_format($stats['products'])" color="emerald" />
                    <x-nexora-kpi title="Total Orders" :value="number_format($stats['orders'])" color="indigo" />
                    <x-nexora-kpi title="Exhibitors" :value="number_format($stats['exhibitors'])" color="amber" />
                    <x-nexora-kpi title="Escrow Volume" :value="'KES ' . number_format($stats['tradeVolume'])" color="sky" />
                </div>
                <div class="glass-card rounded-2xl p-6">
                    <p class="text-xs text-zinc-500">Aggregated live from all 47 county trade boards and private exhibitors on the KICC platform.</p>
                </div>
            </div>

            <div x-show="tab === 'analytics'" x-cloak x-transition:enter.duration.200ms>
                @include('dashboards.analytics-tab', ['analytics' => $analytics ?? []])
            </div>
        </main>
    </div>
</div>
@endsection