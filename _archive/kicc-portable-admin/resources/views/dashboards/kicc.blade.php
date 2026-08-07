@extends('layouts.app')

@section('title', 'KICC Platform Control Center')

@php
use App\Models\County;
use App\Models\Sector;
use App\Models\Ministry;
use App\Models\Agency;
use App\Models\User;
use App\Models\SectorEntity;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\Order;
use App\Models\Booking;
use App\Models\Exhibition;
use App\Models\Venue;

$totalCounties = County::count();
$totalSectors = Sector::count();
$totalMinistries = Ministry::count();
$totalAgencies = Agency::count();
$totalUsers = User::count();
$totalProducts = Product::count();
$totalOrders = Order::count();
$totalBookings = Booking::count();
$totalExhibitions = Exhibition::count();
$totalVenues = Venue::count();
$totalEntities = SectorEntity::count();
$recentCounties = County::orderBy('name')->take(6)->get();
$recentSectors = Sector::orderBy('name')->take(12)->get();
$recentUsers = User::orderBy('created_at', 'desc')->take(10)->get();
$allMinistries = Ministry::withCount('agencies')->orderBy('name')->get();
$allAgencies = Agency::with('ministry')->orderBy('name')->take(20)->get();
@endphp

@section('content')
<div class="p-6 lg:p-8" x-data="{ tab: 'overview' }">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-black text-white">KICC Platform Control Center</h1>
            <p class="text-white/30 text-sm mt-1">Platform Administrator · All Access</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs text-white/30">🔐</span>
            <span class="text-xs text-white/50 font-semibold">{{ auth()->user()->name ?? 'Admin' }}</span>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <div class="flex gap-1 mb-8 overflow-x-auto scrollbar-hide flex-wrap">
        @foreach([
            ['id'=>'overview','label'=>'Overview','icon'=>'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
            ['id'=>'counties','label'=>'Counties','icon'=>'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z'],
            ['id'=>'sectors','label'=>'Sectors','icon'=>'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
            ['id'=>'national','label'=>'National','icon'=>'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
            ['id'=>'users','label'=>'Users','icon'=>'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
            ['id'=>'sync','label'=>'Sync','icon'=>'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'],
        ] as $t)
        <button @click="tab='{{ $t['id'] }}'"
            class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0"
            :class="tab==='{{ $t['id'] }}' ? 'bg-[#901C1E]/15 text-[#FFCD05] border border-[#901C1E]/30' : 'text-white/40 border border-transparent hover:text-white hover:bg-white/5'">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $t['icon'] }}"/></svg>
            {{ $t['label'] }}
        </button>
        @endforeach
    </div>

    {{-- ═══ OVERVIEW TAB ═══ --}}
    <div x-show="tab==='overview'" x-cloak>
        {{-- KPI Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <x-admin.kpi-card label="Total Counties" :value="$totalCounties" accent="#FFCD05"
                icon='<svg class="w-5 h-5" fill="none" stroke="#FFCD05" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>' />
            <x-admin.kpi-card label="Active Sectors" :value="$totalSectors" accent="#0B1E57"
                icon='<svg class="w-5 h-5" fill="none" stroke="#0B1E57" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>' />
            <x-admin.kpi-card label="Ministries" :value="$totalMinistries" accent="#2D6A4F"
                icon='<svg class="w-5 h-5" fill="none" stroke="#2D6A4F" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>' />
            <x-admin.kpi-card label="Platform Users" :value="$totalUsers" accent="#901C1E"
                icon='<svg class="w-5 h-5" fill="none" stroke="#901C1E" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>' />
        </div>

        {{-- Admin Hierarchy + Quick Stats --}}
        <div class="grid lg:grid-cols-2 gap-6 mb-8">
            <x-admin.hierarchy-card :items="[
                ['label'=>'KICC Platform Admin','count'=>1,'sublabel'=>'super admin','color'=>'#FFCD05','icon'=>'🔐','tag'=>'Full Access'],
                ['label'=>'National Gov. Entities','count'=>$totalMinistries+$totalAgencies,'sublabel'=>'ministries & agencies','color'=>'#2D6A4F','icon'=>'🏛️','tag'=>'Oversight'],
                ['label'=>'County Administrations','count'=>$totalCounties,'sublabel'=>'counties','color'=>'#0B1E57','icon'=>'📍','tag'=>'Local'],
                ['label'=>'Registered Users','count'=>$totalUsers,'sublabel'=>'platform users','color'=>'#901C1E','icon'=>'👥','tag'=>'Accounts'],
            ]" />

            <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                <h3 class="font-bold text-white text-sm mb-5">Platform Summary</h3>
                <div class="grid grid-cols-2 gap-4">
                    @foreach([
                        ['Products',$totalProducts,'📦'],
                        ['Orders',$totalOrders,'🛒'],
                        ['Bookings',$totalBookings,'🎫'],
                        ['Exhibitions',$totalExhibitions,'📅'],
                        ['Venues',$totalVenues,'🏢'],
                        ['Sector Entities',$totalEntities,'📋'],
                    ] as $s)
                    <div class="bg-[#141B2E] rounded-xl p-4">
                        <div class="flex items-center gap-2 mb-2"><span>{{ $s[2] }}</span><span class="text-white/30 text-xs">{{ $s[0] }}</span></div>
                        <div class="text-xl font-black text-white">{{ $s[1] }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Recent Counties --}}
        <div class="bg-[#0D1220] border border-white/8 rounded-2xl overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-white/8">
                <h3 class="font-bold text-white text-sm">All Counties</h3>
                <span class="text-xs text-white/30">{{ $totalCounties }} total</span>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-px bg-white/5">
                @foreach($recentCounties as $c)
                <div class="bg-[#0D1220] p-4 hover:bg-[#141B2E] transition-colors">
                    <div class="text-xs font-bold text-white/30 uppercase mb-1">{{ $c->code ?? '—' }}</div>
                    <div class="text-sm font-semibold text-white">{{ $c->name }}</div>
                    <div class="text-xs text-white/30 mt-1">{{ $c->region ?? '—' }}</div>
                    <div class="text-xs text-white/20 mt-1">{{ $c->population_2024 ? number_format($c->population_2024).' pop' : '—' }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ═══ COUNTIES TAB ═══ --}}
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
                    @foreach(County::orderBy('name')->get() as $c)
                    <tr class="hover:bg-white/2 transition-colors">
                        <td class="px-4 py-3"><span class="font-semibold text-white text-sm">{{ $c->name }}</span></td>
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

    {{-- ═══ SECTORS TAB ═══ --}}
    <div x-show="tab==='sectors'" x-cloak>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($recentSectors as $s)
            <div class="bg-[#0D1220] border border-white/8 hover:border-[#FFCD05]/30 rounded-2xl p-5 transition-all">
                <div class="flex items-start justify-between mb-3">
                    <span class="text-3xl">{{ $s->icon ?? '📊' }}</span>
                    <span class="text-xs text-white/20">{{ $s->code ?? '—' }}</span>
                </div>
                <div class="font-bold text-white text-sm mb-1">{{ $s->name }}</div>
                <div class="text-white/30 text-xs">{{ $s->description ? Str::limit($s->description, 80) : 'No description' }}</div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ═══ NATIONAL TAB ═══ --}}
    <div x-show="tab==='national'" x-cloak>
        <div class="grid lg:grid-cols-2 gap-6 mb-6">
            <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                <h3 class="font-bold text-white text-sm mb-4">Ministries ({{ $totalMinistries }})</h3>
                <div class="space-y-3">
                    @foreach($allMinistries as $m)
                    <div class="flex items-center gap-3 py-2 border-b border-white/5 last:border-0">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold shrink-0 text-white" style="background:{{ $m->color ?? '#0B1E57' }}">{{ $m->code[0] ?? 'M' }}</div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-semibold text-white truncate">{{ $m->name }}</div>
                            <div class="text-xs text-white/30">{{ $m->agencies_count ?? 0 }} agencies · {{ $m->code }}</div>
                        </div>
                        <x-admin.badge :status="$m->is_active ? 'active' : 'inactive'" />
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                <h3 class="font-bold text-white text-sm mb-4">Agencies ({{ $totalAgencies }})</h3>
                <div class="space-y-3">
                    @foreach($allAgencies as $a)
                    <div class="flex items-center gap-3 py-2 border-b border-white/5 last:border-0">
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-semibold text-white truncate">{{ $a->name }}</div>
                            <div class="text-xs text-white/30">{{ $a->ministry?->name ?? '—' }} · {{ $a->code }}</div>
                        </div>
                        <x-admin.badge :status="$a->is_active ? 'active' : 'inactive'" />
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ USERS TAB ═══ --}}
    <div x-show="tab==='users'" x-cloak>
        <div class="bg-[#0D1220] border border-white/8 rounded-2xl overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-white/8">
                <h3 class="font-bold text-white text-sm">User Accounts</h3>
                <span class="text-xs text-white/30">{{ $totalUsers }} users</span>
            </div>
            <table class="w-full">
                <thead><tr class="bg-[#141B2E]">
                    <th class="text-[10px] font-bold text-white/30 uppercase tracking-wider px-4 py-3 text-left">Name</th>
                    <th class="text-[10px] font-bold text-white/30 uppercase tracking-wider px-4 py-3 text-left">Email</th>
                    <th class="text-[10px] font-bold text-white/30 uppercase tracking-wider px-4 py-3 text-left">Type</th>
                    <th class="text-[10px] font-bold text-white/30 uppercase tracking-wider px-4 py-3 text-left">Status</th>
                    <th class="text-[10px] font-bold text-white/30 uppercase tracking-wider px-4 py-3 text-right">Created</th>
                </tr></thead>
                <tbody class="divide-y divide-white/5">
                    @foreach($recentUsers as $u)
                    <tr class="hover:bg-white/2 transition-colors">
                        <td class="px-4 py-3"><span class="font-semibold text-white text-sm">{{ $u->name }}</span></td>
                        <td class="px-4 py-3 text-sm text-white/50">{{ $u->email }}</td>
                        <td class="px-4 py-3 text-sm text-white/50">{{ $u->account_type ?? '—' }}</td>
                        <td class="px-4 py-3"><x-admin.badge :status="$u->status ?? 'active'" /></td>
                        <td class="px-4 py-3 text-sm text-white/30 text-right">{{ $u->created_at ? $u->created_at->format('d M Y') : '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══ SYNC TAB ═══ --}}
    <div x-show="tab==='sync'" x-cloak>
        <div class="grid lg:grid-cols-2 gap-6">
            <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                <div class="flex items-center gap-3 mb-6">
                    <span class="text-2xl">📥</span>
                    <div><h3 class="font-bold text-white">Pull from TiDB Cloud</h3><p class="text-xs text-white/30">Download latest data from production</p></div>
                </div>
                <form method="POST" action="{{ route('kicc.sync') }}">
                    @csrf
                    <input type="hidden" name="direction" value="from">
                    <input type="hidden" name="tables" value="all">
                    <button class="w-full bg-[#0B1E57] text-white px-6 py-3 rounded-xl text-sm font-bold hover:bg-[#0D2A7A] transition-all">Pull All Data</button>
                </form>
            </div>
            <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                <div class="flex items-center gap-3 mb-6">
                    <span class="text-2xl">📤</span>
                    <div><h3 class="font-bold text-white">Push to TiDB Cloud</h3><p class="text-xs text-white/30">Upload local changes to production</p></div>
                </div>
                <form method="POST" action="{{ route('kicc.sync') }}">
                    @csrf
                    <input type="hidden" name="direction" value="to">
                    <input type="hidden" name="tables" value="all">
                    <button class="w-full bg-[#901C1E] text-white px-6 py-3 rounded-xl text-sm font-bold hover:bg-[#7b1618] transition-all">Push Local Changes</button>
                </form>
            </div>
        </div>
        @if(session('sync_output'))
        <div class="mt-6 bg-[#0D1220] border border-white/8 rounded-2xl p-6">
            <h4 class="font-bold text-white text-xs mb-3">Sync Output</h4>
            <pre class="text-xs text-white/50 font-mono whitespace-pre-wrap">{{ session('sync_output') }}</pre>
        </div>
        @endif
    </div>
</div>
@endsection