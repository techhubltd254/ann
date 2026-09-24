<div class="glass-card rounded-xl p-4">
    <h3 class="font-bold text-white text-sm mb-3 flex items-center gap-2">
        <svg class="w-4 h-4 text-[#FFCD05]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        Active Pipelines in {{ $countyName ?? 'this County' }}
    </h3>

    @php
        $countyPipelines = $countyPipelines ?? collect();
        $totalGmv = $countyPipelines->sum('gmv');
    @endphp

    @if($countyPipelines->isEmpty())
    <p class="text-zinc-500 text-xs py-4 text-center">No pipeline activity recorded for this county yet.</p>
    @else
    <div class="space-y-2">
        @foreach($countyPipelines->take(10) as $cp)
        <div class="flex items-center justify-between py-1.5 border-b border-white/5 last:border-0">
            <div class="flex items-center gap-2">
                <span class="font-mono font-bold text-[10px] text-[#FFCD05]">{{ $cp->code }}</span>
                <span class="text-[10px] text-zinc-400">{{ $cp->trades ?? 0 }} trades</span>
            </div>
            <span class="font-mono text-[11px] text-white font-bold">KES {{ number_format($cp->gmv) }}</span>
        </div>
        @endforeach
    </div>
    @if($totalGmv > 0)
    <div class="mt-3 pt-2 border-t border-white/5 flex justify-between text-xs">
        <span class="text-zinc-400">Total county GMV</span>
        <span class="text-white font-bold">KES {{ number_format($totalGmv) }}</span>
    </div>
    @endif
    @endif
</div>