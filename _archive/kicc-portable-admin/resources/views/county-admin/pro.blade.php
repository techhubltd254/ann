@extends('layouts.app')

@section('title', $county->name . ' County Admin')

@section('content')
<div class="p-6 lg:p-8" x-data="{ tab: 'overview' }">
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-[#FFCD05]/15 flex items-center justify-center text-xl">📍</div>
            <div>
                <h1 class="text-2xl font-black text-white">{{ $county->name }} County Admin</h1>
                <p class="text-white/30 text-sm mt-1">{{ $county->tagline ?? 'Manage your county content' }}</p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="/export/county/{{ $county->slug }}" class="bg-[#0B1E57] text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-[#0D2A7A] transition-all inline-flex items-center gap-1">📥 JSON</a>
            <a href="/export/county/{{ $county->slug }}/csv" class="bg-[#0B1E57] text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-[#0D2A7A] transition-all inline-flex items-center gap-1">📊 CSV</a>
            <a href="{{ $county->slug }}.go.ke" target="_blank" class="bg-[#141B2E] text-white/50 px-4 py-2 rounded-xl text-xs font-bold hover:text-white transition-all inline-flex items-center gap-1">🌐 Site</a>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-1 mb-8 overflow-x-auto scrollbar-hide flex-wrap">
        @foreach([
            ['id'=>'overview','label'=>'Overview','icon'=>'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
            ['id'=>'content','label'=>'Content','icon'=>'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
            ['id'=>'sectors','label'=>'Sectors','icon'=>'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
            ['id'=>'entities','label'=>'Entities','icon'=>'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
            ['id'=>'tourism','label'=>'Tourism','icon'=>'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z'],
            ['id'=>'hotels','label'=>'Hotels','icon'=>'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
            ['id'=>'farms','label'=>'Farms','icon'=>'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
            ['id'=>'health','label'=>'Health','icon'=>'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z'],
            ['id'=>'institutions','label'=>'Institutions','icon'=>'M12 14l9-5-9-5-9 5 9 5zm0 7l5.5-3M12 21l-5.5-3M12 14l5.5-3'],
            ['id'=>'transport','label'=>'Transport','icon'=>'M8 7h8m0 0v12H8V7zm0 0a2 2 0 014 0m-4 0a2 2 0 00-2 2v12a2 2 0 002 2h4a2 2 0 002-2V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2'],
            ['id'=>'culture','label'=>'Culture','icon'=>'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064'],
            ['id'=>'products','label'=>'Products','icon'=>'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
            ['id'=>'profile','label'=>'Profile','icon'=>'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
            ['id'=>'weather','label'=>'Weather','icon'=>'M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z'],
            ['id'=>'gallery','label'=>'Gallery','icon'=>'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
        ] as $t)
        <button @click="tab='{{ $t['id'] }}'"
            class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0"
            :class="tab==='{{ $t['id'] }}' ? 'bg-[#FFCD05]/15 text-[#FFCD05] border border-[#FFCD05]/30' : 'text-white/40 border border-transparent hover:text-white hover:bg-white/5'">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $t['icon'] }}"/></svg>
            {{ $t['label'] }}
        </button>
        @endforeach
    </div>

    {{-- ═══ OVERVIEW ═══ --}}
    <div x-show="tab==='overview'" x-cloak>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <x-admin.kpi-card label="Linked Sectors" :value="$sectors->count()" accent="#FFCD05" change="{{ $allSectors->count() }} available" />
            <x-admin.kpi-card label="Sector Entities" :value="$entities->count()" accent="#0B1E57" />
            <x-admin.kpi-card label="Local Products" :value="$products->count()" accent="#2D6A4F" />
            <x-admin.kpi-card label="Total Records" :value="$attractions->count()+$hotels->count()+$farms->count()+$health->count()+$institutions->count()+$transport->count()+$culture->count()" accent="#901C1E" />
        </div>

        <div class="grid lg:grid-cols-2 gap-6 mb-6">
            {{-- County Info --}}
            <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-white text-sm">County Profile</h3>
                    <button @click="tab='content'" class="text-xs text-[#FFCD05] hover:underline">Edit</button>
                </div>
                <div class="space-y-3">
                    <div><span class="text-white/30 text-xs">Capital</span><div class="text-white text-sm font-semibold">{{ $county->capital ?? '—' }}</div></div>
                    <div><span class="text-white/30 text-xs">Population</span><div class="text-white text-sm font-semibold">{{ $county->population_2024 ? number_format($county->population_2024) : '—' }}</div></div>
                    <div><span class="text-white/30 text-xs">Area</span><div class="text-white text-sm font-semibold">{{ $county->area_km2 ? number_format($county->area_km2).' km²' : '—' }}</div></div>
                    <div><span class="text-white/30 text-xs">Region</span><div class="text-white text-sm font-semibold">{{ $county->region ?? '—' }}</div></div>
                </div>
            </div>

            {{-- Quick Stats --}}
            <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                <h3 class="font-bold text-white text-sm mb-4">Sector Breakdown</h3>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-[#141B2E] rounded-xl p-3">
                        <div class="text-lg font-black text-white">{{ $attractions->count() }}</div>
                        <div class="text-white/30 text-[10px]">Attractions</div>
                    </div>
                    <div class="bg-[#141B2E] rounded-xl p-3">
                        <div class="text-lg font-black text-white">{{ $hotels->count() }}</div>
                        <div class="text-white/30 text-[10px]">Hotels</div>
                    </div>
                    <div class="bg-[#141B2E] rounded-xl p-3">
                        <div class="text-lg font-black text-white">{{ $farms->count() }}</div>
                        <div class="text-white/30 text-[10px]">Farms</div>
                    </div>
                    <div class="bg-[#141B2E] rounded-xl p-3">
                        <div class="text-lg font-black text-white">{{ $health->count() }}</div>
                        <div class="text-white/30 text-[10px]">Health</div>
                    </div>
                    <div class="bg-[#141B2E] rounded-xl p-3">
                        <div class="text-lg font-black text-white">{{ $institutions->count() }}</div>
                        <div class="text-white/30 text-[10px]">Institutions</div>
                    </div>
                    <div class="bg-[#141B2E] rounded-xl p-3">
                        <div class="text-lg font-black text-white">{{ $transport->count() }}</div>
                        <div class="text-white/30 text-[10px]">Transport</div>
                    </div>
                    <div class="bg-[#141B2E] rounded-xl p-3">
                        <div class="text-lg font-black text-white">{{ $culture->count() }}</div>
                        <div class="text-white/30 text-[10px]">Culture</div>
                    </div>
        </div>

        {{-- Sync --}}
        <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-4 mt-6">
            <div class="flex items-center justify-between">
                <div><span class="text-xs text-white/30">Data sync</span><span class="text-[10px] text-white/20 block mt-0.5">Sync county data with TiDB Cloud</span></div>
                <form method="POST" action="{{ route('kicc.sync.county') }}">
                    @csrf
                    <input type="hidden" name="direction" value="from">
                    <button class="bg-[#0B1E57] text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-[#0D2A7A] transition-all">Pull</button>
                </form>
                <form method="POST" action="{{ route('kicc.sync.county') }}">
                    @csrf
                    <input type="hidden" name="direction" value="to">
                    <button class="bg-[#901C1E] text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-[#7b1618] transition-all">Push</button>
                </form>
            </div>
        </div>
    </div>
        </div>
    </div>

    {{-- ═══ CONTENT ═══ --}}
    <div x-show="tab==='content'" x-cloak>
        <div class="grid lg:grid-cols-2 gap-6">
            <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                <h3 class="font-bold text-white text-sm mb-4">Edit County Info</h3>
                <form method="POST" action="{{ route('county.admin.content', $county->slug) }}">
                    @csrf
                    <div class="space-y-4">
                        <div><label class="text-[10px] font-bold text-white/30 uppercase block mb-1">Tagline</label><input name="tagline" value="{{ $county->tagline }}" class="w-full bg-[#141B2E] border border-white/10 rounded-xl px-4 py-2 text-sm text-white outline-none focus:border-[#FFCD05]/50"></div>
                        <div><label class="text-[10px] font-bold text-white/30 uppercase block mb-1">Description</label><textarea name="description" rows="4" class="w-full bg-[#141B2E] border border-white/10 rounded-xl px-4 py-2 text-sm text-white outline-none focus:border-[#FFCD05]/50">{{ $county->description }}</textarea></div>
                        <div><label class="text-[10px] font-bold text-white/30 uppercase block mb-1">Capital</label><input name="capital" value="{{ $county->capital }}" class="w-full bg-[#141B2E] border border-white/10 rounded-xl px-4 py-2 text-sm text-white outline-none focus:border-[#FFCD05]/50"></div>
                        <div><label class="text-[10px] font-bold text-white/30 uppercase block mb-1">Population</label><input name="population_2024" value="{{ $county->population_2024 }}" class="w-full bg-[#141B2E] border border-white/10 rounded-xl px-4 py-2 text-sm text-white outline-none focus:border-[#FFCD05]/50"></div>
                        <button class="bg-[#901C1E] text-white px-6 py-2 rounded-xl text-sm font-bold hover:bg-[#7b1618] transition-colors">Save Changes</button>
                    </div>
                </form>
            </div>
            <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                <h3 class="font-bold text-white text-sm mb-4">Upload Image</h3>
                <form method="POST" action="{{ route('county.admin.image', $county->slug) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="border-2 border-dashed border-white/10 rounded-xl p-8 text-center hover:border-[#FFCD05]/30 transition-colors">
                        <div class="text-3xl mb-3">📁</div>
                        <p class="text-white/30 text-sm mb-3">Drop county image here</p>
                        <input type="file" name="image" accept="image/*" class="text-sm text-white/50 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-[#901C1E] file:text-white hover:file:bg-[#7b1618]">
                    </div>
                    <button class="mt-4 bg-[#FFCD05] text-[#07090F] px-6 py-2 rounded-xl text-sm font-bold hover:bg-[#e6b904] transition-colors w-full">Upload</button>
                </form>
            </div>
        </div>
    </div>

    {{-- ═══ SECTORS ═══ --}}
    <div x-show="tab==='sectors'" x-cloak>
        <div class="flex items-center justify-between mb-6">
            <h3 class="font-bold text-white text-sm">Linked Sectors ({{ $sectors->count() }})</h3>
            <button @click="tab='add-sector'" class="bg-[#FFCD05]/15 text-[#FFCD05] border border-[#FFCD05]/30 px-4 py-2 rounded-xl text-xs font-bold hover:bg-[#FFCD05]/25 transition-all">+ Link Sector</button>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($sectors as $s)
            <div class="bg-[#0D1220] border border-white/8 hover:border-[#FFCD05]/30 rounded-2xl p-5 transition-all group relative">
                <div class="flex items-start justify-between">
                    <div><span class="text-3xl">{{ $s->icon ?? '📊' }}</span></div>
                    <form method="POST" action="{{ route('county.admin.sector.unlink', [$county->slug, $s->id]) }}" onsubmit="return confirm('Unlink {{ $s->name }} from {{ $county->name }}?')">
                        @csrf @method('DELETE')
                        <button class="text-white/20 hover:text-[#901C1E] transition-colors text-xs font-bold">✕</button>
                    </form>
                </div>
                <div class="font-bold text-white text-sm mt-2">{{ $s->name }}</div>
                <div class="text-white/30 text-xs mt-1">{{ $s->description ? Str::limit($s->description, 60) : '—' }}</div>
            </div>
            @endforeach
        </div>

        <div x-show="tab==='add-sector'" x-cloak class="mt-6 bg-[#0D1220] border border-white/8 rounded-2xl p-6">
            <h3 class="font-bold text-white text-sm mb-4">Link Sector to {{ $county->name }}</h3>
            <form method="POST" action="{{ route('county.admin.sector.link', $county->slug) }}">
                @csrf
                <div class="flex gap-3">
                    <select name="sector_id" class="flex-1 bg-[#141B2E] border border-white/10 rounded-xl px-4 py-2 text-sm text-white outline-none focus:border-[#FFCD05]/50">
                        @foreach($allSectors as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                    <button class="bg-[#FFCD05] text-[#07090F] px-6 py-2 rounded-xl text-sm font-bold hover:bg-[#e6b904] transition-colors">Link</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══ ENTITIES ═══ --}}
    <div x-show="tab==='entities'" x-cloak x-data="{ showForm: false }">
        <div class="flex items-center justify-between mb-6">
            <h3 class="font-bold text-white text-sm">Sector Entities ({{ $entities->count() }})</h3>
            <button @click="showForm=!showForm" class="bg-[#901C1E] text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-[#7b1618] transition-all">+ Add Entity</button>
        </div>

        <div x-show="showForm" x-cloak class="mb-6 bg-[#0D1220] border border-white/8 rounded-2xl p-6">
            <h3 class="font-bold text-white text-sm mb-4">New Entity</h3>
            <form method="POST" action="{{ route('county.admin.entity', $county->slug) }}">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="text-[10px] font-bold text-white/30 uppercase block mb-1">Name</label><input name="name" required class="w-full bg-[#141B2E] border border-white/10 rounded-xl px-4 py-2 text-sm text-white"></div>
                    <div><label class="text-[10px] font-bold text-white/30 uppercase block mb-1">Sector</label>
                        <select name="sector_id" class="w-full bg-[#141B2E] border border-white/10 rounded-xl px-4 py-2 text-sm text-white">
                            @foreach($sectors as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label class="text-[10px] font-bold text-white/30 uppercase block mb-1">Type</label><input name="type" class="w-full bg-[#141B2E] border border-white/10 rounded-xl px-4 py-2 text-sm text-white"></div>
                    <div><label class="text-[10px] font-bold text-white/30 uppercase block mb-1">Description</label><textarea name="description" rows="2" class="w-full bg-[#141B2E] border border-white/10 rounded-xl px-4 py-2 text-sm text-white"></textarea></div>
                </div>
                <button class="mt-4 bg-[#901C1E] text-white px-6 py-2 rounded-xl text-sm font-bold hover:bg-[#7b1618]">Save Entity</button>
            </form>
        </div>

        @if($entities->count() > 0)
        <div class="bg-[#0D1220] border border-white/8 rounded-2xl overflow-hidden">
            <table class="w-full">
                <thead><tr class="bg-[#141B2E]">
                    <th class="text-[10px] font-bold text-white/30 uppercase tracking-wider px-4 py-3 text-left">Name</th>
                    <th class="text-[10px] font-bold text-white/30 uppercase tracking-wider px-4 py-3 text-left">Type</th>
                    <th class="text-[10px] font-bold text-white/30 uppercase tracking-wider px-4 py-3 text-left">Sector</th>
                    <th class="text-[10px] font-bold text-white/30 uppercase tracking-wider px-4 py-3 text-right">Actions</th>
                </tr></thead>
                <tbody class="divide-y divide-white/5">
                    @foreach($entities as $e)
                    <tr class="hover:bg-white/2 transition-colors">
                        <td class="px-4 py-3 font-semibold text-white text-sm">{{ $e->name }}</td>
                        <td class="px-4 py-3 text-sm text-white/50">{{ $e->type ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-white/50">{{ $e->sector?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="{{ route('county.admin.entity.delete', [$county->slug, $e->id]) }}" onsubmit="return confirm('Delete?')">
                                @csrf @method('DELETE')
                                <button class="text-xs text-white/30 hover:text-[#901C1E] transition-colors font-bold">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-12 text-white/30">
            <span class="text-4xl block mb-3">📋</span>
            <p class="text-sm">No entities added yet.</p>
        </div>
        @endif
    </div>

    {{-- ═══ TOURISM ═══ --}}
    <div x-show="tab==='tourism'" x-cloak>
        <div class="grid lg:grid-cols-2 gap-6">
            <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                <h3 class="font-bold text-white text-sm mb-4">Tourism Attractions ({{ $attractions->count() }})</h3>
                @if($attractions->count() > 0)
                <div class="space-y-2">
                    @foreach($attractions as $a)
                    <div class="flex items-center justify-between py-2 border-b border-white/5 last:border-0">
                        <div class="text-sm text-white">{{ $a->name }}</div>
                        <span class="text-xs text-white/30">{{ $a->entry_fee ? 'KES '.number_format($a->entry_fee) : 'Free' }}</span>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-white/30 text-sm text-center py-4">No attractions listed</p>
                @endif
            </div>
            <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                <h3 class="font-bold text-white text-sm mb-4">Hotels &amp; Resorts ({{ $hotels->count() }})</h3>
                @if($hotels->count() > 0)
                <div class="space-y-2">
                    @foreach($hotels as $h)
                    <div class="flex items-center justify-between py-2 border-b border-white/5 last:border-0">
                        <div class="text-sm text-white">{{ $h->name }}</div>
                        <span class="text-xs" style="color:#FFCD05">{{ $h->star_rating ? str_repeat('★', $h->star_rating) : '—' }}</span>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-white/30 text-sm text-center py-4">No hotels listed</p>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══ HOTELS ═══ --}}
    <div x-show="tab==='hotels'" x-cloak>
        <div class="flex items-center justify-between mb-6">
            <h3 class="font-bold text-white text-sm">Hotels &amp; Resorts ({{ $hotels->count() }})</h3>
            <button @click="document.getElementById('hotel-form').classList.toggle('hidden')" class="bg-[#901C1E] text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-[#7b1618] transition-all">+ Add Hotel</button>
        </div>
        <div id="hotel-form" class="hidden mb-6 bg-[#0D1220] border border-white/8 rounded-2xl p-6">
            <form method="POST" action="{{ route('county.admin.hotel', $county->slug) }}">
                @csrf
                <div class="grid grid-cols-3 gap-4">
                    <div><label class="text-[10px] font-bold text-white/30 uppercase block mb-1">Name</label><input name="name" required class="w-full bg-[#141B2E] border border-white/10 rounded-xl px-4 py-2 text-sm text-white"></div>
                    <div><label class="text-[10px] font-bold text-white/30 uppercase block mb-1">Star Rating</label><select name="star_rating" class="w-full bg-[#141B2E] border border-white/10 rounded-xl px-4 py-2 text-sm text-white">@for($s=1;$s<=5;$s++)<option value="{{$s}}">{{$s}}★</option>@endfor</select></div>
                    <div><label class="text-[10px] font-bold text-white/30 uppercase block mb-1">Price Range</label><input name="price_range" class="w-full bg-[#141B2E] border border-white/10 rounded-xl px-4 py-2 text-sm text-white" placeholder="KES 5,000-15,000"></div>
                </div>
                <button class="mt-4 bg-[#901C1E] text-white px-6 py-2 rounded-xl text-sm font-bold">Save Hotel</button>
            </form>
        </div>
        @if($hotels->count() > 0)
        <div class="bg-[#0D1220] border border-white/8 rounded-2xl overflow-hidden">
            <table class="w-full">
                <thead><tr class="bg-[#141B2E]"><th class="text-[10px] font-bold text-white/30 uppercase px-4 py-3 text-left">Name</th><th class="text-[10px] font-bold text-white/30 uppercase px-4 py-3 text-left">Rating</th><th class="text-[10px] font-bold text-white/30 uppercase px-4 py-3 text-right">Actions</th></tr></thead>
                <tbody class="divide-y divide-white/5">
                    @foreach($hotels as $h)
                    <tr class="hover:bg-white/2"><td class="px-4 py-3 text-sm text-white">{{ $h->name }}</td><td class="px-4 py-3 text-sm" style="color:#FFCD05">{{ str_repeat('★', $h->star_rating ?? 0) }}</td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="{{ route('county.admin.hotel.delete', [$county->slug, $h->id]) }}" onsubmit="return confirm('Delete {{ $h->name }}?')">@csrf @method('DELETE')<button class="text-xs text-white/30 hover:text-[#901C1E] font-bold">Delete</button></form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-12 text-white/30"><span class="text-4xl block mb-3">🏨</span><p class="text-sm">No hotels listed yet.</p></div>
        @endif
    </div>

    {{-- ═══ FARMS ═══ --}}
    <div x-show="tab==='farms'" x-cloak>
        @include('county-admin.partials.data-table', ['type'=>'farm','items'=>$farms,'label'=>'Farms','icon'=>'🌾','fields'=>['Name'=>'name','Size'=>'size_acres','Crops'=>'main_crops']])
    </div>

    {{-- ═══ HEALTH ═══ --}}
    <div x-show="tab==='health'" x-cloak>
        @include('county-admin.partials.data-table', ['type'=>'health','items'=>$health,'label'=>'Health Facilities','icon'=>'🏥','fields'=>['Name'=>'name','Type'=>'type','Services'=>'services']])
    </div>

    {{-- ═══ INSTITUTIONS ═══ --}}
    <div x-show="tab==='institutions'" x-cloak>
        @include('county-admin.partials.data-table', ['type'=>'institution','items'=>$institutions,'label'=>'Institutions','icon'=>'🎓','fields'=>['Name'=>'name','Type'=>'type','Students'=>'student_count']])
    </div>

    {{-- ═══ TRANSPORT ═══ --}}
    <div x-show="tab==='transport'" x-cloak>
        @include('county-admin.partials.data-table', ['type'=>'transport','items'=>$transport,'label'=>'Transport','icon'=>'🚛','fields'=>['Name'=>'name','Type'=>'type','Description'=>'description']])
    </div>

    {{-- ═══ CULTURE ═══ --}}
    <div x-show="tab==='culture'" x-cloak>
        @include('county-admin.partials.data-table', ['type'=>'culture','items'=>$culture,'label'=>'Culture Sites','icon'=>'🎭','fields'=>['Name'=>'name','Type'=>'type','Community'=>'community']])
    </div>

    {{-- ═══ PRODUCTS ═══ --}}
    <div x-show="tab==='products'" x-cloak>
        @include('county-admin.partials.data-table', ['type'=>'product','items'=>$products,'label'=>'County Products','icon'=>'📦','fields'=>['Name'=>'name','Category'=>'category','Price (KSh)'=>'price','Unit'=>'unit']])
    </div>

    {{-- ═══ PROFILE ═══ --}}
    <div x-show="tab==='profile'" x-cloak>
        <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
            <h3 class="font-bold text-white text-sm mb-5">Edit County Profile</h3>
            <form method="POST" action="{{ route('county.admin.profile', $county->slug) }}">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="text-[10px] font-bold text-white/30 uppercase block mb-1">Tagline</label><input name="tagline" value="{{ $county->tagline }}" class="w-full bg-[#141B2E] border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white"></div>
                    <div><label class="text-[10px] font-bold text-white/30 uppercase block mb-1">Capital</label><input name="capital" value="{{ $county->capital }}" class="w-full bg-[#141B2E] border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white"></div>
                    <div><label class="text-[10px] font-bold text-white/30 uppercase block mb-1">Population</label><input name="population_2024" value="{{ $county->population_2024 }}" class="w-full bg-[#141B2E] border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white"></div>
                    <div><label class="text-[10px] font-bold text-white/30 uppercase block mb-1">Area (km²)</label><input name="area_km2" value="{{ $county->area_km2 }}" class="w-full bg-[#141B2E] border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white"></div>
                    <div><label class="text-[10px] font-bold text-white/30 uppercase block mb-1">Region</label><input name="region" value="{{ $county->region }}" class="w-full bg-[#141B2E] border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white"></div>
                    <div class="col-span-2"><label class="text-[10px] font-bold text-white/30 uppercase block mb-1">Description</label><textarea name="description" rows="3" class="w-full bg-[#141B2E] border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white">{{ $county->description }}</textarea></div>
                </div>
                <button class="bg-[#901C1E] text-white px-6 py-2.5 rounded-xl text-sm font-bold mt-4 hover:bg-[#7b1618] transition-all">Update Profile</button>
            </form>
        </div>
    </div>

    {{-- ═══ WEATHER ═══ --}}
    <div x-show="tab==='weather'" x-cloak>
        <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
            <h3 class="font-bold text-white text-sm mb-5">Seasonal Weather Data</h3>
            <form method="POST" action="{{ route('county.admin.weather', $county->slug) }}">
                @csrf
                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div><label class="text-[10px] font-bold text-white/30 uppercase block mb-1">Month</label>
                        <select name="month" class="w-full bg-[#141B2E] border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white">
                            @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $i=>$m)
                            <option value="{{ $i+1 }}">{{ $m }}</option>
                            @endforeach
                        </select></div>
                    <div><label class="text-[10px] font-bold text-white/30 uppercase block mb-1">Avg Temp (°C)</label><input name="avg_temp_c" class="w-full bg-[#141B2E] border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white" placeholder="e.g. 28.5"></div>
                    <div><label class="text-[10px] font-bold text-white/30 uppercase block mb-1">Rainfall (mm)</label><input name="rainfall_mm" class="w-full bg-[#141B2E] border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white" placeholder="e.g. 120"></div>
                    <div><label class="text-[10px] font-bold text-white/30 uppercase block mb-1">Tourism Season</label>
                        <select name="tourism_season" class="w-full bg-[#141B2E] border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white">
                            <option value="">Select...</option><option value="peak">Peak</option><option value="mid">Mid</option><option value="low">Low</option>
                        </select></div>
                    <div><label class="text-[10px] font-bold text-white/30 uppercase block mb-1">Agriculture Season</label>
                        <select name="agri_season" class="w-full bg-[#141B2E] border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white">
                            <option value="">Select...</option><option value="dry">Dry</option><option value="light_rains">Light Rains</option><option value="heavy_rains">Heavy Rains</option>
                        </select></div>
                    <div><label class="text-[10px] font-bold text-white/30 uppercase block mb-1">Weather Tag</label><input name="weather_tag" class="w-full bg-[#141B2E] border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white" placeholder="e.g. Hot & dry"></div>
                </div>
                <button class="bg-[#901C1E] text-white px-6 py-2.5 rounded-xl text-sm font-bold hover:bg-[#7b1618] transition-all">Save Weather Data</button>
            </form>
        </div>
    </div>

    {{-- ═══ GALLERY ═══ --}}
    <div x-show="tab==='gallery'" x-cloak>
        <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-bold text-white text-sm">County Image Gallery</h3>
                <form method="POST" action="{{ route('county.admin.image', $county->slug) }}" enctype="multipart/form-data">
                    @csrf
                    <label class="bg-[#901C1E] text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-[#7b1618] transition-all cursor-pointer inline-flex items-center gap-1">+ Upload Image<input type="file" name="image" accept="image/*" class="hidden" onchange="this.form.submit()"></label>
                </form>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @forelse($county->images ?? [] as $img)
                <div class="bg-[#141B2E] rounded-xl overflow-hidden group relative">
                    <img src="{{ $img->url ?? 'https://placehold.co/400x300/141B2E/ffffff?text=Image' }}" class="w-full aspect-[4/3] object-cover" loading="lazy">
                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-all flex items-center justify-center opacity-0 group-hover:opacity-100">
                        <form method="POST" action="{{ route('county.admin.image.delete', [$county->slug, $img->id]) }}" onsubmit="return confirm('Delete this image?')">
                            @csrf @method('DELETE')
                            <button class="bg-red-500 text-white px-3 py-1.5 rounded-lg text-xs font-bold">Delete</button>
                        </form>
                    </div>
                </div>
                @empty
                <div class="col-span-4 text-center py-12 text-white/30">
                    <div class="text-3xl mb-2">🖼️</div>
                    <p class="text-sm">No images uploaded yet. Click "Upload Image" to add one.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection