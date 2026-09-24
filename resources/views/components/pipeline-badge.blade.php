@php
    $pipelineCode = $pipelineCode ?? 'A1';
    $feeRate = $feeRate ?? '4%';
    $isLocked = $isLocked ?? false;
    $lockReason = $lockReason ?? null;
@endphp
<div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold
    {{ $isLocked ? 'bg-amber-500/20 text-amber-400' : 'bg-emerald-500/20 text-emerald-400' }}">
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        @if($isLocked)
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        @else
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
        @endif
    </svg>
    <span>
        {{ $isLocked ? 'Pending' : 'Active' }}
        pipeline {{ $pipelineCode }} · {{ $feeRate }} fee
    </span>
    @if($isLocked && $lockReason)
    <span class="text-[9px] text-zinc-500 ml-1">({{ $lockReason }})</span>
    @endif
</div>