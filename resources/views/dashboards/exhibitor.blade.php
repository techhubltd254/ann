@extends('layouts.blank')

@section('title', 'Exhibitor Portal — KICC')

@section('content')
@php $accent = '#38BDF8'; @endphp
<div class="flex min-h-screen bg-[#F9FAFB]">
    {{-- Sidebar --}}
    <div class="w-56 bg-white border-r border-gray-200 flex flex-col shrink-0 min-h-screen">
        <div class="flex items-center gap-3 px-4 border-b border-gray-200 h-16">
            <a href="{{ route('admin.portal') }}" class="text-gray-400 hover:text-gray-700 text-xs">&larr; Portal</a>
            <div class="flex items-center gap-2">
                <img src="{{ media('kicc/kicc-logo.png') }}" alt="KICC" class="h-7 w-auto" style="filter: brightness(0) invert(1);">
                <div style="color: {{ $accent }}" class="text-[9px] font-black tracking-[0.15em] uppercase">Exhibitor<br>Portal</div>
            </div>
        </div>
        <div class="flex-1 py-3 overflow-y-auto">
            @foreach($navItems as $item)
            <a href="{{ route('exhibitor.admin', ['tab' => $item['tab']]) }}"
               class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-semibold transition-all {{ $tab === $item['tab'] ? 'text-gray-900 border-r-2' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-800' }}"
               style="{{ $tab === $item['tab'] ? 'background: '.$accent.'33; border-color: '.$accent : '' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/></svg>
                <span class="truncate text-xs">{{ $item['label'] }}</span>
            </a>
            @endforeach
        </div>
        <a href="{{ $storefrontUrl }}" class="flex items-center gap-3 px-4 py-3 border-t border-gray-200 text-gray-400 hover:text-gray-700 text-xs transition-colors">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            <span>View My Website</span>
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
                <div class="font-black text-gray-900 text-sm">{{ $user->name }}</div>
                <div class="text-[10px] font-bold uppercase tracking-widest" style="color: {{ $accent }}">Private Exhibitor{{ $user->county ? ' — ' . $user->county->name . ' County' : '' }}</div>
            </div>
            <a href="{{ $storefrontUrl }}" class="px-3 py-1.5 text-xs font-bold rounded-lg text-gray-900" style="background: {{ $accent }}">My Website &nearr;</a>
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
            <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900">{{ $stats['products'] }}</div><div class="text-xs text-gray-400 mt-1">Live Products</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900">{{ number_format($stats['stock']) }}</div><div class="text-xs text-gray-400 mt-1">Units in Stock</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900">{{ $stats['orders'] }}</div><div class="text-xs text-gray-400 mt-1">Orders Received</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black" style="color: {{ $accent }}">KES {{ number_format($stats['revenue']) }}</div><div class="text-xs text-gray-400 mt-1">Total Escrow Volume</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-amber-600">KES {{ number_format($stats['escrowHeld']) }}</div><div class="text-xs text-gray-400 mt-1">Held in Escrow</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-emerald-600">KES {{ number_format($stats['escrowReleased']) }}</div><div class="text-xs text-gray-400 mt-1">Released to You</div></div>
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h3 class="font-bold text-gray-900 mb-4">How your trade pipeline works</h3>
                <div class="grid md:grid-cols-4 gap-3 text-xs">
                    <div class="rounded-xl p-4 border border-gray-100"><div class="font-bold text-gray-900 mb-1">1. You list products</div><div class="text-gray-500">Products appear on the KICC marketplace &amp; your own website instantly.</div></div>
                    <div class="rounded-xl p-4 border border-gray-100"><div class="font-bold text-gray-900 mb-1">2. Buyer pays</div><div class="text-gray-500">Payment is held safely in KICC trade escrow — not released yet.</div></div>
                    <div class="rounded-xl p-4 border border-gray-100"><div class="font-bold text-gray-900 mb-1">3. You ship</div><div class="text-gray-500">Courier partners pick up &amp; deliver to the buyer.</div></div>
                    <div class="rounded-xl p-4 border border-gray-100"><div class="font-bold text-gray-900 mb-1">4. You get paid</div><div class="text-gray-500">Buyer confirms delivery &rarr; escrow releases to your account.</div></div>
                </div>
            </div>
            @endif

            {{-- ═══════ PRODUCTS ═══════ --}}
            @if($tab === 'products')
            <div class="grid lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white border border-gray-200 rounded-2xl p-6">
                    <h3 class="font-bold text-gray-900 mb-5">My Products ({{ $products->count() }})</h3>
                    @forelse($products as $p)
                    <div class="flex items-center gap-4 py-3 border-b border-gray-100 last:border-0">
                        <img src="{{ $p->image_url }}" class="w-12 h-12 rounded-xl object-cover bg-gray-100" alt="">
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-gray-900 text-sm truncate">{{ $p->name }}</div>
                            <div class="text-xs text-gray-400">{{ $p->category?->name }} &middot; {{ $p->county?->name }} &middot; {{ $p->variants->sum('stock') }} in stock</div>
                        </div>
                        <div class="text-sm font-bold text-gray-900">KES {{ number_format($p->price ?? 0) }}</div>
                        <a href="{{ route('marketplace.show', $p->slug) }}" class="text-[10px] font-bold text-gray-400 hover:text-gray-900">VIEW</a>
                        <form method="POST" action="{{ route('exhibitor.admin.products.delete', $p->id) }}" onsubmit="return confirm('Remove this product?')">@csrf<button class="text-[10px] font-bold text-[#901C1E] hover:underline">DELETE</button></form>
                    </div>
                    @empty
                    <p class="text-gray-400 text-sm py-6 text-center">No products yet — list your first product &rarr;</p>
                    @endforelse
                </div>
                <div class="bg-white border border-gray-200 rounded-2xl p-6 h-fit">
                    <h3 class="font-bold text-gray-900 mb-5">List a New Product</h3>
                    <form method="POST" action="{{ route('exhibitor.admin.products.store') }}" class="space-y-3">
                        @csrf
                        <input name="name" required placeholder="Product name" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2" style="--tw-ring-color: {{ $accent }}">
                        <select name="category_id" required class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-600">
                            <option value="">Select category…</option>
                            @foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                        </select>
                        <textarea name="description" required rows="3" placeholder="Describe your product…" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm"></textarea>
                        <div class="grid grid-cols-3 gap-2">
                            <input name="price" type="number" min="1" required placeholder="KES" class="border border-gray-200 rounded-xl px-3 py-2.5 text-sm">
                            <input name="stock" type="number" min="0" required placeholder="Stock" class="border border-gray-200 rounded-xl px-3 py-2.5 text-sm">
                            <input name="unit" placeholder="Unit" class="border border-gray-200 rounded-xl px-3 py-2.5 text-sm">
                        </div>
                        <button class="w-full py-2.5 rounded-xl text-sm font-bold text-gray-900" style="background: {{ $accent }}">Publish to Marketplace</button>
                    </form>
                </div>
            </div>
            @endif

            {{-- ═══════ ORDERS ═══════ --}}
            @if($tab === 'orders')
            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h3 class="font-bold text-gray-900 mb-5">Orders Containing My Products</h3>
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
                    <div class="mt-2"><span class="text-[10px] font-bold px-2 py-1 rounded-full {{ $o->fulfillment_status === 'fulfilled' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600' }}">{{ strtoupper($o->fulfillment_status ?? 'unfulfilled') }}</span></div>
                </div>
                @empty
                <p class="text-gray-400 text-sm py-6 text-center">No orders yet. Share your website to start selling.</p>
                @endforelse
            </div>
            @endif

            {{-- ═══════ ESCROW ═══════ --}}
            @if($tab === 'escrow')
            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h3 class="font-bold text-gray-900 mb-5">Escrow Earnings</h3>
                @forelse($escrows as $e)
                <div class="py-4 border-b border-gray-100 last:border-0">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <div class="font-semibold text-gray-900 text-sm">{{ $e->escrow_id }}</div>
                            <div class="text-xs text-gray-400">Buyer: {{ $e->buyer?->name ?? 'Guest' }} &middot; {{ $e->created_at?->format('d M Y') }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-black text-gray-900">KES {{ number_format($e->amount) }}</div>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $e->status === 'released' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600' }}">{{ strtoupper($e->status) }}</span>
                        </div>
                    </div>
                    <div class="flex gap-1.5 mt-2">
                        @foreach($e->steps ?? [] as $i => $step)
                        <div class="flex-1 h-1.5 rounded-full {{ ($step['done'] ?? false) ? '' : 'bg-gray-100' }}" style="{{ ($step['done'] ?? false) ? 'background: '.$accent : '' }}" title="{{ $step['label'] ?? '' }}"></div>
                        @endforeach
                    </div>
                </div>
                @empty
                <p class="text-gray-400 text-sm py-6 text-center">No escrow transactions yet.</p>
                @endforelse
            </div>
            @endif

            {{-- ═══════ MY WEBSITE ═══════ --}}
            @if($tab === 'website')
            <div class="bg-white border border-gray-200 rounded-2xl p-8 max-w-2xl">
                <h3 class="font-bold text-gray-900 mb-2">Your Exhibitor Website</h3>
                <p class="text-sm text-gray-500 mb-6">Every exhibitor on KICC gets a public storefront website. Share this link with buyers — your products are already on it.</p>
                <div class="flex items-center gap-3 bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 mb-6">
                    <code class="text-sm text-gray-900 font-bold flex-1 truncate">{{ $storefrontUrl }}</code>
                    <a href="{{ $storefrontUrl }}" class="px-3 py-1.5 text-xs font-bold rounded-lg text-gray-900 shrink-0" style="background: {{ $accent }}">Open &nearr;</a>
                </div>
                <div class="grid grid-cols-2 gap-3 text-xs text-gray-500">
                    <div class="border border-gray-100 rounded-xl p-4"><div class="font-bold text-gray-900 mb-1">Auto-updating</div>Products you publish appear on your website instantly.</div>
                    <div class="border border-gray-100 rounded-xl p-4"><div class="font-bold text-gray-900 mb-1">Escrow protected</div>Buyers see the escrow badge — trust converts to sales.</div>
                </div>
            </div>
            @endif

        </div>
    </div>
</div>
@endsection
