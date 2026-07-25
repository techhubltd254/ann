@php
$totalSlots = $allocation->sum('total_slots');
$usedSlots = $allocation->sum('used_slots');
$activeSubs = $subscribers->where('status', 'active')->count();
@endphp

@extends('layouts.blank', ['title' => $county->name . ' County Admin', 'bodyClass' => ''])

@section('content')
<div class="flex min-h-screen bg-[#07090F]">
    <div x-data="{ collapsed: false }" class="flex">
        <div class="w-[230px] bg-[#050709] border-r border-white/8 flex flex-col shrink-0 min-h-screen">
            <div class="flex items-center justify-between px-4 border-b border-white/8 h-16">
                <div class="font-black text-white text-xs tracking-tight leading-tight">
                    <div>KICC</div>
                    <div class="text-[#FFCD05] text-[9px] tracking-[0.15em] uppercase">Global Exhibition</div>
                </div>
            </div>
            <div class="flex-1 py-3 overflow-y-auto">
                @foreach($navItems as $item)
                <a href="{{ $item['route'] ?? '#' }}" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-semibold transition-all text-white/35 hover:bg-white/5 hover:text-white"
                   style="{{ ($item['active'] ?? false) ? 'background: #0B1E5722; border-right: 2px solid #0B1E57; color: white' : '' }}">
                    @if(isset($item['icon']))
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/></svg>
                    @endif
                    <span class="truncate text-xs">{{ $item['label'] }}</span>
                </a>
                @endforeach
            </div>
            <a href="/" class="flex items-center gap-3 px-4 py-4 border-t border-white/8 text-white/30 hover:text-white text-xs transition-colors">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                <span>Exit Dashboard</span>
            </a>
        </div>
    </div>
    <div class="flex-1 flex flex-col overflow-hidden">
        <div class="bg-[#050709] border-b border-white/8 px-6 h-16 flex items-center justify-between shrink-0">
            <div>
                <div class="font-black text-white text-sm">{{ $county->name }} County Administration</div>
                <div class="text-[10px] font-bold uppercase tracking-widest" style="color: #0B1E57">COUNTY ADMIN</div>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-white font-black text-xs" style="background: #0B1E57">{{ substr($county->name,0,2) }}</div>
            </div>
        </div>
        <div class="flex-1 overflow-y-auto p-6 space-y-8">
            {{-- KPI Cards --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-5">
                    <div class="flex items-start justify-between mb-4"><div class="p-2.5 rounded-xl" style="background: #0B1E5722"><svg class="w-4 h-4 text-[#0B1E57]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/></svg></div></div>
                    <div class="text-2xl font-black text-white">{{ $totalSlots - $usedSlots }}</div>
                    <div class="text-xs text-white/35 mt-1 font-medium">Available Slots</div>
                </div>
                <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-5">
                    <div class="flex items-start justify-between mb-4"><div class="p-2.5 rounded-xl" style="background: #901C1E22"><svg class="w-4 h-4 text-[#901C1E]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"/></svg></div></div>
                    <div class="text-2xl font-black text-white">{{ $activeSubs }}</div>
                    <div class="text-xs text-white/35 mt-1 font-medium">Active Subscribers</div>
                </div>
                <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-5">
                    <div class="flex items-start justify-between mb-4"><div class="p-2.5 rounded-xl" style="background: #FFCD0522"><svg class="w-4 h-4 text-[#FFCD05]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/></svg></div></div>
                    <div class="text-2xl font-black text-white">{{ $config->revenue_share_pct }}%</div>
                    <div class="text-xs text-white/35 mt-1 font-medium">Revenue Share</div>
                </div>
                <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-5">
                    <div class="flex items-start justify-between mb-4"><div class="p-2.5 rounded-xl" style="background: #2D6A4F22"><svg class="w-4 h-4 text-[#2D6A4F]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg></div></div>
                    <div class="text-2xl font-black text-white">KES {{ number_format($config->wallet_balance) }}</div>
                    <div class="text-xs text-white/35 mt-1 font-medium">Wallet Balance</div>
                </div>
            </div>

            {{-- Slots + Subscribers --}}
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
        </div>
    </div>
</div>
@endsection