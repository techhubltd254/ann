@extends('layouts.blank')

@section('title', $county->name . ' County Portal — KICC')

@section('content')
@php $accent = '#14B8A6'; @endphp
<div class="flex min-h-screen bg-[#F9FAFB]">
    {{-- Sidebar --}}
    <div class="w-56 bg-white border-r border-gray-200 flex flex-col shrink-0 min-h-screen">
        <div class="flex items-center gap-3 px-4 border-b border-gray-200 h-16">
            <a href="{{ route('admin.portal') }}" class="text-gray-400 hover:text-gray-700 text-xs">&larr; Portal</a>
            <div class="flex items-center gap-2">
                <img src="{{ media('kicc/kicc-logo.png') }}" alt="KICC" class="h-7 w-auto" style="filter: brightness(0) invert(1);">
                <div style="color: {{ $accent }}" class="text-[9px] font-black tracking-[0.15em] uppercase">County<br>Portal</div>
            </div>
        </div>
        <div class="flex-1 py-3 overflow-y-auto">
            @foreach($navItems as $item)
            <a href="{{ route('county.admin', ['tab' => $item['tab']]) }}"
               class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-semibold transition-all {{ $tab === $item['tab'] ? 'text-gray-900 border-r-2' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-800' }}"
               style="{{ $tab === $item['tab'] ? 'background: '.$accent.'33; border-color: '.$accent : '' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/></svg>
                <span class="truncate text-xs">{{ $item['label'] }}</span>
            </a>
            @endforeach
        </div>
        <a href="{{ $countySiteUrl }}" class="flex items-center gap-3 px-4 py-3 border-t border-gray-200 text-gray-400 hover:text-gray-700 text-xs transition-colors">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            <span>View County Website</span>
        </a>
        <a href="{{ route('county.admin.pro', $county->slug) }}" class="flex items-center gap-3 px-4 py-3 border-t border-gray-200 text-gray-400 hover:text-gray-700 text-xs transition-colors">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            <span>Professional Admin</span>
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
                <div class="font-black text-gray-900 text-sm">{{ $county->name }} County</div>
                <div class="text-[10px] font-bold uppercase tracking-widest" style="color: {{ $accent }}">County Exhibitor — Trade Board</div>
            </div>
            <a href="{{ $countySiteUrl }}" class="px-3 py-1.5 text-xs font-bold rounded-lg text-gray-900" style="background: {{ $accent }}">County Website &nearr;</a>
        </div>
        <div class="flex-1 overflow-y-auto p-6">

            @if($tab === 'overview')
            <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900">{{ $stats['products'] }}</div><div class="text-xs text-gray-400 mt-1">County Products Live</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900">{{ $stats['exhibitors'] }}</div><div class="text-xs text-gray-400 mt-1">Private Exhibitors</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900">{{ $stats['orders'] }}</div><div class="text-xs text-gray-400 mt-1">Orders</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black" style="color: {{ $accent }}">KES {{ number_format($stats['revenue']) }}</div><div class="text-xs text-gray-400 mt-1">Trade Volume</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-amber-600">KES {{ number_format($stats['escrowHeld']) }}</div><div class="text-xs text-gray-400 mt-1">In Escrow</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900">{{ number_format($stats['sectorEntities']) }}</div><div class="text-xs text-gray-400 mt-1">Sector Entities</div></div>
            </div>
            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h3 class="font-bold text-gray-900 mb-3">County trade position</h3>
                <p class="text-sm text-gray-500 leading-relaxed">{{ $county->name }} County operates as an independent exhibitor on the KICC platform with its own public website, trade board storefront, and escrow revenue account. All products below — from the trade board and private county exhibitors — are sold under the county pavilion on the national marketplace.</p>
            </div>
            @endif

            @if($tab === 'products')
            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h3 class="font-bold text-gray-900 mb-5">All County Products ({{ $products->count() }})</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="text-left text-[10px] uppercase tracking-widest text-gray-400 border-b border-gray-100">
                            <th class="pb-3 pr-4">Product</th><th class="pb-3 pr-4">Seller</th><th class="pb-3 pr-4">Category</th><th class="pb-3 pr-4">Stock</th><th class="pb-3">Price</th>
                        </tr></thead>
                        <tbody>
                        @foreach($products as $p)
                        <tr class="border-b border-gray-50 last:border-0">
                            <td class="py-2.5 pr-4 font-semibold text-gray-900"><a href="{{ route('marketplace.show', $p->slug) }}" class="hover:underline">{{ $p->name }}</a></td>
                            <td class="py-2.5 pr-4 text-gray-500 text-xs">{{ $p->seller?->name ?? '—' }}</td>
                            <td class="py-2.5 pr-4 text-gray-500 text-xs">{{ $p->category?->name }}</td>
                            <td class="py-2.5 pr-4 text-gray-500 text-xs">{{ $p->variants->sum('stock') }}</td>
                            <td class="py-2.5 font-bold text-gray-900">KES {{ number_format($p->price ?? 0) }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            @if($tab === 'exhibitors')
            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h3 class="font-bold text-gray-900 mb-5">Private Exhibitors in {{ $county->name }} ({{ $exhibitors->count() }})</h3>
                @forelse($exhibitors as $e)
                <div class="flex items-center gap-4 py-3 border-b border-gray-100 last:border-0">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-gray-900 font-black text-xs shrink-0" style="background: {{ $accent }}">{{ strtoupper(substr($e->name, 0, 2)) }}</div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-gray-900 text-sm">{{ $e->name }}</div>
                        <div class="text-xs text-gray-400">{{ $e->email }} &middot; {{ $e->product_count }} products</div>
                    </div>
                    <a href="{{ route('exhibitor.site', \Illuminate\Support\Str::slug($e->name)) }}" class="text-[10px] font-bold px-2.5 py-1 rounded-full border border-gray-200 text-gray-500 hover:text-gray-900">WEBSITE &nearr;</a>
                </div>
                @empty
                <p class="text-gray-400 text-sm py-6 text-center">No private exhibitors registered in {{ $county->name }} yet.</p>
                @endforelse
            </div>
            @endif

            @if($tab === 'orders')
            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h3 class="font-bold text-gray-900 mb-5">Orders Containing {{ $county->name }} Products</h3>
                @forelse($orders as $o)
                <div class="py-4 border-b border-gray-100 last:border-0">
                    <div class="flex items-center justify-between mb-2">
                        <div class="font-semibold text-gray-900 text-sm">{{ $o->order_number }}</div>
                        <div class="text-xs text-gray-400">{{ $o->created_at?->format('d M Y H:i') }}</div>
                    </div>
                    @foreach($o->items as $item)
                    <div class="flex justify-between text-xs text-gray-500 py-1">
                        <span>{{ $item->product_name }} ({{ $item->variant_name }}) × {{ $item->quantity }}</span>
                        <span class="font-bold text-gray-900">KES {{ number_format($item->total) }}</span>
                    </div>
                    @endforeach
                </div>
                @empty
                <p class="text-gray-400 text-sm py-6 text-center">No orders for county products yet.</p>
                @endforelse
            </div>
            @endif

            @if($tab === 'escrow')
            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h3 class="font-bold text-gray-900 mb-5">Escrow Revenue — {{ $county->name }} Sellers</h3>
                @forelse($escrows as $e)
                <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                    <div>
                        <div class="font-semibold text-gray-900 text-sm">{{ $e->escrow_id }}</div>
                        <div class="text-xs text-gray-400">Seller: {{ $e->seller?->name }} &middot; Buyer: {{ $e->buyer?->name ?? 'Guest' }}</div>
                    </div>
                    <div class="text-right">
                        <div class="font-black text-gray-900">KES {{ number_format($e->amount) }}</div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $e->status === 'released' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600' }}">{{ strtoupper($e->status) }}</span>
                    </div>
                </div>
                @empty
                <p class="text-gray-400 text-sm py-6 text-center">No escrow transactions yet.</p>
                @endforelse
            </div>
            @endif

            @if($tab === 'website')
            <div class="bg-white border border-gray-200 rounded-2xl p-8 max-w-2xl">
                <h3 class="font-bold text-gray-900 mb-2">{{ $county->name }} County Website</h3>
                <p class="text-sm text-gray-500 mb-6">Your county is a website by itself — tourism, sectors, products and exhibitors, all under one public address.</p>
                <div class="flex items-center gap-3 bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 mb-6">
                    <code class="text-sm text-gray-900 font-bold flex-1 truncate">{{ $countySiteUrl }}</code>
                    <a href="{{ $countySiteUrl }}" class="px-3 py-1.5 text-xs font-bold rounded-lg text-gray-900 shrink-0" style="background: {{ $accent }}">Open &nearr;</a>
                </div>
                <div class="grid grid-cols-2 gap-3 text-xs text-gray-500">
                    <div class="border border-gray-100 rounded-xl p-4"><div class="font-bold text-gray-900 mb-1">County pavilion</div>All county products &amp; exhibitors on one page.</div>
                    <div class="border border-gray-100 rounded-xl p-4"><div class="font-bold text-gray-900 mb-1">National reach</div>Your county appears on the 47-county 3D map &amp; marketplace.</div>
                </div>
            </div>
            @endif

        </div>
    </div>
</div>
@endsection
