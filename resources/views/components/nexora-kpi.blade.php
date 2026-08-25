@props(['title', 'value', 'growth' => null, 'sparkline' => [], 'color' => 'indigo'])

@php
$colors = [
    'indigo' => ['from' => '#6366F1', 'to' => '#6366F1'],
    'emerald' => ['from' => '#10B981', 'to' => '#10B981'],
    'amber' => ['from' => '#F59E0B', 'to' => '#F59E0B'],
    'violet' => ['from' => '#8B5CF6', 'to' => '#8B5CF6'],
    'red' => ['from' => '#EF4444', 'to' => '#EF4444'],
    'cyan' => ['from' => '#06B6D4', 'to' => '#06B6D4'],
    'rose' => ['from' => '#F43F5E', 'to' => '#F43F5E'],
    'sky' => ['from' => '#0EA5E9', 'to' => '#0EA5E9'],
];
$c = $colors[$color] ?? $colors['indigo'];
$pos = $growth !== null && $growth > 0;
$neg = $growth !== null && $growth < 0;
@endphp

<div class="kpi-card">
    <div class="flex items-start justify-between">
        <div class="flex-1 min-w-0">
            <p class="text-[10px] font-semibold text-zinc-500 uppercase tracking-widest mb-1">{{ $title }}</p>
            <p class="text-2xl font-bold text-white">{{ $value }}</p>
            @if($growth !== null)
            <p class="flex items-center gap-1 mt-2 text-xs font-medium {{ $pos ? 'text-emerald-400' : ($neg ? 'text-red-400' : 'text-zinc-400') }}">
                @if($pos)
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                @elseif($neg)
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                @endif
                {{ is_numeric($growth) ? number_format(abs($growth), 1) . '%' : $growth }}
                <span class="text-zinc-500 font-normal">vs prev.</span>
            </p>
            @endif
        </div>
        @if(count($sparkline) > 0)
        <div class="flex items-end gap-0.5 h-10 shrink-0">
            @foreach($sparkline as $bar)
            @php $maxVal = max($sparkline) ?: 1; $h = max($bar / $maxVal * 100, 8); @endphp
            <div class="w-1.5 rounded-sm" style="height: {{ $h }}%; background: {{ $c['from'] }}33; border-top: 1.5px solid {{ $c['from'] }};"></div>
            @endforeach
        </div>
        @endif
    </div>
</div>