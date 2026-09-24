@if($tab === 'earnings')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-white">Pipeline Earnings</h2>
            <p class="text-zinc-400 text-sm mt-1">Live revenue captured by every pipeline — escrow released → pool accrued → ledger posted</p>
        </div>
        <div class="flex gap-2">
            <span class="text-[10px] font-bold px-3 py-1.5 rounded-full bg-emerald-500/20 text-emerald-400">
                {{ $earnCoverage['earned'] ?? 0 }}/{{ $earnCoverage['total'] ?? 0 }} pipelines earning
            </span>
        </div>
    </div>

    {{-- Top KPI cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="glass-card rounded-2xl p-5 border-l-4 border-emerald-500">
            <div class="text-xs text-zinc-400 uppercase tracking-wide">Total GMV Captured</div>
            <div class="text-2xl font-bold text-white mt-1">KES {{ number_format($earningsTotal ?? 0) }}</div>
            <div class="text-xs text-zinc-500 mt-1">{{ number_format($earningsCount ?? 0) }} released escrows</div>
        </div>
        <div class="glass-card rounded-2xl p-5 border-l-4 border-sky-500">
            <div class="text-xs text-zinc-400 uppercase tracking-wide">Mother Pool Balance</div>
            <div class="text-2xl font-bold text-white mt-1">KES {{ number_format($poolEarnings ?? 0) }}</div>
            <div class="text-xs text-zinc-500 mt-1">contributions: KES {{ number_format($poolContribTotal ?? 0) }}</div>
        </div>
        <div class="glass-card rounded-2xl p-5 border-l-4 border-amber-500">
            <div class="text-xs text-zinc-400 uppercase tracking-wide">Pipelines Earning</div>
            <div class="text-2xl font-bold text-white mt-1">{{ $earnCoverage['earned'] ?? 0 }} / {{ $earnCoverage['total'] ?? 0 }}</div>
            <div class="text-xs text-zinc-500 mt-1">of 214 registered pipelines</div>
        </div>
        <div class="glass-card rounded-2xl p-5 border-l-4 border-rose-500">
            <div class="text-xs text-zinc-400 uppercase tracking-wide">Revenue Engine</div>
            <div class="text-2xl font-bold text-white mt-1">
                <span class="text-emerald-400">&#9679;</span> {{ $earningsCount > 0 ? 'ACTIVE' : 'IDLE' }}
            </div>
            <div class="text-xs text-zinc-500 mt-1">run: php artisan kicc:pipeline:earn</div>
        </div>
    </div>

    {{-- Earnings by pipeline --}}
    <div class="glass-card rounded-2xl p-5">
        <h3 class="font-bold text-white text-sm mb-4">Top Earning Pipelines</h3>
        <div class="overflow-x-auto" style="max-height:400px; overflow-y:auto;">
            <table class="w-full text-xs">
                <thead><tr class="text-zinc-500 border-b border-white/5">
                    <th class="text-left py-2 pr-3 font-semibold">Pipeline</th>
                    <th class="text-left py-2 pr-3 font-semibold">Trades</th>
                    <th class="text-left py-2 pr-3 font-semibold">GMV</th>
                    <th class="text-left py-2 font-semibold">Share</th>
                </tr></thead>
                <tbody class="divide-y divide-white/5">
                @forelse($earningsByPipeline as $e)
                <tr class="hover:bg-white/5 transition">
                    <td class="py-2.5 pr-3">
                        <span class="font-mono font-bold text-[#FFCD05]">{{ $e->code }}</span>
                        <span class="text-[10px] text-zinc-500 ml-1">{{ \Illuminate\Support\Str::title(str_replace('-', ' ', \Illuminate\Support\Str::slug($e->code))) }}</span>
                    </td>
                    <td class="py-2.5 pr-3 text-zinc-300">{{ $e->trades }}</td>
                    <td class="py-2.5 pr-3 font-mono text-white font-bold">KES {{ number_format($e->gmv) }}</td>
                    <td class="py-2.5">
                        @php $pct = $earningsTotal > 0 ? ($e->gmv / $earningsTotal) * 100 : 0; @endphp
                        <div class="flex items-center gap-2">
                            <div class="w-20 h-1.5 rounded-full bg-zinc-700 overflow-hidden">
                                <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-emerald-400" style="width: {{ $pct }}%"></div>
                            </div>
                            <span class="text-[10px] text-zinc-500">{{ number_format($pct, 1) }}%</span>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="py-8 text-center text-zinc-500">No earnings yet. Run <code class="text-zinc-400 bg-zinc-700 px-1 rounded">php artisan kicc:pipeline:earn</code> to fill the pipeline.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Earnings by day + coverage --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="glass-card rounded-2xl p-5">
            <h3 class="font-bold text-white text-sm mb-4">Revenue by Day (last 14)</h3>
            <div class="space-y-2">
                @forelse($earningsByDay as $d)
                <div class="flex items-center justify-between text-xs">
                    <span class="text-zinc-400">{{ $d->day }}</span>
                    <div class="flex-1 mx-3 h-2 rounded-full bg-zinc-700 overflow-hidden">
                        @php $max = $earningsByDay->first()?->gmv ?: 1; @endphp
                        <div class="h-full rounded-full bg-gradient-to-r from-sky-500 to-blue-400" style="width: {{ ($d->gmv / $max) * 100 }}%"></div>
                    </div>
                    <span class="text-zinc-300 font-mono">KES {{ number_format($d->gmv) }} · {{ $d->trades }} trades</span>
                </div>
                @empty
                <p class="text-zinc-500 text-sm py-4 text-center">No revenue yet.</p>
                @endforelse
            </div>
        </div>
        <div class="glass-card rounded-2xl p-5">
            <h3 class="font-bold text-white text-sm mb-4">Pipeline Earnings Coverage</h3>
            <div class="text-center py-4">
                <div class="text-5xl font-black text-white">{{ $earnCoverage['earned'] ?? 0 }}<span class="text-zinc-500 text-2xl">/{{ $earnCoverage['total'] ?? 0 }}</span></div>
                <div class="text-xs text-zinc-500 mt-2">pipelines with real escrow-settled revenue</div>
                <div class="mt-4 h-2.5 rounded-full bg-zinc-700 overflow-hidden max-w-md mx-auto">
                    @php $covPct = ($earnCoverage['total'] ?? 0) > 0 ? (($earnCoverage['earned'] ?? 0) / $earnCoverage['total']) * 100 : 0; @endphp
                    <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-emerald-400" style="width: {{ $covPct }}%"></div>
                </div>
                <div class="text-xs text-zinc-500 mt-2">{{ number_format($covPct, 1) }}% coverage</div>
            </div>
            <div class="flex justify-center gap-3 mt-4">
                <a href="{{ route('kicc.admin', ['tab' => 'pool']) }}" class="text-[10px] font-bold px-4 py-2 rounded-lg bg-rose-500/20 text-rose-400 hover:bg-rose-500/30 transition-all">View Selling Pool</a>
                <a href="{{ route('kicc.admin', ['tab' => 'escrow']) }}" class="text-[10px] font-bold px-4 py-2 rounded-lg bg-white/5 text-zinc-300 hover:bg-white/10 transition-all">View Escrows</a>
            </div>
        </div>
    </div>
</div>
@endif