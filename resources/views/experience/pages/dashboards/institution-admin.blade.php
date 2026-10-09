@extends('layouts.nexora')

@section('title', $institution->name . ' — Nexora Control')

@php $accent = '#0B0B0B'; @endphp

@section('content')
<div class="flex min-h-screen experience-admin-shell" x-data="{ tab: '{{ $tab ?? 'overview' }}', kpiRange: 'monthly', setTab(t) { this.tab = t; } }">

    {{--  SIDEBAR  --}}


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
                <form method="POST" action="{{ route('institution.admin.sync', $institution->slug) }}" style="display:inline" onsubmit="return confirm('Confirm this action?')">@csrf<button type="submit" class="btn-primary text-xs h-8 px-3 py-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Sync
                </button></form>
                <div class="flex items-center gap-2.5">
                    <div class="text-xs text-zinc-200 font-medium">{{ Auth::user()?->name ?? 'Admin' }}</div>
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white font-bold text-xs shadow-lg shadow-indigo-500/20">{{ strtoupper(substr(Auth::user()?->name ?? 'A', 0, 1)) }}</div>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-6 scrollbar-hide">
            @isset($errors) @if($errors->any())
            <div class="mb-4 px-5 py-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">{{ $errors->first() }}</div>
            @endif @endisset

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

            @elseif($tab === 'tile-media')
            @include('dashboards.institution-admin.tile-media')

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
@push('scripts')
<x-r2-large-upload
    owner-type="App\\Models\\CountyInstitution"
    :owner-id="$institution->id"
    r2-path="institutions/{{ $institution->slug }}/video/hero/hero.mp4"
/>
@endpush

@endsection