@php
    $institutionName = $institutionName ?? 'this Institution';
    $institutionEarnings = $institutionEarnings ?? collect([]);
    $totalEarnings = $institutionEarnings->sum('gmv');
    $totalEscrows = $institutionEarnings->sum('trades');
@endphp
<div class="glass-card rounded-xl p-4">
    <h3 class="font-bold text-white text-sm mb-3 flex items-center gap-2">
        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/></svg>
        Pipeline Contributions — {{ $institutionName }}
    </h3>

    @if($institutionEarnings->isEmpty())
    <p class="text-zinc-500 text-xs py-4 text-center">No pipeline contributions recorded yet.</p>
    @else
    <div class="flex gap-4 mb-3 text-xs">
        <div><span class="text-zinc-500">Total: </span><span class="text-white font-bold">KES {{ number_format($totalEarnings) }}</span></div>
        <div><span class="text-zinc-500">Escrows: </span><span class="text-white font-bold">{{ $totalEscrows }}</span></div>
    </div>
    <div class="space-y-1.5">
        @foreach($institutionEarnings->take(8) as $ie)
        <div class="flex items-center justify-between py-1 border-b border-white/5 last:border-0">
            <div class="flex items-center gap-2">
                <span class="font-mono text-[10px] text-[#FFCD05]">{{ $ie->code }}</span>
                <span class="text-[9px] text-zinc-500">{{ $ie->trades }} trades</span>
            </div>
            <span class="font-mono text-[10px] text-white">KES {{ number_format($ie->gmv) }}</span>
        </div>
        @endforeach
    </div>
    @endif
</div>