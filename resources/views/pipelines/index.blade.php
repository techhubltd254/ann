@extends('layouts.nexora')

@section('title', 'KICC Pipelines — Revenue Infrastructure')

@section('content')
<div class="min-h-screen bg-[#0A1024] text-white">
    <div class="max-w-7xl mx-auto px-5 py-12">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-black text-white">Pipeline Sectors</h1>
                <p class="text-zinc-400 mt-2">{{ $totals['total'] ?? 214 }} pipelines · {{ $totals['active'] ?? 184 }} earning · {{ $totals['locked'] ?? 30 }} awaiting data</p>
            </div>
            <a href="{{ route('kicc.admin', ['tab' => 'pipeline-settings']) }}" class="text-[10px] font-bold px-3 py-1.5 rounded-lg bg-rose-500/20 text-rose-400 hover:bg-rose-500/30">Mother Admin</a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($sectors as $s)
            @php
                $icons = ['trade'=>'🏪','agriculture'=>'🌾','tourism'=>'🏛','investment'=>'💼','financing'=>'💰','government'=>'🏛','creative'=>'🎭','health'=>'🏥','education'=>'📚','energy'=>'⚡','mobility'=>'🚢','identity'=>'🆔','milk-dairy'=>'🥛'];
                $icon = $icons[$s->sector] ?? '📋';
                $pct = $s->total > 0 ? round(($s->active / $s->total) * 100) : 0;
            @endphp
            <a href="{{ route('pipelines.sector', $s->sector) }}" class="glass-card rounded-2xl p-5 hover:border-rose-500/30 transition-all group">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-white/5 flex items-center justify-center text-2xl">{{ $icon }}</div>
                    <div class="flex-1">
                        <div class="font-bold text-white text-sm group-hover:text-rose-400 transition-colors">{{ ucfirst(str_replace('-', ' ', $s->sector)) }}</div>
                        <div class="text-xs text-zinc-500 mt-1">{{ $s->total }} pipelines · {{ $s->active }} earning · {{ $s->locked }} locked</div>
                        <div class="mt-2 h-1.5 rounded-full bg-zinc-700 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-emerald-400" style="width: {{ $pct }}%"></div>
                        </div>
                        <div class="text-[10px] text-zinc-500 mt-1">{{ $pct }}% active</div>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
</div>
@endsection
