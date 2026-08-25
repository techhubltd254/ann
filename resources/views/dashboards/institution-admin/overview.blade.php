<div class="space-y-6">
    {{-- Welcome + KPI Row --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Welcome back, {{ $institution->name }}</h1>
            <p class="text-zinc-500 text-sm mt-1">{{ $institution->county?->name }} County · Institution Dashboard</p>
        </div>
        <div class="flex items-center gap-2">
            <button class="btn-ghost text-xs">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Filter
            </button>
            <select class="text-xs w-32" x-model="kpiRange">
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="monthly" selected>Monthly</option>
                <option value="yearly">Yearly</option>
            </select>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        @php
        $prev = $revenuePrevMonth ?: 1;
        $revGrowth = $revenue > 0 ? round((($revenue - $prev) / $prev) * 100, 1) : 0;
        $ordGrowth = $totalOrders > 0 ? round(($orders30d / max($totalOrders,1)) * 100, 1) : 0;
        $prodNew = $marketplaceProducts->where('created_at', '>=', now()->subDays(30))->count();
        @endphp
        <x-nexora-kpi title="Total Revenue" :value="'KES ' . number_format($revenue)" :growth="$revGrowth" :sparkline="[12,18,15,22,20,28,25,32,30,38,35,42]" color="emerald" />
        <x-nexora-kpi title="Total Orders" :value="number_format($totalOrders)" :growth="$ordGrowth" :sparkline="[4,8,6,10,12,7,15,11,18,14,20,22]" color="indigo" />
        <x-nexora-kpi title="Products" :value="number_format($marketplaceProducts->count())" :growth="$prodNew" :sparkline="[3,3,5,5,7,7,10,10,12,12,15,15]" color="amber" />
        <x-nexora-kpi title="Videos" :value="number_format($videos->count())" growth="+2 new" :sparkline="[0,1,1,2,2,3,3,4,4,5,5,6]" color="violet" />
    </div>

    {{-- Sales Chart --}}
    <div class="glass-card rounded-2xl p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <span class="text-[10px] font-semibold text-zinc-500 uppercase tracking-widest">Revenue Overview</span>
                <h3 class="text-xl font-bold text-white mt-1">KES {{ number_format($monthly->sum('new')) }}</h3>
            </div>
            <div class="flex gap-1 bg-white/5 p-1 rounded-lg">
                <button class="px-3 py-1.5 text-xs text-zinc-400 hover:text-white rounded-md">7D</button>
                <button class="px-3 py-1.5 text-xs bg-indigo-500/20 text-indigo-400 rounded-md font-medium">30D</button>
                <button class="px-3 py-1.5 text-xs text-zinc-400 hover:text-white rounded-md">90D</button>
            </div>
        </div>
        <canvas id="revenueChart" height="220"></canvas>
    </div>

    {{-- Recent Transactions --}}
    <div class="glass-card rounded-2xl p-6">
        <div class="flex items-center justify-between mb-4">
            <span class="text-[10px] font-semibold text-zinc-500 uppercase tracking-widest">Recent Transactions</span>
            <a href="#" @click.prevent="setTab('transactions')" class="text-[11px] text-indigo-400 hover:text-indigo-300">View All →</a>
        </div>
        <div class="overflow-x-auto max-h-[320px] overflow-y-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="text-zinc-500 border-b border-white/5">
                        <th class="text-left py-3 font-semibold">ID</th>
                        <th class="text-left py-3 font-semibold">Customer</th>
                        <th class="text-left py-3 font-semibold">Product</th>
                        <th class="text-left py-3 font-semibold">Status</th>
                        <th class="text-right py-3 font-semibold">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($transactions as $tx)
                    <tr class="hover:bg-white/5 transition cursor-pointer" @click="openDrawer('transaction')">
                        <td class="py-3 font-mono text-zinc-400">#{{ $tx->order_number }}</td>
                        <td class="py-3 text-zinc-200">{{ $tx->customer_name ?? 'Guest' }}</td>
                        <td class="py-3 text-zinc-400 truncate max-w-[180px]">{{ $tx->product_name }}</td>
                        <td class="py-3">
                            @php
                            $ps = $tx->payment_status ?? 'pending';
                            $badge = match($ps) {
                                'paid','released','success','completed' => 'status-paid',
                                'pending','held' => 'status-pending',
                                'failed','cancelled','refunded' => 'status-failed',
                                default => 'status-draft'
                            };
                            @endphp
                            <span class="{{ $badge }}">{{ ucfirst($ps) }}</span>
                        </td>
                        <td class="py-3 text-right font-semibold text-zinc-200">KES {{ number_format($tx->total) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center text-zinc-500 text-sm">No transactions yet. Sync your products to start selling.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Bottom quick stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="glass-card rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div>
                    <div class="text-xs text-zinc-500 font-medium">Active Customers</div>
                    <div class="text-xl font-bold text-white">{{ $customers->count() }}</div>
                </div>
            </div>
            <div class="progress-bar">
                <div class="progress-fill bg-gradient-to-r from-indigo-500 to-violet-600" style="width: {{ min($customers->count() * 10, 100) }}%"></div>
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
            <div class="progress-bar">
                <div class="progress-fill bg-gradient-to-r from-emerald-500 to-emerald-400" style="width: {{ min($sectorEntities->count() * 15, 100) }}%"></div>
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
            <div class="text-[10px] text-zinc-500">
                {{ $institution->synced_at ? 'Last synced ' . $institution->synced_at->format('d M Y H:i') : 'Run sync to publish changes' }}
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('revenueChart');
    if (!ctx) return;
    var monthly = @json($monthly);
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: monthly.map(m => m.month),
            datasets: [
                {
                    label: '{{ $institution->name }}',
                    data: monthly.map(m => m.new),
                    borderColor: '#6366F1',
                    backgroundColor: 'rgba(99,102,241,0.08)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointBackgroundColor: '#6366F1',
                    borderWidth: 2,
                },
                {
                    label: 'Other Sellers',
                    data: monthly.map(m => m.existing),
                    borderColor: '#52525B',
                    backgroundColor: 'rgba(82,82,91,0.05)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 2,
                    pointBackgroundColor: '#52525B',
                    borderWidth: 1.5,
                    borderDash: [4,4],
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: {
                    display: true,
                    labels: { color: '#A1A1AA', usePointStyle: true, pointStyle: 'circle', padding: 16, font: { size: 11 } }
                },
                tooltip: {
                    backgroundColor: 'rgba(22,25,32,0.95)',
                    titleColor: '#E2E8F0',
                    bodyColor: '#A1A1AA',
                    borderColor: 'rgba(255,255,255,0.06)',
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 8,
                }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(255,255,255,0.03)' },
                    ticks: { color: '#71717A', font: { size: 10 } }
                },
                y: {
                    grid: { color: 'rgba(255,255,255,0.03)' },
                    ticks: { color: '#71717A', font: { size: 10 }, callback: v => 'KES ' + v.toLocaleString() }
                }
            }
        }
    });
});
</script>
@endpush