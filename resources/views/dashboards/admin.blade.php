@extends('layouts.blank', ['title' => 'Platform Admin', 'bodyClass' => ''])

@section('content')
<div class="flex min-h-screen bg-[#07090F]">
    <div class="w-[230px] bg-[#050709] border-r border-white/8 flex flex-col shrink-0 min-h-screen">
        <div class="flex items-center justify-between px-4 border-b border-white/8 h-16">
            <div class="font-black text-white text-xs tracking-tight leading-tight">
                <div>KICC</div>
                <div class="text-[#FFCD05] text-[9px] tracking-[0.15em] uppercase">Platform Admin</div>
            </div>
        </div>
        <div class="flex-1 py-3 overflow-y-auto">
            @foreach($navItems as $item)
            <a href="#" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-semibold transition-all {{ ($item['active'] ?? false) ? 'text-white bg-[#901C1E]/15 border-r-2 border-[#901C1E]' : 'text-white/35 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/></svg>
                <span class="truncate text-xs">{{ $item['label'] }}</span>
            </a>
            @endforeach
        </div>
        <a href="/" class="flex items-center gap-3 px-4 py-4 border-t border-white/8 text-white/30 hover:text-white text-xs transition-colors">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            <span>Exit Dashboard</span>
        </a>
    </div>
    <div class="flex-1 flex flex-col overflow-hidden">
        <div class="bg-[#050709] border-b border-white/8 px-6 h-16 flex items-center justify-between shrink-0">
            <div>
                <div class="font-black text-white text-sm">Platform Administration</div>
                <div class="text-[10px] font-bold uppercase tracking-widest text-[#901C1E]">SUPER ADMIN</div>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-white font-black text-xs bg-[#901C1E]">PA</div>
            </div>
        </div>
        <div class="flex-1 overflow-y-auto p-6">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-5"><div class="text-2xl font-black text-white">{{ $stats['counties'] }}</div><div class="text-xs text-white/35 mt-1">Counties</div></div>
                <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-5"><div class="text-2xl font-black text-white">{{ $stats['users'] }}</div><div class="text-xs text-white/35 mt-1">Users</div></div>
                <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-5"><div class="text-2xl font-black text-white">{{ $stats['products'] }}</div><div class="text-xs text-white/35 mt-1">Active Products</div></div>
                <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-5"><div class="text-2xl font-black text-white">{{ $stats['orders'] }}</div><div class="text-xs text-white/35 mt-1">Total Orders</div></div>
                <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-5"><div class="text-2xl font-black text-white">{{ $stats['exhibitions'] }}</div><div class="text-xs text-white/35 mt-1">Active Exhibitions</div></div>
                <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-5"><div class="text-2xl font-black text-white">{{ $stats['venues'] }}</div><div class="text-xs text-white/35 mt-1">Venues</div></div>
                <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-5"><div class="text-2xl font-black text-white">KES {{ number_format($stats['paymentVolume']) }}</div><div class="text-xs text-white/35 mt-1">Payment Volume</div></div>
                <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-5"><div class="text-2xl font-black text-white">{{ $stats['activeSubs'] }}</div><div class="text-xs text-white/35 mt-1">Active Subscribers</div></div>
            </div>

            <div class="grid lg:grid-cols-2 gap-6">
                <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                    <h3 class="font-bold text-white mb-5">Recent Orders</h3>
                    @forelse($recentOrders as $o)
                    <div class="flex items-center gap-3 py-3 border-b border-white/5 last:border-0">
                        <div class="w-10 h-10 bg-[#141B2E] rounded-xl flex items-center justify-center"><svg class="w-4 h-4 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg></div>
                        <div class="flex-1 min-w-0"><div class="font-semibold text-white text-sm">{{ $o->order_number }}</div><div class="text-xs text-white/30">KES {{ number_format($o->grand_total) }}</div></div>
                        <span class="text-[10px] font-bold px-2.5 py-1 rounded-full border {{ $o->payment_status === 'confirmed' ? 'text-emerald-400 bg-emerald-500/15 border-emerald-500/25' : 'text-[#FFCD05] bg-[#FFCD05]/15 border-[#FFCD05]/25' }} capitalize">{{ $o->payment_status }}</span>
                    </div>
                    @empty
                    <p class="text-white/40 text-sm">No orders yet.</p>
                    @endforelse
                </div>
                <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                    <h3 class="font-bold text-white mb-5">Recent Payments</h3>
                    @forelse($recentPayments as $p)
                    <div class="flex items-center gap-3 py-3 border-b border-white/5 last:border-0">
                        <div class="flex-1 min-w-0"><div class="font-semibold text-white text-sm font-mono">{{ $p->intent_id }}</div><div class="text-xs text-white/30">KES {{ number_format($p->amount) }}</div></div>
                        <span class="text-[10px] font-bold px-2.5 py-1 rounded-full border {{ $p->status === 'confirmed' ? 'text-emerald-400 bg-emerald-500/15 border-emerald-500/25' : $p->status === 'failed' ? 'text-[#901C1E] bg-[#901C1E]/15 border-[#901C1E]/25' : 'text-[#FFCD05] bg-[#FFCD05]/15 border-[#FFCD05]/25' }} capitalize">{{ $p->status }}</span>
                    </div>
                    @empty
                    <p class="text-white/40 text-sm">No payments yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection