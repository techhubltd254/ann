@if($tab === 'search-analytics')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-white">Search Analytics</h2>
            <p class="text-zinc-400 text-sm mt-1">Google search terms • Referral sources • UTM campaigns — data captured by SearchIntent middleware</p>
        </div>
        <span class="text-[10px] font-bold px-3 py-1.5 rounded-full bg-sky-500/20 text-sky-400">{{ $searchTotal ?? 0 }} searches tracked</span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- Top search queries --}}
        <div class="glass-card rounded-2xl p-5 lg:col-span-2">
            <h3 class="font-bold text-white text-sm mb-4">Top Search Terms</h3>
            <div class="overflow-x-auto" style="max-height:400px; overflow-y:auto;">
                <table class="w-full text-xs">
                    <thead><tr class="text-zinc-500 border-b border-white/5">
                        <th class="text-left py-2 pr-3 font-semibold">Query</th>
                        <th class="text-left py-2 pr-3 font-semibold">Engine</th>
                        <th class="text-left py-2 pr-3 font-semibold">Searches</th>
                        <th class="text-left py-2 font-semibold">Last Seen</th>
                    </tr></thead>
                    <tbody class="divide-y divide-white/5">
                    @forelse($searchQuery ?? [] as $sq)
                    <tr class="hover:bg-white/5 transition">
                        <td class="py-2.5 pr-3 text-white font-semibold">{{ $sq->query }}</td>
                        <td class="py-2.5 pr-3"><span class="text-[10px] px-2 py-0.5 rounded-full bg-zinc-800 text-zinc-400">{{ $sq->engine }}</span></td>
                        <td class="py-2.5 pr-3 text-zinc-300">{{ $sq->searches }}</td>
                        <td class="py-2.5 text-zinc-500">{{ $sq->last_seen ? \Carbon\Carbon::parse($sq->last_seen)->diffForHumans() : '' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="py-8 text-center text-zinc-500">No search data yet. Will appear when users arrive from Google searches.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Referral sources + engines --}}
        <div class="space-y-4">
            <div class="glass-card rounded-2xl p-5">
                <h3 class="font-bold text-white text-sm mb-4">Referral Sources</h3>
                @forelse($searchSources ?? [] as $ss)
                <div class="flex items-center justify-between py-1.5 border-b border-white/5 last:border-0">
                    <span class="text-xs text-zinc-400">{{ $ss->source ?: 'direct' }}</span>
                    <div class="flex items-center gap-2">
                        <div class="w-16 h-1.5 rounded-full bg-zinc-700 overflow-hidden">
                            @php $max = $searchSources->first()?->visits ?: 1; @endphp
                            <div class="h-full rounded-full bg-gradient-to-r from-sky-500 to-blue-400" style="width: {{ ($ss->visits / $max) * 100 }}%"></div>
                        </div>
                        <span class="text-[10px] text-zinc-400">{{ $ss->visits }}</span>
                    </div>
                </div>
                @empty
                <p class="text-xs text-zinc-500 py-2">No data yet.</p>
                @endforelse
            </div>

            <div class="glass-card rounded-2xl p-5">
                <h3 class="font-bold text-white text-sm mb-4">Search Engines</h3>
                @forelse($searchEngines ?? [] as $se)
                <div class="flex items-center justify-between py-1.5 border-b border-white/5 last:border-0">
                    <span class="text-xs text-zinc-400">{{ ucfirst($se->engine ?: 'direct') }}</span>
                    <span class="text-[10px] text-zinc-500">{{ $se->c }}</span>
                </div>
                @empty
                <p class="text-xs text-zinc-500 py-2">No data yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endif