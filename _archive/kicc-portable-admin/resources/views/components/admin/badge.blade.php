@props(['status' => 'active'])
@php
$map = [
    'active' => ['bg' => 'rgba(45,106,79,0.15)', 'text' => '#2D6A4F', 'border' => 'rgba(45,106,79,0.3)'],
    'pending' => ['bg' => 'rgba(255,205,5,0.15)', 'text' => '#FFCD05', 'border' => 'rgba(255,205,5,0.3)'],
    'suspended' => ['bg' => 'rgba(144,28,30,0.15)', 'text' => '#901C1E', 'border' => 'rgba(144,28,30,0.3)'],
    'expiring' => ['bg' => 'rgba(231,111,81,0.15)', 'text' => '#E76F51', 'border' => 'rgba(231,111,81,0.3)'],
    'inactive' => ['bg' => 'rgba(255,255,255,0.05)', 'text' => 'rgba(255,255,255,0.3)', 'border' => 'rgba(255,255,255,0.1)'],
];
$s = $map[$status] ?? $map['active'];
@endphp
<span style="background:{{ $s['bg'] }};color:{{ $s['text'] }};border-color:{{ $s['border'] }}" class="text-[10px] font-bold px-2.5 py-1 rounded-full border">{{ ucfirst($status) }}</span>
