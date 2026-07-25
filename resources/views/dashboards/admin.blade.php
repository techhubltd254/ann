@extends('layouts.blank')

@section('title', 'Platform Admin — KICC')

@section('content')
<div class="flex min-h-screen bg-[#07090F]">
    <div class="w-56 bg-[#050709] border-r border-white/8 flex flex-col shrink-0 min-h-screen">
        <div class="flex items-center gap-3 px-4 border-b border-white/8 h-16">
            <a href="{{ route('admin.portal') }}" class="text-white/30 hover:text-white text-xs">&larr; Back</a>
            <div class="font-black text-white text-xs tracking-tight"><div>KICC</div><div class="text-[#FFCD05] text-[9px] tracking-[0.15em] uppercase">National Admin</div></div>
        </div>
        <div class="flex-1 py-3 overflow-y-auto">
            @foreach($navItems as $item)
            <a href="{{ route('dashboard.admin', ['tab' => $item['tab']]) }}" 
               class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-semibold transition-all {{ $tab === $item['tab'] ? 'text-white bg-[#901C1E]/15 border-r-2 border-[#901C1E]' : 'text-white/35 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/></svg>
                <span class="truncate text-xs">{{ $item['label'] }}</span>
            </a>
            @endforeach
        </div>
        <a href="/" class="flex items-center gap-3 px-4 py-4 border-t border-white/8 text-white/30 hover:text-white text-xs transition-colors">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            <span>Exit</span>
        </a>
    </div>

    <div class="flex-1 flex flex-col overflow-hidden">
        <div class="bg-[#050709] border-b border-white/8 px-6 h-16 flex items-center justify-between shrink-0">
            <div>
                <div class="font-black text-white text-sm">National Administration</div>
                <div class="text-[10px] font-bold uppercase tracking-widest text-[#901C1E]">SUPER ADMIN — FULL CRUD</div>
            </div>
        </div>
        <div class="flex-1 overflow-y-auto p-6">

@if(session('success'))
<div class="bg-emerald-500/15 border border-emerald-500/25 text-emerald-400 rounded-xl px-5 py-3 mb-6 text-sm">{{ session('success') }}</div>
@endif

@if($tab === 'overview')
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-5"><div class="text-2xl font-black text-white">{{ $stats['counties'] }}</div><div class="text-xs text-white/35 mt-1">Counties</div></div>
    <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-5"><div class="text-2xl font-black text-white">{{ $stats['users'] }}</div><div class="text-xs text-white/35 mt-1">Users</div></div>
    <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-5"><div class="text-2xl font-black text-white">{{ $stats['products'] }}</div><div class="text-xs text-white/35 mt-1">Products</div></div>
    <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-5"><div class="text-2xl font-black text-white">{{ $stats['orders'] }}</div><div class="text-xs text-white/35 mt-1">Orders</div></div>
    <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-5"><div class="text-2xl font-black text-white">{{ $stats['exhibitions'] }}</div><div class="text-xs text-white/35 mt-1">Exhibitions</div></div>
    <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-5"><div class="text-2xl font-black text-white">{{ $stats['venues'] }}</div><div class="text-xs text-white/35 mt-1">Venues</div></div>
    <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-5"><div class="text-2xl font-black text-white">KES {{ number_format($stats['paymentVolume']) }}</div><div class="text-xs text-white/35 mt-1">Payment Volume</div></div>
    <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-5"><div class="text-2xl font-black text-white">{{ $stats['activeSubs'] }}</div><div class="text-xs text-white/35 mt-1">Active Subscribers</div></div>
</div>

<div class="grid lg:grid-cols-2 gap-6">
    <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
        <h3 class="font-bold text-white mb-5">Recent Orders</h3>
        @forelse($recentOrders as $o)
        <div class="flex items-center gap-3 py-3 border-b border-white/5 last:border-0">
            <div class="flex-1 min-w-0"><div class="font-semibold text-white text-sm">{{ $o->order_number }}</div><div class="text-xs text-white/30">KES {{ number_format($o->grand_total) }}</div></div>
            <form method="POST" action="{{ route('admin.delete-order', $o->id) }}" onsubmit="return confirm('Delete order?')">@csrf<button class="text-[10px] text-[#901C1E] hover:underline">Delete</button></form>
        </div>
        @empty <p class="text-white/40 text-sm">No orders.</p> @endforelse
    </div>
    <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
        <h3 class="font-bold text-white mb-5">Recent Payments</h3>
        @forelse($recentPayments as $p)
        <div class="flex items-center gap-3 py-3 border-b border-white/5 last:border-0">
            <div class="flex-1 min-w-0"><div class="font-semibold text-white text-sm font-mono">{{ $p->intent_id }}</div><div class="text-xs text-white/30">KES {{ number_format($p->amount) }}</div></div>
            <span class="text-[10px] font-bold px-2.5 py-1 rounded-full border capitalize {{ $p->status === 'confirmed' ? 'text-emerald-400 bg-emerald-500/15 border-emerald-500/25' : ($p->status === 'failed' ? 'text-[#901C1E] bg-[#901C1E]/15 border-[#901C1E]/25' : 'text-[#FFCD05] bg-[#FFCD05]/15 border-[#FFCD05]/25') }}">{{ $p->status }}</span>
        </div>
        @empty <p class="text-white/40 text-sm">No payments.</p> @endforelse
    </div>
</div>

@elseif($tab === 'counties')
<div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
    <div class="flex items-center justify-between mb-5"><h3 class="font-bold text-white">All Counties ({{ $counties->count() }})</h3></div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="text-white/40 text-[10px] uppercase tracking-wider border-b border-white/8">
                <th class="text-left py-3 px-3">Name</th><th class="text-left py-3 px-3">Capital</th><th class="text-right py-3 px-3">Population</th><th class="text-center py-3 px-3">Actions</th>
            </tr></thead>
            <tbody>
            @foreach($counties as $c)
            <tr class="border-b border-white/5 hover:bg-white/5">
                <td class="py-3 px-3 text-white font-semibold">{{ $c->name }}</td>
                <td class="py-3 px-3 text-white/50">{{ $c->capital ?? '—' }}</td>
                <td class="py-3 px-3 text-white/50 text-right">{{ $c->population_2024 ? number_format($c->population_2024) : '—' }}</td>
                <td class="py-3 px-3 text-center">
                    <a href="{{ route('counties.show', $c->slug) }}" class="text-[#FFCD05] text-xs hover:underline">View</a>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

@elseif($tab === 'products')
<div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
    <div class="flex items-center justify-between mb-5"><h3 class="font-bold text-white">All Products ({{ $allProducts->count() }})</h3></div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="text-white/40 text-[10px] uppercase tracking-wider border-b border-white/8">
                <th class="text-left py-3 px-3">Name</th><th class="text-left py-3 px-3">County</th><th class="text-right py-3 px-3">Price</th><th class="text-center py-3 px-3">Actions</th>
            </tr></thead>
            <tbody>
            @foreach($allProducts as $p)
            <tr class="border-b border-white/5 hover:bg-white/5">
                <td class="py-3 px-3 text-white font-semibold">{{ $p->name }}</td>
                <td class="py-3 px-3 text-white/50">{{ $p->county?->name ?? '—' }}</td>
                <td class="py-3 px-3 text-kicc-gold text-right">KES {{ number_format($p->price ?? 0) }}</td>
                <td class="py-3 px-3 text-center">
                    <a href="{{ route('marketplace.show', $p->slug) }}" class="text-[#FFCD05] text-xs hover:underline mr-3">View</a>
                    <form method="POST" action="{{ route('admin.delete-product', $p->id) }}" class="inline" onsubmit="return confirm('Delete {{ $p->name }}?')">
                        @csrf<button class="text-[#901C1E] text-xs hover:underline">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

@elseif($tab === 'orders')
<div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
    <div class="flex items-center justify-between mb-5"><h3 class="font-bold text-white">Orders</h3></div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="text-white/40 text-[10px] uppercase tracking-wider border-b border-white/8">
                <th class="text-left py-3 px-3">Order #</th><th class="text-right py-3 px-3">Total</th><th class="text-center py-3 px-3">Status</th><th class="text-center py-3 px-3">Actions</th>
            </tr></thead>
            <tbody>
            @foreach($allOrders ?? $recentOrders as $o)
            <tr class="border-b border-white/5 hover:bg-white/5">
                <td class="py-3 px-3 text-white font-semibold font-mono">{{ $o->order_number }}</td>
                <td class="py-3 px-3 text-right text-white">KES {{ number_format($o->grand_total) }}</td>
                <td class="py-3 px-3 text-center"><span class="text-[10px] font-bold px-2.5 py-1 rounded-full border {{ $o->payment_status === 'confirmed' ? 'text-emerald-400 bg-emerald-500/15' : 'text-[#FFCD05] bg-[#FFCD05]/15' }}">{{ $o->payment_status }}</span></td>
                <td class="py-3 px-3 text-center">
                    <form method="POST" action="{{ route('admin.delete-order', $o->id) }}" class="inline" onsubmit="return confirm('Delete order?')">@csrf<button class="text-[#901C1E] text-xs hover:underline">Delete</button></form>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

@elseif($tab === 'users')
<div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
    <div class="flex items-center justify-between mb-5"><h3 class="font-bold text-white">Users ({{ $allUsers->count() }})</h3></div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="text-white/40 text-[10px] uppercase tracking-wider border-b border-white/8">
                <th class="text-left py-3 px-3">Name</th><th class="text-left py-3 px-3">Email</th><th class="text-center py-3 px-3">Actions</th>
            </tr></thead>
            <tbody>
            @foreach($allUsers as $u)
            <tr class="border-b border-white/5 hover:bg-white/5">
                <td class="py-3 px-3 text-white font-semibold">{{ $u->name ?? '—' }}</td>
                <td class="py-3 px-3 text-white/50">{{ $u->email ?? '—' }}</td>
                <td class="py-3 px-3 text-center">
                    <form method="POST" action="{{ route('admin.delete-user', $u->id) }}" class="inline" onsubmit="return confirm('Delete user {{ $u->name }}?')">@csrf<button class="text-[#901C1E] text-xs hover:underline">Delete</button></form>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

@elseif($tab === 'venues')
<div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
    <h3 class="font-bold text-white mb-5">Venues</h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach($allVenues as $v)
        <div class="bg-[#141B2E] rounded-xl p-4 border border-white/8 hover:border-kicc-gold/30 transition-all">
            <div class="font-bold text-white text-sm">{{ $v->name }}</div>
            <div class="text-white/40 text-xs mt-1">{{ $v->venue_type }}</div>
            <a href="{{ route('venues.show', $v->slug) }}" class="text-[#FFCD05] text-xs hover:underline mt-2 inline-block">View</a>
        </div>
        @endforeach
    </div>
</div>

@elseif($tab === 'payments')
<div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
    <h3 class="font-bold text-white mb-5">Payment Intents</h3>
    @forelse($recentPayments as $p)
    <div class="flex items-center gap-3 py-3 border-b border-white/5 last:border-0">
        <div class="flex-1"><div class="font-semibold text-white text-sm">{{ $p->intent_id }}</div><div class="text-xs text-white/30">{{ $p->reference_type }} #{{ $p->reference_id }} · KES {{ number_format($p->amount) }}</div></div>
        <span class="text-[10px] font-bold px-2.5 py-1 rounded-full border capitalize {{ $p->status === 'confirmed' ? 'text-emerald-400 bg-emerald-500/15' : ($p->status === 'failed' ? 'text-[#901C1E] bg-[#901C1E]/15' : 'text-[#FFCD05] bg-[#FFCD05]/15') }}">{{ $p->status }}</span>
    </div>
    @empty <p class="text-white/40">No payments yet.</p> @endforelse
</div>
@endif

        </div>
    </div>
</div>
@endsection