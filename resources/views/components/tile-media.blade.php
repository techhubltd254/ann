@props([
    'slot'    => 'hero',
    'type'    => 'default',   // default | institution | product | county | venue | landing
    'id'      => null,
    'class'   => '',
    'alt'     => '',
    'overlay' => true,
    'ratio'   => 'aspect-video',
])

@php
    $resolver = app(\App\Services\TileMediaResolver::class);
    $map = [
        'institution' => \App\Models\CountyInstitution::class,
        'product'     => \App\Models\Marketplace\Product::class,
        'county'      => \App\Models\County::class,
        'venue'       => \App\Models\Venue::class,
        'landing'     => 'landing_page',
        'default'     => \App\Services\TileMediaResolver::OWNER_TYPE,
    ];
    $ownerType = $map[$type] ?? \App\Services\TileMediaResolver::OWNER_TYPE;
    $ownerId   = $type === 'default' ? \App\Services\TileMediaResolver::OWNER_ID : (int) $id;
    $tile      = $resolver->tile($ownerType, $ownerId, $slot);
    $url       = $tile['url'] ?? null;
    $kind      = $tile['kind'] ?? 'image';
    $label     = $alt ?: ($tile['alt'] ?? $slot);
@endphp

{{-- One component for every tile. Video uses the identical attribute set everywhere:
     autoplay muted loop playsinline + poster + graceful fallback. --}}
@if($url)
<div class="tile-media relative overflow-hidden {{ $ratio }} {{ $class }}"
     data-tile-slot="{{ $slot }}" data-tile-state="{{ $tile['state'] }}" data-tile-source="{{ $tile['source'] ?? '' }}">
    @if($kind === 'video')
    <video class="tile-media-video absolute inset-0 w-full h-full object-cover"
           autoplay muted loop playsinline preload="metadata"
           @if(!empty($tile['poster'])) poster="{{ $tile['poster'] }}" @endif>
        <source src="{{ $url }}" type="{{ str_ends_with($url, '.webm') ? 'video/webm' : 'video/mp4' }}">
    </video>
    @else
    <img src="{{ $url }}" alt="{{ $label }}"
         class="tile-media-img absolute inset-0 w-full h-full object-cover"
         loading="lazy" decoding="async">
    @endif

    @if($overlay)
    <div class="tile-media-scrim absolute inset-0 bg-gradient-to-t from-black/55 via-transparent to-transparent pointer-events-none"></div>
    @endif

    {{ $slot2 ?? '' }}
</div>
@else
<div class="tile-media tile-media-empty relative overflow-hidden {{ $ratio }} {{ $class }}"
     data-tile-slot="{{ $slot }}" data-tile-state="empty">
    <div class="absolute inset-0 flex items-center justify-center bg-black/25 text-white/60 text-[11px] text-center px-3">
        {{ $alt ?: 'No media published — add one in the admin' }}
    </div>
</div>
@endif
