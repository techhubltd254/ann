@if(request()->is('admin/kicc','admin/kicc/*','kicc-admin','kicc-admin/*') && \Illuminate\Support\Facades\Gate::allows('view-private-revenue'))
@php
    // Single source of truth for the split — every rendered row derives from this table.
    $pipelineCode = $pipelineCode ?? 'A1';
    $pipelineName = $pipelineName ?? 'Marketplace Pipeline';
    $feeRate      = (float)($feeRate ?? 3.0);          // total platform fee %
    $subtotal     = (float)($subtotal ?? 0);            // producer net (what the maker receives)
    $isLocked     = $isLocked ?? false;
    $split = [
        ['label' => 'County pipeline', 'rate' => 0.80],
        ['label' => 'Institution',     'rate' => 1.20],
        ['label' => 'National pool',   'rate' => 0.60],
        ['label' => 'KICC platform',   'rate' => 0.40],
    ];
    $splitPct = array_sum(array_column($split, 'rate')); // 3.00%
    $buyerPays = round($subtotal * (1 + $splitPct / 100), 2);
@endphp
<div class="rounded-lg bg-white/5 border border-white/10 p-3" data-pipeline-fee-breakdown>
    <div class="flex items-center gap-2 mb-2">
        <div class="w-6 h-6 rounded-md bg-white flex items-center justify-center p-0.5"><img src="{{ tile_url('logo') }}" alt="KICC" class="w-full h-full object-contain"></div>
        <div class="text-xs text-white font-semibold">Pipeline fee breakdown</div>
        <span class="ml-auto text-[9px] font-mono text-zinc-500">{{ $pipelineCode }}</span>
    </div>
    <div class="space-y-1 text-[11px]">
        <div class="flex justify-between">
            <span class="text-zinc-400">Producer net</span>
            <span class="text-white font-semibold">KES {{ number_format($subtotal, 2) }}</span>
        </div>
        @foreach($split as $row)
        <div class="flex justify-between">
            <span class="text-zinc-400">{{ $row['label'] }} ({{ number_format($row['rate'], 2) }}%)</span>
            <span class="text-white">KES {{ number_format(round($subtotal * $row['rate'] / 100, 2), 2) }}</span>
        </div>
        @endforeach
        <div class="flex justify-between border-t border-white/5 pt-1 mt-1">
            <span class="text-zinc-300 font-semibold">Buyer pays</span>
            <span class="text-emerald-400 font-bold">KES {{ number_format($buyerPays, 2) }}</span>
        </div>
    </div>
    <div class="mt-2 text-[9px] text-zinc-500">
        Producer net — {{ $buyerPays > 0 ? number_format($subtotal / $buyerPays * 100, 2) : '0.00' }}% ·
        @foreach($split as $row){{ $row['label'] }} — {{ number_format($row['rate'], 2) }}%{{ !$loop->last ? ' · ' : '' }}@endforeach
    </div>
    @if($isLocked)
    <div class="mt-2 text-[9px] text-amber-400 bg-amber-500/10 rounded px-2 py-1">
        This pipeline is awaiting regulatory approval. Revenue will be captured once approved.
    </div>
    @endif
</div>

@endif
