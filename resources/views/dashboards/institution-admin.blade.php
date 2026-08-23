@extends('layouts.blank')

@section('title', $institution->name . ' — Institution Admin')

@section('content')
@php $accent = '#FFCD05'; $accentText = 'text-[#FFCD05]'; @endphp
<div class="flex h-screen bg-[#121212] text-zinc-100 font-sans overflow-hidden">

    {{-- ═══════ SIDEBAR ═══════ --}}
    <aside class="w-64 bg-[#18181B] border-r border-zinc-800 flex flex-col justify-between p-4 overflow-y-auto shrink-0">
        <div class="space-y-6">
            {{-- Workspace switcher --}}
            <div class="flex items-center justify-between p-2 rounded-lg bg-zinc-900/80 border border-zinc-800">
                <div class="flex items-center gap-2 min-w-0">
                    @if($institution->logo_url)
                    <img src="{{ $institution->logo_url }}" class="w-7 h-7 rounded object-cover shrink-0">
                    @else
                    <div class="w-7 h-7 rounded bg-[#FFCD05] flex items-center justify-center font-bold text-xs text-black shrink-0">
                        {{ strtoupper(substr($institution->name, 0, 2)) }}
                    </div>
                    @endif
                    <div class="min-w-0">
                        <p class="text-[10px] text-zinc-400">Institution</p>
                        <p class="font-semibold text-xs leading-tight truncate">{{ $institution->name }}</p>
                    </div>
                </div>
                <a href="{{ route('counties.show', $institution->county?->slug) }}" title="View public page" class="text-zinc-500 hover:text-[#FFCD05] transition shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
            </div>

            {{-- Nav groups --}}
            @php
            $groups = [
                'Main Menu' => ['overview', 'products', 'videos', 'transactions'],
                'Customers' => ['customers'],
                'Management' => ['production', 'sectors'],
                'Settings' => ['profile', 'team'],
            ];
            $labels = collect($navItems)->keyBy('tab');
            @endphp

            @foreach($groups as $group => $tabs)
            <div class="space-y-1">
                <p class="px-2 text-[10px] font-semibold text-zinc-500 uppercase tracking-wider">{{ $group }}</p>
                @foreach($tabs as $t)
                @php $item = $labels[$t] ?? null; @endphp
                @if($item)
                <a href="{{ route('institution.admin', [$institution->slug, 'tab' => $t]) }}"
                   class="flex items-center gap-3 px-2.5 py-2 rounded-lg text-xs cursor-pointer transition w-full {{ $tab === $t ? 'bg-[#FFCD05]/10 text-[#FFCD05] font-medium border border-[#FFCD05]/20' : 'text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50 border border-transparent' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/></svg>
                    <span class="truncate">{{ $item['label'] }}</span>
                </a>
                @endif
                @endforeach
            </div>
            @endforeach
        </div>

        <div class="space-y-2 pt-4 border-t border-zinc-800/50">
            <a href="{{ route('logout') }}" onclick="event.preventDefault();document.getElementById('logout-form').submit();"
               class="flex items-center gap-3 px-2.5 py-2 rounded-lg text-xs text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                <span>Sign out</span>
            </a>
            <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">@csrf</form>
            <div class="px-2.5 py-1.5 text-[10px] text-zinc-600">
                {{ $institution->county?->name }} County · {{ $institution->synced_at ? 'Last sync: ' . $institution->synced_at->diffForHumans() : 'Never synced' }}
            </div>
        </div>
    </aside>

    {{-- ═══════ MAIN ═══════ --}}
    <main class="flex-1 flex flex-col overflow-y-auto">
        {{-- Header --}}
        <header class="h-16 border-b border-zinc-800 px-6 flex items-center justify-between bg-[#121212] shrink-0">
            <div class="flex items-center gap-2 text-xs text-zinc-400">
                <span>Dashboard</span>
                <span>&gt;</span>
                <span class="text-[#FFCD05] font-medium">{{ ucfirst($tab) }}</span>
            </div>
            <div class="flex items-center gap-3">
                <div class="relative">
                    <svg class="absolute left-2.5 top-2.5 h-4 w-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" placeholder="Search..." id="inst-search"
                           class="bg-[#18181B] border border-zinc-800 text-xs rounded-lg pl-8 pr-4 py-2 text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05] w-48">
                </div>
                @if(session('success'))
                <span class="text-[11px] text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-3 py-1.5 rounded-lg hidden md:inline-block">{{ session('success') }}</span>
                @endif
                <a href="{{ route('institution.admin.sync', $institution->slug) }}"
                   class="bg-[#18181B] border border-zinc-800 text-xs text-zinc-300 px-3 py-2 rounded-lg hover:bg-zinc-800 transition inline-flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-[#FFCD05]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Sync Now
                </a>
            </div>
        </header>

        <div class="p-6 space-y-6">
            @if($errors->any())
            <div class="bg-red-500/10 border border-red-500/20 text-red-400 rounded-xl px-5 py-3 text-sm">{{ $errors->first() }}</div>
            @endif

            {{-- ═══════ OVERVIEW ═══════ --}}
            @if($tab === 'overview')
            <div>
                <h1 class="text-xl font-semibold">Welcome back, {{ $institution->name }}</h1>
                <p class="text-xs text-zinc-500 mt-1">{{ $institution->county?->name }} County · Global marketplace exporter</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @php
                $revGrowth = $revenue > 0 ? round((($revenue - $revenuePrevMonth) / max($revenue, 1)) * 100, 2) : 0;
                @endphp
                <x-inst-kpi title="Total Revenue" :value="'KES ' . number_format($revenue)" :growth="($revGrowth >= 0 ? '+' : '') . number_format($revGrowth, 2) . '%'" label="vs prev. month" accent />
                <x-inst-kpi title="Total Orders" :value="number_format($totalOrders)" :growth="'+' . number_format($orderGrowth, 2) . '%'" label="last 30 days" accent />
                <x-inst-kpi title="Marketplace Products" :value="number_format($marketplaceProducts->count())" :growth="'+' . $marketplaceProducts->where('created_at', '>=', now()->subDays(30))->count() . ' new'" label="last 30 days" accent />
                <x-inst-kpi title="Videos" :value="number_format($videos->count())" :growth="$institution->synced_at ? 'Synced ' . $institution->synced_at->diffForHumans() : 'Not synced'" label="auto-published" accent />
            </div>

            {{-- Chart --}}
            <div class="bg-[#18181B] border border-zinc-800/80 rounded-xl p-5 space-y-4">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <div>
                        <span class="text-xs text-zinc-400 font-medium tracking-wider uppercase">Sales Trend</span>
                        <div class="flex items-center gap-4 mt-1">
                            <span class="text-2xl font-bold">KES {{ number_format($monthly->sum('new')) }}</span>
                            <div class="flex items-center gap-3 text-xs">
                                <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-[#FFCD05]"></span>{{ $institution->name }}</span>
                                <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-zinc-600"></span>Other Sellers</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-1 bg-[#121212] p-1 rounded-lg border border-zinc-800 text-xs">
                        <button class="px-3 py-1 text-zinc-400 hover:text-white" onclick="setRange(this,'weekly')">Weekly</button>
                        <button class="px-3 py-1 bg-zinc-800 text-[#FFCD05] rounded font-medium" onclick="setRange(this,'monthly')">Monthly</button>
                        <button class="px-3 py-1 text-zinc-400 hover:text-white" onclick="setRange(this,'yearly')">Yearly</button>
                    </div>
                </div>
                <div class="h-48 pt-6 flex items-end justify-between gap-2 border-b border-zinc-800 pb-2">
                    @php $maxVal = max($monthly->max('new'), $monthly->max('existing'), 1); @endphp
                    @foreach($monthly as $m)
                    <div class="flex-1 flex flex-col items-center gap-2 h-full justify-end group relative" title="{{ $m['month'] }}: {{ $institution->name }} KES {{ number_format($m['new']) }} · Others KES {{ number_format($m['existing']) }}">
                        <div class="w-full flex items-end justify-center gap-1 h-32">
                            <div class="w-2 bg-[#FFCD05] rounded-t transition-all" style="height: {{ max($m['new'] / $maxVal * 100, 2) }}%"></div>
                            <div class="w-2 bg-zinc-700 rounded-t transition-all" style="height: {{ max($m['existing'] / $maxVal * 100, 2) }}%"></div>
                        </div>
                        <span class="text-[10px] text-zinc-500">{{ $m['month'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Recent transactions --}}
            <div class="bg-[#18181B] border border-zinc-800/80 rounded-xl p-5 space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-zinc-400 font-medium tracking-wider uppercase">Recent Transactions</span>
                    <a href="{{ route('institution.admin', [$institution->slug, 'tab' => 'transactions']) }}" class="text-[11px] text-[#FFCD05] hover:underline">View all →</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-zinc-300">
                        <thead class="text-zinc-500 border-b border-zinc-800 uppercase text-[10px]">
                            <tr>
                                <th class="pb-3">ID</th>
                                <th class="pb-3">Customer</th>
                                <th class="pb-3">Product</th>
                                <th class="pb-3">Status</th>
                                <th class="pb-3">Qty</th>
                                <th class="pb-3 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800/50">
                            @forelse($transactions as $tx)
                            <tr>
                                <td class="py-3 font-mono text-zinc-400">#{{ $tx->order_number }}</td>
                                <td class="py-3">{{ $tx->customer_name ?? 'Guest' }}</td>
                                <td class="py-3 text-zinc-400 truncate max-w-[200px]">{{ $tx->product_name }}</td>
                                <td class="py-3">
                                    @php
                                    $cls = match($tx->payment_status) {
                                        'paid', 'released', 'success', 'completed' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                        'pending', 'held' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                        'failed', 'cancelled', 'refunded' => 'bg-red-500/10 text-red-400 border-red-500/20',
                                        default => 'bg-zinc-500/10 text-zinc-400 border-zinc-500/20'
                                    };
                                    @endphp
                                    <span class="px-2 py-0.5 rounded-full border text-[10px] {{ $cls }}">{{ ucfirst($tx->payment_status ?? 'pending') }}</span>
                                </td>
                                <td class="py-3">{{ $tx->quantity }}</td>
                                <td class="py-3 text-right font-medium">KES {{ number_format($tx->total) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="py-10 text-center text-zinc-500">No transactions yet — sync your products to start selling.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- ═══════ PRODUCTS ═══════ --}}
            @if($tab === 'products')
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold">Products</h1>
                    <p class="text-xs text-zinc-500 mt-1">Synced live to the county portal + global marketplace</p>
                </div>
            </div>
            <div class="bg-[#18181B] border border-zinc-800/80 rounded-xl p-5">
                <form method="POST" action="{{ route('institution.admin.products.store', $institution->slug) }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-5">
                    @csrf
                    <input name="name" required placeholder="Product name *" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]">
                    <input name="price" type="number" min="0" step="0.01" required placeholder="Price (KES) *" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]">
                    <input name="unit" placeholder="Unit (kg, 500ml, piece)" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]">
                    <input name="category" placeholder="Category (Beverage, Food...)" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]">
                    <input name="stock" type="number" min="0" placeholder="Stock" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]">
                    <input name="image" type="file" accept="image/*" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-400">
                    <textarea name="description" rows="2" placeholder="Description" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05] md:col-span-2"></textarea>
                    <button class="bg-[#FFCD05] text-black text-xs font-bold px-4 py-2 rounded-lg hover:bg-[#e6b904] transition h-fit">+ Add & Sync</button>
                </form>
                <table class="w-full text-left text-xs text-zinc-300">
                    <thead class="text-zinc-500 border-b border-zinc-800 uppercase text-[10px]">
                        <tr>
                            <th class="pb-3">Product</th>
                            <th class="pb-3">Price</th>
                            <th class="pb-3">Unit</th>
                            <th class="pb-3">Marketplace</th>
                            <th class="pb-3">County Page</th>
                            <th class="pb-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-800/50">
                        @forelse($marketplaceProducts as $i => $p)
                        <tr>
                            <td class="py-3 font-medium">{{ $p->name }}</td>
                            <td class="py-3 text-[#FFCD05] font-semibold">KES {{ number_format($p->variants->min('price') ?? 0) }}</td>
                            <td class="py-3 text-zinc-400">{{ $p->unit }}</td>
                            <td class="py-3"><span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px]">LIVE</span></td>
                            <td class="py-3">
                                @if($countyProducts->where('name', $p->name)->first())
                                <span class="px-2 py-0.5 rounded-full bg-[#FFCD05]/10 text-[#FFCD05] border border-[#FFCD05]/20 text-[10px]">LIVE</span>
                                @else
                                <span class="px-2 py-0.5 rounded-full bg-zinc-500/10 text-zinc-400 border border-zinc-500/20 text-[10px]">—</span>
                                @endif
                            </td>
                            <td class="py-3 text-right">
                                <form method="POST" action="{{ route('institution.admin.products.delete', [$institution->slug, $i]) }}" onsubmit="return confirm('Remove product?')" class="inline">
                                    @csrf
                                    <button class="text-[10px] text-red-400 hover:text-red-300">Remove</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="py-10 text-center text-zinc-500">No products yet. Add one above — it goes straight to the marketplace.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @endif

            {{-- ═══════ VIDEOS ═══════ --}}
            @if($tab === 'videos')
            <div>
                <h1 class="text-xl font-semibold">Videos</h1>
                <p class="text-xs text-zinc-500 mt-1">Upload unlimited videos — each with a title & description. They auto-publish to your sector entries.</p>
            </div>
            <div class="bg-[#18181B] border border-zinc-800/80 rounded-xl p-5">
                <form method="POST" action="{{ route('institution.admin.videos.upload', $institution->slug) }}" enctype="multipart/form-data" class="space-y-3 mb-6">
                    @csrf
                    <div class="grid md:grid-cols-2 gap-3">
                        <input name="title" required placeholder="Video title *" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]">
                        <select name="entity_key" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 focus:outline-none focus:border-[#FFCD05]">
                            <option value="">Attach to institution (general)</option>
                            @foreach($sectorEntities as $se)
                            <option value="{{ $se->sector?->slug }}">{{ $se->name }} ({{ $se->sector?->name }})</option>
                            @endforeach
                        </select>
                    </div>
                    <textarea name="description" rows="2" placeholder="Video description" class="w-full bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]"></textarea>
                    <div class="flex items-center gap-3">
                        <input type="file" name="video" accept="video/mp4,video/webm,video/quicktime" required class="text-xs text-zinc-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#FFCD05] file:text-black hover:file:bg-[#e6b904]">
                        <button class="bg-[#FFCD05] text-black text-xs font-bold px-4 py-2 rounded-lg hover:bg-[#e6b904] transition">Upload Video</button>
                    </div>
                </form>

                <div class="grid md:grid-cols-2 gap-4">
                    @forelse($videos as $vi => $v)
                    <div class="bg-[#121212] border border-zinc-800 rounded-xl overflow-hidden">
                        <video controls preload="metadata" class="w-full bg-black aspect-video">
                            <source src="{{ media($v->path ?? $v['path'] ?? '') }}" type="video/mp4">
                        </video>
                        <div class="p-4">
                            <div class="font-semibold text-sm text-zinc-100 truncate">{{ $v->original_name ?? $v['title'] ?? 'Untitled' }}</div>
                            @if(!empty($v['description']))
                            <p class="text-[11px] text-zinc-500 mt-1 line-clamp-2">{{ $v['description'] }}</p>
                            @elseif(!empty($v->metadata['description']))
                            <p class="text-[11px] text-zinc-500 mt-1 line-clamp-2">{{ $v->metadata['description'] }}</p>
                            @endif
                            <div class="flex items-center justify-between mt-3">
                                <span class="text-[10px] text-zinc-500">{{ $v->slot ?? 'institution_video' }}</span>
                                <form method="POST" action="{{ route('institution.admin.videos.delete', [$institution->slug, $vi]) }}" onsubmit="return confirm('Delete this video?')">
                                    @csrf
                                    <button class="text-[10px] text-red-400 hover:text-red-300">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="md:col-span-2 text-center py-12 text-zinc-500 text-sm">No videos yet. Upload your production clips, tours, or product demos.</div>
                    @endforelse
                </div>
            </div>
            @endif

            {{-- ═══════ TRANSACTIONS ═══════ --}}
            @if($tab === 'transactions')
            <div>
                <h1 class="text-xl font-semibold">Transactions</h1>
                <p class="text-xs text-zinc-500 mt-1">All orders for your products</p>
            </div>
            <div class="bg-[#18181B] border border-zinc-800/80 rounded-xl p-5">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-zinc-300">
                        <thead class="text-zinc-500 border-b border-zinc-800 uppercase text-[10px]">
                            <tr>
                                <th class="pb-3">ID</th>
                                <th class="pb-3">Customer</th>
                                <th class="pb-3">Product</th>
                                <th class="pb-3">Status</th>
                                <th class="pb-3">Qty</th>
                                <th class="pb-3 text-right">Amount</th>
                                <th class="pb-3">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800/50">
                            @forelse($transactions as $tx)
                            <tr>
                                <td class="py-3 font-mono text-zinc-400">#{{ $tx->order_number }}</td>
                                <td class="py-3">{{ $tx->customer_name ?? 'Guest' }}</td>
                                <td class="py-3 text-zinc-400 truncate max-w-[220px]">{{ $tx->product_name }}</td>
                                <td class="py-3">
                                    @php
                                    $cls = match($tx->payment_status) {
                                        'paid', 'released', 'success', 'completed' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                        'pending', 'held' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                        'failed', 'cancelled', 'refunded' => 'bg-red-500/10 text-red-400 border-red-500/20',
                                        default => 'bg-zinc-500/10 text-zinc-400 border-zinc-500/20'
                                    };
                                    @endphp
                                    <span class="px-2 py-0.5 rounded-full border text-[10px] {{ $cls }}">{{ ucfirst($tx->payment_status ?? 'pending') }}</span>
                                </td>
                                <td class="py-3">{{ $tx->quantity }}</td>
                                <td class="py-3 text-right font-medium">KES {{ number_format($tx->total) }}</td>
                                <td class="py-3 text-zinc-500">{{ \Carbon\Carbon::parse($tx->placed_at)->format('d M Y') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="py-10 text-center text-zinc-500">No transactions yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- ═══════ CUSTOMERS ═══════ --}}
            @if($tab === 'customers')
            <div>
                <h1 class="text-xl font-semibold">Customer List</h1>
                <p class="text-xs text-zinc-500 mt-1">Buyers who purchased your products</p>
            </div>
            <div class="bg-[#18181B] border border-zinc-800/80 rounded-xl p-5">
                <table class="w-full text-left text-xs text-zinc-300">
                    <thead class="text-zinc-500 border-b border-zinc-800 uppercase text-[10px]">
                        <tr>
                            <th class="pb-3">Customer</th>
                            <th class="pb-3">Contact</th>
                            <th class="pb-3">Orders</th>
                            <th class="pb-3 text-right">Total Spent</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-800/50">
                        @forelse($customers as $c)
                        <tr>
                            <td class="py-3 font-medium">{{ $c->name }}</td>
                            <td class="py-3 text-zinc-400">{{ $c->email ?? $c->phone }}</td>
                            <td class="py-3">{{ $c->orders_count }}</td>
                            <td class="py-3 text-right font-medium text-[#FFCD05]">KES {{ number_format($c->total_spent) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="py-10 text-center text-zinc-500">No customers yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @endif

            {{-- ═══════ PRODUCTION CHAIN ═══════ --}}
            @if($tab === 'production')
            <div>
                <h1 class="text-xl font-semibold">Production Chain</h1>
                <p class="text-xs text-zinc-500 mt-1">Stage-by-stage story of how your products are made</p>
            </div>
            <div class="bg-[#18181B] border border-zinc-800/80 rounded-xl p-5">
                <form method="POST" action="{{ route('institution.admin.production', $institution->slug) }}" class="space-y-3">
                    @csrf
                    <div id="chain-rows">
                        @forelse(($institution->production_chain ?? []) as $ci => $step)
                        <div class="grid md:grid-cols-3 gap-3 mb-3 chain-row">
                            <input name="production_chain[{{ $ci }}][step]" value="{{ $step['step'] }}" placeholder="Stage name (e.g. Harvesting)" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]">
                            <textarea name="production_chain[{{ $ci }}][description]" rows="1" placeholder="What happens at this stage?" class="md:col-span-2 bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]">{{ $step['description'] }}</textarea>
                        </div>
                        @empty
                        <div class="grid md:grid-cols-3 gap-3 mb-3 chain-row">
                            <input name="production_chain[0][step]" placeholder="Stage name (e.g. Seedling)" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]">
                            <textarea name="production_chain[0][description]" rows="1" placeholder="What happens at this stage?" class="md:col-span-2 bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]"></textarea>
                        </div>
                        @endforelse
                    </div>
                    <button type="button" onclick="addChainRow()" class="text-[11px] text-[#FFCD05] border border-[#FFCD05]/30 px-3 py-1.5 rounded-lg hover:bg-[#FFCD05]/10">+ Add stage</button>
                    <button class="bg-[#FFCD05] text-black text-xs font-bold px-5 py-2 rounded-lg hover:bg-[#e6b904] transition block mt-4">Save Production Chain</button>
                </form>
            </div>
            @endif

            {{-- ═══════ SECTOR MAPPING ═══════ --}}
            @if($tab === 'sectors')
            <div>
                <h1 class="text-xl font-semibold">Sector Mapping</h1>
                <p class="text-xs text-zinc-500 mt-1">Which sectors your institution spans — one org can populate multiple sectors</p>
            </div>
            <div class="bg-[#18181B] border border-zinc-800/80 rounded-xl p-5">
                @php $availableSectors = $institution->county?->sectors ?? collect(); @endphp
                <form method="POST" action="{{ route('institution.admin.sectors', $institution->slug) }}" class="space-y-3">
                    @csrf
                    <div id="sector-rows">
                        @forelse(($institution->sector_mappings ?? []) as $si => $m)
                        <div class="grid md:grid-cols-5 gap-3 mb-3 sector-row">
                            <select name="sector_mappings[{{ $si }}][sector_slug]" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 focus:outline-none focus:border-[#FFCD05] md:col-span-1">
                                @foreach($availableSectors as $s)
                                <option value="{{ $s->slug }}" @selected($m['sector_slug'] === $s->slug)>{{ $s->name }}</option>
                                @endforeach
                            </select>
                            <input name="sector_mappings[{{ $si }}][entry_name]" value="{{ $m['entry_name'] }}" placeholder="Entry name (e.g. Macadamia Plantation Tour)" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]">
                            <input name="sector_mappings[{{ $si }}][entry_type]" value="{{ $m['entry_type'] }}" placeholder="Type (tour, farm, shop)" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]">
                            <input name="sector_mappings[{{ $si }}][entry_fee]" value="{{ $m['entry_fee'] }}" type="number" min="0" placeholder="Entry fee KES" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]">
                            <input name="sector_mappings[{{ $si }}][location]" value="{{ $m['location'] ?? '' }}" placeholder="Location" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]">
                            <textarea name="sector_mappings[{{ $si }}][description]" rows="1" placeholder="Entry description" class="md:col-span-5 bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]">{{ $m['description'] ?? '' }}</textarea>
                        </div>
                        @empty
                        <div class="grid md:grid-cols-5 gap-3 mb-3 sector-row">
                            <select name="sector_mappings[0][sector_slug]" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 focus:outline-none focus:border-[#FFCD05]">
                                @foreach($availableSectors as $s)
                                <option value="{{ $s->slug }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                            <input name="sector_mappings[0][entry_name]" placeholder="Entry name (e.g. Macadamia Plantation Tour)" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]">
                            <input name="sector_mappings[0][entry_type]" placeholder="Type (tour, farm, shop)" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]">
                            <input name="sector_mappings[0][entry_fee]" type="number" min="0" placeholder="Entry fee KES" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]">
                            <input name="sector_mappings[0][location]" placeholder="Location" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]">
                            <textarea name="sector_mappings[0][description]" rows="1" placeholder="Entry description" class="md:col-span-5 bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]"></textarea>
                        </div>
                        @endforelse
                    </div>
                    <button type="button" onclick="addSectorRow()" class="text-[11px] text-[#FFCD05] border border-[#FFCD05]/30 px-3 py-1.5 rounded-lg hover:bg-[#FFCD05]/10">+ Add sector mapping</button>
                    <button class="bg-[#FFCD05] text-black text-xs font-bold px-5 py-2 rounded-lg hover:bg-[#e6b904] transition block mt-4">Save Mappings</button>
                </form>
            </div>
            @endif

            {{-- ═══════ PROFILE ═══════ --}}
            @if($tab === 'profile')
            <div>
                <h1 class="text-xl font-semibold">Institution Profile</h1>
                <p class="text-xs text-zinc-500 mt-1">Your public-facing identity</p>
            </div>
            <div class="grid lg:grid-cols-3 gap-4">
                <div class="bg-[#18181B] border border-zinc-800/80 rounded-xl p-5 lg:col-span-2">
                    <form method="POST" action="{{ route('institution.admin.profile', $institution->slug) }}" class="space-y-3">
                        @csrf
                        <div class="grid md:grid-cols-2 gap-3">
                            <input name="name" value="{{ $institution->name }}" required class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 focus:outline-none focus:border-[#FFCD05]">
                            <input name="type" value="{{ $institution->type }}" placeholder="Type (PLC, Farm, Cooperative)" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]">
                            <input name="headquarters" value="{{ $institution->headquarters }}" placeholder="Headquarters" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]">
                            <input name="founded_year" value="{{ $institution->founded_year }}" type="number" placeholder="Founded year" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]">
                            <input name="phone" value="{{ $institution->phone }}" placeholder="Phone" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]">
                            <input name="email" value="{{ $institution->email }}" type="email" placeholder="Email" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]">
                            <input name="website" value="{{ $institution->website }}" placeholder="Website" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]">
                            <input name="location" value="{{ $institution->location }}" placeholder="Location" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]">
                            <input name="lat" value="{{ $institution->lat }}" step="0.000001" placeholder="Latitude" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]">
                            <input name="lng" value="{{ $institution->lng }}" step="0.000001" placeholder="Longitude" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]">
                        </div>
                        <textarea name="description" rows="3" placeholder="Short description" class="w-full bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]">{{ $institution->description }}</textarea>
                        <textarea name="story" rows="6" placeholder="Full story — the complete writeup about your institution" class="w-full bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]">{{ $institution->story }}</textarea>
                        <button class="bg-[#FFCD05] text-black text-xs font-bold px-5 py-2 rounded-lg hover:bg-[#e6b904] transition">Save Profile</button>
                    </form>
                </div>
                <div class="bg-[#18181B] border border-zinc-800/80 rounded-xl p-5 h-fit">
                    <h3 class="text-xs font-semibold text-zinc-300 mb-3">Logo & Cover</h3>
                    @if($institution->logo_url)
                    <img src="{{ $institution->logo_url }}" class="w-24 h-24 rounded-xl object-cover mb-3 bg-[#121212]">
                    @endif
                    @if($institution->cover_image_url)
                    <img src="{{ $institution->cover_image_url }}" class="w-full h-32 object-cover rounded-xl mb-3 bg-[#121212]">
                    @endif
                    <form method="POST" action="{{ route('institution.admin.logo', $institution->slug) }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <input type="file" name="logo" accept="image/*" class="text-xs text-zinc-400 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-[10px] file:font-bold file:bg-[#FFCD05] file:text-black">
                        <input type="file" name="cover" accept="image/*" class="text-xs text-zinc-400 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-[10px] file:font-bold file:bg-zinc-700 file:text-white">
                        <button class="w-full bg-[#FFCD05] text-black text-xs font-bold px-4 py-2 rounded-lg hover:bg-[#e6b904] transition">Upload Images</button>
                    </form>
                </div>
            </div>
            @endif

            {{-- ═══════ TEAM ═══════ --}}
            @if($tab === 'team')
            <div>
                <h1 class="text-xl font-semibold">Team Members</h1>
                <p class="text-xs text-zinc-500 mt-1">People who can manage {{ $institution->name }}</p>
            </div>
            <div class="bg-[#18181B] border border-zinc-800/80 rounded-xl p-5 max-w-2xl">
                <form method="POST" action="{{ route('institution.admin.team.add', $institution->slug) }}" class="grid md:grid-cols-3 gap-3 mb-5">
                    @csrf
                    <input name="name" required placeholder="Full name *" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]">
                    <input name="email" type="email" required placeholder="Email *" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]">
                    <button class="bg-[#FFCD05] text-black text-xs font-bold px-4 py-2 rounded-lg hover:bg-[#e6b904] transition">+ Add Member</button>
                </form>
                <div class="space-y-2">
                    @php $team = \App\Models\User::where('institution_id', $institution->id)->get(); @endphp
                    @forelse($team as $member)
                    <div class="flex items-center justify-between py-2 border-b border-zinc-800/50 last:border-0">
                        <div>
                            <div class="font-medium text-xs text-zinc-200">{{ $member->name }}</div>
                            <div class="text-[10px] text-zinc-500">{{ $member->email }}</div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-[#FFCD05]/10 text-[#FFCD05] border border-[#FFCD05]/20">{{ $member->id === auth()->id() ? 'You' : 'Admin' }}</span>
                            @if($member->id !== auth()->id())
                            <form method="POST" action="{{ route('institution.admin.team.remove', [$institution->slug, $member->id]) }}" onsubmit="return confirm('Remove this member?')">
                                @csrf
                                <button class="text-[10px] text-red-400 hover:text-red-300">Remove</button>
                            </form>
                            @endif
                        </div>
                    </div>
                    @empty
                    <p class="text-zinc-500 text-xs py-6 text-center">No team members added.</p>
                    @endforelse
                </div>
            </div>
            @endif
        </div>
    </main>
</div>

<script>
function addChainRow() {
    var rows = document.getElementById('chain-rows');
    var count = rows.querySelectorAll('.chain-row').length;
    var div = document.createElement('div');
    div.className = 'grid md:grid-cols-3 gap-3 mb-3 chain-row';
    div.innerHTML = '<input name="production_chain[' + count + '][step]" placeholder="Stage name" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]">' +
        '<textarea name="production_chain[' + count + '][description]" rows="1" placeholder="What happens at this stage?" class="md:col-span-2 bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]"></textarea>';
    rows.appendChild(div);
}
function addSectorRow() {
    var rows = document.getElementById('sector-rows');
    var count = rows.querySelectorAll('.sector-row').length;
    var select = rows.querySelector('select');
    var options = select ? select.innerHTML : '';
    var div = document.createElement('div');
    div.className = 'grid md:grid-cols-5 gap-3 mb-3 sector-row';
    div.innerHTML = '<select name="sector_mappings[' + count + '][sector_slug]" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 focus:outline-none focus:border-[#FFCD05]">' + options + '</select>' +
        '<input name="sector_mappings[' + count + '][entry_name]" placeholder="Entry name" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]">' +
        '<input name="sector_mappings[' + count + '][entry_type]" placeholder="Type" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]">' +
        '<input name="sector_mappings[' + count + '][entry_fee]" type="number" min="0" placeholder="Fee KES" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]">' +
        '<input name="sector_mappings[' + count + '][location]" placeholder="Location" class="bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]">' +
        '<textarea name="sector_mappings[' + count + '][description]" rows="1" placeholder="Entry description" class="md:col-span-5 bg-[#121212] border border-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-[#FFCD05]"></textarea>';
    rows.appendChild(div);
}
function setRange(btn, range) {
    var parent = btn.parentElement;
    parent.querySelectorAll('button').forEach(function(b) {
        b.className = 'px-3 py-1 text-zinc-400 hover:text-white';
    });
    btn.className = 'px-3 py-1 bg-zinc-800 text-[#FFCD05] rounded font-medium';
}
// Live search filter
var search = document.getElementById('inst-search');
if (search) {
    search.addEventListener('input', function() {
        var q = this.value.toLowerCase();
        document.querySelectorAll('table tbody tr, .grid .group').forEach(function(el) {
            el.style.display = el.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
}
</script>
@endsection