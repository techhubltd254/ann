@if($tab === 'cache')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-white">Redis Cache</h2>
            <p class="text-zinc-400 text-sm mt-1">Performance monitoring — Redis on droplet :6379</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('kicc.admin') }}?tab=cache&action=clear" class="text-[10px] font-bold px-3 py-1.5 rounded-lg bg-rose-500/20 text-rose-400 hover:bg-rose-500/30"
               onclick="return confirm('Clear ALL Redis caches? Public pages may be slower temporarily.');">
                Clear All Cache
            </a>
            <a href="{{ route('kicc.admin') }}?tab=cache&action=warm" class="text-[10px] font-bold px-3 py-1.5 rounded-lg bg-emerald-500/20 text-emerald-400 hover:bg-emerald-500/30">
                Warm Cache
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="glass-card rounded-2xl p-5 border-l-4 border-emerald-500">
            <div class="text-xs text-zinc-400 uppercase tracking-wide">Cache Used</div>
            <div class="text-2xl font-bold text-white mt-1">{{ $cacheStats['used_memory'] ?? '?' }}</div>
            <div class="text-xs text-zinc-500 mt-1">Redis memory consumption</div>
        </div>
        <div class="glass-card rounded-2xl p-5 border-l-4 border-sky-500">
            <div class="text-xs text-zinc-400 uppercase tracking-wide">Hit Rate</div>
            <div class="text-2xl font-bold text-white mt-1">{{ $cacheStats['hit_rate'] ?? '?' }}</div>
            <div class="text-xs text-zinc-500 mt-1">{{ number_format($cacheStats['hits'] ?? 0) }} hits / {{ number_format($cacheStats['misses'] ?? 0) }} misses</div>
        </div>
        <div class="glass-card rounded-2xl p-5 border-l-4 border-amber-500">
            <div class="text-xs text-zinc-400 uppercase tracking-wide">Total Keys</div>
            <div class="text-2xl font-bold text-white mt-1">{{ number_format($cacheStats['keys'] ?? 0) }}</div>
            <div class="text-xs text-zinc-500 mt-1">Cached data fragments</div>
        </div>
        <div class="glass-card rounded-2xl p-5 border-l-4 border-violet-500">
            <div class="text-xs text-zinc-400 uppercase tracking-wide">Uptime</div>
            <div class="text-2xl font-bold text-white mt-1">{{ $cacheStats['uptime'] ?? '?' }}</div>
            <div class="text-xs text-zinc-500 mt-1">Redis server uptime</div>
        </div>
    </div>

    {{-- Cache tiers --}}
    <div class="glass-card rounded-2xl p-5">
        <h3 class="font-bold text-white text-sm mb-4">Cache Tiers</h3>
        <div class="space-y-3">
            @php
                $tiers = [
                    ['Public Pages', '1 hour', 'counties, marketplace, venues, exhibitions', 3600],
                    ['API Responses', '5 minutes', 'pipeline graph, escrow data, product API', 300],
                    ['Hot Data', '30 seconds', 'pool balance, real-time stats, top earners', 30],
                    ['Cold Data', '24 hours', 'pipeline registry, county classification, product categories', 86400],
                ];
            @endphp
            @foreach($tiers as $t)
            <div class="flex items-center justify-between py-2 border-b border-white/5 last:border-0">
                <div>
                    <div class="font-semibold text-white text-sm">{{ $t[0] }}</div>
                    <div class="text-xs text-zinc-500">{{ $t[2] }}</div>
                </div>
                <div class="text-right">
                    <div class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-white/10 text-zinc-300">{{ $t[1] }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Clear cache actions --}}
    <div class="glass-card rounded-2xl p-5">
        <h3 class="font-bold text-white text-sm mb-3">Cache Operations</h3>
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-zinc-300">Clear Public Pages Cache</div>
                    <div class="text-xs text-zinc-500">Busts cached county, marketplace, venue pages</div>
                </div>
                <a href="{{ route('kicc.admin') }}?tab=cache&action=clear-public" class="text-[10px] font-bold px-3 py-1.5 rounded-lg bg-white/10 text-zinc-300 hover:bg-white/20 transition-all">Clear</a>
            </div>
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-zinc-300">Warm All Cache</div>
                    <div class="text-xs text-zinc-500">Pre-populates critical cache keys (pipelines, counties, pools)</div>
                </div>
                <a href="{{ route('kicc.admin') }}?tab=cache&action=warm" class="text-[10px] font-bold px-3 py-1.5 rounded-lg bg-emerald-500/20 text-emerald-400 hover:bg-emerald-500/30">Warm</a>
            </div>
        </div>
    </div>
</div>
@endif