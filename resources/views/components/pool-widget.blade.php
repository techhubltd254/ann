@php
    $poolBalance = $poolBalance ?? 0;
    $contributionsToday = $contributionsToday ?? 0;
    $activePipelines = $activePipelines ?? 0;
    $topEarners = $topEarners ?? collect([]);
@endphp
<div class="glass-card rounded-xl p-5">
    <div class="flex items-center gap-2 mb-3">
        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center text-white font-bold text-xs">₿</div>
        <div>
            <div class="font-bold text-white text-sm">Mother Pool</div>
            <div class="text-[10px] text-zinc-500">All county pipeline earnings accrue here</div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 mb-3">
        <div>
            <div class="text-[10px] text-zinc-500 uppercase tracking-wide">Pool Balance</div>
            <div class="text-xl font-black text-white">KES {{ number_format($poolBalance) }}</div>
        </div>
        <div>
            <div class="text-[10px] text-zinc-500 uppercase tracking-wide">Today</div>
            <div class="text-xl font-black text-white">KES {{ number_format($contributionsToday) }}</div>
        </div>
    </div>

    <div class="text-[10px] text-zinc-500 mb-2">{{ $activePipelines }} active pipelines contributing</div>

    @if($topEarners->isNotEmpty())
    <div class="pt-2 border-t border-white/5">
        <div class="text-[10px] text-zinc-500 uppercase tracking-wide mb-1">Top Earners</div>
        @foreach($topEarners->take(3) as $te)
        <div class="flex items-center justify-between py-0.5">
            <span class="font-mono text-[10px] text-zinc-400">{{ $te->code }}</span>
            <span class="font-mono text-[10px] text-white">KES {{ number_format($te->gmv) }}</span>
        </div>
        @endforeach
    </div>
    @endif
</div>