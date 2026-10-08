@props([
    'entity' => null,
    'label' => null,
    'showIcon' => true,
    'class' => '',
])

@php
    $service = app(\App\Services\MapPinService::class);
    $pinUrl = $service->pinUrl($entity);
    $osmUrl = $service->osmUrl($entity);
    $name = $label ?? $service->entityName($entity);
    $location = $service->entityLocation($entity);
    $hasCoords = $service->hasCoordinates($entity);
    $coords = $service->resolveCoordinates($entity);
@endphp

@if($pinUrl)
<div class="flex items-start gap-2.5 {{ $class }}">
    <div class="mt-0.5 shrink-0 w-5 h-5 rounded-full bg-[#0b0b0b]/10 flex items-center justify-center">
        <svg class="w-3 h-3 text-[#0b0b0b]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
    </div>
    <div class="min-w-0">
        <a href="{{ $pinUrl }}" target="_blank" rel="noopener"
           class="text-sm font-semibold text-[#0b0b0b] hover:text-[#FFCD05] transition-colors truncate block">
            {{ $name }}
        </a>
        @if($location)
        <p class="text-xs text-gray-400 mt-0.5">{{ $location }}</p>
        @endif
        @if($coords)
        <p class="text-[10px] text-gray-300 mt-0.5">{{ number_format($coords['lat'], 4) }}, {{ number_format($coords['lng'], 4) }}</p>
        @endif
        <div class="flex gap-2 mt-1">
            <a href="{{ $pinUrl }}" target="_blank" rel="noopener"
               class="text-[10px] font-bold text-[#0b0b0b] hover:text-[#FFCD05] transition-colors">
                View on Google Maps
            </a>
            @if($osmUrl)
            <span class="text-[10px] text-gray-300">·</span>
            <a href="{{ $osmUrl }}" target="_blank" rel="noopener"
               class="text-[10px] font-bold text-gray-500 hover:text-[#0b0b0b] transition-colors">
                OpenStreetMap
            </a>
            @endif
        </div>
    </div>
</div>
@else
<div class="flex items-start gap-2.5 {{ $class }}">
    <div class="mt-0.5 shrink-0 w-5 h-5 rounded-full bg-gray-100 flex items-center justify-center">
        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
    </div>
    <div class="min-w-0">
        <span class="text-sm text-gray-600">{{ $name }}</span>
        @if($location)
        <p class="text-xs text-gray-400 mt-0.5">{{ $location }}</p>
        @endif
    </div>
</div>
@endif