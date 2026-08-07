@extends('layouts.blank')

@section('title', $county->name . ' County — Professional Admin')

@section('content')
@php $accent = '#14B8A6'; @endphp
<div class="flex min-h-screen bg-[#F3F4F6]">
    {{-- Sidebar --}}
    <div class="w-60 bg-white border-r border-gray-200 flex flex-col shrink-0 min-h-screen">
        <div class="flex items-center gap-3 px-4 border-b border-gray-200 h-16">
            <a href="{{ route('admin.portal') }}" class="text-gray-400 hover:text-gray-700 text-xs">&larr; Portal</a>
            <div class="flex items-center gap-2">
                <img src="{{ media('kicc/kicc-logo.png') }}" alt="KICC" class="h-6 w-auto" style="filter: brightness(0);">
                <div style="color: {{ $accent }}" class="text-[9px] font-black tracking-[0.15em] uppercase">County<br>Admin</div>
            </div>
        </div>
        <div class="flex-1 py-3 overflow-y-auto">
            @foreach($navItems as $item)
            <a href="{{ route('county.admin.pro', [$county->slug, 'tab' => $item['tab']]) }}"
               class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-semibold transition-all {{ $tab === $item['tab'] ? 'text-gray-900 border-r-2' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-800' }}"
               style="{{ $tab === $item['tab'] ? 'background: '.$accent.'22; border-color: '.$accent : '' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/></svg>
                <span class="truncate text-xs">{{ $item['label'] }}</span>
            </a>
            @endforeach
        </div>
        <a href="{{ route('counties.show', $county->slug) }}" class="flex items-center gap-3 px-4 py-3 border-t border-gray-200 text-gray-400 hover:text-gray-700 text-xs transition-colors">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            <span>View County Page</span>
        </a>
        <a href="/" class="flex items-center gap-3 px-4 py-4 border-t border-gray-200 text-gray-400 hover:text-gray-700 text-xs transition-colors">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            <span>Exit</span>
        </a>
    </div>

    {{-- Main --}}
    <div class="flex-1 flex flex-col overflow-hidden">
        <div class="bg-white border-b border-gray-200 px-6 h-16 flex items-center justify-between shrink-0">
            <div>
                <div class="font-black text-gray-900 text-xl">{{ $county->name }} <span class="text-sm text-gray-400 font-medium">Admin</span></div>
                <div class="text-[10px] font-bold uppercase tracking-widest" style="color: {{ $accent }}">Full Control · Analytics · Content · Images · Prices</div>
            </div>
            <a href="{{ route('exhibitor.admin') }}" class="px-3 py-1.5 text-xs font-bold rounded-lg text-white" style="background: {{ $accent }}">View County Page &nearr;</a>
        </div>
        <div class="flex-1 overflow-y-auto p-6">

            @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-5 py-3 mb-6 text-sm">{{ session('success') }}</div>
            @endif
            @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-5 py-3 mb-6 text-sm">{{ $errors->first() }}</div>
            @endif

            {{-- ═══════ OVERVIEW ═══════ --}}
            @if($tab === 'overview')
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-3xl font-black text-gray-900">{{ $stats['products'] }}</div><div class="text-xs text-gray-400 mt-1">County Products</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-3xl font-black text-gray-900">{{ $stats['attractions'] }}</div><div class="text-xs text-gray-400 mt-1">Attractions</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-3xl font-black text-gray-900">{{ $stats['hotels'] }}</div><div class="text-xs text-gray-400 mt-1">Hotels</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-3xl font-black text-gray-900">{{ $stats['marketplaceProducts'] }}</div><div class="text-xs text-gray-400 mt-1">Marketplace Listings</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-3xl font-black text-gray-900">{{ $stats['orders'] }}</div><div class="text-xs text-gray-400 mt-1">Orders</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-3xl font-black" style="color: {{ $accent }}">KES {{ number_format($stats['revenue']) }}</div><div class="text-xs text-gray-400 mt-1">Total Revenue</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-3xl font-black text-gray-900">{{ count($sectorImages) }}</div><div class="text-xs text-gray-400 mt-1">Sector Images Managed</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-3xl font-black text-gray-900">{{ $plans->count() }}</div><div class="text-xs text-gray-400 mt-1">Available Packages</div></div>
            </div>
            <div class="grid lg:grid-cols-2 gap-6 mb-8">
                <div class="bg-white border border-gray-200 rounded-2xl p-6"><h3 class="font-bold text-gray-900 mb-4">Quick Actions</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <a href="{{ route('county.admin.pro', [$county->slug, 'tab' => 'content']) }}" class="border border-gray-200 rounded-xl p-4 text-sm font-semibold text-gray-700 hover:border-[#14B8A6]/40 hover:text-[#14B8A6] transition-all text-center">✏️ Edit Content</a>
                        <a href="{{ route('county.admin.pro', [$county->slug, 'tab' => 'images']) }}" class="border border-gray-200 rounded-xl p-4 text-sm font-semibold text-gray-700 hover:border-[#14B8A6]/40 hover:text-[#14B8A6] transition-all text-center">🖼️ Manage Images</a>
                        <a href="{{ route('county.admin.pro', [$county->slug, 'tab' => 'prices']) }}" class="border border-gray-200 rounded-xl p-4 text-sm font-semibold text-gray-700 hover:border-[#14B8A6]/40 hover:text-[#14B8A6] transition-all text-center">💰 Set Prices</a>
                        <a href="{{ route('county.admin.pro', [$county->slug, 'tab' => 'ads']) }}" class="border border-gray-200 rounded-xl p-4 text-sm font-semibold text-gray-700 hover:border-[#14B8A6]/40 hover:text-[#14B8A6] transition-all text-center">📢 Advertise</a>
                    </div>
                </div>
                <div class="bg-white border border-gray-200 rounded-2xl p-6"><h3 class="font-bold text-gray-900 mb-4">Current Package</h3>
                    <p class="text-sm text-gray-500">Your county is on the <strong class="text-gray-900">Free</strong> plan. Upgrade to access more features.</p>
                    <a href="{{ route('county.admin.pro', [$county->slug, 'tab' => 'packages']) }}" class="mt-4 inline-flex items-center gap-2 text-sm font-bold" style="color: {{ $accent }}">Browse packages &nearr;</a>
                </div>
            </div>
            @endif

            {{-- ═══════ DETAILS ═══════ --}}
            @if($tab === 'details')
            <div class="max-w-3xl">
                <div class="bg-white border border-gray-200 rounded-2xl p-6">
                    <h3 class="font-bold text-gray-900 mb-2">County Details</h3>
                    <p class="text-xs text-gray-400 mb-6">Edit all county metadata. Changes take effect immediately on the public county page.</p>
                    <form method="POST" action="{{ route('county.admin.details', $county->slug) }}" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Tagline</label>
                                <input name="tagline" value="{{ $county->tagline }}" class="w-full h-11 px-4 rounded-xl border border-gray-200 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Capital</label>
                                <input name="capital" value="{{ $county->capital }}" class="w-full h-11 px-4 rounded-xl border border-gray-200 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Population (2024)</label>
                                <input name="population_2024" type="number" value="{{ $county->population_2024 }}" class="w-full h-11 px-4 rounded-xl border border-gray-200 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Area (km²)</label>
                                <input name="area_km2" type="number" step="0.01" value="{{ $county->area_km2 }}" class="w-full h-11 px-4 rounded-xl border border-gray-200 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Economic Zone</label>
                                <input name="economic_zone" value="{{ $county->economic_zone }}" class="w-full h-11 px-4 rounded-xl border border-gray-200 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Emoji Icon</label>
                                <input name="icon_emoji" value="{{ $county->icon_emoji }}" placeholder="📍" class="w-full h-11 px-4 rounded-xl border border-gray-200 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Latitude</label>
                                <input name="latitude" type="number" step="0.0001" value="{{ $county->latitude }}" class="w-full h-11 px-4 rounded-xl border border-gray-200 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Longitude</label>
                                <input name="longitude" type="number" step="0.0001" value="{{ $county->longitude }}" class="w-full h-11 px-4 rounded-xl border border-gray-200 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Warmest Month</label>
                                <input name="warmest_month" value="{{ $county->warmest_month }}" class="w-full h-11 px-4 rounded-xl border border-gray-200 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Coolest Month</label>
                                <input name="coolest_month" value="{{ $county->coolest_month }}" class="w-full h-11 px-4 rounded-xl border border-gray-200 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Rainy Season</label>
                                <input name="rainy_season" value="{{ $county->rainy_season }}" class="w-full h-11 px-4 rounded-xl border border-gray-200 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Dry Season</label>
                                <input name="dry_season" value="{{ $county->dry_season }}" class="w-full h-11 px-4 rounded-xl border border-gray-200 text-sm">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Description</label>
                            <textarea name="description" rows="4" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm">{{ $county->description }}</textarea>
                        </div>
                        <button class="h-12 px-8 rounded-xl text-white font-bold text-sm" style="background: #14B8A6">Save All Details</button>
                    </form>
                </div>
            </div>
            @endif

            {{-- ═══════ SECTORS ═══════ --}}
            @if($tab === 'sectors')
            <div class="grid lg:grid-cols-2 gap-6">
                <div class="bg-white border border-gray-200 rounded-2xl p-6">
                    <h3 class="font-bold text-gray-900 mb-2">Link Sectors to {{ $county->name }}</h3>
                    <p class="text-xs text-gray-400 mb-5">Toggle sectors on/off for this county.</p>
                    <div class="space-y-2 max-h-96 overflow-y-auto">
                        @foreach($allSectors as $s)
                        <form method="POST" action="{{ route('county.admin.sector', $county->slug) }}" class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                            @csrf
                            <input type="hidden" name="sector_id" value="{{ $s->id }}">
                            @if($linkedSectors->contains($s->id))
                            <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-emerald-500"></span><span class="font-semibold text-gray-900 text-sm">{{ $s->name }}</span></span>
                            <input type="hidden" name="action" value="detach">
                            <button class="text-xs font-bold px-3 py-1.5 rounded-lg bg-red-50 text-red-600 hover:bg-red-100">Remove</button>
                            @else
                            <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-gray-300"></span><span class="text-gray-500 text-sm">{{ $s->name }}</span></span>
                            <input type="hidden" name="action" value="attach">
                            <button class="text-xs font-bold px-3 py-1.5 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200">Add</button>
                            @endif
                        </form>
                        @endforeach
                    </div>
                </div>
                <div class="bg-white border border-gray-200 rounded-2xl p-6">
                    <h3 class="font-bold text-gray-900 mb-2">Add Entity to Sector</h3>
                    <p class="text-xs text-gray-400 mb-5">Create a new entity (attraction, product, institution, etc.) under a sector.</p>
                    <form method="POST" action="{{ route('county.admin.entity', $county->slug) }}" class="space-y-3">
                        @csrf
                        <select name="sector_id" required class="w-full h-11 px-4 rounded-xl border border-gray-200 text-sm">
                            <option value="">Select sector…</option>
                            @foreach($linkedSectors as $lsId)
                            @php $ls = $allSectors->firstWhere('id', $lsId); @endphp
                            @if($ls)<option value="{{ $ls->id }}">{{ $ls->name }}</option>@endif
                            @endforeach
                        </select>
                        <input name="name" required placeholder="Entity name *" class="w-full h-11 px-4 rounded-xl border border-gray-200 text-sm">
                        <input name="entity_type" required placeholder="Type (e.g. hotel, school, farm)" class="w-full h-11 px-4 rounded-xl border border-gray-200 text-sm">
                        <textarea name="description" rows="2" placeholder="Description" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm"></textarea>
                        <button class="w-full h-12 rounded-xl text-white font-bold text-sm" style="background: #14B8A6">Add Entity</button>
                    </form>
                    <h4 class="font-bold text-gray-900 mt-6 mb-3">Existing Entities ({{ $sectorEntities->count() }})</h4>
                    <div class="space-y-2 max-h-80 overflow-y-auto">
                        @forelse($sectorEntities as $e)
                        <div class="flex items-center justify-between py-2 border-b border-gray-50">
                            <div><div class="font-semibold text-gray-900 text-sm">{{ $e->name }}</div><div class="text-xs text-gray-400">{{ $e->entity_type }} · {{ $e->sector?->name ?? '—' }}</div></div>
                            <form method="POST" action="{{ route('county.admin.entity.delete', [$county->slug, $e->id]) }}" onsubmit="return confirm('Delete this entity?')">@csrf<button class="text-xs font-bold text-red-500 hover:text-red-700">Delete</button></form>
                        </div>
                        @empty
                        <p class="text-gray-400 text-sm py-6 text-center">No entities yet. Add one above.</p>
                        @endforelse
                    </div>
                </div>
            </div>
            @endif

            {{-- ═══════ CONTENT ═══════ --}}
            @if($tab === 'content')
            <div class="bg-white border border-gray-200 rounded-2xl p-6 max-w-2xl">
                <h3 class="font-bold text-gray-900 mb-2">County Content Editor</h3>
                <p class="text-xs text-gray-400 mb-6">Changes are live on <strong>{{ route('counties.show', $county->slug) }}</strong> immediately.</p>
                <form method="POST" action="{{ route('county.admin.content', $county->slug) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Tagline</label>
                        <input name="tagline" value="{{ $county->tagline }}" class="w-full h-11 px-4 rounded-xl border border-gray-200 text-sm outline-none focus:ring-2" style="--tw-ring-color: {{ $accent }}">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Description</label>
                        <textarea name="description" rows="5" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm outline-none focus:ring-2" style="--tw-ring-color: {{ $accent }}">{{ $county->description }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Tourism Highlights</label>
                        <textarea name="tourism_highlights" rows="3" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm outline-none focus:ring-2" style="--tw-ring-color: {{ $accent }}">{{ is_array($county->tourism_highlights) ? implode(', ', $county->tourism_highlights) : $county->tourism_highlights }}</textarea>
                    </div>
                    <button class="h-12 px-8 rounded-xl text-white font-bold text-sm" style="background: {{ $accent }}">Save Content</button>
                </form>
            </div>
            @endif

            {{-- ═══════ IMAGES ═══════ --}}
            @if($tab === 'images')
            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h3 class="font-bold text-gray-900 mb-2">Sector Images</h3>
                <p class="text-xs text-gray-400 mb-6">Upload a new image and it reflects everywhere immediately (county page, sector pages, marketplace). Delete to show fallback.</p>
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach($sectorImages as $sector => $img)
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <div class="h-36 bg-gray-100 overflow-hidden">
                            @if($img['exists'])
                            <img src="{{ media($img['path']) }}" class="w-full h-full object-cover" alt="{{ $sector }}">
                            @else
                            <div class="w-full h-full flex items-center justify-center text-gray-300 text-2xl">{{ strtoupper($sector[0]) }}</div>
                            @endif
                        </div>
                        <div class="p-3">
                            <div class="text-sm font-bold text-gray-900 capitalize">{{ $sector }}</div>
                            <div class="flex gap-1.5 mt-2">
                                <form method="POST" action="{{ route('county.admin.image.upload', $county->slug) }}" enctype="multipart/form-data" class="flex-1">
                                    @csrf
                                    <input type="hidden" name="sector" value="{{ $sector }}">
                                    <label class="flex items-center justify-center h-8 rounded-lg border border-gray-200 text-[10px] font-bold text-gray-500 cursor-pointer hover:border-[#14B8A6]/40 transition-all">
                                        <input type="file" name="image" accept="image/*" class="sr-only" onchange="this.form.submit()">
                                        Upload
                                    </label>
                                </form>
                                @if($img['exists'])
                                <form method="POST" action="{{ route('county.admin.image.delete', [$county->slug, $sector]) }}" onsubmit="return confirm('Remove this image?')">
                                    @csrf
                                    <button class="h-8 px-3 rounded-lg border border-red-200 text-red-500 text-[10px] font-bold hover:bg-red-50">Delete</button>
                                </form>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- ═══════ PRICES ═══════ --}}
            @if($tab === 'prices')
            <div class="grid lg:grid-cols-2 gap-6">
                <div class="bg-white border border-gray-200 rounded-2xl p-6">
                    <h3 class="font-bold text-gray-900 mb-5">County Products — Prices</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead><tr class="text-left text-[10px] uppercase tracking-widest text-gray-400 border-b border-gray-100">
                                <th class="pb-3 pr-4">Product</th><th class="pb-3 pr-4">Current Price</th><th class="pb-3">Set Price</th>
                            </tr></thead>
                            <tbody>
                            @foreach($products as $p)
                            <tr class="border-b border-gray-50 last:border-0">
                                <td class="py-2.5 pr-4 font-semibold text-gray-900 text-sm">{{ $p->name }}</td>
                                <td class="py-2.5 pr-4 font-bold" style="color: {{ $accent }}">KES {{ number_format($p->price) }}</td>
                                <td class="py-2.5">
                                    <form method="POST" action="{{ route('county.admin.price', $county->slug) }}" class="flex gap-1.5">
                                        @csrf
                                        <input type="hidden" name="table" value="county_products">
                                        <input type="hidden" name="id" value="{{ $p->id }}">
                                        <input type="number" name="price" min="0" placeholder="KES" class="w-20 h-8 px-2 rounded-lg border border-gray-200 text-xs">
                                        <button class="h-8 px-3 rounded-lg text-white text-[10px] font-bold" style="background: {{ $accent }}">SET</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="bg-white border border-gray-200 rounded-2xl p-6">
                    <h3 class="font-bold text-gray-900 mb-5">Tourism Attractions — Entry Fees</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead><tr class="text-left text-[10px] uppercase tracking-widest text-gray-400 border-b border-gray-100">
                                <th class="pb-3 pr-4">Attraction</th><th class="pb-3 pr-4">Entry Fee</th><th class="pb-3">Set Fee</th>
                            </tr></thead>
                            <tbody>
                            @foreach($attractions as $a)
                            <tr class="border-b border-gray-50 last:border-0">
                                <td class="py-2.5 pr-4 font-semibold text-gray-900 text-sm">{{ $a->name }}</td>
                                <td class="py-2.5 pr-4 font-bold" style="color: {{ $accent }}">KES {{ number_format($a->entry_fee ?: 0) }}</td>
                                <td class="py-2.5">
                                    <form method="POST" action="{{ route('county.admin.price', $county->slug) }}" class="flex gap-1.5">
                                        @csrf
                                        <input type="hidden" name="table" value="county_tourism_attractions">
                                        <input type="hidden" name="id" value="{{ $a->id }}">
                                        <input type="number" name="price" min="0" placeholder="KES" class="w-20 h-8 px-2 rounded-lg border border-gray-200 text-xs">
                                        <button class="h-8 px-3 rounded-lg text-white text-[10px] font-bold" style="background: {{ $accent }}">SET</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            {{-- ═══════ MARKETPLACE ═══════ --}}
            @if($tab === 'marketplace')
            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h3 class="font-bold text-gray-900 mb-5">Marketplace Listings — {{ $county->name }} ({{ $marketplaceProducts->count() }})</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="text-left text-[10px] uppercase tracking-widest text-gray-400 border-b border-gray-100">
                            <th class="pb-3 pr-4">Product</th><th class="pb-3 pr-4">Price</th><th class="pb-3 pr-4">Stock</th><th class="pb-3">Link</th>
                        </tr></thead>
                        <tbody>
                        @foreach($marketplaceProducts as $mp)
                        <tr class="border-b border-gray-50 last:border-0">
                            <td class="py-2.5 pr-4 font-semibold text-gray-900">{{ $mp->name }}</td>
                            <td class="py-2.5 pr-4 font-bold" style="color: {{ $accent }}">KES {{ number_format($mp->price ?? 0) }}</td>
                            <td class="py-2.5 pr-4 text-gray-500">{{ $mp->variants->sum('stock') }}</td>
                            <td class="py-2.5"><a href="{{ route('marketplace.show', $mp->slug) }}" class="text-[10px] font-bold px-2.5 py-1 rounded-full border border-gray-200 text-gray-500 hover:text-gray-900">VIEW</a></td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- ═══════ ADVERTISING ═══════ --}}
            @if($tab === 'ads')
            <div class="grid lg:grid-cols-2 gap-6">
                <div class="bg-white border border-gray-200 rounded-2xl p-6">
                    <h3 class="font-bold text-gray-900 mb-5">Create an Ad</h3>
                    <form method="POST" action="{{ route('county.admin.ads', $county->slug) }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <input name="name" required placeholder="Product/service name *" class="w-full h-11 px-4 rounded-xl border border-gray-200 text-sm">
                        <textarea name="description" rows="2" placeholder="Describe what you're advertising" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm"></textarea>
                        <div class="grid grid-cols-2 gap-2">
                            <input name="price" type="number" min="0" step="0.01" required placeholder="Price (KES) *" class="h-11 px-4 rounded-xl border border-gray-200 text-sm">
                            <input name="image" type="file" accept="image/*" class="h-11 px-3 rounded-xl border border-gray-200 text-sm text-gray-500">
                        </div>
                        <button class="w-full h-12 rounded-xl text-white font-bold text-sm" style="background: {{ $accent }}">Publish Ad</button>
                    </form>
                </div>
                <div class="bg-white border border-gray-200 rounded-2xl p-6">
                    <h3 class="font-bold text-gray-900 mb-5">Active Ads ({{ $ads->count() }})</h3>
                    @forelse($ads as $ad)
                    <div class="flex items-center gap-3 py-3 border-b border-gray-100 last:border-0">
                        @if($ad->image_url)
                        <img src="{{ $ad->image_url }}" class="w-12 h-12 rounded-xl object-cover bg-gray-100">
                        @else
                        <div class="w-12 h-12 rounded-xl bg-gray-100 flex items-center justify-center text-gray-300">📢</div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-gray-900 text-sm truncate">{{ $ad->name }}</div>
                            <div class="text-xs text-gray-400">KES {{ number_format($ad->budget) }} · {{ $ad->created_at?->format('d M Y') }}</div>
                        </div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600">{{ $ad->is_active ? 'LIVE' : 'PENDING' }}</span>
                    </div>
                    @empty
                    <p class="text-gray-400 text-sm py-6 text-center">No ads yet. Create your first ad to promote county goods.</p>
                    @endforelse
                </div>
            </div>
            @endif

            {{-- ═══════ PACKAGES ═══════ --}}
            @if($tab === 'packages')
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($plans as $p)
                <div class="bg-white border-2 border-gray-200 rounded-2xl p-6 flex flex-col {{ $p->slug === 'county-premium' ? 'border-[#14B8A6] shadow-lg' : '' }}">
                    @if($p->slug === 'county-premium')
                    <span class="text-[10px] font-black uppercase tracking-widest text-[#14B8A6] mb-1">Recommended</span>
                    @endif
                    <div class="font-black text-gray-900 text-lg">{{ $p->name }}</div>
                    <div class="text-3xl font-black text-gray-900 mt-2">KES {{ number_format($p->price) }}<span class="text-sm font-medium text-gray-400">/mo</span></div>
                    <ul class="mt-4 space-y-1.5 text-sm text-gray-500 flex-1">
                        <li>{{ $p->max_booths >= 999 ? 'Unlimited' : $p->max_booths }} booth{{ $p->max_booths > 1 ? 's' : '' }}</li>
                        <li>{{ $p->max_exhibitions >= 999 ? 'Unlimited' : $p->max_exhibitions }} exhibition{{ $p->max_exhibitions > 1 ? 's' : '' }}</li>
                        <li>{{ $p->has_analytics ? '✅' : '—' }} Analytics</li>
                        <li>{{ $p->has_livestream ? '✅' : '—' }} Livestream</li>
                        <li>{{ $p->has_priority_support ? '✅' : '—' }} Priority Support</li>
                    </ul>
                    <form method="POST" action="{{ route('county.admin.package', $county->slug) }}" class="mt-4">
                        @csrf
                        <input type="hidden" name="plan_slug" value="{{ $p->slug }}">
                        <button class="w-full h-12 rounded-xl font-bold text-sm text-white" style="background: {{ $p->price == 0 ? '#6B7280' : $accent }}">{{ $p->price == 0 ? 'Current Plan' : 'Purchase' }}</button>
                    </form>
                </div>
                @endforeach
            </div>
            @endif

            {{-- ═══════ REPORTS ═══════ --}}
            @if($tab === 'reports')
            <div class="max-w-xl">
                <div class="bg-white border border-gray-200 rounded-2xl p-6 mb-4"><h3 class="font-bold text-gray-900 mb-2">Download Reports</h3>
                    <p class="text-xs text-gray-400 mb-4">CSV reports of your county data — open in Excel, Sheets, or any analytics tool.</p>
                    <div class="space-y-2">
                        @foreach([['products', 'Products Report'], ['attractions', 'Tourism Attractions Report'], ['hotels', 'Hotels Report']] as $r)
                        <a href="{{ route('county.admin.report', [$county->slug, $r[0]]) }}" class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 hover:border-[#14B8A6]/40 transition-all">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <div class="font-semibold text-gray-900 text-sm">{{ $r[1] }}</div>
                            </div>
                            <span class="text-xs font-bold" style="color: {{ $accent }}">Download CSV &darr;</span>
                        </a>
                        @endforeach
                    </div>
                </div>
                <div class="bg-white border border-gray-200 rounded-2xl p-6"><h3 class="font-bold text-gray-900 mb-2">Analytics Summary</h3>
                    <div class="space-y-2 text-sm text-gray-500">
                        <div class="flex justify-between border-b border-gray-50 pb-2"><span>Total marketplace products</span><span class="font-bold text-gray-900">{{ $stats['marketplaceProducts'] }}</span></div>
                        <div class="flex justify-between border-b border-gray-50 pb-2"><span>Total product stock value</span><span class="font-bold text-gray-900">KES {{ number_format($products->sum('price')) }}</span></div>
                        <div class="flex justify-between border-b border-gray-50 pb-2"><span>Total attractions</span><span class="font-bold text-gray-900">{{ $stats['attractions'] }}</span></div>
                        <div class="flex justify-between"><span>Total orders placed</span><span class="font-bold text-gray-900">{{ $stats['orders'] }}</span></div>
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>
</div>
@endsection