@extends('layouts.nexora')

@section('title', $institution->name . ' — Nexora Control')

@php $accent = '#6366F1'; @endphp

@section('content')
<div class="flex h-screen overflow-hidden" x-data="{ tab: '{{ $tab ?? 'overview' }}', kpiRange: 'monthly', setTab(t) { this.tab = t; } }">

    {{--  SIDEBAR  --}}
    <aside class="glass-nav flex flex-col w-64 shrink-0 z-30 overflow-y-auto">
        <div class="flex items-center gap-3 h-16 px-5 border-b border-white/5 shrink-0">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center font-black text-white text-sm shadow-lg shadow-indigo-500/25">
                {{ strtoupper(substr($institution->name, 0, 2)) }}
            </div>
            <div>
                <div class="text-white font-bold text-sm leading-tight truncate max-w-[140px]">{{ $institution->name }}</div>
                <div class="text-indigo-400 text-[9px] font-bold tracking-[0.15em] uppercase">Institution Admin</div>
            </div>
        </div>

        <div class="flex-1 px-3 py-4 space-y-5 scrollbar-hide">
            @php $groups = [
                'Overview' => [
                    ['tab' => 'overview', 'label' => 'Dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                    ['tab' => 'analytics', 'label' => 'Analytics', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                    ['tab' => 'transactions', 'label' => 'Transactions', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                ],
                'Commerce' => [
                    ['tab' => 'products', 'label' => 'Products', 'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
                    ['tab' => 'customers', 'label' => 'Customers', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                ],
                'Content' => [
                    ['tab' => 'videos', 'label' => 'Videos', 'icon' => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z'],
                    ['tab' => 'production', 'label' => 'Production Chain', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                    ['tab' => 'sectors', 'label' => 'Sector Mapping', 'icon' => 'M4 6h16M4 10h16M4 14h16M4 18h16'],
                ],
                'Settings' => [
                    ['tab' => 'profile', 'label' => 'Profile', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                    ['tab' => 'team', 'label' => 'Team', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197'],
                ],
            ]; @endphp

            @foreach($groups as $groupName => $items)
            <div class="space-y-0.5">
                <p class="px-3 text-[9px] font-semibold text-zinc-500 uppercase tracking-[0.15em] mb-1.5">{{ $groupName }}</p>
                @foreach($items as $item)
                <a href="{{ route('institution.admin', [$institution->slug, 'tab' => $item['tab']]) }}"
                   class="sidebar-link text-xs {{ $tab === $item['tab'] ? 'sidebar-link-active' : 'sidebar-link-inactive' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/></svg>
                    <span>{{ $item['label'] }}</span>
                </a>
                @endforeach
            </div>
            @endforeach

            {{-- Sync button --}}
            <div class="pt-3">
                <a href="{{ route('institution.admin.sync', $institution->slug) }}" class="sidebar-link text-xs bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 hover:bg-indigo-500/20 mb-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>Sync Now</span>
                </a>
                <div class="px-3 text-[10px] text-zinc-600">{{ $institution->synced_at ? 'Last sync: '.$institution->synced_at->diffForHumans() : 'Never synced' }}</div>
            </div>

            {{-- Bottom links --}}
            <div class="pt-2 space-y-0.5 border-t border-white/5">
                <a href="{{ route('counties.show', $institution->county?->slug) }}" class="sidebar-link sidebar-link-inactive text-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    <span>View Public Page</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="sidebar-link sidebar-link-inactive w-full text-xs"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg><span>Logout</span></button></form>
            </div>
        </div>
    </aside>

    {{--  MAIN VIEWPORT  --}}
    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="glass-header h-16 px-6 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2 text-xs text-zinc-500">
                <span>{{ $institution->name }}</span>
                <span>/</span>
                <span class="text-indigo-400 font-medium">{{ ucfirst($tab) }}</span>
            </div>
            <div class="flex items-center gap-3">
                @if(session('success'))
                <span class="text-[10px] text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-2.5 py-1 rounded-lg">{{ session('success') }}</span>
                @endif
                <a href="{{ route('institution.admin.sync', $institution->slug) }}" class="btn-primary text-xs h-8 px-3 py-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Sync
                </a>
                <div class="flex items-center gap-2.5">
                    <div class="text-xs text-zinc-200 font-medium">{{ Auth::user()?->name ?? 'Admin' }}</div>
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white font-bold text-xs shadow-lg shadow-indigo-500/20">{{ strtoupper(substr(Auth::user()?->name ?? 'A', 0, 1)) }}</div>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-6 scrollbar-hide">
            @if($errors->any())
            <div class="mb-4 px-5 py-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">{{ $errors->first() }}</div>
            @endif

            {{--  OVERVIEW  --}}
            @if($tab === 'overview')
            @include('dashboards.institution-admin.overview')

            @elseif($tab === 'products')
            @include('dashboards.institution-admin.products')

            @elseif($tab === 'transactions')
            @include('dashboards.institution-admin.transactions')

            @elseif($tab === 'analytics')
            @include('dashboards.analytics-tab', ['analytics' => $analytics ?? []])

            @elseif($tab === 'customers')
            @include('dashboards.institution-admin.customers')

            @elseif($tab === 'videos')
            @include('dashboards.institution-admin.videos')

            @elseif($tab === 'production')
            @include('dashboards.institution-admin.production')

            @elseif($tab === 'sectors')
            @include('dashboards.institution-admin.sectors')

            @elseif($tab === 'profile')
            @include('dashboards.institution-admin.profile')

            @elseif($tab === 'team')
            @include('dashboards.institution-admin.team')
            @endif
        </main>
    </div>
</div>
@endsection