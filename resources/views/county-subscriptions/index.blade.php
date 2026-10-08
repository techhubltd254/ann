@extends('layouts.app')

@section('title', ($county->name ?? 'County') . ' — Subscriptions & Revenue')

@section('content')
<div class="max-w-7xl mx-auto px-5 py-10">
    <div class="flex items-center justify-between mb-8" data-reveal>
        <div>
            <div class="text-kicc-gold text-xs font-bold uppercase tracking-[0.2em] mb-1">{{ $county->name }} County</div>
            <h1 class="text-3xl font-black text-gray-900 tracking-tight" data-split>Subscriptions &amp; Revenue</h1>
        </div>
        <a href="{{ route('dashboard.index') }}" class="text-sm text-kicc-gold hover:underline font-semibold" data-magnetic>&larr; Back to Dashboard</a>
    </div>

    {{-- Overview cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
        @foreach([
            ['Available Slots', $allocation->sum('availableSlots'), 'of ' . $allocation->sum('total_slots') . ' total', '#2a2a2a'],
            ['Active Subscribers', $subscribers->where('status', 'active')->count(), 'businesses in your county', '#b3261e'],
            ['Revenue Share', $config->revenue_share_pct . '%', 'to ' . $county->name, '#FFCD05'],
            ['Wallet Balance', 'KES ' . number_format($config->wallet_balance), 'lifetime: KES ' . number_format($config->lifetime_earnings ?? 0), '#2a2a2a'],
        ] as $i => $c)
        <div class="bg-white border border-gray-200 rounded-2xl p-5 card-hover" data-tilt="6" data-reveal data-reveal-delay="{{ $i * 70 }}">
            <div class="tilt-glare"></div>
            <div class="text-xs uppercase tracking-wider mb-1 font-bold" style="color: {{ $c[3] }}">{{ $c[0] }}</div>
            <div class="text-2xl font-black text-gray-900">{{ $c[1] }}</div>
            <div class="text-gray-400 text-xs mt-1">{{ $c[2] }}</div>
        </div>
        @endforeach
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Subscription Plans --}}
        <div class="lg:col-span-2">
            <h2 class="text-xl font-black text-gray-900 mb-5" data-reveal>Subscription Plans</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                @foreach($plan as $i => $p)
                <div class="bg-white rounded-2xl border border-gray-200 p-6 hover:border-kicc-gold/40 transition-all card-hover" data-reveal data-reveal-delay="{{ ($i % 2) * 90 }}">
                    <div class="text-xs font-bold uppercase tracking-widest text-kicc-gold mb-2">{{ $p->name }}</div>
                    <div class="text-2xl font-black text-gray-900 mb-1">KES {{ number_format($p->price) }}<span class="text-sm font-medium text-gray-400">/mo</span></div>
                    <p class="text-sm text-[#5A6480] mb-4">{{ $p->description }}</p>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center gap-2 {{ $p->max_booths > 0 ? 'text-[#5A6480]' : 'text-gray-400' }}">
                            <svg class="w-4 h-4 shrink-0 {{ $p->max_booths > 0 ? 'text-emerald-400' : 'text-gray-900/20' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ $p->max_booths > 0 ? $p->max_booths . ' booths' : 'Unlimited booths' }}
                        </li>
                        <li class="flex items-center gap-2 {{ $p->has_analytics ? 'text-[#5A6480]' : 'text-gray-400' }}">
                            <svg class="w-4 h-4 shrink-0 {{ $p->has_analytics ? 'text-emerald-400' : 'text-gray-900/20' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            {{ $p->has_analytics ? 'Analytics' : 'No analytics' }}
                        </li>
                        <li class="flex items-center gap-2 {{ $p->has_livestream ? 'text-[#5A6480]' : 'text-gray-400' }}">
                            <svg class="w-4 h-4 shrink-0 {{ $p->has_livestream ? 'text-emerald-400' : 'text-gray-900/20' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            {{ $p->has_livestream ? 'Livestream' : 'No livestream' }}
                        </li>
                    </ul>
                    <form method="POST" action="{{ route('subscriptions.index') }}" class="mt-5">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $p->id }}">
                        <button type="submit" class="w-full text-sm font-bold text-kicc-gold border border-kicc-gold/30 rounded-xl px-4 py-2.5 hover:bg-kicc-gold/10 transition-all" data-magnetic>Select Plan</button>
                    </form>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Allocation + Recent Activity --}}
        <div class="space-y-5">
            <div class="bg-white rounded-2xl border border-gray-200 p-6 card-hover" data-reveal>
                <h3 class="font-bold text-gray-900 mb-4 text-sm uppercase tracking-wider">Slot Allocation</h3>
                @forelse($allocation as $a)
                <div class="mb-4">
                    <div class="flex justify-between text-sm mb-1.5">
                        <span class="font-semibold text-gray-700">{{ ucfirst($a->slot_type) }}s</span>
                        <span class="text-[#5A6480]">{{ $a->used_slots }} / {{ $a->total_slots }}</span>
                    </div>
                    <div class="h-2 bg-sky-100 rounded-full overflow-hidden">
                        <div class="h-full bg-kicc-gold rounded-full transition-all" style="width: {{ $a->total_slots > 0 ? ($a->used_slots / $a->total_slots) * 100 : 0 }}%"></div>
                    </div>
                    <div class="flex justify-between text-xs text-[#5A6480] mt-1">
                        <span>{{ $a->availableSlots() }} available</span>
                        <span class="text-emerald-400">{{ $a->status }}</span>
                    </div>
                </div>
                @empty
                <p class="text-sm text-[#5A6480]">No slot allocations yet.</p>
                @endforelse
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 p-6 card-hover" data-reveal data-reveal-delay="90">
                <h3 class="font-bold text-gray-900 mb-4 text-sm uppercase tracking-wider">Recent Transactions</h3>
                <div class="space-y-3">
                    @forelse($transactions as $txn)
                    <div class="flex justify-between text-sm">
                        <span class="text-[#5A6480]">{{ ucfirst($txn->type) }}</span>
                        <span class="font-bold {{ $txn->amount >= 0 ? 'text-emerald-400' : 'text-[#e86f71]' }}">
                            {{ $txn->amount >= 0 ? '+' : '' }}KES {{ number_format(abs($txn->amount)) }}
                        </span>
                    </div>
                    @empty
                    <p class="text-sm text-[#5A6480]">No transactions yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 p-6 card-hover" data-reveal data-reveal-delay="180">
                <h3 class="font-bold text-gray-900 mb-4 text-sm uppercase tracking-wider">Financial Config</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-[#5A6480]">Revenue share</span><span class="font-bold text-gray-900">{{ $config->revenue_share_pct }}%</span></div>
                    <div class="flex justify-between"><span class="text-[#5A6480]">M-Pesa Paybill</span><span class="font-bold text-gray-900">{{ $config->mpesa_paybill ?? 'Not set' }}</span></div>
                    <div class="flex justify-between"><span class="text-[#5A6480]">Settlement period</span><span class="font-bold text-gray-900 capitalize">{{ $config->settlement_period ?? 'Monthly' }}</span></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
