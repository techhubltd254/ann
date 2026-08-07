@props(['color' => 'gold'])
@php
$colors = [
    'gold' => 'bg-[#FFCD05]/15 text-[#FFCD05] border-[#FFCD05]/30',
    'red' => 'bg-[#901C1E]/15 text-[#901C1E] border-[#901C1E]/30',
    'navy' => 'bg-[#0B1E57]/20 text-blue-400 border-blue-500/25',
    'emerald' => 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30',
];
$c = $colors[$color] ?? $colors['gold'];
@endphp
<span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full border {{ $c }}">{{ $slot }}</span>