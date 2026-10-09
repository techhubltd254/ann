@php
$totalSlots = $allocation->sum('total_slots');
$usedSlots = $allocation->sum('used_slots');
$activeSubs = $subscribers->where('status', 'active')->count();
@endphp

@extends('layouts.admin-theme', ['title' => $county->name . ' County Admin', 'themeColor' => '#22C55E', 'brandName' => $county->name, 'brandSub' => 'County Admin'])

@section('content')
<div class="flex min-h-screen bg-zinc-900">
    <div x-data="{ collapsed: false }" class="flex">
        <div class="w-[230px] bg-zinc-800 border-r border-white/10 flex flex-col shrink-0 min-h-screen">
            <div class="flex items-center justify-between px-4 border-b border-white/10 h-16">
                <div class="flex items-center gap-2">
                    <div class="rounded-lg bg-[#16A34A] px-2 py-1 flex items-center justify-center">
                        <img src="{{ media('kicc/kicc-logo.png') }}" alt="KICC" class="h-6 w-auto">
                    </div>
                    <div class="text-[#22C55E] text-[9px] font-black tracking-[0.15em] uppercase leading-tight">Global<br>Exhibition</div>
                </div>
            </div>
            <div class="flex-1 py-3 overflow-y-auto">
                @foreach($navItems as $item)
                <a href="{{ route('dashboard.county', ['tab' => $item['tab']]) }}" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-semibold transition-all text-zinc-400 hover:bg-white/10 hover:text-white"
                   style="{{ $tab === $item['tab'] ? 'background: #22C55E22; border-right: 2px solid #22C55E; color: white' : '' }}">
                    @if(isset($item['icon']))
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/></svg>
                    @endif
                    <span class="truncate text-xs">{{ $item['label'] }}</span>
                </a>
                @endforeach
            </div>
            <a href="/" class="flex items-center gap-3 px-4 py-4 border-t border-white/10 text-zinc-100 hover:text-white text-xs transition-colors">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                <span>Exit Dashboard</span>
            </a>
        </div>
    </div>
    <div class="flex-1 flex flex-col overflow-hidden">
        <div class="bg-zinc-800 border-b border-white/10 px-6 h-16 flex items-center justify-between shrink-0">
            <div>
                <div class="font-black text-white text-sm">{{ $county->name }} — Forest County Admin</div>
                <div class="text-[10px] font-bold uppercase tracking-widest" style="color: #22C55E">COUNTY ADMIN</div>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-white font-black text-xs" style="background: #FFFFFF">{{ substr($county->name,0,2) }}</div>
            </div>
        </div>
        <div class="flex-1 overflow-y-auto p-6 space-y-8">
            {{-- KPI Cards (always visible) --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="glass-card border border-white/10 rounded-2xl p-5">
                    <div class="flex items-start justify-between mb-4"><div class="p-2.5 rounded-xl" style="background: #22C55E22"><svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/></svg></div></div>
                    <div class="text-2xl font-black text-white">{{ $totalSlots - $usedSlots }}</div>
                    <div class="text-xs text-zinc-400 mt-1 font-medium">Available Slots</div>
                </div>
                <div class="glass-card border border-white/10 rounded-2xl p-5">
                    <div class="flex items-start justify-between mb-4"><div class="p-2.5 rounded-xl" style="background: #16A34A22"><svg class="w-4 h-4 text-[#16A34A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"/></svg></div></div>
                    <div class="text-2xl font-black text-white">{{ $activeSubs }}</div>
                    <div class="text-xs text-zinc-400 mt-1 font-medium">Active Subscribers</div>
                </div>
                <div class="glass-card border border-white/10 rounded-2xl p-5">
                    <div class="flex items-start justify-between mb-4"><div class="p-2.5 rounded-xl" style="background: #22C55E22"><svg class="w-4 h-4 text-[#22C55E]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/></svg></div></div>
                    <div class="text-2xl font-black text-white">{{ $config->revenue_share_pct }}%</div>
                    <div class="text-xs text-zinc-400 mt-1 font-medium">Revenue Share</div>
                </div>
                <div class="glass-card border border-white/10 rounded-2xl p-5">
                    <div class="flex items-start justify-between mb-4"><div class="p-2.5 rounded-xl" style="background: #22C55E22"><svg class="w-4 h-4 text-zinc-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg></div></div>
                    <div class="text-2xl font-black text-white">KES {{ number_format($config->wallet_balance) }}</div>
                    <div class="text-xs text-zinc-400 mt-1 font-medium">Wallet Balance</div>
                </div>
            </div>

            @if($tab === 'overview')
            {{-- Slots + Subscribers --}}
            <div class="grid lg:grid-cols-2 gap-6">
                <div class="glass-card border border-white/10 rounded-2xl p-6">
                    <h3 class="font-bold text-white mb-5">Slot Allocation</h3>
                    @forelse($allocation as $a)
                    <div class="mb-4">
                        <div class="flex justify-between text-sm mb-1.5">
                            <span class="font-semibold text-white">{{ ucfirst($a->slot_type) }}s</span>
                            <span class="text-zinc-100">{{ $a->used_slots }} / {{ $a->total_slots }}</span>
                        </div>
                        <div class="h-2 bg-sky-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all" style="width: {{ $a->total_slots > 0 ? ($a->used_slots / $a->total_slots) * 100 : 0 }}%; background: #22C55E"></div>
                        </div>
                        <div class="flex justify-between text-xs text-zinc-100 mt-1">
                            <span>{{ $a->availableSlots() }} available</span>
                            <span class="text-emerald-400">{{ $a->status }}</span>
                        </div>
                    </div>
                    @empty
                    <p class="text-zinc-100 text-sm">No slot allocations yet.</p>
                    @endforelse
                </div>
                <div class="glass-card border border-white/10 rounded-2xl p-6">
                    <h3 class="font-bold text-white mb-5">Recent Subscribers</h3>
                    <div class="space-y-3">
                        @forelse($subscribers->take(5) as $s)
                        <div class="flex items-center gap-3 py-2 border-b border-gray-100 last:border-0">
                            <div class="w-10 h-10 bg-zinc-900 rounded-xl flex items-center justify-center"><span class="text-white font-bold text-sm">{{ substr($s->user?->name ?? '?',0,1) }}</span></div>
                            <div class="flex-1">
                                <div class="text-sm font-semibold text-white">{{ $s->user?->name ?? 'Anonymous' }}</div>
                                <div class="text-xs text-zinc-100">{{ $s->plan?->name ?? 'No plan' }}</div>
                            </div>
                            <span class="text-[10px] font-bold px-2.5 py-1 rounded-full border {{ $s->status === 'active' ? 'text-emerald-400 bg-emerald-500/15 border-emerald-500/25' : 'text-[#22C55E] bg-[#22C55E]/15 border-[#22C55E]/25' }} capitalize">{{ $s->status }}</span>
                        </div>
                        @empty
                        <p class="text-zinc-100 text-sm">No subscribers yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            @elseif($tab === 'slots')
            <div class="glass-card border border-white/10 rounded-2xl p-6">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="font-bold text-white">Slot Management</h3>
                    <span class="text-xs text-zinc-400">{{ $totalSlots - $usedSlots }} of {{ $totalSlots }} available</span>
                </div>
                 <div class="overflow-x-auto max-h-[320px] overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="text-zinc-100 text-[10px] uppercase tracking-wider border-b border-white/10">
                            <th class="text-left py-3 px-3">Slot Type</th><th class="text-right py-3 px-3">Total</th><th class="text-right py-3 px-3">Used</th><th class="text-right py-3 px-3">Available</th><th class="text-center py-3 px-3">Status</th>
                        </tr></thead>
                        <tbody>
                        @forelse($allocation as $a)
                        <tr class="border-b border-gray-100 hover:bg-white/10">
                            <td class="py-3 px-3 text-white font-semibold capitalize">{{ $a->slot_type }}s</td>
                            <td class="py-3 px-3 text-zinc-100 text-right">{{ $a->total_slots }}</td>
                            <td class="py-3 px-3 text-zinc-100 text-right">{{ $a->used_slots }}</td>
                            <td class="py-3 px-3 text-kicc-gold text-right font-bold">{{ $a->availableSlots() }}</td>
                            <td class="py-3 px-3 text-center"><span class="text-[10px] font-bold px-2.5 py-1 rounded-full border text-emerald-400 bg-emerald-500/15 border-emerald-500/25">{{ $a->status }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="py-8 text-center text-zinc-100">No slot allocations yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @elseif($tab === 'subscribers')
            <div class="glass-card border border-white/10 rounded-2xl p-6">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="font-bold text-white">All Subscribers ({{ $subscribers->count() }})</h3>
                </div>
                 <div class="overflow-x-auto max-h-[320px] overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="text-zinc-100 text-[10px] uppercase tracking-wider border-b border-white/10">
                            <th class="text-left py-3 px-3">Business</th><th class="text-left py-3 px-3">Plan</th><th class="text-center py-3 px-3">Status</th><th class="text-right py-3 px-3">Joined</th>
                        </tr></thead>
                        <tbody>
                        @forelse($subscribers as $s)
                        <tr class="border-b border-gray-100 hover:bg-white/10">
                            <td class="py-3 px-3 text-white font-semibold">{{ $s->user?->name ?? 'Anonymous' }}</td>
                            <td class="py-3 px-3 text-zinc-100">{{ $s->plan?->name ?? 'No plan' }}</td>
                            <td class="py-3 px-3 text-center"><span class="text-[10px] font-bold px-2.5 py-1 rounded-full border {{ $s->status === 'active' ? 'text-emerald-400 bg-emerald-500/15 border-emerald-500/25' : 'text-[#22C55E] bg-[#22C55E]/15 border-[#22C55E]/25' }} capitalize">{{ $s->status }}</span></td>
                            <td class="py-3 px-3 text-zinc-100 text-right text-xs">{{ $s->created_at?->format('M d, Y') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="py-8 text-center text-zinc-100">No subscribers yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @elseif($tab === 'content')
            <div class="glass-card border border-white/10 rounded-2xl p-6">
                <h3 class="font-bold text-white mb-5">County Content</h3>
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <a href="{{ route('counties.show', $county->slug) }}" class="bg-zinc-900 border border-white/10 rounded-xl p-5 hover:border-kicc-gold/40 transition-all group block">
                        <div class="font-bold text-white text-sm group-hover:text-kicc-gold transition-colors">Tourism Attractions</div>
                        <div class="text-zinc-400 text-xs mt-1">Manage &rarr;</div>
                    </a>
                    <a href="{{ route('counties.show', $county->slug) }}" class="bg-zinc-900 border border-white/10 rounded-xl p-5 hover:border-kicc-gold/40 transition-all group block">
                        <div class="font-bold text-white text-sm group-hover:text-kicc-gold transition-colors">Hotels & Lodges</div>
                        <div class="text-zinc-400 text-xs mt-1">Manage &rarr;</div>
                    </a>
                    <a href="{{ route('marketplace.index') }}" class="bg-zinc-900 border border-white/10 rounded-xl p-5 hover:border-kicc-gold/40 transition-all group block" data-magnetic>
                        <div class="font-bold text-white text-sm group-hover:text-kicc-gold transition-colors">Local Products</div>
                        <div class="text-zinc-400 text-xs mt-1">Manage &rarr;</div>
                    </a>
                    <a href="{{ route('counties.show', $county->slug) }}" class="bg-zinc-900 border border-white/10 rounded-xl p-5 hover:border-kicc-gold/40 transition-all group block">
                        <div class="font-bold text-white text-sm group-hover:text-kicc-gold transition-colors">County Profile</div>
                        <div class="text-zinc-400 text-xs mt-1">Manage &rarr;</div>
                    </a>
                    <a href="{{ route('exhibitions.index') }}" class="bg-zinc-900 border border-white/10 rounded-xl p-5 hover:border-kicc-gold/40 transition-all group block" data-magnetic>
                        <div class="font-bold text-white text-sm group-hover:text-kicc-gold transition-colors">Events</div>
                        <div class="text-zinc-400 text-xs mt-1">Manage &rarr;</div>
                    </a>
                    <a href="{{ route('room3d.index') }}" class="bg-zinc-900 border border-white/10 rounded-xl p-5 hover:border-kicc-gold/40 transition-all group block" data-magnetic>
                        <div class="font-bold text-white text-sm group-hover:text-kicc-gold transition-colors">3D Experiences</div>
                        <div class="text-zinc-400 text-xs mt-1">Manage &rarr;</div>
                    </a>
                </div>
            </div>

            @elseif($tab === 'settlements')
            <div class="glass-card border border-white/10 rounded-2xl p-6">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="font-bold text-white">Settlement History</h3>
                    <span class="text-xs text-zinc-400">Wallet: KES {{ number_format($config->wallet_balance) }}</span>
                </div>
                 <div class="overflow-x-auto max-h-[320px] overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="text-zinc-100 text-[10px] uppercase tracking-wider border-b border-white/10">
                            <th class="text-left py-3 px-3">Date</th><th class="text-left py-3 px-3">Description</th><th class="text-right py-3 px-3">Amount</th><th class="text-center py-3 px-3">Type</th>
                        </tr></thead>
                        <tbody>
                        @forelse($transactions as $t)
                        <tr class="border-b border-gray-100 hover:bg-white/10">
                            <td class="py-3 px-3 text-zinc-100 text-xs">{{ $t->created_at?->format('M d, Y') }}</td>
                            <td class="py-3 px-3 text-white font-semibold">{{ $t->description ?? $t->type ?? 'Transaction' }}</td>
                            <td class="py-3 px-3 text-right font-bold {{ ($t->amount ?? 0) >= 0 ? 'text-emerald-400' : 'text-[#16A34A]' }}">KES {{ number_format($t->amount ?? 0) }}</td>
                            <td class="py-3 px-3 text-center"><span class="text-[10px] font-bold px-2.5 py-1 rounded-full border text-zinc-100 bg-sky-50 border-white/10 capitalize">{{ $t->type ?? 'txn' }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="py-8 text-center text-zinc-100">No transactions yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection