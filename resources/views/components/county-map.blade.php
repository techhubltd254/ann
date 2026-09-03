@props([
    'county' => null,
    'institutions' => [],
    'height' => '380px',
    'id' => null,
])

@php
    $mapId = $id ?? 'county-map-' . uniqid();
    $cLat = (float) ($county->latitude ?? -0.7167);
    $cLng = (float) ($county->longitude ?? 37.15);
    $points = [];
    foreach ($institutions as $inst) {
        $points[] = [
            'name' => (string) $inst->name,
            'lat' => (float) ($inst->lat ?? 0),
            'lng' => (float) ($inst->lng ?? 0),
            'loc' => (string) ($inst->location ?? ''),
            'web' => (string) ($inst->website ?? ''),
        ];
    }
    $pointsJson = json_encode($points);
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

        var map = L.map(mapEl).setView([{{ $cLat }}, {{ $cLng }}], 12);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        // County pin (gold)
        var countyIcon = L.divIcon({
            className: '',
            html: '<div style="width:18px;height:18px;background:#FFCD05;border:3px solid #fff;border-radius:50%;box-shadow:0 2px 6px rgba(0,0,0,.4)"></div>',
            iconSize: [18, 18], iconAnchor: [9, 9]
        });
        L.marker([{{ $cLat }}, {{ $cLng }}], { icon: countyIcon })
            .addTo(map)
            .bindPopup('<strong>{{ $county->name }} County</strong>');

        // Institution pins (blue) — first one opens its popup
        var points = {!! $pointsJson !!};

        var first = true;
        points.forEach(function (p) {
            if (!p.lat || !p.lng) return;
            var icon = L.divIcon({
                className: '',
                html: '<div style="width:14px;height:14px;background:#0B1E57;border:2px solid #fff;border-radius:50%;box-shadow:0 2px 6px rgba(0,0,0,.4)"></div>',
                iconSize: [14, 14], iconAnchor: [7, 7]
            });
            var html = '<strong>' + p.name + '</strong>';
            if (p.loc) html += '<br><span style="font-size:12px">' + p.loc + '</span>';
            if (p.web) html += '<br><a href="' + p.web + '" target="_blank" rel="noopener" style="font-size:12px;color:#0B1E57;text-decoration:underline">Official Website</a>';
            if (p.lat && p.lng) html += '<br><a href="https://www.google.com/maps/search/?api=1&query=' + p.lat + ',' + p.lng + '" target="_blank" rel="noopener" style="font-size:12px;color:#0B1E57;text-decoration:underline"> View on Google Maps</a>';
            var m = L.marker([p.lat, p.lng], { icon: icon }).addTo(map).bindPopup(html);
            if (first) { m.openPopup(); first = false; }
        });

        // Fit bounds if we have institution pins
        if (points.some(p => p.lat && p.lng)) {
            var withCoords = points.filter(p => p.lat && p.lng).map(p => [p.lat, p.lng]);
            withCoords.push([{{ $cLat }}, {{ $cLng }}]);
            map.fitBounds(withCoords, { padding: [40, 40] });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMap);
    } else {
        initMap();
    }
})();
</script>
@endpush