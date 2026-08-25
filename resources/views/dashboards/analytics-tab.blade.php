<div class="space-y-6" x-data="{
    range: 'monthly',
    showForecast: true,
    insights: [
        { icon: '📈', title: 'Revenue Momentum', text: 'Your 90-day revenue trend shows consistent growth. Consider expanding product categories to capitalize.', type: 'positive' },
        { icon: '⚠️', title: 'Customer Retention Gap', text: 'Repeat purchase rate is below industry benchmark. A loyalty program could recover 15-20% of lapsed customers.', type: 'warning' },
        { icon: '💡', title: 'Peak Season Opportunity', text: 'Historical data indicates a 40% demand surge in Q4. Start inventory planning and campaign scheduling now.', type: 'insight' },
        { icon: '🎯', title: 'Top Performer Identified', text: 'Your best-selling product drives 60% of revenue. Create bundled offers to lift secondary products.', type: 'positive' },
    ]
}">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Business Intelligence</h1>
            <p class="text-zinc-500 text-sm mt-1">Data-driven insights &amp; performance analytics</p>
        </div>
        <div class="flex items-center gap-2">
            <select class="w-36" x-model="range">
                <option value="weekly">Weekly</option>
                <option value="monthly" selected>Monthly</option>
                <option value="quarterly">Quarterly</option>
                <option value="yearly">Yearly</option>
            </select>
            <button class="btn-primary text-xs" @click="$refs.reportModal.showModal ? $refs.reportModal.showModal() : ''">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export Report
            </button>
        </div>
    </div>

    {{-- KPI Row with Trend --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-nexora-kpi title="Revenue (MTD)" :value="$analytics['revenue_mtd'] ?? 'KES 0'" :growth="$analytics['revenue_growth'] ?? 0" color="indigo" :sparkline="$analytics['revenue_sparkline'] ?? []" />
        <x-nexora-kpi title="Avg Order Value" :value="$analytics['avg_order_value'] ?? 'KES 0'" :growth="$analytics['aov_growth'] ?? 0" color="emerald" :sparkline="$analytics['aov_sparkline'] ?? []" />
        <x-nexora-kpi title="Conversion Rate" :value="$analytics['conversion_rate'] ?? '0%'" :growth="$analytics['conversion_growth'] ?? 0" color="amber" :sparkline="$analytics['conversion_sparkline'] ?? []" />
        <x-nexora-kpi title="Customer Lifetime" :value="$analytics['clv'] ?? 'KES 0'" :growth="$analytics['clv_growth'] ?? 0" color="violet" :sparkline="$analytics['clv_sparkline'] ?? []" />
    </div>

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- Revenue Forecast --}}
        <div class="lg:col-span-2 glass-card rounded-2xl p-6" style="max-height:460px;">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <span class="text-[10px] font-semibold text-zinc-500 uppercase tracking-widest">Revenue Forecast</span>
                    <h3 class="text-lg font-bold text-white mt-1">KES {{ number_format($analytics['forecast_total'] ?? 0) }}</h3>
                </div>
                <label class="flex items-center gap-2 text-xs text-zinc-400">
                    <input type="checkbox" x-model="showForecast" class="rounded border-zinc-600 bg-zinc-800">
                    Show forecast
                </label>
            </div>
            <div style="height:280px;">
            <canvas id="forecastChart" height="250"></canvas>
            </div>
        </div>

        {{-- Insights Rail --}}
        <div class="glass-card rounded-2xl p-5 overflow-y-auto" style="max-height: 380px;">
            <h3 class="text-[10px] font-semibold text-zinc-500 uppercase tracking-widest mb-4">AI Insights</h3>
            <div class="space-y-4">
                <template x-for="(item, i) in insights" :key="i">
                    <div class="flex gap-3 p-3 rounded-xl transition hover:bg-white/5 cursor-pointer" @click="item.expanded = !item.expanded">
                        <div class="text-lg shrink-0 mt-0.5" x-text="item.icon"></div>
                        <div>
                            <div class="text-sm font-semibold text-white" x-text="item.title"></div>
                            <div class="text-xs text-zinc-400 mt-1 leading-relaxed" x-text="item.text" x-show="item.expanded || true"></div>
                            <div class="flex items-center gap-2 mt-2">
                                <span class="text-[10px] px-2 py-0.5 rounded-full font-medium"
                                      :class="item.type === 'positive' ? 'bg-emerald-500/10 text-emerald-400' : (item.type === 'warning' ? 'bg-amber-500/10 text-amber-400' : 'bg-indigo-500/10 text-indigo-400')"
                                      x-text="item.type === 'positive' ? 'Actionable' : (item.type === 'warning' ? 'Attention' : 'Insight')"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Performance Breakdown --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="glass-card rounded-2xl p-6">
            <span class="text-[10px] font-semibold text-zinc-500 uppercase tracking-widest">Revenue by Source</span>
            <canvas id="sourceChart" height="220" class="mt-4"></canvas>
        </div>
        <div class="glass-card rounded-2xl p-6">
            <span class="text-[10px] font-semibold text-zinc-500 uppercase tracking-widest">Monthly Performance</span>
            <canvas id="performanceChart" height="220" class="mt-4"></canvas>
        </div>
    </div>

{{-- Growth Metrics Table --}}
    <div class="glass-card rounded-2xl">
        <div class="px-6 py-4 border-b border-white/5 flex items-center justify-between">
            <span class="text-[10px] font-semibold text-zinc-500 uppercase tracking-widest">Growth Metrics</span>
            <div class="flex gap-1 text-xs">
                <button class="px-2.5 py-1 rounded bg-white/10 text-zinc-200 font-medium">MoM</button>
                <button class="px-2.5 py-1 rounded text-zinc-500 hover:text-zinc-200">QoQ</button>
                <button class="px-2.5 py-1 rounded text-zinc-500 hover:text-zinc-200">YoY</button>
            </div>
        </div>
        <div class="overflow-y-auto max-h-[320px]">
        <table class="w-full text-xs">
            <thead>
                <tr class="text-zinc-500 border-b border-white/5">
                    <th class="text-left py-3.5 px-6 font-semibold">Metric</th>
                    <th class="text-right py-3.5 font-semibold">Current</th>
                    <th class="text-right py-3.5 font-semibold">Previous</th>
                    <th class="text-right py-3.5 pr-6 font-semibold">Change</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @foreach($analytics['metrics'] ?? [] as $metric)
                <tr class="hover:bg-white/5 transition">
                    <td class="py-3.5 px-6 text-zinc-200">{{ $metric['label'] }}</td>
                    <td class="py-3.5 text-right text-zinc-200 font-medium">{{ $metric['current'] }}</td>
                    <td class="py-3.5 text-right text-zinc-500">{{ $metric['previous'] }}</td>
                    <td class="py-3.5 pr-6 text-right font-semibold {{ ($metric['change'] ?? 0) >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                        {{ ($metric['change'] ?? 0) >= 0 ? '+' : '' }}{{ number_format($metric['change'] ?? 0, 1) }}%
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Forecast Chart
    var fc = document.getElementById('forecastChart');
    if (fc) {
        var forecastData = @json($analytics['forecast_data'] ?? []);
        new Chart(fc, {
            type: 'line',
            data: {
                labels: forecastData.map(function(d) { return d.label || ''; }),
                datasets: [
                    {
                        label: 'Actual',
                        data: forecastData.map(function(d) { return d.actual; }),
                        borderColor: '#6366F1',
                        backgroundColor: 'rgba(99,102,241,0.1)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointBackgroundColor: '#6366F1',
                        borderWidth: 2,
                    },
                    {
                        label: 'Forecast',
                        data: forecastData.map(function(d) { return d.forecast; }),
                        borderColor: '#8B5CF6',
                        backgroundColor: 'rgba(139,92,246,0.05)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 3,
                        pointBorderColor: '#8B5CF6',
                        pointBorderWidth: 2,
                        pointBackgroundColor: 'transparent',
                        borderWidth: 2,
                        borderDash: [6, 4],
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: {
                    legend: { labels: { color: '#A1A1AA', usePointStyle: true, font: { size: 11 } } },
                    tooltip: { backgroundColor: 'rgba(22,25,32,0.95)', titleColor: '#E2E8F0', bodyColor: '#A1A1AA', borderColor: 'rgba(255,255,255,0.06)', borderWidth: 1, padding: 12, cornerRadius: 8 }
                },
                scales: {
                    x: { grid: { color: 'rgba(255,255,255,0.03)' }, ticks: { color: '#71717A', font: { size: 10 } } },
                    y: { grid: { color: 'rgba(255,255,255,0.03)' }, ticks: { color: '#71717A', font: { size: 10 } } }
                }
            }
        });
    }

    // Source (Donut) Chart
    var sc = document.getElementById('sourceChart');
    if (sc) {
        var sourceData = @json($analytics['source_data'] ?? []);
        new Chart(sc, {
            type: 'doughnut',
            data: {
                labels: sourceData.map(function(d) { return d.label; }),
                datasets: [{
                    data: sourceData.map(function(d) { return d.value; }),
                    backgroundColor: ['#6366F1', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899', '#06B6D4'],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: { position: 'bottom', labels: { color: '#A1A1AA', padding: 12, usePointStyle: true, font: { size: 10 } } },
                    tooltip: { backgroundColor: 'rgba(22,25,32,0.95)', titleColor: '#E2E8F0', bodyColor: '#A1A1AA', borderColor: 'rgba(255,255,255,0.06)', borderWidth: 1, padding: 12 }
                }
            }
        });
    }

    // Performance (Bar) Chart
    var pc = document.getElementById('performanceChart');
    if (pc) {
        var perfData = @json($analytics['performance_data'] ?? []);
        new Chart(pc, {
            type: 'bar',
            data: {
                labels: perfData.map(function(d) { return d.label; }),
                datasets: [
                    {
                        label: 'Revenue',
                        data: perfData.map(function(d) { return d.revenue; }),
                        backgroundColor: 'rgba(99,102,241,0.7)',
                        borderRadius: 4,
                    },
                    {
                        label: 'Orders',
                        data: perfData.map(function(d) { return d.orders; }),
                        backgroundColor: 'rgba(16,185,129,0.7)',
                        borderRadius: 4,
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { labels: { color: '#A1A1AA', usePointStyle: true, font: { size: 10 } } } },
                scales: {
                    x: { grid: { color: 'rgba(255,255,255,0.03)' }, ticks: { color: '#71717A', font: { size: 10 } } },
                    y: { grid: { color: 'rgba(255,255,255,0.03)' }, ticks: { color: '#71717A', font: { size: 10 } } }
                }
            }
        });
    }
});
</script>
@endpush