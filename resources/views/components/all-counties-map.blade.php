@props([
    'counties' => [],
    'height' => '400px',
])

@php
    $mapId = 'all-counties-map-' . uniqid();
    $points = [];
    foreach ($counties as $c) {
        $points[] = [
            'name' => (string) $c->name,
            'slug' => (string) $c->slug,
            'lat' => (float) ($c->latitude ?? 0),
            'lng' => (float) ($c->longitude ?? 0),
            'sectors' => $c->sectors_count ?? 0,
        ];
    }
    $pointsJson = json_encode($points, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT);
@endphp

<div class="rounded-2xl overflow-hidden border border-gray-200 bg-white" style="height: {{ $height }}">
    <div id="{{ $mapId }}" class="w-full h-full" style="z-index:1"></div>
</div>

@push('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var map = L.map('{{ $mapId }}', { scrollWheelZoom: false }).setView([0.5, 38], 6.5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '© <a href="https://openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

    var points = {!! $pointsJson !!};
    var bounds = [];
    points.forEach(function(p) {
        if (!p.lat || !p.lng) return;
        var marker = L.circleMarker([p.lat, p.lng], {
            radius: 8,
            fillColor: '#B3261E',
            color: '#FFFFFF',
            weight: 2,
            opacity: 1,
            fillOpacity: 0.8,
        }).addTo(map);
        marker.bindPopup(
            '<a href="/counties/' + p.slug + '" style="font-weight:700;color:#B3261E;text-decoration:none;">' +
            p.name + '</a><br><span style="font-size:11px;color:#0B0B0B;">' + p.sectors + ' sectors</span>'
        );
        bounds.push([p.lat, p.lng]);
    });
    if (bounds.length > 0) map.fitBounds(bounds, { padding: [30, 30] });
});
</script>
@endpush