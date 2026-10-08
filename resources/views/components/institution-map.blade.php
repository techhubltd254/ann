@props([
    'lat' => null,
    'lng' => null,
    'name' => '',
    'location' => null,
    'website' => null,
    'height' => '320px',
    'id' => null,
])

@php
    $mapId = $id ?? 'map-' . uniqid();
    $lat = $lat !== null ? (float) $lat : -0.7167;
    $lng = $lng !== null ? (float) $lng : 37.15;
@endphp

<div class="rounded-2xl overflow-hidden border border-gray-200 bg-white" style="height: {{ $height }}">
    <div id="{{ $mapId }}" class="w-full h-full" style="z-index:1"></div>
</div>

@push('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function() {
    function initMap() {
        var mapEl = document.getElementById('{{ $mapId }}');
        if (!mapEl || typeof L === 'undefined') return;
        if (mapEl.dataset.initialized) return;
        mapEl.dataset.initialized = '1';

        var map = L.map(mapEl).setView([{{ $lat }}, {{ $lng }}], 15);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        var marker = L.marker([{{ $lat }}, {{ $lng }}]).addTo(map);
        var popup = '<strong>{{ $name }}</strong>';
        @if($location)
        popup += '<br><span style="font-size:12px">{{ $location }}</span>';
        @endif
        @if($website)
        popup += '<br><a href="{{ $website }}" target="_blank" rel="noopener" style="font-size:12px;color:#0b0b0b;text-decoration:underline">Official Website</a>';
        @endif
        @if($location || $website)
        popup += '<br><a href="https://www.google.com/maps/search/?api=1&query={{ $lat }},{{ $lng }}" target="_blank" rel="noopener" style="font-size:12px;color:#0b0b0b;text-decoration:underline"> View on Google Maps</a>';
        @endif
        marker.bindPopup(popup).openPopup();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMap);
    } else {
        initMap();
    }
})();
</script>
@endpush