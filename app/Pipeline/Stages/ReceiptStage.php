<?php

namespace App\Pipeline\Stages;

class ReceiptStage
{
    public function handle(array $context): array
    {
        $context['receipt'] = [
            'reference' => $context['booking']['reference'] ?? $context['itinerary']['name'] ?? 'Draft',
            'anchor_name' => $context['anchor_name'] ?? '',
            'county_name' => $context['county']?->name ?? '',
            'itinerary' => $context['itinerary'] ?? [],
            'booking' => $context['booking'] ?? [],
            'correlations' => $context['correlations'] ?? [],
            'selections' => $context['selections'] ?? [],
            'generated_at' => now()->toIso8601String(),
            'currency' => 'KES',
        ];

        return $context;
    }
}