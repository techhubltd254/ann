@php
    $mesh = $mesh ?? [];
    $upstream = $mesh['upstream'] ?? [];
    $downstream = $mesh['downstream'] ?? [];
    $pipelineCode = $mesh['pipeline_code'] ?? $pipelineCode ?? 'A1';
@endphp
<div class="glass-card rounded-xl p-4">
    <h3 class="font-bold text-white text-sm mb-3 flex items-center gap-2">
        <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
        Pipeline Mesh — {{ $pipelineCode }}
    </h3>

    <div class="flex items-center justify-center gap-2 mb-4">
        @if(count($upstream) > 0)
        <div class="flex-1 text-center">
            <div class="text-[9px] text-zinc-500 uppercase tracking-wide mb-1">Upstream</div>
            <div class="flex flex-wrap justify-center gap-1">
                @foreach(array_slice($upstream, 0, 5) as $u)
                <a href="{{ route('pipelines.sector', \App\Models\Marketplace\Product::where('pipeline_code', $u)->first()?->category?->sector ?? 'trade') }}" class="text-[9px] font-mono px-1.5 py-0.5 rounded bg-zinc-800 text-zinc-400 hover:text-white transition">{{ $u }}</a>
                @endforeach
                @if(count($upstream) > 5)
                <span class="text-[9px] text-zinc-600">+{{ count($upstream) - 5 }}</span>
                @endif
            </div>
        </div>
        @endif

        <div class="text-center px-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-rose-500 to-amber-500 flex items-center justify-center text-white font-bold text-xs">{{ $pipelineCode }}</div>
            <div class="text-[8px] text-zinc-500 mt-1">CURRENT</div>
        </div>

        @if(count($downstream) > 0)
        <div class="flex-1 text-center">
            <div class="text-[9px] text-zinc-500 uppercase tracking-wide mb-1">Downstream</div>
            <div class="flex flex-wrap justify-center gap-1">
                @foreach(array_slice($downstream, 0, 5) as $d)
                <a href="{{ route('pipelines.sector', \App\Models\Marketplace\Product::where('pipeline_code', $d)->first()?->category?->sector ?? 'trade') }}" class="text-[9px] font-mono px-1.5 py-0.5 rounded bg-zinc-800 text-zinc-400 hover:text-white transition">{{ $d }}</a>
                @endforeach
                @if(count($downstream) > 5)
                <span class="text-[9px] text-zinc-600">+{{ count($downstream) - 5 }}</span>
                @endif
            </div>
        </div>
        @endif
    </div>

    @if(count($downstream) + count($upstream) === 0)
    <p class="text-center text-[10px] text-zinc-500 py-2">Pipeline {{ $pipelineCode }} has no edges in the current graph. It will connect when related pipelines settle.</p>
    @else
    <div class="text-[9px] text-zinc-500 text-center">
        {{ count($upstream) }} upstream · {{ count($downstream) }} downstream · Total {{ count($upstream) + count($downstream) }} pipeline edges
    </div>
    @endif
</div>