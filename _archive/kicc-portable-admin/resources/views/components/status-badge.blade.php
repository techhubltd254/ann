@props(['status' => 'active'])
@php
$styles = [
    'active' => 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30',
    'pending' => 'bg-amber-500/15 text-amber-400 border-amber-500/30',
    'suspended' => 'bg-red-500/15 text-red-400 border-red-500/30',
    'expiring' => 'bg-orange-500/15 text-orange-400 border-orange-500/30',
    'inactive' => 'bg-white/5 text-white/30 border-white/10',
];
$s = $styles[$status] ?? $styles['inactive'];
@endphp
<span class="text-[10px] font-bold px-2.5 py-1 rounded-full border {{ $s }}">{{ ucfirst($status) }}</span>