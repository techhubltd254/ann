@extends('layouts.app')

@section('title', $info[$sector]['title'] . ' — ' . $county->name . ' County')
@section('description', $info[$sector]['desc'])

@php
$sectorColors = [
    'tourism' => ['bg' => 'from-amber-500 to-orange-600', 'tag' => 'bg-amber-100', 'text' => 'text-amber-700', 'hover' => 'hover:border-amber-300'],
    'hotels' => ['bg' => 'from-rose-500 to-pink-600', 'tag' => 'bg-rose-100', 'text' => 'text-rose-700', 'hover' => 'hover:border-rose-300'],
    'products' => ['bg' => 'from-violet-500 to-purple-600', 'tag' => 'bg-violet-100', 'text' => 'text-violet-700', 'hover' => 'hover:border-violet-300'],
    'institutions' => ['bg' => 'from-blue-500 to-indigo-600', 'tag' => 'bg-blue-100', 'text' => 'text-blue-700', 'hover' => 'hover:border-blue-300'],
    'farms' => ['bg' => 'from-emerald-500 to-green-600', 'tag' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'hover' => 'hover:border-emerald-300'],
    'transport' => ['bg' => 'from-cyan-500 to-teal-600', 'tag' => 'bg-cyan-100', 'text' => 'text-cyan-700', 'hover' => 'hover:border-cyan-300'],
    'health' => ['bg' => 'from-red-500 to-rose-600', 'tag' => 'bg-red-100', 'text' => 'text-red-700', 'hover' => 'hover:border-red-300'],
    'culture' => ['bg' => 'from-orange-500 to-amber-600', 'tag' => 'bg-orange-100', 'text' => 'text-orange-700', 'hover' => 'hover:border-orange-300'],
];
$colors = $sectorColors[$sector] ?? ['bg' => 'from-gray-500 to-gray-600', 'tag' => 'bg-gray-100', 'text' => 'text-gray-700', 'hover' => 'hover:border-gray-300'];
$img = asset('storage/counties/' . $county->slug . '/' . $sector . '.jpeg');
@endphp

@section('content')
<div class="relative h-[50vh] min-h-[400px] flex items-center overflow-hidden">
    <div class="absolute inset-0">
        <img src="{{ $img }}" alt="{{ $info[$sector]['title'] }}"
             class="w-full h-full object-cover"
             onerror="this.parentElement.style.background='linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%)'">
        <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/50 to-transparent"></div>
    </div>
    <div class="relative w-full max-w-7xl mx-auto px-6 lg:px-8 py-16">
        <a href="{{ route('counties.show', $county->slug) }}"
           class="inline-flex items-center gap-2 text-white/70 hover:text-white mb-6 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            <span class="text-sm font-medium">Back to {{ $county->name }} County</span>
        </a>
        <div class="flex items-start gap-6 max-w-3xl">
            <div class="text-6xl drop-shadow-lg">{{ $info[$sector]['icon'] }}</div>
            <div>
                <h1 class="text-4xl md:text-5xl font-bold text-white mb-2 leading-tight">{{ $info[$sector]['title'] }}</h1>
                <p class="text-gray-300 text-lg">{{ $info[$sector]['desc'] }}</p>
                <div class="mt-4 flex flex-wrap gap-3 items-center">
                    <span class="text-sm bg-white/10 backdrop-blur-sm text-white px-4 py-1.5 rounded-full border border-white/10">{{ $items->total() }} listings</span>
                    <span class="text-sm bg-white/10 backdrop-blur-sm text-white px-4 py-1.5 rounded-full border border-white/10">{{ $county->name }} County</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-6 lg:px-8 py-12">
    @if($items->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($items as $item)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-xl transition-all duration-300 {{ $colors['hover'] }} group">
            <div class="h-48 bg-gradient-to-br {{ $colors['bg'] }} flex items-center justify-center overflow-hidden relative">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="relative text-6xl group-hover:scale-110 transition-transform duration-500 drop-shadow-lg">{{ $info[$sector]['icon'] }}</div>
            </div>
            <div class="p-6">
                <h3 class="font-bold text-gray-900 text-lg mb-2">{{ $item->name }}</h3>
                @if($item->category ?? false)
                <span class="inline-block text-xs font-medium {{ $colors['tag'] }} {{ $colors['text'] }} px-2.5 py-1 rounded-full mb-3">{{ $item->category }}</span>
                @endif
                @if($item->description)
                <p class="text-sm text-gray-500 mb-4 line-clamp-2">{{ $item->description }}</p>
                @endif
                <div class="space-y-2 text-sm text-gray-500 border-t border-gray-100 pt-4">
                    @if($item->location ?? false)
                    <p class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ $item->location }}
                    </p>
                    @endif
                    @if(($item->phone ?? false))
                    <p class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        {{ $item->phone }}
                    </p>
                    @endif
                    @if(($item->email ?? false))
                    <p class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        {{ $item->email }}
                    </p>
                    @endif
                    @if(($item->star_rating ?? false))
                    <p class="text-yellow-500 text-sm">{{ str_repeat('⭐', $item->star_rating) }}</p>
                    @endif
                    @if(($item->entry_fee ?? false))
                    <p class="font-medium text-gray-700 flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Entry: {{ $item->entry_fee }}
                    </p>
                    @endif
                    @if(($item->price_range_min ?? false) && ($item->price_range_max ?? false))
                    <p class="font-semibold text-gray-700 flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        KSh {{ number_format($item->price_range_min) }} – {{ number_format($item->price_range_max) }}
                    </p>
                    @endif
                    @if(($item->size_acres ?? false))
                    <p class="flex items-center gap-2">{{ number_format($item->size_acres) }} acres</p>
                    @endif
                    @if(($item->student_count ?? false))
                    <p class="flex items-center gap-2">{{ number_format($item->student_count) }} students</p>
                    @endif
                    @if(($item->services ?? false))
                    <p><span class="font-medium text-gray-600">Services:</span> <span class="text-xs">{{ $item->services }}</span></p>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
    <div class="mt-10">
        {{ $items->links() }}
    </div>
    @else
    <div class="text-center py-20">
        <div class="text-7xl mb-6 opacity-30">{{ $info[$sector]['icon'] }}</div>
        <h3 class="text-2xl font-bold text-gray-600 mb-2">No listings yet</h3>
        <p class="text-gray-400 mb-6">Data for this sector in {{ $county->name }} is being populated.</p>
        <a href="{{ route('counties.show', $county->slug) }}"
           class="inline-flex items-center gap-2 text-amber-600 hover:text-amber-700 font-semibold transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to {{ $county->name }}
        </a>
    </div>
    @endif
</div>
@endSection
