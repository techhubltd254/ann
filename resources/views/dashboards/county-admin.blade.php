@extends('layouts.nexora')

@section('title', $county->name . ' County — Nexora Control')

@php $accent = '#6366F1'; @endphp

@section('content')
<div class="flex h-screen overflow-hidden" x-data="{ 
    tab: '{{ $tab ?? 'overview' }}', 
    search: '',
    setTab(t) { this.tab = t; },
}">

    {{-- ═══════ SIDEBAR ═══════ --}}
    <aside class="glass-nav flex flex-col w-64 shrink-0 z-30 overflow-y-auto">
        <div class="flex items-center gap-3 h-16 px-5 border-b border-white/5 shrink-0">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center font-black text-white text-sm shadow-lg shadow-indigo-500/25">M</div>
            <div>
                <div class="text-white font-bold text-sm leading-tight">{{ $county->name }}</div>
                <div class="text-indigo-400 text-[9px] font-bold tracking-[0.15em] uppercase">County Admin · KICC</div>
            </div>
        </div>

        <div class="flex-1 px-3 py-4 space-y-5 scrollbar-hide">
            @php
            $groups = [
                'Overview' => [
                    ['tab' => 'overview', 'label' => 'Dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                    ['tab' => 'details', 'label' => 'General Info', 'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ['tab' => 'analytics', 'label' => 'Analytics', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                    ['tab' => 'content', 'label' => 'Content CMS', 'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
                ],
                'Tourism & Hospitality' => [
                    ['tab' => 'attractions_list', 'label' => 'Attractions & Sites', 'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
                    ['tab' => 'hotels', 'label' => 'Hotels & Stays', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                    ['tab' => 'marketplace', 'label' => 'Marketplace', 'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
                ],
                'Immersive Media' => [
                    ['tab' => 'videos4d', 'label' => '4D Gaussian Studio', 'icon' => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z'],
                    ['tab' => 'hero', 'label' => 'Hero Videos', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                    ['tab' => 'images', 'label' => 'Sector Videos', 'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
                ],
                'Operations' => [
                    ['tab' => 'prices', 'label' => 'Pricing Engine', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1'],
                    ['tab' => 'ads', 'label' => 'Advertising', 'icon' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z'],
                    ['tab' => 'packages', 'label' => 'Subscriptions', 'icon' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z'],
                    ['tab' => 'reports', 'label' => 'Reports & Exports', 'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                ],
                'Administration' => [
                    ['tab' => 'institutions', 'label' => 'Institutions', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                    ['tab' => 'sectors', 'label' => 'Sectors', 'icon' => 'M4 6h16M4 10h16M4 14h16M4 18h16'],
                ],
            ];
            @endphp

            @foreach($groups as $groupName => $items)
            <div class="space-y-0.5">
                <p class="px-3 text-[9px] font-semibold text-zinc-500 uppercase tracking-[0.15em] mb-1.5">{{ $groupName }}</p>
                @foreach($items as $item)
                <a href="{{ route('county.admin.pro', [$county->slug, 'tab' => $item['tab']]) }}"
                   class="sidebar-link text-xs {{ $tab === $item['tab'] ? 'sidebar-link-active' : 'sidebar-link-inactive' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/></svg>
                    <span>{{ $item['label'] }}</span>
                </a>
                @endforeach
            </div>
            @endforeach

            {{-- Plan card --}}
            <div class="pt-3">
                <div class="glass rounded-xl p-4 bg-gradient-to-br from-indigo-500/5 to-violet-600/5 border border-indigo-500/10">
                    <div class="text-xs font-semibold text-white mb-1">County Enterprise</div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-[10px] text-indigo-400 font-medium">Free Tier</span>
                        <span class="text-[10px] text-zinc-600">·</span>
                        <span class="text-[10px] text-zinc-500">4/12 GB used</span>
                    </div>
                    <div class="progress-bar mb-3">
                        <div class="progress-fill bg-gradient-to-r from-indigo-500 to-violet-600" style="width:33%"></div>
                    </div>
                    <button class="w-full py-1.5 rounded-lg text-[10px] font-bold text-white bg-gradient-to-r from-indigo-500 to-violet-600 hover:from-indigo-400 hover:to-violet-500 transition">Upgrade →</button>
                </div>
            </div>

            {{-- Bottom links --}}
            <div class="pt-2 space-y-0.5 border-t border-white/5">
                <a href="{{ route('counties.show', $county->slug) }}" class="sidebar-link sidebar-link-inactive text-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    <span>View Public Portal</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="sidebar-link sidebar-link-inactive w-full text-xs"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg><span>Logout</span></button></form>
            </div>
        </div>
    </aside>

    {{-- ═══════ MAIN VIEWPORT ═══════ --}}
    <div class="flex-1 flex flex-col overflow-hidden">
        {{-- Header --}}
        <header class="glass-header h-16 px-6 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-4">
                <button @click="sidebarOpen = !sidebarOpen" class="text-zinc-400 hover:text-white p-1 hidden lg:block">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="flex items-center gap-2 text-xs text-zinc-500">
                    <span>{{ $county->name }}</span>
                    <span>/</span>
                    <span class="text-indigo-400 font-medium" x-text="tab.charAt(0).toUpperCase()+tab.slice(1).replace(/_/g,' ')"></span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                {{-- Search --}}
                <div class="relative hidden md:block">
                    <svg class="absolute left-2.5 top-2 text-zinc-500" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" placeholder="Search attractions, products..." class="w-52 h-8 pl-8 pr-3 text-xs rounded-lg bg-white/5 border border-white/10 text-zinc-300 placeholder-zinc-500 focus:border-indigo-500/40 focus:ring-1 focus:ring-indigo-500/20 outline-none">
                </div>
                @if(session('success'))
                <span class="text-[10px] text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-2.5 py-1 rounded-lg hidden sm:block">{{ session('success') }}</span>
                @endif
                {{-- Quick actions --}}
                <button class="btn-primary text-[10px] h-8 px-3 py-0 hidden md:inline-flex">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    New
                </button>
                {{-- Notifications --}}
                <button class="relative text-zinc-400 hover:text-white p-1.5">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <span class="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full bg-indigo-500 border border-[#0D0F12]"></span>
                </button>
                {{-- User --}}
                <div class="flex items-center gap-2.5">
                    <div class="text-right hidden sm:block">
                        <div class="text-xs font-medium text-zinc-200">{{ Auth::user()?->name ?? 'Admin' }}</div>
                        <div class="text-[9px] text-zinc-500">{{ $county->name }}</div>
                    </div>
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white font-bold text-xs shadow-lg shadow-indigo-500/20">{{ strtoupper(substr(Auth::user()?->name ?? 'A', 0, 1)) }}</div>
                </div>
            </div>
        </header>

        {{-- Content area --}}
        <main class="flex-1 overflow-y-auto p-6 scrollbar-hide">
            @if($errors->any())
            <div class="mb-4 px-5 py-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm flex items-center gap-2">{{ $errors->first() }}</div>
            @endif

            {{-- ═══════ OVERVIEW — Executive Dashboard ═══════ --}}
            @if($tab === 'overview')
            <div class="space-y-6">
                {{-- 8-card KPI grid --}}
                <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3.5">
                    <x-nexora-kpi title="County Products" :value="$stats['products'] . ' Active'" :growth="($stats['products_new'] ?? 0) . ' new this month'" color="indigo" :sparkline="[8,9,9,10,11,11,11,12,12,13,13,13]" />
                    <x-nexora-kpi title="Attractions" :value="$stats['attractions'] . ' Sites'" :growth="($stats['top_attractions'] ?? '') ? 'Featuring ' . $stats['top_attractions'] : 'Explore county sites'" color="cyan" :sparkline="[5,5,6,6,6,7,7,7,8,8,9,9]" />
                    <x-nexora-kpi title="Hotels" :value="$stats['hotels'] . ' Partners'" :growth="($stats['hotel_rating'] ?? 0) > 0 ? 'Avg. rating ' . $stats['hotel_rating'] . '★' : 'Hospitality partners'" color="emerald" :sparkline="[2,2,3,3,3,3,4,4,4,4,4,4]" />
                    <x-nexora-kpi title="Marketplace" :value="$stats['marketplaceProducts'] . ' Active'" :growth="($stats['marketplace_new'] ?? 0) . ' new listings'" color="amber" :sparkline="[10,11,12,13,14,15,16,16,17,17,18,18]" />
                    <x-nexora-kpi title="Orders" :value="$stats['orders']" :growth="$stats['orders'] . ' lifetime orders'" color="emerald" :sparkline="[80,95,102,110,125,130,128,135,140,138,142,142]" />
                    <x-nexora-kpi title="Total Revenue" :value="'KES ' . number_format($stats['revenue'])" :growth="$stats['revenue'] > 0 ? 'Real escrow releases' : 'Awaiting first sale'" color="indigo" :sparkline="[120,145,160,180,220,250,280,310,350,380,420,480]" />
                    <x-nexora-kpi title="Sector Images" :value="$stats['sector_images'] . ' Managed'" growth="Media library" color="violet" :sparkline="[40,55,70,85,95,105,110,115,120,124,127,128]" />
                    <x-nexora-kpi title="Packages" :value="$stats['packages'] . ' Tiers'" :growth="$stats['institutions'] . ' institutions · ' . $stats['sector_entities'] . ' entities'" color="rose" :sparkline="[1,1,1,2,2,2,3,3,3,4,4,4]" />
                </div>

                {{-- Middle section: chart + donut + quick actions --}}
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    {{-- Revenue chart --}}
                    <div class="lg:col-span-2 glass-card rounded-2xl p-5">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <span class="text-[10px] font-semibold text-zinc-500 uppercase tracking-widest">Revenue Performance</span>
                                <h3 class="text-lg font-bold text-white mt-1">KES {{ number_format($stats['revenue']) }}</h3>
                            </div>
                            <div class="flex gap-1 bg-white/5 p-0.5 rounded-lg">
                                <button class="px-2.5 py-1 text-[10px] text-zinc-400 hover:text-white rounded-md">1M</button>
                                <button class="px-2.5 py-1 text-[10px] bg-indigo-500/20 text-indigo-400 rounded-md font-medium">3M</button>
                                <button class="px-2.5 py-1 text-[10px] text-zinc-400 hover:text-white rounded-md">6M</button>
                                <button class="px-2.5 py-1 text-[10px] text-zinc-400 hover:text-white rounded-md">1Y</button>
                            </div>
                        </div>
                        <canvas id="revenueChart" style="max-height:200px; width:100%;"></canvas>
                    </div>

                    {{-- Sector distribution --}}
                    <div class="glass-card rounded-2xl p-5" style="max-height:380px; overflow:hidden;">
                        <span class="text-[10px] font-semibold text-zinc-500 uppercase tracking-widest">Sector Distribution</span>
                        <div style="height:220px; position:relative;">
                        <canvas id="sectorChart" style="max-height:220px;"></canvas>
                        </div>
                        <div class="grid grid-cols-2 gap-2 mt-4">
                            @foreach([['Hospitality',35,'#6366F1'],['Eco-Tourism',28,'#10B981'],['Agri-Trade',22,'#F59E0B'],['Cultural',15,'#8B5CF6']] as $s)
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full shrink-0" style="background:{{ $s[2] }}"></span>
                                <span class="text-[10px] text-zinc-400">{{ $s[0] }} <span class="text-zinc-600">{{ $s[1] }}%</span></span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Quick action pills --}}
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('county.admin.pro', [$county->slug, 'tab' => 'content']) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white/5 border border-white/10 text-zinc-300 hover:bg-indigo-500/10 hover:border-indigo-500/30 hover:text-indigo-400 text-xs font-medium transition-all">✏️ Edit Content</a>
                    <a href="{{ route('county.admin.pro', [$county->slug, 'tab' => 'images']) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white/5 border border-white/10 text-zinc-300 hover:bg-indigo-500/10 hover:border-indigo-500/30 hover:text-indigo-400 text-xs font-medium transition-all">🖼️ Media Manager</a>
                    <a href="{{ route('county.admin.pro', [$county->slug, 'tab' => 'prices']) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white/5 border border-white/10 text-zinc-300 hover:bg-indigo-500/10 hover:border-indigo-500/30 hover:text-indigo-400 text-xs font-medium transition-all">💰 Set Prices</a>
                    <a href="{{ route('county.admin.pro', [$county->slug, 'tab' => 'ads']) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white/5 border border-white/10 text-zinc-300 hover:bg-indigo-500/10 hover:border-indigo-500/30 hover:text-indigo-400 text-xs font-medium transition-all">📢 Advertise</a>
                    <a href="{{ route('county.admin.pro', [$county->slug, 'tab' => 'reports']) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white/5 border border-white/10 text-zinc-300 hover:bg-indigo-500/10 hover:border-indigo-500/30 hover:text-indigo-400 text-xs font-medium transition-all">📊 Reports</a>
                </div>

                {{-- Data tables --}}
                <div class="glass-card rounded-2xl overflow-hidden">
                    <div class="flex items-center gap-1 px-5 pt-4 pb-0">
                        @foreach(['Top Attractions','Hotels','Products','Orders'] as $ti)
                        <button class="px-3 py-1.5 text-[10px] rounded-lg font-medium transition {{ $loop->first ? 'bg-indigo-500/15 text-indigo-400' : 'text-zinc-500 hover:text-zinc-300' }}">{{ $ti }}</button>
                        @endforeach
                        <span class="flex-1"></span>
                        <a href="{{ route('county.admin.pro', [$county->slug, 'tab' => 'reports']) }}" class="text-[10px] text-indigo-400 hover:text-indigo-300">View All →</a>
                    </div>
                    <div class="p-5">
                        <table class="w-full text-xs">
                            <thead><tr class="text-zinc-500 border-b border-white/5">
                                <th class="text-left py-3 font-semibold pr-3">Entity</th>
                                <th class="text-left py-3 font-semibold pr-3">Category</th>
                                <th class="text-left py-3 font-semibold pr-3">Status</th>
                                <th class="text-right py-3 font-semibold">Price</th>
                            </tr></thead>
                            <tbody class="divide-y divide-white/5">
                                @foreach(fn() => $attractions->take(5) ?? []() as $a)
                                @php break; @endphp
                                @endforeach
                                @forelse($attractions->take(5) as $a)
                                <tr class="hover:bg-white/5 transition cursor-pointer" onclick="openInspector({{ json_encode(['id'=>$a->id,'name'=>$a->name,'category'=>$a->category,'status'=>'Published','price'=>number_format($a->entry_fee ?? 0)]) }})">
                                    <td class="py-3 font-medium text-zinc-200">{{ $a->name }}</td>
                                    <td class="py-3"><span class="text-[10px] px-2 py-0.5 rounded-full bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">{{ $a->category }}</span></td>
                                    <td class="py-3"><span class="text-emerald-400">Published</span></td>
                                    <td class="py-3 text-right font-medium text-zinc-200">KES {{ number_format($a->entry_fee ?? 0) }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="py-8 text-center text-zinc-500">No entities yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ═══════ 4D VIDEOS — Gaussian Studio ═══════ --}}
            @elseif($tab === 'videos4d')
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-bold text-white">4D Gaussian Splatting Studio</h1>
                        <p class="text-zinc-500 text-sm">Volumetric media, cinematic journeys & immersive 3D scenes</p>
                    </div>
                    <button class="btn-primary text-xs">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Process New Media
                    </button>
                </div>

                {{-- Scene grid --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse($video4dMap as $slug => $info)
                    <div class="glass-card rounded-2xl overflow-hidden group">
                        <div class="aspect-video bg-gradient-to-br from-indigo-900/30 to-black flex items-center justify-center relative overflow-hidden">
                            @if($info['video'])
                            <video class="w-full h-full object-cover" controls preload="metadata" poster="">
                                <source src="{{ media($info['video']) }}" type="video/mp4">
                            </video>
                            @else
                            <div class="text-center">
                                <div class="w-12 h-12 rounded-full bg-indigo-500/10 flex items-center justify-center mx-auto mb-2">
                                    <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                </div>
                                <span class="text-[10px] text-zinc-500">No media uploaded</span>
                            </div>
                            @endif
                            <div class="absolute top-2 right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition">
                                <span class="text-[9px] px-1.5 py-0.5 rounded bg-black/70 text-indigo-300 border border-indigo-500/30">4D</span>
                                <span class="text-[9px] px-1.5 py-0.5 rounded bg-black/70 text-emerald-300 border border-emerald-500/30">60fps</span>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="font-semibold text-sm text-zinc-200">{{ $info['name'] }}</div>
                                    <div class="text-[10px] text-zinc-500 mt-0.5">{{ $info['icon'] }} {{ ucfirst($info['entityType']) }} scene</div>
                                </div>
                                @if($info['video'])
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 shrink-0">Live</span>
                                @endif
                            </div>
                            @if(!$info['video'])
                            <form method="POST" action="{{ route('county.admin.4d.upload', $county->slug) }}" enctype="multipart/form-data" class="mt-3 p-2 border border-dashed border-white/10 rounded-lg hover:border-indigo-500/30 transition">
                                @csrf
                                <input type="hidden" name="entity_type" value="{{ $info['entityType'] }}">
                                <input type="hidden" name="entity_id" value="{{ $info['entityId'] }}">
                                <label class="flex items-center gap-2 cursor-pointer justify-center">
                                    <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                    <span class="text-[10px] text-zinc-500">Upload 4D Media</span>
                                    <input type="file" name="video" accept="video/mp4, video/webm" class="hidden" onchange="this.form.submit()">
                                </label>
                            </form>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="lg:col-span-3 glass-card rounded-2xl p-10 text-center">
                        <div class="w-16 h-16 rounded-2xl bg-indigo-500/10 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        </div>
                        <h3 class="text-sm font-semibold text-zinc-200 mb-1">No 4D Media Yet</h3>
                        <p class="text-xs text-zinc-500 mb-4">Upload volumetric scenes of this county's attractions, hotels, and landmarks</p>
                        <button class="btn-primary text-xs">Upload Your First Scene</button>
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- ═══════ EXISTING TABS (inherited from original) ═══════ --}}
            @elseif($tab === 'details')
            <div class="glass-card rounded-2xl p-6">
                <h2 class="text-lg font-bold text-white mb-4">County Details</h2>
                <form method="POST" action="{{ route('county.admin.details', $county->slug) }}" class="space-y-4 max-w-2xl">
                    @csrf
                    @php $fields = ['tagline','capital','population_2024','area_km2','economic_zone','latitude','longitude','warmest_month','coolest_month','rainy_season','dry_season']; @endphp
                    <div class="grid md:grid-cols-2 gap-3">
                        @foreach($fields as $f)
                        <div>
                            <label class="text-[10px] font-semibold text-zinc-500 uppercase tracking-widest mb-1 block">{{ ucwords(str_replace('_',' ',$f)) }}</label>
                            <input name="{{ $f }}" value="{{ $county->$f ?? '' }}" placeholder="{{ ucfirst(str_replace('_',' ',$f)) }}">
                        </div>
                        @endforeach
                    </div>
                    <div>
                        <label class="text-[10px] font-semibold text-zinc-500 uppercase tracking-widest mb-1 block">Description</label>
                        <textarea name="description" rows="4">{{ $county->description }}</textarea>
                    </div>
                    <button class="btn-primary">Save Changes</button>
                </form>
            </div>

            @elseif($tab === 'content')
            <div class="glass-card rounded-2xl p-6 max-w-2xl">
                <h2 class="text-lg font-bold text-white mb-4">Content Editor</h2>
                <form method="POST" action="{{ route('county.admin.content', $county->slug) }}" class="space-y-4">
                    @csrf
                    <div><label class="text-[10px] font-semibold text-zinc-500 uppercase tracking-widest mb-1 block">Tagline</label><input name="tagline" value="{{ $county->tagline }}"></div>
                    <div><label class="text-[10px] font-semibold text-zinc-500 uppercase tracking-widest mb-1 block">Description</label><textarea name="description" rows="5">{{ $county->description }}</textarea></div>
                    <div><label class="text-[10px] font-semibold text-zinc-500 uppercase tracking-widest mb-1 block">Tourism Highlights</label><textarea name="tourism_highlights" rows="3">{{ is_array($county->tourism_highlights) ? implode(', ', $county->tourism_highlights) : $county->tourism_highlights }}</textarea></div>
                    <button class="btn-primary">Save Content</button>
                </form>
            </div>

            @elseif($tab === 'hero')
            <div class="max-w-2xl space-y-4">
                <div class="glass-card rounded-2xl p-6">
                    <h2 class="text-lg font-bold text-white mb-4">Hero Video</h2>
                    @php $heroAsset = \App\Models\MediaAsset::resolveSlot(\App\Models\County::class, $county->id, 'hero_video'); @endphp
                    <div class="bg-black rounded-xl overflow-hidden mb-4 aspect-video">
                        @if($heroAsset)
                        <video autoplay muted loop playsinline class="w-full h-full object-cover" src="{{ $heroAsset->mp4Url() ?? $heroAsset->url() }}"></video>
                        @else
                        <div class="flex items-center justify-center h-full text-zinc-500 text-sm">No hero video set</div>
                        @endif
                    </div>
                    @if($heroAsset)
                    <div class="text-xs text-zinc-400 mb-4">{{ $heroAsset->original_name }} · {{ number_format($heroAsset->size_bytes / 1024 / 1024, 1) }} MB</div>
                    @endif
                    <div class="flex gap-3">
                        <form method="POST" action="{{ route('county.admin.hero.upload', $county->slug) }}" enctype="multipart/form-data" class="flex-1 flex gap-3">
                            @csrf
                            <input type="file" name="video" accept="video/mp4,video/webm" class="flex-1">
                            <button class="btn-primary text-xs">Upload</button>
                        </form>
                        @if($heroAsset)
                        <form method="POST" action="{{ route('county.admin.hero.delete', $county->slug) }}" onsubmit="return confirm('Delete hero video?')">@csrf<button class="btn-danger text-xs">Delete</button></form>
                        @endif
                    </div>
                </div>
            </div>

            @elseif($tab === 'images')
            <div class="glass-card rounded-2xl p-6">
                <h2 class="text-lg font-bold text-white mb-4">Sector Videos &amp; Images</h2>
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach($sectorImages as $sector => $img)
                    @if($sector === 'hero')
                    {{-- Hero Video card — full width, shows current hero video playing --}}
                    <div class="sm:col-span-2 lg:col-span-4 glass rounded-xl overflow-hidden border border-indigo-500/20">
                        <div class="grid md:grid-cols-3 gap-0">
                            <div class="md:col-span-2 bg-black relative min-h-[200px]">
                                @if(!empty($img['video']))
                                <video autoplay muted loop playsinline class="absolute inset-0 w-full h-full object-cover">
                                    <source src="{{ $img['video'] }}" type="video/mp4">
                                </video>
                                <div class="absolute bottom-2 left-3 text-[10px] px-2 py-1 rounded bg-black/70 text-indigo-300 border border-indigo-500/30">🎬 Hero Video Playing</div>
                                @else
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="text-center">
                                        <svg class="w-12 h-12 mx-auto text-zinc-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        <div class="text-sm font-semibold text-zinc-500">No hero video</div>
                                        <div class="text-[10px] text-zinc-600 mt-1">Upload to play on the county homepage</div>
                                    </div>
                                </div>
                                @endif
                            </div>
                            <div class="p-5 flex flex-col justify-center">
                                <div class="text-sm font-bold text-white mb-1">Hero Video</div>
                                <div class="text-[10px] text-zinc-500 mb-3">Plays on the county landing page in a loop</div>
                                <form method="POST" action="{{ route('county.admin.hero.upload', $county->slug) }}" enctype="multipart/form-data" class="mb-2">
                                    @csrf
                                    <label class="flex items-center justify-center h-10 rounded-xl bg-gradient-to-r from-indigo-500 to-violet-600 text-white text-xs font-bold cursor-pointer hover:from-indigo-400 hover:to-violet-500 transition active:scale-95">
                                        <input type="file" name="video" accept="video/mp4,video/webm" class="sr-only" onchange="this.form.submit()">
                                        🎬 Upload &amp; Sync Hero Video
                                    </label>
                                </form>
                                @if(!empty($img['video']))
                                <div class="flex items-center gap-2 text-[10px]">
                                    <span class="text-emerald-400">✅ Live — {{ $img['video_name'] ?? 'Hero video' }}</span>
                                    <form method="POST" action="{{ route('county.admin.hero.delete', $county->slug) }}" onsubmit="return confirm('Delete hero video?')">@csrf<button class="text-red-400 hover:text-red-300 underline ml-2">Delete</button></form>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @else
                    {{-- Sector cards --}}
                    <div class="glass rounded-xl overflow-hidden border border-white/5">
                        <div class="h-32 bg-white/5 overflow-hidden relative">
                            @if(!empty($img['video']))
                            <video autoplay muted loop playsinline class="w-full h-full object-cover" onerror="this.style.display='none'">
                                <source src="{{ $img['video'] }}" type="video/mp4">
                            </video>
                            <div class="absolute top-1.5 right-1.5 text-[9px] px-1.5 py-0.5 rounded bg-black/60 text-indigo-300 border border-indigo-500/30">🎬</div>
                            @elseif($img['exists'])
                            <img src="{{ media($img['path']) }}" class="w-full h-full object-cover">
                            @else
                            <div class="w-full h-full flex items-center justify-center text-zinc-600 text-3xl font-bold">{{ strtoupper($sector[0]) }}</div>
                            @endif
                        </div>
                        <div class="p-3">
                            <div class="text-sm font-semibold text-zinc-200 capitalize">{{ $sector }} @if(!empty($img['video']))<span class="text-[10px] text-indigo-400 ml-1">🎬 Live</span>@endif</div>
                            <div class="flex flex-col gap-1.5 mt-2">
                                {{-- Row 1: Video upload --}}
                                @if($sector !== 'hero')
                                <form method="POST" action="{{ route('county.admin.sector.video.upload', $county->slug) }}" enctype="multipart/form-data" class="flex gap-1.5">
                                    @csrf
                                    <input type="hidden" name="sector" value="{{ $sector }}">
                                    <label class="flex-1 flex items-center justify-center h-7 rounded-lg border border-white/10 text-[10px] font-medium text-zinc-500 cursor-pointer hover:border-indigo-500/40 hover:text-indigo-400 transition">
                                        <input type="file" name="video" accept="video/mp4,video/webm" class="sr-only" onchange="this.form.submit()">
                                        🎬 Upload Video
                                    </label>
                                </form>
                                @endif
                                @if($sector === 'hero')
                                <form method="POST" action="{{ route('county.admin.hero.upload', $county->slug) }}" enctype="multipart/form-data" class="flex gap-1.5">
                                    @csrf
                                    <label class="flex-1 flex items-center justify-center h-7 rounded-lg border border-white/10 text-[10px] font-medium text-zinc-500 cursor-pointer hover:border-indigo-500/40 hover:text-indigo-400 transition">
                                        <input type="file" name="video" accept="video/mp4,video/webm" class="sr-only" onchange="this.form.submit()">
                                        🎬 Upload Hero Video
                                    </label>
                                </form>
                                @if(!empty($img['video']))
                                <form method="POST" action="{{ route('county.admin.hero.delete', $county->slug) }}" onsubmit="return confirm('Delete hero video?')">@csrf<button class="h-7 px-2 rounded-lg border border-red-500/20 text-red-400 text-[10px] font-medium hover:bg-red-500/10">×</button></form>
                                @endif
                                @endif
                                {{-- Row 2: Image upload (fallback poster) --}}
                                @if($sector !== 'hero')
                                <div class="flex gap-1.5">
                                <form method="POST" action="{{ route('county.admin.image.upload', $county->slug) }}" enctype="multipart/form-data" class="flex-1">
                                    @csrf
                                    <input type="hidden" name="sector" value="{{ $sector }}">
                                    <label class="flex items-center justify-center h-7 rounded-lg border border-white/10 text-[10px] font-medium text-zinc-500 cursor-pointer hover:border-indigo-500/40 hover:text-indigo-400 transition"><input type="file" name="image" accept="image/*" class="sr-only" onchange="this.form.submit()">🖼️ Image</label>
                                </form>
                                @if($img['exists'])
                                <form method="POST" action="{{ route('county.admin.image.delete', [$county->slug, $sector]) }}" onsubmit="return confirm('Delete image?')">@csrf<button class="h-7 px-2 rounded-lg border border-red-500/20 text-red-400 text-[10px] font-medium hover:bg-red-500/10">×</button></form>
                                @endif
                                @if(!empty($img['video']))
                                <form method="POST" action="{{ route('county.admin.sector.video.delete', [$county->slug, $sector]) }}" onsubmit="return confirm('Delete video?')">@csrf<button class="h-7 px-2 rounded-lg border border-red-500/20 text-red-400 text-[10px] font-medium hover:bg-red-500/10">✕</button></form>
                                @endif
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>

            @elseif($tab === 'prices')
            <div class="grid lg:grid-cols-2 gap-4">
                <div class="glass-card rounded-2xl p-6">
                    <h2 class="text-sm font-bold text-white mb-4">County Products — Prices</h2>
                     <div class="overflow-x-auto max-h-[320px] overflow-y-auto">
                        <table class="w-full text-xs">
                            <thead><tr class="text-zinc-500 border-b border-white/5"><th class="text-left py-2 pr-3 font-semibold">Product</th><th class="text-left py-2 pr-3 font-semibold">Current</th><th class="text-left py-2 font-semibold">Set</th></tr></thead>
                            <tbody>
                            @foreach($products as $p)
                            <tr class="border-b border-white/5">
                                <td class="py-2 pr-3 font-medium text-zinc-200">{{ $p->name }}</td>
                                <td class="py-2 pr-3 font-bold text-indigo-400">KES {{ number_format($p->price) }}</td>
                                <td class="py-2">
                                    <form method="POST" action="{{ route('county.admin.price', $county->slug) }}" class="flex gap-1.5">
                                        @csrf
                                        <input type="hidden" name="table" value="county_products">
                                        <input type="hidden" name="id" value="{{ $p->id }}">
                                        <input type="number" name="price" min="0" placeholder="KES" class="w-16 h-7 text-xs">
                                        <button class="btn-primary text-[10px] h-7 px-2 py-0">Set</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="glass-card rounded-2xl p-6">
                    <h2 class="text-sm font-bold text-white mb-4">Attractions — Entry Fees</h2>
                     <div class="overflow-x-auto max-h-[320px] overflow-y-auto">
                        <table class="w-full text-xs">
                            <thead><tr class="text-zinc-500 border-b border-white/5"><th class="text-left py-2 pr-3 font-semibold">Attraction</th><th class="text-left py-2 pr-3 font-semibold">Fee</th><th class="text-left py-2 font-semibold">Set</th></tr></thead>
                            <tbody>
                            @foreach($attractions as $a)
                            <tr class="border-b border-white/5">
                                <td class="py-2 pr-3 font-medium text-zinc-200">{{ $a->name }}</td>
                                <td class="py-2 pr-3 font-bold text-indigo-400">KES {{ number_format($a->entry_fee ?: 0) }}</td>
                                <td class="py-2">
                                    <form method="POST" action="{{ route('county.admin.price', $county->slug) }}" class="flex gap-1.5">
                                        @csrf
                                        <input type="hidden" name="table" value="county_tourism_attractions">
                                        <input type="hidden" name="id" value="{{ $a->id }}">
                                        <input type="number" name="price" min="0" placeholder="KES" class="w-16 h-7 text-xs">
                                        <button class="btn-primary text-[10px] h-7 px-2 py-0">Set</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @elseif($tab === 'marketplace')
            <div class="glass-card rounded-2xl p-6">
                <h2 class="text-sm font-bold text-white mb-4">Marketplace — {{ $county->name }} ({{ $marketplaceProducts->count() }})</h2>
                <table class="w-full text-xs">
                    <thead><tr class="text-zinc-500 border-b border-white/5"><th class="text-left py-3 font-semibold">Product</th><th class="text-left py-3 font-semibold">Price</th><th class="text-left py-3 font-semibold">Stock</th><th class="text-left py-3 font-semibold">Actions</th></tr></thead>
                    <tbody class="divide-y divide-white/5">
                    @foreach($marketplaceProducts as $mp)
                    <tr><td class="py-3 font-medium text-zinc-200">{{ $mp->name }}</td><td class="py-3 text-indigo-400 font-semibold">KES {{ number_format($mp->price ?? 0) }}</td><td class="py-3 text-zinc-500">{{ $mp->variants->sum('stock') }}</td><td class="py-3"><a href="{{ route('marketplace.show', $mp->slug) }}" class="btn-ghost text-[10px] py-1 px-2">View</a></td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            @elseif($tab === 'ads')
            <div class="grid lg:grid-cols-2 gap-4">
                <div class="glass-card rounded-2xl p-6">
                    <h2 class="text-sm font-bold text-white mb-4">Create Ad</h2>
                    <form method="POST" action="{{ route('county.admin.ads', $county->slug) }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <input name="name" required placeholder="Product/service name">
                        <textarea name="description" rows="2" placeholder="Description"></textarea>
                        <div class="grid grid-cols-2 gap-2">
                            <input name="price" type="number" min="0" step="0.01" required placeholder="Price (KES)">
                            <input name="image" type="file" accept="image/*">
                        </div>
                        <button class="btn-primary">Publish</button>
                    </form>
                </div>
                <div class="glass-card rounded-2xl p-6">
                    <h2 class="text-sm font-bold text-white mb-4">Active Ads ({{ $ads->count() }})</h2>
                    @forelse($ads as $ad)
                    <div class="flex items-center gap-3 py-3 border-b border-white/5 last:border-0">
                        <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-lg shrink-0">📢</div>
                        <div class="flex-1 min-w-0"><div class="font-medium text-zinc-200 text-sm truncate">{{ $ad->name }}</div><div class="text-[10px] text-zinc-500">KES {{ number_format($ad->budget) }}</div></div>
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">{{ $ad->is_active ? 'Live' : 'Pending' }}</span>
                    </div>
                    @empty
                    <p class="text-zinc-500 text-xs py-6 text-center">No ads yet.</p>
                    @endforelse
                </div>
            </div>

            @elseif($tab === 'packages')
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($plans as $p)
                <div class="glass-card rounded-2xl p-5 flex flex-col {{ $p->slug === 'county-premium' ? 'border-indigo-500/40 ring-1 ring-indigo-500/20' : '' }}">
                    @if($p->slug === 'county-premium')<span class="text-[10px] font-bold uppercase tracking-widest text-indigo-400 mb-1">Recommended</span>@endif
                    <div class="font-bold text-white text-lg">{{ $p->name }}</div>
                    <div class="text-2xl font-bold text-white mt-1">KES {{ number_format($p->price) }}<span class="text-sm font-medium text-zinc-500">/mo</span></div>
                    <ul class="mt-4 space-y-1.5 text-xs text-zinc-400 flex-1">
                        <li>{{ $p->max_booths >= 999 ? 'Unlimited' : $p->max_booths }} booths</li>
                        <li>{{ $p->max_exhibitions >= 999 ? 'Unlimited' : $p->max_exhibitions }} exhibitions</li>
                        <li>{{ $p->has_analytics ? '✅ Analytics' : '— Analytics' }}</li>
                        <li>{{ $p->has_livestream ? '✅ Livestream' : '— Livestream' }}</li>
                    </ul>
                    <form method="POST" action="{{ route('county.admin.package', $county->slug) }}" class="mt-4">@csrf
                        <input type="hidden" name="plan_slug" value="{{ $p->slug }}">
                        <button class="w-full py-2.5 rounded-xl font-bold text-xs text-white {{ $p->price == 0 ? 'bg-zinc-700' : 'bg-gradient-to-r from-indigo-500 to-violet-600 hover:from-indigo-400 hover:to-violet-500' }}">{{ $p->price == 0 ? 'Current' : 'Purchase' }}</button>
                    </form>
                </div>
                @endforeach
            </div>

            @elseif($tab === 'reports')
            <div class="glass-card rounded-2xl p-6 max-w-xl">
                <h2 class="text-sm font-bold text-white mb-4">Download Reports</h2>
                <div class="space-y-2">
                    @foreach([['products','Products Report'],['attractions','Tourism Report'],['hotels','Hotels Report']] as $r)
                    <a href="{{ route('county.admin.report', [$county->slug, $r[0]]) }}" class="flex items-center justify-between bg-white/5 border border-white/10 rounded-xl px-4 py-3 hover:border-indigo-500/30 transition">
                        <div class="flex items-center gap-3"><svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg><span class="text-sm font-medium text-zinc-200">{{ $r[1] }}</span></div>
                        <span class="text-xs font-semibold text-indigo-400">Download CSV ↓</span>
                    </a>
                    @endforeach
                </div>
            </div>

            @elseif($tab === 'sectors')
            <div class="grid lg:grid-cols-2 gap-4">
                <div class="glass-card rounded-2xl p-6">
                    <h2 class="text-sm font-bold text-white mb-4">Link Sectors</h2>
                    <div class="space-y-2 max-h-96 overflow-y-auto">
                        @foreach($allSectors as $s)
                        <form method="POST" action="{{ route('county.admin.sector', $county->slug) }}" class="flex items-center justify-between py-2 border-b border-white/5 last:border-0">
                            @csrf
                            <input type="hidden" name="sector_id" value="{{ $s->id }}">
                            @if($linkedSectors->contains($s->id))
                            <span class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span><span class="font-medium text-zinc-200 text-sm">{{ $s->name }}</span></span>
                            <input type="hidden" name="action" value="detach">
                            <button class="text-[10px] font-medium px-2.5 py-1 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20">Remove</button>
                            @else
                            <span class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-zinc-600"></span><span class="text-zinc-500 text-sm">{{ $s->name }}</span></span>
                            <input type="hidden" name="action" value="attach">
                            <button class="text-[10px] font-medium px-2.5 py-1 rounded-lg bg-white/5 text-zinc-400 hover:bg-white/10">Add</button>
                            @endif
                        </form>
                        @endforeach
                    </div>
                </div>
                <div class="glass-card rounded-2xl p-6">
                    <h2 class="text-sm font-bold text-white mb-4">Add Entity</h2>
                    <form method="POST" action="{{ route('county.admin.entity', $county->slug) }}" class="space-y-3">
                        @csrf
                        <select name="sector_id" required>
                            <option value="">Select sector…</option>
                            @foreach($linkedSectors as $lsId)
                            @php $ls = $allSectors->firstWhere('id', $lsId); @endphp
                            @if($ls)<option value="{{ $ls->id }}">{{ $ls->name }}</option>@endif
                            @endforeach
                        </select>
                        <input name="name" required placeholder="Entity name">
                        <input name="entity_type" required placeholder="Type">
                        <textarea name="description" rows="2" placeholder="Description"></textarea>
                        <button class="btn-primary">Add Entity</button>
                    </form>
                    <h3 class="text-sm font-bold text-white mt-6 mb-3">Existing ({{ $sectorEntities->count() }})</h3>
                    <div class="space-y-2 max-h-80 overflow-y-auto">
                        @forelse($sectorEntities as $e)
                        <div class="flex items-center justify-between py-2 border-b border-white/5">
                            <div><div class="font-medium text-zinc-200 text-sm">{{ $e->name }}</div><div class="text-[10px] text-zinc-500">{{ $e->entity_type }} · {{ $e->sector?->name }}</div></div>
                            <form method="POST" action="{{ route('county.admin.entity.delete', [$county->slug, $e->id]) }}" onsubmit="return confirm('Delete?')">@csrf<button class="text-[10px] text-red-400 hover:text-red-300">Delete</button></form>
                        </div>
                        @empty
                        <p class="text-zinc-500 text-xs py-4 text-center">No entities.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            @elseif($tab === 'institutions')
            <div class="glass-card rounded-2xl p-6 max-w-3xl">
                <h2 class="text-sm font-bold text-white mb-4">Institutions</h2>
                <form method="POST" action="{{ route('county.admin.institution.store', $county->slug) }}" class="grid sm:grid-cols-2 gap-3 mb-6">
                    @csrf
                    <input name="name" required placeholder="Institution name">
                    <input name="type" placeholder="Type (PLC, Farm)">
                    <input name="admin_email" type="email" placeholder="Admin email">
                    <input name="admin_name" placeholder="Admin name">
                    <textarea name="description" rows="2" placeholder="Description" class="sm:col-span-2"></textarea>
                    <button class="btn-primary sm:col-span-2">Create</button>
                </form>
                <div class="space-y-3">
                    @forelse($institutions as $inst)
                    <div class="flex items-center justify-between bg-white/5 border border-white/10 rounded-xl p-4 hover:border-indigo-500/30 transition">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-xl bg-indigo-500/10 flex items-center justify-center text-indigo-400 font-bold text-xs shrink-0">{{ strtoupper(substr($inst->name, 0, 2)) }}</div>
                            <div class="min-w-0"><div class="font-semibold text-zinc-200 text-sm truncate">{{ $inst->name }}</div><div class="text-[10px] text-zinc-500">{{ $inst->sectorEntities->count() }} entities · {{ $inst->synced_at ? 'Synced '.$inst->synced_at->diffForHumans() : 'Never synced' }}</div></div>
                        </div>
                        <div class="flex gap-2 shrink-0">
                            <a href="{{ route('institution.admin', $inst->slug ?? $inst->id) }}" class="btn-ghost text-[10px] py-1.5 px-3">Open</a>
                            <form method="POST" action="{{ route('county.admin.institution.delete', [$county->slug, $inst->id]) }}" onsubmit="return confirm('Delete?')">@csrf<button class="btn-danger text-[10px] py-1.5 px-2">Delete</button></form>
                        </div>
                    </div>
                    @empty
                    <p class="text-zinc-500 text-xs py-6 text-center">No institutions.</p>
                    @endforelse
                </div>
            </div>

            @elseif($tab === 'analytics')
            @include('dashboards.analytics-tab', ['analytics' => $analytics ?? []])

            @elseif(in_array($tab, ['attractions_list']))
            <div class="glass-card rounded-2xl p-6">
                <h2 class="text-sm font-bold text-white mb-4">Attractions & Sites ({{ $attractions->count() }})</h2>
                <table class="w-full text-xs">
                    <thead><tr class="text-zinc-500 border-b border-white/5"><th class="text-left py-3 font-semibold">Name</th><th class="text-left py-3 font-semibold">Category</th><th class="text-left py-3 font-semibold">Location</th><th class="text-right py-3 font-semibold">Fee</th></tr></thead>
                    <tbody class="divide-y divide-white/5">
                    @foreach($attractions as $a)
                    <tr class="hover:bg-white/5 transition cursor-pointer" onclick="openInspector({name:'{{ $a->name }}', type:'attraction', status:'Published', category:'{{ $a->category }}'})">
                        <td class="py-3 font-medium text-zinc-200">{{ $a->name }}</td>
                        <td class="py-3"><span class="text-[10px] px-2 py-0.5 rounded-full bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">{{ $a->category }}</span></td>
                        <td class="py-3 text-zinc-500">{{ $a->location }}</td>
                        <td class="py-3 text-right font-medium text-zinc-200">KES {{ number_format($a->entry_fee ?? 0) }}</td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </main>

    {{-- Detail Inspector Drawer — hidden by default, slides in from right --}}
    <div id="inspector-backdrop" class="fixed inset-0 bg-black/40 z-40 hidden transition-opacity duration-300" onclick="closeInspector()"></div>
    <aside id="detail-inspector"
           class="fixed top-0 right-0 h-full w-full max-w-lg bg-[#161A22]/95 backdrop-blur-md border-l border-white/10 z-50 p-6 flex flex-col shadow-2xl transition-transform duration-300 ease-in-out translate-x-full pointer-events-none">
        <div class="flex items-center justify-between pb-4 border-b border-white/10 shrink-0">
            <h3 class="text-sm font-semibold text-white">Item Inspector</h3>
            <button id="close-inspector-btn" class="p-1 rounded-md text-gray-400 hover:text-white hover:bg-white/5 transition" aria-label="Close inspector">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div id="inspector-content" class="flex-1 overflow-y-auto py-4">
            <p class="text-xs text-gray-500">No active item.</p>
        </div>
    </aside>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var inspector = document.getElementById('detail-inspector');
    var closeBtn = document.getElementById('close-inspector-btn');
    var inspectorContent = document.getElementById('inspector-content');

    // Opens the inspector with item data
    window.openInspector = function(data) {
        if (!inspector || !data) return;
        inspector.classList.remove('translate-x-full', 'pointer-events-none');
        inspector.classList.add('translate-x-0');
        var backdrop = document.getElementById('inspector-backdrop');
        if (backdrop) backdrop.classList.remove('hidden');
        inspectorContent.innerHTML = '<div class="space-y-4">' +
            '<div class="p-4 bg-[#1C212D] rounded-lg border border-white/5">' +
            '<p class="text-xs text-indigo-400 font-mono uppercase">' + (data.type || 'Item') + '</p>' +
            '<h4 class="text-lg font-bold text-white mt-1">' + (data.name || 'Untitled') + '</h4>' +
            (data.category ? '<p class="text-xs text-gray-400 mt-2">Category: <span class="text-indigo-400">' + data.category + '</span></p>' : '') +
            (data.status ? '<p class="text-xs text-gray-400 mt-2">Status: <span class="text-emerald-400">' + data.status + '</span></p>' : '') +
            (data.price ? '<p class="text-xs text-gray-400 mt-2">Price: <span class="text-white font-semibold">KES ' + data.price + '</span></p>' : '') +
            '</div></div>';
    };

    // Closes the inspector
    window.closeInspector = function() {
        inspector.classList.remove('translate-x-0');
        inspector.classList.add('translate-x-full', 'pointer-events-none');
        var backdrop = document.getElementById('inspector-backdrop');
        if (backdrop) backdrop.classList.add('hidden');
        inspectorContent.innerHTML = '<p class="text-xs text-gray-500">No active item.</p>';
    };

    // Close button
    if (closeBtn) {
        closeBtn.addEventListener('click', closeInspector);
    }
});
    var rc = document.getElementById('revenueChart');
    if (rc) {
        new Chart(rc, {
            type: 'line',
            data: {
                labels: ['Jul','Aug','Sep','Oct','Nov','Dec','Jan','Feb','Mar','Apr','May','Jun'],
                datasets: [{
                    label: 'Revenue',
                    data: [120,145,160,180,220,250,280,310,350,380,420,480],
                    borderColor: '#6366F1',
                    backgroundColor: 'rgba(99,102,241,0.1)',
                    fill: true, tension: 0.4, pointRadius: 3, pointBackgroundColor: '#6366F1', borderWidth: 2,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: { legend: { display: false }, tooltip: { backgroundColor: 'rgba(22,25,32,0.95)', titleColor: '#fff', bodyColor: '#A1A1AA', borderColor: 'rgba(255,255,255,0.06)', borderWidth: 1, padding: 12 } },
                scales: { x: { grid: { color: 'rgba(255,255,255,0.03)' }, ticks: { color: '#71717A', font: { size: 10 } } }, y: { grid: { color: 'rgba(255,255,255,0.03)' }, ticks: { color: '#71717A', font: { size: 10 }, callback: v => 'KES ' + v + 'k' } } }
            }
        });
    }
    var sc = document.getElementById('sectorChart');
    if (sc) {
        new Chart(sc, {
            type: 'doughnut',
            data: {
                labels: ['Hospitality','Eco-Tourism','Agri-Trade','Cultural'],
                datasets: [{ data: [35,28,22,15], backgroundColor: ['#6366F1','#10B981','#F59E0B','#8B5CF6'], borderWidth: 0 }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '70%',
                plugins: { legend: { display: false }, tooltip: { backgroundColor: 'rgba(22,25,32,0.95)', titleColor: '#fff', bodyColor: '#A1A1AA', borderColor: 'rgba(255,255,255,0.06)', borderWidth: 1, padding: 12 } }
            }
        });
    }
});
</script>
@endpush
@endsection