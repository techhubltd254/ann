@extends('layouts.nexora')

@section('title', $institution->name . ' — Nexora Control')

@php $accent = '#6366F1'; $accentGrad = 'from-indigo-500 to-violet-600'; @endphp

@section('content')
<div class="flex h-screen overflow-hidden" x-data="{
    drawer: null, sidebarOpen: true, search: '', tab: '{{ $tab ?? 'overview' }}',
    kpiRange: 'monthly',
    setTab(t) { this.tab = t; history.replaceState(null,'','?tab='+t); },
    openDrawer(id) { this.drawer = id; },
    closeDrawer() { this.drawer = null; }
}">

    {{-- ═══════ SIDEBAR ═══════ --}}
    <aside class="glass-nav flex flex-col w-64 shrink-0 z-30 transition-all duration-300" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-64'">
        <div class="flex items-center gap-3 h-16 px-5 border-b border-white/5 shrink-0">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center font-black text-white text-sm">N</div>
            <div>
                <div class="text-white font-bold text-sm leading-tight">Nexora</div>
                <div class="text-indigo-400 text-[9px] font-bold tracking-[0.2em] uppercase">Control Center</div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto px-3 py-4 space-y-6 scrollbar-hide">
            @php
            $navGroups = [
                'Overview' => [
                    ['tab' => 'overview', 'label' => 'Dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                    ['tab' => 'products', 'label' => 'Products', 'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
                    ['tab' => 'transactions', 'label' => 'Transactions', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2'],
                    ['tab' => 'analytics', 'label' => 'Analytics', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                ],
                'Content' => [
                    ['tab' => 'videos', 'label' => 'Videos', 'icon' => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z'],
                    ['tab' => 'production', 'label' => 'Production Chain', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                    ['tab' => 'sectors', 'label' => 'Sector Mapping', 'icon' => 'M4 6h16M4 10h16M4 14h16M4 18h16'],
                ],
                'Customers' => [
                    ['tab' => 'customers', 'label' => 'Customers', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857'],
                ],
                'Settings' => [
                    ['tab' => 'profile', 'label' => 'Profile', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                    ['tab' => 'team', 'label' => 'Team', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197'],
                ],
            ];
            @endphp

            @foreach($navGroups as $group => $items)
            <div class="space-y-1">
                <p class="px-3 text-[10px] font-semibold text-zinc-500 uppercase tracking-widest">{{ $group }}</p>
                @foreach($items as $item)
                <a href="{{ route('institution.admin', [$institution->slug, 'tab' => $item['tab']]) }}"
                   @click.prevent="setTab('{{ $item['tab'] }}')"
                   class="sidebar-link"
                   :class="tab === '{{ $item['tab'] }}' ? 'sidebar-link-active' : 'sidebar-link-inactive'">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/></svg>
                    <span>{{ $item['label'] }}</span>
                </a>
                @endforeach
            </div>
            @endforeach
        </div>

        {{-- Bottom upgrade card --}}
        <div class="p-3 border-t border-white/5">
            <div class="glass rounded-xl p-4 bg-gradient-to-br from-indigo-500/5 to-violet-600/5 border border-indigo-500/10">
                <div class="text-xs font-semibold text-white mb-1">Upgrade to Pro</div>
                <div class="text-[10px] text-zinc-400 mb-3">Unlock unlimited products &amp; priority support</div>
                <button class="w-full py-2 rounded-lg text-xs font-bold text-white bg-gradient-to-r from-indigo-500 to-violet-600 hover:from-indigo-400 hover:to-violet-500 transition">Upgrade →</button>
            </div>
        </div>
    </aside>

    {{-- ═══════ MAIN VIEWPORT ═══════ --}}
    <div class="flex-1 flex flex-col overflow-hidden">
        {{-- Header --}}
        <header class="glass-header h-16 px-6 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-4">
                <button @click="sidebarOpen = !sidebarOpen" class="text-zinc-400 hover:text-white p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="flex items-center gap-2 text-xs text-zinc-500">
                    <span>Institution</span>
                    <span>/</span>
                    <span class="text-indigo-400 font-medium">{{ $institution->name }}</span>
                    <span class="text-zinc-600">·</span>
                    <span class="text-zinc-500">{{ ucfirst($tab) }}</span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                {{-- Quick action button --}}
                <a href="{{ route('institution.admin.sync', $institution->slug) }}"
                   class="inline-flex items-center gap-2 text-xs font-semibold px-4 py-2 rounded-xl bg-gradient-to-r from-indigo-500 to-violet-600 text-white hover:from-indigo-400 hover:to-violet-500 transition active:scale-95">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Sync Now
                </a>

                {{-- Notification bell --}}
                <button class="relative text-zinc-400 hover:text-white p-1.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <span class="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full bg-indigo-500 border border-[#0D0F12]"></span>
                </button>

                {{-- User avatar --}}
                <div class="flex items-center gap-2.5">
                    <div class="text-right">
                        <div class="text-xs font-medium text-zinc-200">{{ Auth::user()?->name ?? 'Admin' }}</div>
                        <div class="text-[10px] text-zinc-500">{{ $institution->name }}</div>
                    </div>
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white font-bold text-xs">{{ strtoupper(substr(Auth::user()?->name ?? 'A', 0, 2)) }}</div>
                </div>
            </div>
        </header>

        {{-- ═══════ ALERTS ═══════ --}}
        @if(session('success'))
        <div class="mx-6 mt-4 px-5 py-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm flex items-center gap-3">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('success') }}
        </div>
        @endif
        @if($errors->any())
        <div class="mx-6 mt-4 px-5 py-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm flex items-center gap-3">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ $errors->first() }}
        </div>
        @endif

        {{-- ═══════ CONTENT AREA ═══════ --}}
        <main class="flex-1 overflow-y-auto p-6 scrollbar-hide">
            <div x-show="tab === 'overview'" x-transition:enter.duration.200ms>
                @include('dashboards.institution-admin.overview')
            </div>
            <div x-show="tab === 'products'" x-cloak x-transition:enter.duration.200ms>
                @include('dashboards.institution-admin.products')
            </div>
            <div x-show="tab === 'transactions'" x-cloak x-transition:enter.duration.200ms>
                @include('dashboards.institution-admin.transactions')
            </div>
            <div x-show="tab === 'analytics'" x-cloak x-transition:enter.duration.200ms>
                @include('dashboards.institution-admin.analytics')
            </div>
            <div x-show="tab === 'videos'" x-cloak x-transition:enter.duration.200ms>
                @include('dashboards.institution-admin.videos')
            </div>
            <div x-show="tab === 'production'" x-cloak x-transition:enter.duration.200ms>
                @include('dashboards.institution-admin.production')
            </div>
            <div x-show="tab === 'sectors'" x-cloak x-transition:enter.duration.200ms>
                @include('dashboards.institution-admin.sectors')
            </div>
            <div x-show="tab === 'customers'" x-cloak x-transition:enter.duration.200ms>
                @include('dashboards.institution-admin.customers')
            </div>
            <div x-show="tab === 'profile'" x-cloak x-transition:enter.duration.200ms>
                @include('dashboards.institution-admin.profile')
            </div>
            <div x-show="tab === 'team'" x-cloak x-transition:enter.duration.200ms>
                @include('dashboards.institution-admin.team')
            </div>
        </main>
    </div>

    @stack('inline-scripts')
</div>

@php $instStats = json_encode([ 'revenue' => $revenue ?? 0, 'orders' => $totalOrders ?? 0, 'products' => $marketplaceProducts->count() ?? 0, 'videos' => $videos->count() ?? 0 ]); @endphp
@endsection