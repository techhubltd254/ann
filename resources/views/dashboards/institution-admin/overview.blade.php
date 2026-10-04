<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Welcome back, {{ $institution->name }}</h1>
            <p class="text-zinc-500 text-sm mt-1">{{ $institution->county?->name }} County &middot; Institution Dashboard</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-nexora-kpi title="Total Revenue" :value="'KES ' . number_format($revenue)" growth="0" :sparkline="[12,18,15,22,20,28,25,32,30,38,35,42]" color="emerald" />
        <x-nexora-kpi title="Total Orders" :value="number_format($totalOrders)" growth="0" :sparkline="[4,8,6,10,12,7,15,11,18,14,20,22]" color="indigo" />
        <x-nexora-kpi title="Products" :value="number_format($marketplaceProducts->count())" growth="0" :sparkline="[3,3,5,5,7,7,10,10,12,12,15,15]" color="amber" />
        <x-nexora-kpi title="Videos" :value="number_format($videos->count())" growth="+2 new" :sparkline="[0,1,1,2,2,3,3,4,4,5,5,6]" color="violet" />
    </div>

    <div class="glass-card rounded-2xl p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <span class="text-[10px] font-semibold text-zinc-500 uppercase tracking-widest">Transaction History</span>
            </div>
            <a href="#" @click.prevent="setTab('transactions')" class="text-[11px] text-indigo-400 hover:text-indigo-300">View All</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead><tr class="text-zinc-500 border-b border-white/5">
                    <th class="text-left py-3 font-semibold">Order</th><th class="text-left py-3 font-semibold">Customer</th>
                    <th class="text-left py-3 font-semibold">Product</th><th class="text-left py-3 font-semibold">Status</th>
                    <th class="text-right py-3 font-semibold">Amount</th>
                </tr></thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($transactions as $tx)
                    <tr class="hover:bg-white/5 transition">
                        <td class="py-3 font-mono text-zinc-400">#{{ $tx->order_number }}</td>
                        <td class="py-3 text-zinc-200">{{ $tx->customer_name ?? 'Guest' }}</td>
                        <td class="py-3 text-zinc-400 truncate" style="max-width:180px">{{ $tx->product_name }}</td>
                        <td class="py-3"><span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">{{ $tx->payment_status ?? 'pending' }}</span></td>
                        <td class="py-3 text-right font-semibold text-zinc-200">KES {{ number_format($tx->total) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="py-12 text-center text-zinc-500 text-sm">No transactions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="glass-card rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div>
                    <div class="text-xs text-zinc-500 font-medium">Customers</div>
                    <div class="text-xl font-bold text-white">{{ $customers->count() }}</div>
                </div>
            </div>
        </div>
        <div class="glass-card rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <div>
                    <div class="text-xs text-zinc-500 font-medium">Sector Entities</div>
                    <div class="text-xl font-bold text-white">{{ $sectorEntities->count() }}</div>
                </div>
            </div>
        </div>
        <div class="glass-card rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <div>
                    <div class="text-xs text-zinc-500 font-medium">Last Sync</div>
                    <div class="text-xl font-bold text-white">{{ $institution->synced_at ? $institution->synced_at->diffForHumans() : 'Never' }}</div>
                </div>
            </div>
        </div>
    </div>
</div>