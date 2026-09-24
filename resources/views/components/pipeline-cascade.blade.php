@php
    $cascade = $cascade ?? [];
    $settled = $cascade['settled'] ?? 0;
    $failed = $cascade['failed'] ?? 0;
    $dlq = $cascade['dlq'] ?? 0;
    $published = $cascade['published'] ?? 0;
    $pipelineIds = $cascade['pipelines'] ?? [];
    $ok = $cascade['ok'] ?? false;
@endphp
@if($settled > 0)
<div class="rounded-xl border p-4 {{ $ok ? 'bg-emerald-500/10 border-emerald-500/20' : 'bg-rose-500/10 border-rose-500/20' }}">
    <div class="flex items-center gap-2 mb-3">
        <div class="w-8 h-8 rounded-lg {{ $ok ? 'bg-emerald-500/20' : 'bg-rose-500/20' }} flex items-center justify-center">
            @if($ok)
            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            @else
            <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            @endif
        </div>
        <div>
            <div class="font-bold text-white text-sm">Pipeline Cascade Result</div>
            <div class="text-[10px] text-zinc-500">Real-time settlement from the inter-pipeline bus</div>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3">
        <div class="bg-white/5 rounded-lg p-2 text-center">
            <div class="text-lg font-black text-emerald-400">{{ $settled }}</div>
            <div class="text-[9px] text-zinc-500">Settled</div>
        </div>
        <div class="bg-white/5 rounded-lg p-2 text-center">
            <div class="text-lg font-black text-white">{{ $published }}</div>
            <div class="text-[9px] text-zinc-500">Events Published</div>
        </div>
        @if($failed > 0)
        <div class="bg-white/5 rounded-lg p-2 text-center">
            <div class="text-lg font-black text-rose-400">{{ $failed }}</div>
            <div class="text-[9px] text-zinc-500">Failed</div>
        </div>
        @endif
        @if($dlq > 0)
        <div class="bg-white/5 rounded-lg p-2 text-center">
            <div class="text-lg font-black text-amber-400">{{ $dlq }}</div>
            <div class="text-[9px] text-zinc-500">DLQ</div>
        </div>
        @endif
    </div>

    @if(count($pipelineIds) > 0)
    <div class="flex flex-wrap gap-1">
        <div class="text-[9px] text-zinc-500 w-full mb-1">Pipelines settled:</div>
        @foreach(array_slice($pipelineIds, 0, 15) as $pid)
        <span class="text-[9px] font-mono px-1.5 py-0.5 rounded bg-zinc-800 text-zinc-400">{{ is_numeric($pid) ? '#' . $pid : $pid }}</span>
        @endforeach
        @if(count($pipelineIds) > 15)
        <span class="text-[9px] text-zinc-600">+{{ count($pipelineIds) - 15 }} more</span>
        @endif
    </div>
    @endif
</div>
@endif