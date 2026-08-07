@extends('layouts.app')

@section('title', 'County Admin Dashboard')

@php
$user = auth()->user();
$countySlug = session('admin_county_slug');
$county = $countySlug ? \App\Models\County::where('slug', $countySlug)->first() : null;
if (!$county && $user->county_id) {
    $county = \App\Models\County::find($user->county_id);
}
if (!$county) {
    $county = \App\Models\County::first();
    if ($county) session(['admin_county_slug' => $county->slug]);
}
$sectors = $county ? $county->sectors : collect([]);
$entities = $county ? \App\Models\SectorEntity::where('county_id', $county->id)->get() : collect([]);
$products = $county ? \App\Models\CountyProduct::where('county_id', $county->id)->get() : collect([]);
$exhibitions = $county ? $county->exhibitions()->where('status', 'published')->get() : collect([]);
$attractions = $county ? \App\Models\CountyTourismAttraction::where('county_id', $county->id)->where('is_published', true)->get() : collect([]);
$hotels = $county ? \App\Models\CountyHotel::where('county_id', $county->id)->where('is_published', true)->get() : collect([]);
@endphp

@section('content')
<div class="p-6 lg:p-8" x-data="{ tab: 'overview' }">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-black text-white">{{ $county->name ?? 'County' }} Admin</h1>
            <p class="text-white/30 text-sm mt-1">{{ $user->name ?? 'Administrator' }} · County Level</p>
        </div>
    </div>

    <div class="flex gap-1 mb-8 overflow-x-auto scrollbar-hide flex-wrap">
        @foreach([
            ['id'=>'overview','label'=>'Overview','icon'=>'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
            ['id'=>'sectors','label'=>'Sectors','icon'=>'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
            ['id'=>'entities','label'=>'Entities','icon'=>'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
            ['id'=>'tourism','label'=>'Tourism','icon'=>'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z'],
        ] as $t)
        <button @click="tab='{{ $t['id'] }}'"
            class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0"
            :class="tab==='{{ $t['id'] }}' ? 'bg-[#FFCD05]/15 text-[#FFCD05] border border-[#FFCD05]/30' : 'text-white/40 border border-transparent hover:text-white hover:bg-white/5'">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $t['icon'] }}"/></svg>
            {{ $t['label'] }}
        </button>
        @endforeach
    </div>

    {{-- OVERVIEW --}}
    <div x-show="tab==='overview'" x-cloak>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <x-admin.kpi-card label="Linked Sectors" :value="$sectors->count()" accent="#FFCD05" change="Economic sectors" />
            <x-admin.kpi-card label="Total Entities" :value="$entities->count()" accent="#0B1E57" change="Registered businesses" />
            <x-admin.kpi-card label="Local Products" :value="$products->count()" accent="#2D6A4F" change="County products" />
            <x-admin.kpi-card label="Tourism Sites" :value="$attractions->count() + $hotels->count()" accent="#901C1E" change="Attractions & hotels" />
        </div>

        @if($county)
        <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6 mb-6">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 rounded-xl bg-[#FFCD05]/15 flex items-center justify-center text-xl">📍</div>
                <div>
                    <h2 class="text-lg font-black text-white">{{ $county->name }} County</h2>
                    <p class="text-white/30 text-xs">{{ $county->tagline ?? $county->description ? Str::limit($county->description, 100) : 'No description' }}</p>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4 text-center">
                @if($county->capital)<div><div class="text-xs text-white/40">Capital</div><div class="text-white font-bold">{{ $county->capital }}</div></div>@endif
                @if($county->population_2024)<div><div class="text-xs text-white/40">Population</div><div class="text-white font-bold">{{ number_format($county->population_2024) }}</div></div>@endif
                @if($county->area_km2)<div><div class="text-xs text-white/40">Area</div><div class="text-white font-bold">{{ number_format($county->area_km2) }} km²</div></div>@endif
            </div>
        </div>
        @endif

        <div class="grid lg:grid-cols-2 gap-6">
            <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                <h3 class="font-bold text-white text-sm mb-4">County Sectors</h3>
                @if($sectors->count() > 0)
                <div class="flex flex-wrap gap-2">
                    @foreach($sectors as $s)
                    <span class="px-3 py-1.5 rounded-full text-xs font-bold bg-white/5 text-white/60 border border-white/10">{{ $s->name }}</span>
                    @endforeach
                </div>
                @else
                <p class="text-white/30 text-sm">No sectors linked yet</p>
                @endif
            </div>
            <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                <h3 class="font-bold text-white text-sm mb-4">Recent Exhibitions</h3>
                @if($exhibitions->count() > 0)
                <div class="space-y-3">
                    @foreach($exhibitions->take(4) as $ex)
                    <div class="flex items-center justify-between py-2 border-b border-white/5 last:border-0">
                        <div><div class="text-sm font-semibold text-white">{{ $ex->name }}</div><div class="text-xs text-white/30">{{ $ex->venue?->name ?? 'KICC' }}</div></div>
                        <span class="text-xs text-white/30">{{ $ex->start_date ? $ex->start_date->format('d M') : '—' }}</span>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-white/30 text-sm">No upcoming exhibitions</p>
                @endif
            </div>
        </div>
    </div>

    {{-- SECTORS --}}
    <div x-show="tab==='sectors'" x-cloak>
        @if($sectors->count() > 0)
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($sectors as $s)
            <div class="bg-[#0D1220] border border-white/8 hover:border-[#FFCD05]/30 rounded-2xl p-5 transition-all">
                <div class="flex items-start justify-between mb-3">
                    <span class="text-3xl">{{ $s->icon ?? '📊' }}</span>
                </div>
                <div class="font-bold text-white text-sm">{{ $s->name }}</div>
                <div class="text-white/30 text-xs mt-1">{{ $s->description ? Str::limit($s->description, 60) : '—' }}</div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-16 text-white/30">
            <span class="text-4xl block mb-3">📂</span>
            <p class="text-sm">No sectors linked to {{ $county->name ?? 'this county' }}</p>
        </div>
        @endif
    </div>

    {{-- ENTITIES --}}
    <div x-show="tab==='entities'" x-cloak>
        @if($entities->count() > 0)
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($entities as $e)
            <div class="bg-[#0D1220] border border-white/8 hover:border-[#0B1E57]/50 rounded-2xl p-5 transition-all">
                <div class="font-bold text-white text-sm">{{ $e->name }}</div>
                <div class="text-white/40 text-xs mt-1">{{ $e->type ?? 'Entity' }}</div>
                @if($e->description)<p class="text-white/30 text-xs mt-2 line-clamp-2">{{ $e->description }}</p>@endif
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-16 text-white/30">
            <span class="text-4xl block mb-3">📋</span>
            <p class="text-sm">No entities registered yet</p>
        </div>
        @endif
    </div>

    {{-- TOURISM --}}
    <div x-show="tab==='tourism'" x-cloak>
        <div class="grid lg:grid-cols-2 gap-6">
            <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                <h3 class="font-bold text-white text-sm mb-4">Tourism Attractions ({{ $attractions->count() }})</h3>
                @if($attractions->count() > 0)
                <div class="space-y-3">
                    @foreach($attractions as $a)
                    <div class="flex items-center justify-between py-2 border-b border-white/5 last:border-0">
                        <div class="text-sm font-semibold text-white">{{ $a->name }}</div>
                        <span class="text-xs text-white/30">{{ $a->entry_fee ? 'KES '.number_format($a->entry_fee) : 'Free' }}</span>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-white/30 text-sm">No attractions listed</p>
                @endif
            </div>
            <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                <h3 class="font-bold text-white text-sm mb-4">Hotels &amp; Resorts ({{ $hotels->count() }})</h3>
                @if($hotels->count() > 0)
                <div class="space-y-3">
                    @foreach($hotels as $h)
                    <div class="flex items-center justify-between py-2 border-b border-white/5 last:border-0">
                        <div class="text-sm font-semibold text-white">{{ $h->name }}</div>
                        <span class="text-xs" style="color:#FFCD05">{{ $h->star_rating ? str_repeat('★', $h->star_rating) : '—' }}</span>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-white/30 text-sm">No hotels listed</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
