<?php

namespace App\Services;

/**
 * TouchNavigationService — powers multi-touch virtual navigation terminals
 * at expo booths. Generates interactive navigation data for exhibitions and
 * floor plans with booth discovery, search, and routing.
 */
class TouchNavigationService
{
    public function terminalConfig(string $terminalType = 'kiosk'): array
    {
        return [
            'type' => $terminalType,
            'features' => [
                'pinch_zoom' => true,
                'rotate' => true,
                'search' => true,
                'directions' => true,
                'booth_details' => true,
            ],
            'gestures' => [
                'tap' => 'select_booth',
                'double_tap' => 'zoom_to_booth',
                'two_finger_pan' => 'navigate_floor',
                'pinch' => 'zoom_level',
            ],
        ];
    }

    public function navigateTo(array $from, array $to): array
    {
        return [
            'route' => [
                'from' => ['lat' => $from['lat'] ?? 0, 'lng' => $from['lng'] ?? 0],
                'to' => ['lat' => $to['lat'] ?? 0, 'lng' => $to['lng'] ?? 0],
            ],
            'directions_url' => sprintf(
                'https://www.google.com/maps/dir/?api=1&origin=%s,%s&destination=%s,%s',
                $from['lat'] ?? 0, $from['lng'] ?? 0,
                $to['lat'] ?? 0, $to['lng'] ?? 0
            ),
        ];
    }
}