@extends('layouts.app')

@section('title', ($county->name ?? 'County') . ' — Subscriptions & Revenue')

@section('content')
<div class="max-w-7xl mx-auto px-5 py-10">
    <div class="flex items-center justify-between mb-8" data-reveal>
        <div>
            <div class="text-kicc-gold text-xs font-bold uppercase tracking-[0.2em] mb-1">{{ $county->name }} County</div>
            <h1 class="text-3xl font-black text-white tracking-tight">Subscriptions &amp; Revenue</h1>
        </div>
        <a href="{{ route('dashboard.index') }}" class="text-sm text-kicc-gold hover:underline font-semibold">&larr; Back to Dashboard</a>
    </div>

    {{-- Overview cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
        @foreach([
            ['Available Slots', $allocation->sum('availableSlots'), 'of ' . $allocation->sum('total_slots') . ' total', '#0B1E57'],
            ['Active Subscribers', $subscribers->where('status', 'active')->count(), 'businesses in your county', '#901C1E'],
            ['Revenue Share', $config->revenue_share_pct . '%', 'to ' . $county->name, '#FFCD05'],
            ['Wallet Balance', 'KES ' . number_format($config->wallet_balance), 'lifetime: KES ' . number_format($config->lifetime_earnings ?? 0), '#2D6A4F'],
        ] as $i => $c)
        <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-5 card-hover" data-tilt="6" data-reveal data-reveal-delay="{{ $i * 70 }}">
            <div class="tilt-glare"></div>
            <div class="text-xs uppercase tracking-wider mb-1 font-bold" style="color: {{ $c[3] }}">{{ $c[0] }}</div>
            <div class="text-2xl font-black text-white">{{ $c[1] }}</div>
            <div class="text-white/35 text-xs mt-1">{{ $c[2] }}</div>
        </div>
        @endforeach
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Subscription Plans --}}
        <div class="lg:col-span-2">
            <h2 class="text-xl font-black text-white mb-5" data-reveal>Subscription Plans</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                @foreach($plan as $i => $p)
                <div class="bg-[#0D1220] rounded-2xl border border-white/8 p-6 hover:border-kicc-gold/40 transition-all card-hover" data-reveal data-reveal-delay="{{ ($i % 2) * 90 }}">
                    <div class="text-xs font-bold uppercase tracking-widest text-kicc-gold mb-2">{{ $p->name }}</div>
                    <div class="text-2xl font-black text-white mb-1">KES {{ number_format($p->price) }}<span class="text-sm font-medium text-white/35">/mo</span></div>
                    <p class="text-sm text-white/40 mb-4">{{ $p->description }}</p>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center gap-2 {{ $p->max_booths > 0 ? 'text-white/70' : 'text-white/35' }}">
                            <svg class="w-4 h-4 shrink-0 {{ $p->max_booths > 0 ? 'text-emerald-400' : 'text-white/20' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ $p->max_booths > 0 ? $p->max_booths . ' booths' : 'Unlimited booths' }}
                        </li>
                        <li class="flex items-center gap-2 {{ $p->has_analytics ? 'text-white/70' : 'text-white/35' }}">
                            <svg class="w-4 h-4 shrink-0 {{ $p->has_analytics ? 'text-emerald-400' : 'text-white/20' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            {{ $p->has_analytics ? 'Analytics' : 'No analytics' }}
                        </li>
                        <li class="flex items-center gap-2 {{ $p->has_livestream ? 'text-white/70' : 'text-white/35' }}">
                            <svg class="w-4 h-4 shrink-0 {{ $p->has_livestream ? 'text-emerald-400' : 'text-white/20' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            {{ $p->has_livestream ? 'Livestream' : 'No livestream' }}
                        </li>
                    </ul>
                    <form method="POST" action="{{ route('subscriptions.index') }}" class="mt-5">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $p->id }}">
                        <button type="submit" class="w-full text-sm font-bold text-kicc-gold border border-kicc-gold/30 rounded-xl px-4 py-2.5 hover:bg-kicc-gold/10 transition-all">Select Plan</button>
                    </form>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Allocation + Recent Activity --}}
        <div class="space-y-5">
            <div class="bg-[#0D1220] rounded-2xl border border-white/8 p-6" data-reveal>
                <h3 class="font-bold text-white mb-4 text-sm uppercase tracking-wider">Slot Allocation</h3>
                @forelse($allocation as $a)
                <div class="mb-4">
                    <div class="flex justify-between text-sm mb-1.5">
                        <span class="font-semibold text-white/80">{{ ucfirst($a->slot_type) }}s</span>
                        <span class="text-white/40">{{ $a->used_slots }} / {{ $a->total_slots }}</span>
                    </div>
                    <div class="h-2 bg-white/10 rounded-full overflow-hidden">
                        <div class="h-full bg-kicc-gold rounded-full transition-all" style="width: {{ $a->total_slots > 0 ? ($a->used_slots / $a->total_slots) * 100 : 0 }}%"></div>
                    </div>
                    <div class="flex justify-between text-xs text-white/30 mt-1">
                        <span>{{ $a->availableSlots() }} available</span>
                        <span class="text-emerald-400">{{ $a->status }}</span>
                    </div>
                </div>
                @empty
                <p class="text-sm text-white/30">No slot allocations yet.</p>
                @endforelse
            </div>

            <div class="bg-[#0D1220] rounded-2xl border border-white/8 p-6" data-reveal data-reveal-delay="90">
                <h3 class="font-bold text-white mb-4 text-sm uppercase tracking-wider">Recent Transactions</h3>
                <div class="space-y-3">
                    @forelse($transactions as $txn)
                    <div class="flex justify-between text-sm">
                        <span class="text-white/60">{{ ucfirst($txn->type) }}</span>
                        <span class="font-bold {{ $txn->amount >= 0 ? 'text-emerald-400' : 'text-[#e86f71]' }}">
                            {{ $txn->amount >= 0 ? '+' : '' }}KES {{ number_format(abs($txn->amount)) }}
                        </span>
                    </div>
                    @empty
                    <p class="text-sm text-white/30">No transactions yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-[#0D1220] rounded-2xl border border-white/8 p-6" data-reveal data-reveal-delay="180">
                <h3 class="font-bold text-white mb-4 text-sm uppercase tracking-wider">Financial Config</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-white/40">Revenue share</span><span class="font-bold text-white">{{ $config->revenue_share_pct }}%</span></div>
                    <div class="flex justify-between"><span class="text-white/40">M-Pesa Paybill</span><span class="font-bold text-white">{{ $config->mpesa_paybill ?? 'Not set' }}</span></div>
                    <div class="flex justify-between"><span class="text-white/40">Settlement period</span><span class="font-bold text-white capitalize">{{ $config->settlement_period ?? 'Monthly' }}</span></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
