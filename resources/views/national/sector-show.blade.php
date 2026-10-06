@extends('layouts.app')

@section('title', $sector->name . ' — National Economic Sectors — KICC')
@section('description', "{$sector->name} — {$total} registered entities across Kenya's counties on the KICC Digital Economy Platform.")

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-8">
        <a href="{{ route('national.sectors') }}" class="text-sm text-gray-400 hover:text-kicc-gold transition-colors mb-4 inline-block">
            ← All National Sectors
        </a>
        <div class="flex items-center gap-4 mt-2">
            @if($sector->emoji)
            <span class="text-4xl">{{ $sector->emoji }}</span>
            @endif
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ $sector->name }}</h1>
                <p class="text-gray-500 mt-1">{{ $total }} registered {{ Str::plural('entity', $total) }} across Kenya</p>
            </div>
        </div>
        @if($sector->description)
        <p class="mt-4 text-gray-600 max-w-3xl">{{ $sector->description }}</p>
        @endif
    </div>

    @if($entities->isEmpty())
    <div class="bg-gray-50 border border-gray-200 rounded-2xl p-12 text-center">
        <div class="text-4xl mb-3">📋</div>
        <h3 class="text-lg font-semibold text-gray-700">No entities registered yet</h3>
        <p class="text-gray-400 mt-1">Entities for this sector are being onboarded from county institutions.</p>
    </div>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($entities as $e)
        <div class="bg-white border border-gray-200 rounded-2xl p-5 hover:border-kicc-gold/30 transition-all">
            <div class="flex items-start gap-3">
                <div class="flex-1 min-w-0">
                    <h3 class="font-semibold text-gray-900 text-sm truncate">{{ $e->name }}</h3>
                    @if($e->description)
                    <p class="text-gray-500 text-xs mt-1 line-clamp-2">{{ $e->description }}</p>
                    @endif
                    @if($e->county)
                    <div class="flex items-center gap-1.5 mt-2">
                        <svg class="w-3.5 h-3.5 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <a href="{{ route('counties.show', $e->county->slug) }}" class="text-xs text-gray-400 hover:text-kicc-gold truncate">
                            {{ $e->county->name }}
                        </a>
                    </div>
                    @endif
                    @if($e->contact_info)
                    @php $ci = is_array($e->contact_info) ? $e->contact_info : json_decode($e->contact_info, true); @endphp
                    @if(!empty($ci['phone'] ?? null))
                    <div class="text-xs text-gray-400 mt-1">📞 {{ $ci['phone'] }}</div>
                    @endif
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @if($hasMore || $page > 1)
    <div class="flex justify-center gap-3 mt-8">
        @if($page > 1)
        <a href="{{ route('national.sector.show', [$sector->slug, 'page' => $page-1]) }}"
           class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:border-kicc-gold/50 transition-all">
            ← Previous
        </a>
        @endif
        @if($hasMore)
        <a href="{{ route('national.sector.show', [$sector->slug, 'page' => $page+1]) }}"
           class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:border-kicc-gold/50 transition-all">
            Next →
        </a>
        @endif
    </div>
    @endif
    @endif

    {{-- Other sectors sidebar --}}
    @if($allSectors->count() > 1)
    <div class="mt-16 pt-8 border-t border-gray-100">
        <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">All National Sectors</h3>
        <div class="flex flex-wrap gap-2">
            @foreach($allSectors as $as)
            <a href="{{ route('national.sector.show', $as->slug) }}"
               class="px-3 py-1.5 text-xs rounded-full border {{ $as->id === $sector->id ? 'bg-kicc-gold/10 border-kicc-gold/40 text-kicc-gold font-semibold' : 'border-gray-200 text-gray-500 hover:border-kicc-gold/30 hover:text-gray-700' }} transition-all">
                {{ $as->emoji ?? '' }} {{ $as->name }}
            </a>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection