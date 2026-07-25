@extends('layouts.app')

@section('title', $county->name . ' County — Subscriptions & Revenue')

@section('content')
<div class="max-w-7xl mx-auto px-6 lg:px-8 py-10">
    <div class="flex items-center justify-between mb-8">
        <div>
            <div class="text-amber-600 text-xs font-semibold uppercase tracking-[0.2em] mb-1">{{ $county->name }} County</div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Subscriptions &amp; Revenue</h1>
        </div>
        <a href="{{ route('dashboard.index') }}" class="text-sm text-amber-600 hover:text-amber-700 font-medium">&larr; Back to Dashboard</a>
    </div>

    {{-- Overview cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
        <div class="bg-kicc-dark rounded-2xl p-5 text-white">
            <div class="text-gold-400 text-xs uppercase tracking-wider mb-1">Available Slots</div>
            <div class="text-3xl font-extrabold">{{ $allocation->sum('availableSlots') }}</div>
            <div class="text-gray-400 text-sm">of {{ $allocation->sum('total_slots') }} total</div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="text-gray-400 text-xs uppercase tracking-wider mb-1">Active Subscribers</div>
            <div class="text-3xl font-extrabold text-gray-900">{{ $subscribers->where('status', 'active')->count() }}</div>
            <div class="text-gray-500 text-sm">businesses in your county</div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="text-gray-400 text-xs uppercase tracking-wider mb-1">Revenue Share</div>
            <div class="text-3xl font-extrabold text-amber-600">{{ $config->revenue_share_pct }}%</div>
            <div class="text-gray-500 text-sm">to {{ $county->name }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="text-gray-400 text-xs uppercase tracking-wider mb-1">Wallet Balance</div>
            <div class="text-3xl font-extrabold text-gray-900">KES {{ number_format($config->wallet_balance) }}</div>
            <div class="text-gray-500 text-sm">lifetime: KES {{ number_format($config->lifetime_earnings) }}</div>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-8">
        {{-- Subscription Plans --}}
        <div class="lg:col-span-2">
            <h2 class="text-xl font-extrabold text-gray-900 mb-5">Subscription Plans</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                @foreach($plan as $p)
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 hover:shadow-lg hover:border-amber-200 transition-all">
                    <div class="text-xs font-bold uppercase tracking-widest text-amber-600 mb-2">{{ $p->name }}</div>
                    <div class="text-2xl font-extrabold text-gray-900 mb-1">KES {{ number_format($p->price) }}<span class="text-sm font-medium text-gray-400">/mo</span></div>
                    <p class="text-sm text-gray-500 mb-4">{{ $p->description }}</p>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center gap-2 {{ $p->max_booths > 0 ? 'text-gray-700' : 'text-gray-400' }}">
                            <svg class="w-4 h-4 shrink-0 {{ $p->max_booths > 0 ? 'text-emerald-500' : 'text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ $p->max_booths > 0 ? $p->max_booths . ' booths' : 'Unlimited booths' }}
                        </li>
                        <li class="flex items-center gap-2 {{ $p->has_analytics ? 'text-gray-700' : 'text-gray-400' }}">
                            <svg class="w-4 h-4 shrink-0 {{ $p->has_analytics ? 'text-emerald-500' : 'text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            {{ $p->has_analytics ? 'Analytics' : 'No analytics' }}
                        </li>
                        <li class="flex items-center gap-2 {{ $p->has_livestream ? 'text-gray-700' : 'text-gray-400' }}">
                            <svg class="w-4 h-4 shrink-0 {{ $p->has_livestream ? 'text-emerald-500' : 'text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            {{ $p->has_livestream ? 'Livestream' : 'No livestream' }}
                        </li>
                    </ul>
                    <button class="mt-5 w-full text-sm font-semibold text-amber-600 border border-amber-200 rounded-xl px-4 py-2.5 hover:bg-amber-50 transition-all">Select Plan</button>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Allocation + Recent Activity --}}
        <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h3 class="font-bold text-gray-900 mb-4">Slot Allocation</h3>
                @forelse($allocation as $a)
                <div class="mb-4">
                    <div class="flex justify-between text-sm mb-1.5">
                        <span class="font-medium text-gray-700">{{ ucfirst($a->slot_type) }}s</span>
                        <span class="text-gray-500">{{ $a->used_slots }} / {{ $a->total_slots }}</span>
                    </div>
                    <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-amber-500 rounded-full transition-all" style="width: {{ $a->total_slots > 0 ? ($a->used_slots / $a->total_slots) * 100 : 0 }}%"></div>
                    </div>
                    <div class="flex justify-between text-xs text-gray-400 mt-1">
                        <span>{{ $a->availableSlots }} available</span>
                        <span>{{ $a->status }}</span>
                    </div>
                </div>
                @empty
                <p class="text-sm text-gray-400">No slot allocations yet.</p>
                @endforelse
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h3 class="font-bold text-gray-900 mb-4">Recent Transactions</h3>
                <div class="space-y-3">
                    @forelse($transactions as $txn)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">{{ ucfirst($txn->type) }}</span>
                        <span class="font-medium {{ $txn->amount >= 0 ? 'text-emerald-600' : 'text-amber-600' }}">
                            {{ $txn->amount >= 0 ? '+' : '' }}KES {{ number_format(abs($txn->amount)) }}
                        </span>
                    </div>
                    @empty
                    <p class="text-sm text-gray-400">No transactions yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h3 class="font-bold text-gray-900 mb-4">Financial Config</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Revenue share</span><span class="font-medium">{{ $config->revenue_share_pct }}%</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">M-Pesa Paybill</span><span class="font-medium">{{ $config->mpesa_paybill ?? 'Not set' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Settlement period</span><span class="font-medium">{{ $config->settlement_period ?? 'Monthly' }}</span></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endSection