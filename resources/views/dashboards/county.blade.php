@php
$totalSlots = $allocation->sum('total_slots');
$usedSlots = $allocation->sum('used_slots');
$activeSubs = $subscribers->where('status', 'active')->count();
$slotPct = $totalSlots > 0 ? round(($usedSlots / $totalSlots) * 100) : 0;
@endphp

<x-dashboards.shell title="{{ $county->name }} County Administration" role="COUNTY ADMIN · {{ strtoupper($county->name) }}" accent="#0B1E57" initials="{{ substr($county->name,0,2) }}" :navItems="$navItems">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-5 hover:border-white/15 transition-all">
            <div class="flex items-start justify-between mb-4">
                <div class="p-2.5 rounded-xl" style="background: #0B1E5722"><svg class="w-4 h-4" style="color: #0B1E57" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/></svg></div>
            </div>
            <div class="text-2xl font-black text-white">{{ $totalSlots - $usedSlots }}</div>
            <div class="text-xs text-white/35 mt-1 font-medium">Available Slots</div>
        </div>
        <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-5 hover:border-white/15 transition-all">
            <div class="flex items-start justify-between mb-4">
                <div class="p-2.5 rounded-xl" style="background: #901C1E22"><svg class="w-4 h-4" style="color: #901C1E" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"/></svg></div>
            </div>
            <div class="text-2xl font-black text-white">{{ $activeSubs }}</div>
            <div class="text-xs text-white/35 mt-1 font-medium">Active Subscribers</div>
        </div>
        <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-5 hover:border-white/15 transition-all">
            <div class="flex items-start justify-between mb-4">
                <div class="p-2.5 rounded-xl" style="background: #FFCD0522"><svg class="w-4 h-4" style="color: #FFCD05" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/></svg></div>
            </div>
            <div class="text-2xl font-black text-white">{{ $config->revenue_share_pct }}%</div>
            <div class="text-xs text-white/35 mt-1 font-medium">Revenue Share</div>
        </div>
        <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-5 hover:border-white/15 transition-all">
            <div class="flex items-start justify-between mb-4">
                <div class="p-2.5 rounded-xl" style="background: #2D6A4F22"><svg class="w-4 h-4" style="color: #2D6A4F" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg></div>
            </div>
            <div class="text-2xl font-black text-white">KES {{ number_format($config->wallet_balance) }}</div>
            <div class="text-xs text-white/35 mt-1 font-medium">Wallet Balance</div>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
            <h3 class="font-bold text-white mb-5">Slot Allocation</h3>
            @forelse($allocation as $a)
            <div class="mb-4">
                <div class="flex justify-between text-sm mb-1.5">
                    <span class="font-semibold text-white">{{ ucfirst($a->slot_type) }}s</span>
                    <span class="text-white/40">{{ $a->used_slots }} / {{ $a->total_slots }}</span>
                </div>
                <div class="h-2 bg-white/10 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all" style="width: {{ $a->total_slots > 0 ? ($a->used_slots / $a->total_slots) * 100 : 0 }}%; background: #FFCD05"></div>
                </div>
                <div class="flex justify-between text-xs text-white/30 mt-1">
                    <span>{{ $a->availableSlots() }} available</span>
                    <span class="text-emerald-400">{{ $a->status }}</span>
                </div>
            </div>
            @empty
            <p class="text-white/40 text-sm">No slot allocations yet.</p>
            @endforelse
        </div>
        <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
            <h3 class="font-bold text-white mb-5">Recent Subscribers</h3>
            <div class="space-y-3">
                @forelse($subscribers->take(5) as $s)
                <div class="flex items-center gap-3 py-2 border-b border-white/5 last:border-0">
                    <div class="w-10 h-10 bg-[#141B2E] rounded-xl flex items-center justify-center"><span class="text-white font-bold text-sm">{{ substr($s->user?->name ?? '?',0,1) }}</span></div>
                    <div class="flex-1">
                        <div class="text-sm font-semibold text-white">{{ $s->user?->name ?? 'Anonymous' }}</div>
                        <div class="text-xs text-white/30">{{ $s->plan?->name ?? 'No plan' }}</div>
                    </div>
                    <span class="text-[10px] font-bold px-2.5 py-1 rounded-full border {{ $s->status === 'active' ? 'text-emerald-400 bg-emerald-500/15 border-emerald-500/25' : 'text-[#FFCD05] bg-[#FFCD05]/15 border-[#FFCD05]/25' }} capitalize">{{ $s->status }}</span>
                </div>
                @empty
                <p class="text-white/40 text-sm">No subscribers yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-dashboards.shell>