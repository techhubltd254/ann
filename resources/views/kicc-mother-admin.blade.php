@extends('layouts.blank')

@section('title', 'KICC Mother Admin — Platform Control')

@section('content')
<div class="flex min-h-screen bg-[#F3F4F6]">
    {{-- Sidebar --}}
    <div class="w-60 bg-white border-r border-gray-200 flex flex-col shrink-0 min-h-screen">
        <div class="flex items-center gap-3 px-4 border-b border-gray-200 h-16">
            <div class="flex items-center gap-2">
                <img src="{{ media('kicc/kicc-logo.png') }}" alt="KICC" class="h-6 w-auto">
                <div class="text-[9px] font-black text-[#901C1E] tracking-[0.15em] uppercase">Mother<br>Admin</div>
            </div>
        </div>
        <div class="flex-1 py-3 overflow-y-auto">
            @foreach($navItems as $item)
            <a href="{{ route('kicc.admin', ['tab' => $item['tab']]) }}"
               class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-semibold transition-all {{ $tab === $item['tab'] ? 'text-gray-900 bg-[#901C1E]/10 border-r-2 border-[#901C1E]' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-800' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/></svg>
                <span class="truncate text-xs">{{ $item['label'] }}</span>
            </a>
            @endforeach
        </div>
        <div class="px-4 py-3 border-t border-gray-200 space-y-1">
            <a href="{{ route('county.admin') }}" class="flex items-center gap-3 text-gray-400 hover:text-gray-700 text-xs transition-colors py-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span>County Portals</span>
            </a>
            <a href="{{ route('national.admin') }}" class="flex items-center gap-3 text-gray-400 hover:text-gray-700 text-xs transition-colors py-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <span>National Government</span>
            </a>
            <a href="{{ route('exhibitor.admin') }}" class="flex items-center gap-3 text-gray-400 hover:text-gray-700 text-xs transition-colors py-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Exhibitor Portals</span>
            </a>
            <form method="POST" action="{{ route('logout') }}" class="pt-2">@csrf
                <button type="submit" class="flex items-center gap-3 text-gray-400 hover:text-red-500 text-xs transition-colors w-full py-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="flex-1 overflow-y-auto">
        @if(session('success'))
        <div class="bg-green-50 border-b border-green-200 text-green-700 px-6 py-3 text-sm">{{ session('success') }}</div>
        @endif
        @if(session('artisan_result'))
        <div class="bg-blue-50 border-b border-blue-200 text-blue-700 px-6 py-3 text-sm">
            <strong>Artisan: {{ session('artisan_result')['command'] }}</strong> (exit: {{ session('artisan_result')['exit_code'] }})
            <pre class="mt-1 text-xs overflow-x-auto">{{ session('artisan_result')['output'] }}</pre>
        </div>
        @endif
        @if($errors->any())
        <div class="bg-red-50 border-b border-red-200 text-red-600 px-6 py-3 text-sm">{{ $errors->first() }}</div>
        @endif

        {{-- OVERVIEW TAB --}}
        @if($tab === 'overview')
        <div class="p-6">
            <h1 class="text-xl font-black text-gray-900 mb-6">Mother Dashboard</h1>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white rounded-2xl border border-gray-200 p-5">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Counties</div>
                    <div class="text-3xl font-black text-gray-900 mt-1">{{ $stats['counties'] }}</div>
                    <div class="text-xs text-gray-400 mt-1">47 active · {{ $stats['tradeBoards'] }} trade boards</div>
                </div>
                <div class="bg-white rounded-2xl border border-gray-200 p-5">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Users</div>
                    <div class="text-3xl font-black text-gray-900 mt-1">{{ $stats['users'] }}</div>
                    <div class="text-xs text-gray-400 mt-1">{{ $stats['exhibitors'] }} exhibitors · {{ $stats['ministries'] }} ministries</div>
                </div>
                <div class="bg-white rounded-2xl border border-gray-200 p-5">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Products</div>
                    <div class="text-3xl font-black text-gray-900 mt-1">{{ $stats['products'] }}</div>
                    <div class="text-xs text-gray-400 mt-1">{{ $stats['orders'] }} orders placed</div>
                </div>
                <div class="bg-white rounded-2xl border border-gray-200 p-5">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Escrow</div>
                    <div class="text-3xl font-black text-gray-900 mt-1">KES {{ number_format($stats['escrowTotal']) }}</div>
                    <div class="text-xs text-gray-400 mt-1">{{ number_format($stats['escrowHeld']) }} held · {{ number_format($stats['payments']) }} confirmed</div>
                </div>
            </div>

            {{-- Quick Links --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                <a href="{{ route('kicc.admin', ['tab' => 'counties']) }}" class="bg-white rounded-2xl border border-gray-200 p-5 hover:border-[#901C1E]/40 transition-all group">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-xl bg-[#901C1E]/10 flex items-center justify-center text-[#901C1E] font-bold">47</div>
                        <div>
                            <div class="font-bold text-gray-900 text-sm">County Admins</div>
                            <div class="text-xs text-gray-400">All 47 counties</div>
                        </div>
                    </div>
                    <div class="text-xs text-gray-500 group-hover:text-[#901C1E] transition-colors">Manage content, sectors, images, 4D video, ads →</div>
                </a>
                <a href="{{ route('kicc.admin', ['tab' => 'national']) }}" class="bg-white rounded-2xl border border-gray-200 p-5 hover:border-[#901C1E]/40 transition-all group">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-xl bg-[#7C3AED]/10 flex items-center justify-center text-[#7C3AED] font-bold">N</div>
                        <div>
                            <div class="font-bold text-gray-900 text-sm">National Government</div>
                            <div class="text-xs text-gray-400">Ministries & agencies</div>
                        </div>
                    </div>
                    <div class="text-xs text-gray-500 group-hover:text-[#901C1E] transition-colors">Manage ministries, agencies, national sectors →</div>
                </a>
                <a href="{{ route('kicc.admin', ['tab' => 'exhibitors']) }}" class="bg-white rounded-2xl border border-gray-200 p-5 hover:border-[#901C1E]/40 transition-all group">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-xl bg-[#F59E0B]/10 flex items-center justify-center text-[#F59E0B] font-bold">E</div>
                        <div>
                            <div class="font-bold text-gray-900 text-sm">Private Exhibitors</div>
                            <div class="text-xs text-gray-400">{{ $stats['exhibitors'] }} registered</div>
                        </div>
                    </div>
                    <div class="text-xs text-gray-500 group-hover:text-[#901C1E] transition-colors">Manage exhibitors, products, orders →</div>
                </a>
            </div>

            {{-- Artisan Console --}}
            <div class="bg-white rounded-2xl border border-gray-200 p-5">
                <h3 class="font-bold text-gray-900 text-sm mb-3">🛠 Artisan Console</h3>
                <form method="POST" action="{{ route('kicc.admin.artisan') }}" class="flex gap-2">
                    @csrf
                    <select name="command" class="flex-1 h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none focus:ring-2 focus:ring-[#901C1E]/60">
                        <option value="">Select a command…</option>
                        @foreach(['cache:clear','config:clear','route:clear','view:clear','optimize:clear','migrate','schedule:run','queue:restart','horizon:snapshot','dba:index-audit','search:index-es','analytics:trends','recommendations:build','embeddings:build','vendors:score'] as $cmd)
                        <option value="{{ $cmd }}">{{ $cmd }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="h-11 px-6 rounded-xl bg-[#901C1E] text-white font-bold text-sm hover:bg-[#7b1618] transition-all">Run</button>
                </form>
            </div>
        </div>

        {{-- SUB-PORTALS TAB --}}
        @elseif($tab === 'portals')
        <div class="p-6">
            <h1 class="text-xl font-black text-gray-900 mb-6">Sub-Portals</h1>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="{{ route('kicc.admin') }}" class="bg-white rounded-2xl border-2 border-[#901C1E] p-5 hover:shadow-lg transition-all">
                    <div class="font-bold text-gray-900">🏛 KICC Mother Admin</div>
                    <div class="text-xs text-gray-500 mt-1">You are here</div>
                </a>
                <a href="{{ route('county.admin') }}" class="bg-white rounded-2xl border border-gray-200 p-5 hover:border-[#901C1E]/40 transition-all">
                    <div class="font-bold text-gray-900">🗺 County Portals</div>
                    <div class="text-xs text-gray-500 mt-1">47 county admin dashboards</div>
                </a>
                <a href="{{ route('national.admin') }}" class="bg-white rounded-2xl border border-gray-200 p-5 hover:border-[#901C1E]/40 transition-all">
                    <div class="font-bold text-gray-900">🏢 National Government</div>
                    <div class="text-xs text-gray-500 mt-1">Ministries & agencies</div>
                </a>
                <a href="{{ route('exhibitor.admin') }}" class="bg-white rounded-2xl border border-gray-200 p-5 hover:border-[#901C1E]/40 transition-all">
                    <div class="font-bold text-gray-900">🏪 Private Exhibitors</div>
                    <div class="text-xs text-gray-500 mt-1">Product & order management</div>
                </a>
                <a href="{{ route('seller.analytics') }}" class="bg-white rounded-2xl border border-gray-200 p-5 hover:border-[#901C1E]/40 transition-all">
                    <div class="font-bold text-gray-900">📊 Seller Analytics</div>
                    <div class="text-xs text-gray-500 mt-1">Performance dashboard</div>
                </a>
                <a href="{{ route('filament.admin.pages.dashboard') }}" class="bg-white rounded-2xl border border-gray-200 p-5 hover:border-[#901C1E]/40 transition-all">
                    <div class="font-bold text-gray-900">📋 Filament CRUD</div>
                    <div class="text-xs text-gray-500 mt-1">Database management</div>
                </a>
            </div>
        </div>

        {{-- COUNTIES TAB --}}
        @elseif($tab === 'counties')
        <div class="p-6">
            <h1 class="text-xl font-black text-gray-900 mb-6">All 47 Counties</h1>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($counties as $c)
                <a href="{{ route('county.admin.pro', $c->slug) }}" class="bg-white rounded-2xl border border-gray-200 p-4 hover:border-[#901C1E]/40 transition-all group">
                    <div class="flex items-center justify-between mb-2">
                        <div class="font-bold text-gray-900 text-sm group-hover:text-[#901C1E] transition-colors">{{ $c->name }}</div>
                        <span class="text-[10px] font-bold text-gray-400">{{ $c->former_province ?? '—' }}</span>
                    </div>
                    <div class="flex gap-3 text-xs text-gray-400">
                        <span>{{ $c->product_count ?? 0 }} products</span>
                        <span>KES {{ number_format($c->trade_volume ?? 0) }}</span>
                    </div>
                </a>
                @endforeach
            </div>
        </div>

        {{-- NATIONAL GOVERNMENT TAB --}}
        @elseif($tab === 'national')
        <div class="p-6">
            <h1 class="text-xl font-black text-gray-900 mb-6">National Government</h1>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($ministries as $m)
                <div class="bg-white rounded-2xl border border-gray-200 p-4">
                    <div class="font-bold text-gray-900 text-sm">{{ $m->name }}</div>
                    <div class="text-xs text-gray-400 mt-1">{{ $m->agencies->count() }} agencies</div>
                    @if($m->agencies->count() > 0)
                    <div class="mt-2 space-y-1">
                        @foreach($m->agencies as $a)
                        <div class="text-xs text-gray-500 flex items-center gap-2">
                            <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                            {{ $a->name }}
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        {{-- EXHIBITORS TAB --}}
        @elseif($tab === 'exhibitors')
        <div class="p-6">
            <h1 class="text-xl font-black text-gray-900 mb-6">Private Exhibitors ({{ $stats['exhibitors'] }})</h1>
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left">
                            <th class="px-4 py-3 font-bold text-gray-500 text-[10px] uppercase tracking-wider">Name</th>
                            <th class="px-4 py-3 font-bold text-gray-500 text-[10px] uppercase tracking-wider">Email</th>
                            <th class="px-4 py-3 font-bold text-gray-500 text-[10px] uppercase tracking-wider">County</th>
                            <th class="px-4 py-3 font-bold text-gray-500 text-[10px] uppercase tracking-wider">Products</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($exhibitors as $e)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-900 font-semibold">{{ $e->name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $e->email }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $e->county?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $e->product_count ?? 0 }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ORDERS TAB --}}
        @elseif($tab === 'orders')
        <div class="p-6">
            <h1 class="text-xl font-black text-gray-900 mb-6">Recent Orders</h1>
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead><tr class="bg-gray-50 text-left">
                        <th class="px-4 py-3 font-bold text-gray-500 text-[10px] uppercase">#</th>
                        <th class="px-4 py-3 font-bold text-gray-500 text-[10px] uppercase">Customer</th>
                        <th class="px-4 py-3 font-bold text-gray-500 text-[10px] uppercase">Items</th>
                        <th class="px-4 py-3 font-bold text-gray-500 text-[10px] uppercase">Total</th>
                        <th class="px-4 py-3 font-bold text-gray-500 text-[10px] uppercase">Status</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($orders as $o)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-900 font-semibold">#{{ $o->id }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $o->user?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $o->items->count() }}</td>
                            <td class="px-4 py-3 text-gray-500">KES {{ number_format($o->total ?? 0) }}</td>
                            <td class="px-4 py-3"><span class="text-[10px] font-bold uppercase px-2 py-1 rounded-full {{ $o->status === 'confirmed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">{{ $o->status ?? 'pending' }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- PROVIDERS TAB --}}
        @elseif($tab === 'providers')
        <div class="p-6">
            <h1 class="text-xl font-black text-gray-900 mb-6">Provider Certification Queue</h1>
            @foreach($pendingServices as $svc)
            <div class="bg-white rounded-2xl border border-gray-200 p-4 mb-3 flex items-center justify-between">
                <div>
                    <div class="font-semibold text-gray-900 text-sm">{{ $svc['label'] }}</div>
                    <div class="text-xs text-gray-400">{{ $svc['table'] }} · KES {{ number_format($svc['price'] ?? 0) }}</div>
                </div>
                <form method="POST" action="{{ route('kicc.admin.approve', [$svc['table'], $svc['id']]) }}" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-xl bg-[#901C1E] text-white text-xs font-bold hover:bg-[#7b1618]">Approve</button>
                </form>
            </div>
            @endforeach
        </div>

        {{-- ESCROW TAB --}}
        @elseif($tab === 'escrow')
        <div class="p-6">
            <h1 class="text-xl font-black text-gray-900 mb-6">Escrow Transactions</h1>
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead><tr class="bg-gray-50 text-left">
                        <th class="px-4 py-3 font-bold text-gray-500 text-[10px] uppercase">ID</th>
                        <th class="px-4 py-3 font-bold text-gray-500 text-[10px] uppercase">Buyer</th>
                        <th class="px-4 py-3 font-bold text-gray-500 text-[10px] uppercase">Seller</th>
                        <th class="px-4 py-3 font-bold text-gray-500 text-[10px] uppercase">Amount</th>
                        <th class="px-4 py-3 font-bold text-gray-500 text-[10px] uppercase">Status</th>
                        <th class="px-4 py-3 font-bold text-gray-500 text-[10px] uppercase">Action</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($escrows as $esc)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-900 font-semibold">{{ $esc->escrow_id ?? $esc->id }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $esc->buyer?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $esc->seller?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">KES {{ number_format($esc->amount ?? 0) }}</td>
                            <td class="px-4 py-3"><span class="text-[10px] font-bold uppercase px-2 py-1 rounded-full {{ $esc->status === 'released' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">{{ $esc->status ?? 'pending' }}</span></td>
                            <td class="px-4 py-3">
                                @if($esc->status === 'held')
                                <form method="POST" action="{{ route('kicc.admin.escrow.release', $esc->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-xs font-bold text-[#901C1E] hover:underline">Release</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- USERS TAB --}}
        @elseif($tab === 'users')
        <div class="p-6">
            <h1 class="text-xl font-black text-gray-900 mb-6">Users</h1>
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead><tr class="bg-gray-50 text-left">
                        <th class="px-4 py-3 font-bold text-gray-500 text-[10px] uppercase">Name</th>
                        <th class="px-4 py-3 font-bold text-gray-500 text-[10px] uppercase">Email</th>
                        <th class="px-4 py-3 font-bold text-gray-500 text-[10px] uppercase">Type</th>
                        <th class="px-4 py-3 font-bold text-gray-500 text-[10px] uppercase">Roles</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($users as $u)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-900 font-semibold">{{ $u->name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $u->email }}</td>
                            <td class="px-4 py-3"><span class="text-[10px] font-bold px-2 py-1 rounded-full bg-gray-100 text-gray-600">{{ $u->account_type ?? 'user' }}</span></td>
                            <td class="px-4 py-3 text-gray-500">{{ $u->roles->pluck('name')->implode(', ') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- HERO MEDIA TAB --}}
        @elseif($tab === 'hero_media')
        <div class="p-6">
            <h1 class="text-xl font-black text-gray-900 mb-6">Landing Page Hero Media</h1>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Current Hero Video --}}
                <div class="bg-white rounded-2xl border border-gray-200 p-5">
                    <h2 class="font-bold text-gray-900 text-sm mb-3">Current Hero Video</h2>
                    @if($heroAsset)
                    @php
                        $heroVideoSrc = $heroAsset->mp4Url() ?? $heroAsset->webmUrl() ?? $heroAsset->url();
                        $heroPosterUrl = $heroAsset->posterUrl();
                    @endphp
                    <div class="aspect-video bg-gray-900 rounded-xl overflow-hidden mb-3">
                        <video autoplay muted loop playsinline controls preload="auto" class="w-full h-full object-cover" poster="{{ $heroPosterUrl }}">
                            <source src="{{ $heroVideoSrc }}" type="video/mp4">
                        </video>
                    </div>
                    <div class="text-xs text-gray-500 space-y-1">
                        <div><strong>Status:</strong> <span class="text-green-600 font-semibold">Active (MediaAsset #{{ $heroAsset->id }})</span></div>
                        <div><strong>Path:</strong> <code class="text-[10px] bg-gray-100 px-1.5 py-0.5 rounded">{{ $heroAsset->path }}</code></div>
                        <div><strong>Size:</strong> {{ number_format($heroAsset->size_bytes / 1024 / 1024, 1) }} MB</div>
                        <div><strong>Dimensions:</strong> {{ $heroAsset->width }}x{{ $heroAsset->height }}</div>
                        @if($heroAsset->derivatives->count())
                        <div><strong>Derivatives:</strong> {{ $heroAsset->derivatives->pluck('kind')->join(', ') }}</div>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('kicc.admin.hero.delete') }}" class="mt-4" onsubmit="return confirm('Remove the active hero video? The homepage will fall back to the default video.')">
                        @csrf
                        <button type="submit" class="h-10 px-5 rounded-xl bg-red-50 text-red-600 font-bold text-xs hover:bg-red-100 transition-all border border-red-200">Delete Hero Video</button>
                    </form>
                    @else
                    <div class="aspect-video bg-gray-100 rounded-xl flex items-center justify-center mb-3">
                        <div class="text-center text-gray-400">
                            <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            <div class="text-sm font-semibold">No hero video set</div>
                            <div class="text-xs mt-1">Homepage uses default fallback video</div>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Upload New Video --}}
                <div class="bg-white rounded-2xl border border-gray-200 p-5">
                    <h2 class="font-bold text-gray-900 text-sm mb-3">Upload New Video</h2>
                    <p class="text-xs text-gray-400 mb-4">Accepted formats: MP4, WebM, MOV. Max 1 GB per upload.</p>
                    <form method="POST" action="{{ route('kicc.admin.hero.upload') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div class="border-2 border-dashed border-gray-200 rounded-xl p-6 text-center hover:border-[#901C1E]/40 transition-colors cursor-pointer" onclick="document.getElementById('hero-video-input').click()">
                            <svg class="w-10 h-10 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                            <div class="text-sm text-gray-500 font-medium">Click to select video file</div>
                            <div class="text-xs text-gray-400 mt-1" id="hero-file-name"></div>
                            <input type="file" name="video" id="hero-video-input" accept="video/mp4,video/webm,video/quicktime" class="hidden" onchange="document.getElementById('hero-file-name').textContent=this.files[0].name">
                        </div>
                        <button type="submit" class="w-full h-12 rounded-xl bg-[#901C1E] text-white font-bold text-sm hover:bg-[#7b1618] transition-all active:scale-[0.98]">Upload & Set as Hero</button>
                    </form>
                </div>
            </div>

            {{-- Help Section --}}
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-2xl p-5">
                <h3 class="font-bold text-gray-900 text-sm mb-2">How it works</h3>
                <ul class="text-xs text-gray-600 space-y-1 list-disc list-inside">
                    <li>The landing page hero video is managed through this panel using the MediaAsset pipeline system</li>
                    <li>Same system used by county portals for their hero videos (Muranga county works this way)</li>
                    <li>Upload a video file and it becomes the homepage hero immediately</li>
                    <li>Delete to revert to the default fallback video</li>
                    <li>For best results: 1280x720 or 1920x1080, H.264 encoded MP4, under 30 MB</li>
                </ul>
            </div>
        </div>
        @endif
    </div>
</div>
<script>
    // Auto-scroll to the artisan result
    if (window.location.hash === '#artisan') {
        document.querySelector('[data-artisan-result]')?.scrollIntoView({ behavior: 'smooth' });
    }
</script>
@endsection