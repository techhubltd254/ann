@extends('layouts.nexora')

@section('title', 'National Government Portal — Nexora Control')

@php $accent = '#0B0B0B'; @endphp

@section('content')
<div class="flex h-screen overflow-hidden" x-data="{ tab: '{{ $tab ?? 'overview' }}', drawer: null, setTab(t) { this.tab = t; history.replaceState(null,'','?tab='+t); } }">



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

             @if(isset($errors) && $errors->any())
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
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-black text-sm" style="background: {{ $m->color ?: '#0B0B0B' }}">{{ $m->code }}</div>
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
                            <td class="py-3"><span class="text-[10px] font-black px-2 py-1 rounded-md text-white" style="background: {{ $a->ministry?->color ?: '#0B0B0B' }}">{{ $a->code }}</span></td>
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