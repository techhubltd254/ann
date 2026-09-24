@extends('layouts.nexora')

@section('title', $sectorName . ' Pipelines — KICC')

@section('content')
<div class="min-h-screen bg-[#0A1024] text-white">
    <div class="max-w-7xl mx-auto px-5 py-12">
        <div class="mb-8">
            <a href="{{ route('pipelines.index') }}" class="text-zinc-500 hover:text-white text-xs font-bold transition-colors">&larr; All Sectors</a>
            <h1 class="text-3xl font-black text-white mt-2">{{ $sectorName }} Pipelines</h1>
            <p class="text-zinc-400 mt-1">{{ $pipelines->count() }} pipelines · KES {{ number_format($totalGmv) }} total GMV</p>
        </div>

        <div class="grid grid-cols-1 gap-3">
            @foreach($pipelines as $p)
            @php
                $eco = json_decode($p->economics ?? '{}', true) ?: [];
                $regs = json_decode($p->regulators ?? '[]', true) ?: [];
                $isLocked = $p->earning_locked ? true : false;
            @endphp
            <div class="glass-card rounded-xl p-4 {{ $isLocked ? 'border-l-2 border-amber-500/50' : 'border-l-2 border-emerald-500/50' }}">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-mono font-bold text-[#FFCD05]">{{ $p->code }}</span>
                            @if($isLocked)
                            <span class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-400">LOCKED</span>
                            @else
                            <span class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400">EARNING</span>
                            @endif
                            <span class="text-[10px] text-zinc-500">Phase {{ $p->phase }}</span>
                        </div>
                        <div class="text-sm text-zinc-300 mt-1">{{ \Illuminate\Support\Str::title(str_replace('-', ' ', $p->slug ?? $p->code)) }}</div>
                    </div>
                    <div class="text-right text-xs text-zinc-400">
                        <div>Fee: {{ $eco['take_rate'] ?? ($eco['model'] ?? $defaultFeeRate . '%') }}</div>
                        @if($regs)
                        <div class="mt-1 text-[10px] text-zinc-500">Reg: {{ implode(', ', array_map('ucfirst', $regs)) }}</div>
                        @endif
                    </div>
                </div>
                @if($eco['description'] ?? false)
                <div class="text-xs text-zinc-500 mt-2">{{ $eco['description'] }}</div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection