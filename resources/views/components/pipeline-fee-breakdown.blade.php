@php
    $pipelineCode = $pipelineCode ?? 'A1';
    $pipelineName = $pipelineName ?? 'Marketplace Pipeline';
    $feeRate = $feeRate ?? 4.0;
    $subtotal = $subtotal ?? 0;
    $feeAmount = round($subtotal * ($feeRate / 100), 2);
    $isLocked = $isLocked ?? false;
@endphp
<div class="rounded-lg bg-white/5 border border-white/10 p-3">
    <div class="flex items-center gap-2 mb-2">
        <div class="w-6 h-6 rounded-md bg-gradient-to-br from-rose-500 to-amber-500 flex items-center justify-center text-white font-bold text-[9px]">$</div>
        <div class="text-xs text-white font-semibold">Pipeline Processing</div>
    </div>
    <div class="space-y-1 text-[11px]">
        <div class="flex justify-between">
            <span class="text-zinc-400">Pipeline</span>
            <span class="font-mono text-white">{{ $pipelineCode }}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-zinc-400">Fee Rate</span>
            <span class="text-white">{{ $feeRate }}%</span>
        </div>
        <div class="flex justify-between border-t border-white/5 pt-1">
            <span class="text-zinc-400">Pipeline Fee</span>
            <span class="text-emerald-400 font-bold">KES {{ number_format($feeAmount, 2) }}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-zinc-400">Accrues to</span>
            <span class="text-white">Mother Pool</span>
        </div>
    </div>
    @if($isLocked)
    <div class="mt-2 text-[9px] text-amber-400 bg-amber-500/10 rounded px-2 py-1">
        This pipeline is awaiting regulatory approval. Revenue will be captured once approved.
    </div>
    @endif
</div>