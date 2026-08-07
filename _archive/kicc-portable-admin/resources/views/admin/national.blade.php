@extends('layouts.app')

@section('title', 'National Government Admin')

@php
use App\Models\Ministry;
use App\Models\Agency;
use App\Models\County;
use App\Models\Sector;
use App\Models\SectorEntity;
use App\Models\Exhibition;
use App\Models\Venue;

$totalMinistries = Ministry::count();
$totalAgencies = Agency::count();
$totalCounties = County::count();
$totalSectors = Sector::count();
$allMinistries = Ministry::withCount('agencies')->orderBy('name')->get();
$allAgencies = Agency::with('ministry')->orderBy('name')->get();
$counties = County::orderBy('name')->get();
@endphp

@section('content')
<div class="p-6 lg:p-8" x-data="{ tab: 'overview', q: '' }">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-black text-white">National Government Admin</h1>
            <p class="text-white/30 text-sm mt-1">Manage ministries, agencies &amp; county oversight</p>
        </div>
    </div>

    <div class="flex gap-1 mb-8 overflow-x-auto scrollbar-hide flex-wrap">
        @foreach([
            ['id'=>'overview','label'=>'Overview','icon'=>'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
            ['id'=>'ministries','label'=>'Ministries','icon'=>'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
            ['id'=>'counties','label'=>'Counties','icon'=>'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z'],
        ] as $t)
        <button @click="tab='{{ $t['id'] }}'"
            class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0"
            :class="tab==='{{ $t['id'] }}' ? 'bg-[#0B1E57]/20 text-blue-400 border border-blue-500/25' : 'text-white/40 border border-transparent hover:text-white hover:bg-white/5'">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $t['icon'] }}"/></svg>
            {{ $t['label'] }}
        </button>
        @endforeach
    </div>

    {{-- OVERVIEW --}}
    <div x-show="tab==='overview'" x-cloak>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            @foreach([
                ['label'=>'Ministries','val'=>$totalMinistries,'accent'=>'#2D6A4F'],
                ['label'=>'Agencies','val'=>$totalAgencies,'accent'=>'#0B1E57'],
                ['label'=>'Counties','val'=>$totalCounties,'accent'=>'#FFCD05'],
                ['label'=>'Sectors','val'=>$totalSectors,'accent'=>'#901C1E'],
            ] as $s)
            <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                <div class="text-3xl font-black text-white">{{ $s['val'] }}</div>
                <div class="text-white/30 text-sm mt-1">{{ $s['label'] }}</div>
            </div>
            @endforeach
        </div>
        <div class="grid lg:grid-cols-2 gap-6">
            <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                <h3 class="font-bold text-white text-sm mb-4">Quick Actions</h3>
                <div class="flex flex-wrap gap-3">
                    <a href="/admin/ministries" class="px-4 py-2 text-xs font-bold rounded-xl bg-[#2D6A4F]/20 text-emerald-400 border border-emerald-500/30 hover:bg-[#2D6A4F]/30 transition-all">Manage Ministries</a>
                    <a href="/admin/agencies" class="px-4 py-2 text-xs font-bold rounded-xl bg-[#0B1E57]/20 text-blue-400 border border-blue-500/25 hover:bg-[#0B1E57]/30 transition-all">Manage Agencies</a>
                    <a href="/admin/sectors" class="px-4 py-2 text-xs font-bold rounded-xl bg-[#FFCD05]/15 text-[#FFCD05] border border-[#FFCD05]/30 hover:bg-[#FFCD05]/25 transition-all">Manage Sectors</a>
                    <a href="/admin" class="px-4 py-2 text-xs font-bold rounded-xl bg-white/5 text-white/60 border border-white/10 hover:bg-white/10 transition-all">Filament Panel</a>
                </div>
            </div>
            <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                <h3 class="font-bold text-white text-sm mb-4">National Stats</h3>
                <div class="grid grid-cols-2 gap-3">
                    @foreach([
                        ['Total Ministries',$totalMinistries],
                        ['Total Agencies',$totalAgencies],
                        ['Total Counties',$totalCounties],
                        ['Economic Sectors',$totalSectors],
                    ] as $s)
                    <div class="bg-[#141B2E] rounded-xl p-4">
                        <div class="text-lg font-black text-white">{{ $s[1] }}</div>
                        <div class="text-white/30 text-[10px] mt-1">{{ $s[0] }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- MINISTRIES --}}
    <div x-show="tab==='ministries'" x-cloak>
        <div class="relative mb-6">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 text-white/30" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            <input x-model="q" placeholder="Search ministries…" class="w-full pl-10 pr-4 h-11 rounded-xl bg-[#141B2E] border border-white/10 text-white/80 text-sm outline-none focus:ring-1 focus:ring-[#FFCD05] placeholder:text-white/25 transition-all">
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($allMinistries as $m)
            <div x-show="q==='' || '{{ strtolower($m->name) }}'.includes(q.toLowerCase())" class="bg-[#0D1220] border border-white/8 hover:border-[#2D6A4F]/50 rounded-2xl p-5 transition-all">
                <div class="flex items-start justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold text-sm" style="background:{{ $m->color ?? '#2D6A4F' }}">{{ $m->code[0] ?? 'M' }}</div>
                    <x-admin.badge :status="$m->is_active ? 'active' : 'inactive'" />
                </div>
                <h3 class="font-bold text-white text-sm">{{ $m->name }}</h3>
                <div class="text-white/40 text-xs mt-1">{{ $m->code }} · {{ $m->agencies_count ?? 0 }} agencies</div>
                @if($m->description)<p class="text-white/30 text-xs mt-2 line-clamp-2">{{ $m->description }}</p>@endif
            </div>
            @endforeach
        </div>
    </div>

    {{-- COUNTIES --}}
    <div x-show="tab==='counties'" x-cloak>
        <div class="bg-[#0D1220] border border-white/8 rounded-2xl overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-white/8">
                <h3 class="font-bold text-white text-sm">County Directory</h3>
                <span class="text-xs text-white/30">{{ $totalCounties }} counties</span>
            </div>
            <table class="w-full">
                <thead><tr class="bg-[#141B2E]">
                    <th class="text-[10px] font-bold text-white/30 uppercase tracking-wider px-4 py-3 text-left">County</th>
                    <th class="text-[10px] font-bold text-white/30 uppercase tracking-wider px-4 py-3 text-left">Capital</th>
                    <th class="text-[10px] font-bold text-white/30 uppercase tracking-wider px-4 py-3 text-left">Region</th>
                    <th class="text-[10px] font-bold text-white/30 uppercase tracking-wider px-4 py-3 text-right">Population</th>
                    <th class="text-[10px] font-bold text-white/30 uppercase tracking-wider px-4 py-3 text-right">Area</th>
                </tr></thead>
                <tbody class="divide-y divide-white/5">
                    @foreach($counties as $c)
                    <tr class="hover:bg-white/2 transition-colors">
                        <td class="px-4 py-3 font-semibold text-white text-sm">{{ $c->name }}</td>
                        <td class="px-4 py-3 text-sm text-white/50">{{ $c->capital ?? '—' }}</td>
                        <td class="px-4 py-3"><span class="text-[10px] font-bold px-2.5 py-1 rounded-full border bg-blue-500/10 text-blue-400 border-blue-500/20">{{ $c->region ?? '—' }}</span></td>
                        <td class="px-4 py-3 text-sm text-white/50 text-right">{{ $c->population_2024 ? number_format($c->population_2024) : '—' }}</td>
                        <td class="px-4 py-3 text-sm text-white/50 text-right">{{ $c->area_km2 ? number_format($c->area_km2).' km²' : '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection