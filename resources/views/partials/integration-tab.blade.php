@if($tab === 'integration')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-white">Integration Layer</h2>
            <p class="text-zinc-400 text-sm mt-1">Node.js service on port 8787 — 6 payments · 6 freight · 2 services · 14 webhooks</p>
        </div>
        <div class="flex gap-2">
            <span class="text-[10px] font-bold px-3 py-1.5 rounded-full {{ $integrationHealth['ok'] ?? false ? 'bg-emerald-500/20 text-emerald-400' : 'bg-rose-500/20 text-rose-400' }}">
                {{ ($integrationHealth['ok'] ?? false) ? 'ONLINE' : 'OFFLINE' }}
            </span>
            <span class="text-[10px] font-bold px-3 py-1.5 rounded-full bg-amber-500/20 text-amber-400">
                MOCK {{ ($integrationHealth['mock_mode'] ?? true) ? 'ON' : 'OFF' }}
            </span>
        </div>
    </div>

    {{-- Service Health Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="glass-card rounded-2xl p-5 border-l-4 border-emerald-500">
            <div class="text-xs text-zinc-400 uppercase tracking-wide">Integration (8787)</div>
            <div class="text-2xl font-bold text-white mt-1">
                @if($integrationHealth['ok'] ?? false)
                <span class="text-emerald-400">&#9679;</span> Healthy
                @else
                <span class="text-rose-400">&#9679;</span> Offline
                @endif
            </div>
            <div class="text-xs text-zinc-500 mt-1">
                {{ $integrationHealth['payments'] ?? 0 }} payments &middot; {{ $integrationHealth['freight'] ?? 0 }} freight &middot; {{ count($integrationHealth['routes'] ?? []) }} webhooks
            </div>
        </div>
        <div class="glass-card rounded-2xl p-5 border-l-4 border-sky-500">
            <div class="text-xs text-zinc-400 uppercase tracking-wide">Algorithms (8400)</div>
            <div class="text-2xl font-bold text-white mt-1">
                @if($algorithmsHealth['status'] ?? false)
                <span class="text-emerald-400">&#9679;</span> {{ $algorithmsHealth['algorithms'] ?? 0 }} algos
                @else
                <span class="text-rose-400">&#9679;</span> Offline
                @endif
            </div>
            <div class="text-xs text-zinc-500 mt-1">20 algorithms &middot; 6 endpoints</div>
        </div>
        <div class="glass-card rounded-2xl p-5 border-l-4 border-amber-500">
            <div class="text-xs text-zinc-400 uppercase tracking-wide">Providers Configured</div>
            <div class="text-2xl font-bold text-white mt-1">{{ count($integrationCredentials) }}/80</div>
            <div class="text-xs text-zinc-500 mt-1">{{ $integrationLiveCount }} live · {{ 80 - count($integrationCredentials) }} still blank</div>
        </div>
    </div>

    {{-- Provider Status Table --}}
    <div class="glass-card rounded-2xl p-5">
        <h3 class="font-bold text-white text-sm mb-4">Provider Credential Status</h3>
        <div class="overflow-x-auto" style="max-height:500px; overflow-y:auto;">
            <table class="w-full text-xs">
                <thead><tr class="text-zinc-500 border-b border-white/5">
                    <th class="text-left py-2 pr-3 font-semibold">Provider</th>
                    <th class="text-left py-2 pr-3 font-semibold">Kind</th>
                    <th class="text-left py-2 pr-3 font-semibold">Lanes</th>
                    <th class="text-left py-2 pr-3 font-semibold">Status</th>
                    <th class="text-left py-2 font-semibold">Missing Vars</th>
                </tr></thead>
                <tbody class="divide-y divide-white/5">
                @forelse($integrationProviders as $p)
                <tr class="hover:bg-white/5 transition">
                    <td class="py-2.5 pr-3">
                        <div class="text-white font-semibold">{{ $p['label'] }}</div>
                        <div class="text-[9px] text-zinc-500 font-mono">{{ $p['id'] }}</div>
                    </td>
                    <td class="py-2.5 pr-3 text-zinc-400">{{ $p['kind'] }}</td>
                    <td class="py-2.5 pr-3 text-zinc-500">{{ implode(', ', $p['lanes'] ?? []) }}</td>
                    <td class="py-2.5 pr-3">
                        @if($p['mock'] ?? false)
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-400">MOCK</span>
                        @else
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400">LIVE</span>
                        @endif
                    </td>
                    <td class="py-2.5">
                        @if(count($p['missing'] ?? []) > 0)
                        <span class="text-[10px] text-rose-400">{{ implode(', ', array_slice($p['missing'] ?? [], 0, 2)) }}{{ count($p['missing'] ?? []) > 2 ? ' ...' : '' }}</span>
                        @else
                        <span class="text-[10px] text-emerald-400">Complete</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="py-8 text-center text-zinc-500">Cannot reach integration service</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Quick actions --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="glass-card rounded-2xl p-5">
            <h3 class="font-bold text-white text-sm mb-3">Webhook Routes ({{ count($integrationWebhookRoutes) }})</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($integrationWebhookRoutes as $route)
                <span class="text-[10px] font-mono px-2.5 py-1.5 rounded-lg bg-white/5 text-zinc-300 border border-white/10">/webhook/{{ $route }}</span>
                @endforeach
            </div>
        </div>
        <div class="glass-card rounded-2xl p-5">
            <h3 class="font-bold text-white text-sm mb-3">Quick Actions</h3>
            <div class="space-y-2">
                <a href="{{ route('kicc.admin.artisan') }}?command=integration:health" class="flex items-center gap-2 text-xs text-zinc-300 hover:text-white transition">
                    <span class="w-6 h-6 rounded-lg bg-zinc-800 flex items-center justify-center text-[10px]">&#9654;</span>
                    Check Integration Health
                </a>
                <a href="http://127.0.0.1:8787/api/providers" target="_blank" class="flex items-center gap-2 text-xs text-zinc-300 hover:text-white transition">
                    <span class="w-6 h-6 rounded-lg bg-zinc-800 flex items-center justify-center text-[10px]">&#8599;</span>
                    Open Integration API
                </a>
                <a href="http://127.0.0.1:8787/health" target="_blank" class="flex items-center gap-2 text-xs text-zinc-300 hover:text-white transition">
                    <span class="w-6 h-6 rounded-lg bg-zinc-800 flex items-center justify-center text-[10px]">&#8599;</span>
                    Integration Health Endpoint
                </a>
            </div>
        </div>
    </div>

    {{-- Credential Report Download --}}
    <div class="glass-card rounded-2xl p-5">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="font-bold text-white text-sm">Credential Map PDF</h3>
                <p class="text-xs text-zinc-500 mt-1">80 vars across 6 categories · 20 algorithms · 9 agencies · 19 desk systems</p>
            </div>
            <a href="{{ asset('storage/kicc-credential-map.pdf') }}" target="_blank" class="text-[10px] font-bold px-4 py-2 rounded-lg bg-rose-500/20 text-rose-400 hover:bg-rose-500/30 transition-all flex items-center gap-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Download PDF
            </a>
        </div>
    </div>
</div>
@endif