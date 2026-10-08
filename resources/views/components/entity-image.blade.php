@props([
    'entity',
    'size' => 'card',
    'alt' => '',
    'class' => '',
    'width' => 640,
    'quality' => 75,
])

@php
    $src = app(\App\Services\MediaFallbackResolver::class)->resolve($entity, $size);
    $fallback = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="800" height="600"><rect width="800" height="600" fill="#0B0B0B"/><text x="400" y="300" text-anchor="middle" font-family="sans-serif" font-size="40" font-weight="bold" fill="white">' . e($alt ?: 'KICC') . '</text></svg>');
@endphp

<x-fast-image
    :src="$src"
    :alt="$alt"
    :width="$width"
    :quality="$quality"
    :class="$class"
    onerror="this.src='{{ $fallback }}'"
/>