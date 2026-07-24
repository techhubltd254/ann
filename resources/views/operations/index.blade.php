@extends('layouts.app')

@section('title', 'Platform Operations — KICC')

@section('content')
<div class="max-w-7xl mx-auto px-6 lg:px-8 py-10">
    <div class="flex items-center gap-3 mb-8">
        <span class="h-px w-8 bg-amber-500"></span>
        <span class="text-amber-600 text-xs font-semibold uppercase tracking-[0.2em]">Platform Operations</span>
    </div>
    <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight mb-4">Operations & Management</h1>
    <p class="text-gray-500 max-w-2xl mb-10">Advertising campaigns, shipping logistics, and content management for the platform.</p>

    <div class="grid lg:grid-cols-3 gap-8">
        {{-- Advertising --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="w-12 h-12 bg-amber-50 rounded-2xl flex items-center justify-center mb-4">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
            </div>
            <h2 class="text-lg font-bold text-gray-900 mb-1">Advertising</h2>
            <p class="text-sm text-gray-500 mb-4">Campaigns, creatives, and impression tracking</p>
            <div class="space-y-2 text-sm">
                @forelse($campaigns ?? [] as $c)
                <div class="flex justify-between items-center py-2 border-b border-gray-50">
                    <span class="font-medium text-gray-700">{{ $c->name }}</span>
                    <span class="text-xs {{ $c->is_active ? 'text-emerald-600' : 'text-gray-400' }}">{{ $c->is_active ? 'Active' : 'Paused' }}</span>
                </div>
                @empty
                <p class="text-gray-400 text-sm">No campaigns yet</p>
                @endforelse
            </div>
        </div>

        {{-- Logistics --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="w-12 h-12 bg-amber-50 rounded-2xl flex items-center justify-center mb-4">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
            </div>
            <h2 class="text-lg font-bold text-gray-900 mb-1">Logistics</h2>
            <p class="text-sm text-gray-500 mb-4">Courier partners, shipping zones and rates</p>
            <div class="space-y-3">
                @forelse($couriers ?? [] as $c)
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full {{ $c->is_active ? 'bg-emerald-400' : 'bg-gray-300' }}"></span>
                    <span class="text-sm text-gray-700">{{ $c->name }}</span>
                </div>
                @empty
                <p class="text-gray-400 text-sm">No couriers set up</p>
                @endforelse
                <div class="pt-2 border-t border-gray-50">
                    <span class="text-xs text-gray-500">Shipping zones: {{ count($zones ?? []) }} configured</span>
                </div>
            </div>
        </div>

        {{-- SEO --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="w-12 h-12 bg-amber-50 rounded-2xl flex items-center justify-center mb-4">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
            </div>
            <h2 class="text-lg font-bold text-gray-900 mb-1">SEO & Content</h2>
            <p class="text-sm text-gray-500 mb-4">Metadata, content pages, and sitemap</p>
            <div class="space-y-2 text-sm">
                @forelse($pages ?? [] as $p)
                <div class="flex justify-between py-2 border-b border-gray-50">
                    <span class="text-gray-700">{{ $p->title }}</span>
                    <span class="text-xs text-gray-400">{{ $p->is_published ? 'Published' : 'Draft' }}</span>
                </div>
                @empty
                <p class="text-gray-400 text-sm">No content pages yet</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endSection