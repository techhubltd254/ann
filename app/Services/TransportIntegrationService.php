<?php

namespace App\Services;

use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductCategory;
use App\Models\User;

/**
 * Transport options engine.
 *
 * Phase 1 (now): marketplace products in the "transport" category
 *   (airport transfers, private drivers, dhow cruises) + curated nearby
 *   transport institutions (airport, port, SGR terminus).
 *
 * Phase 2 (scaffolded): Uber-style ride estimate + request reservation.
 *   The provider abstraction below is shaped so a real Uber SDK / ride
 *   provider HTTP client can drop in without changing callers.
 *
 * Phase 3 (scaffolded): private exhibitor taxi marketplace — users with
 *   the "exhibitor" role who list vehicles become bookable drivers.
 */
class TransportIntegrationService
{
    /**
     * Marketplace category slugs treated as transport.
     */
    const TRANSPORT_CATEGORY_SLUGS = ['transport', 'private-transport', 'airport-transfer', 'car-hire', 'taxi', 'dhow-cruise', 'boat', 'travel', 'logistics'];

    /**
     * Institution type fragments treated as transport hubs.
     */
    const TRANSPORT_HUB_TYPES = ['Airport', 'Port', 'Terminal', 'Bypass', 'SGR', 'Inland Container Depot', 'Maritime Authority'];

    public function getOptions(float $lat, float $lng, ?int $countyId = null, int $limit = 4): array
    {
        $options = [];

        // --- Phase 1: marketplace transport products ---
        $marketplace = $this->marketplaceTransportProducts($countyId, $limit);
        foreach ($marketplace as $item) {
            $options[] = [
                'provider' => 'marketplace',
                'id' => $item->id,
                'name' => $item->name,
                'slug' => $item->slug,
                'type_label' => $item->category?->name ?? 'Transport',
                'type_emoji' => '🚗',
                'price' => (float) $item->price,
                'unit' => $item->unit ?? 'trip',
                'description' => $item->short_description ?? $item->description ?? '',
                'image_url' => $item->image_url ?? null,
                'booking_url' => route('marketplace.show', $item->slug),
                'eta_minutes' => null,
                'distance_km' => null,
            ];
        }

        // --- Transport hub institutions nearby (catch-all so every page has options) ---
        $remaining = max(0, $limit - count($options));
        if ($remaining > 0 && $countyId) {
            $hubs = \App\Models\CountyInstitution::where('county_id', $countyId)
                ->where('is_published', true)->whereNotNull('lat')->whereNotNull('lng')
                ->get()
                ->filter(fn ($inst) => $this->isTransportHub($inst))
                ->sortBy(function ($inst) use ($lat, $lng) {
                    if (!$lat || !$lng) return 0;
                    $a = (float) ($inst->lat ?? 0); $b = (float) ($inst->lng ?? 0);
                    return $this->haversine($lat, $lng, $a, $b);
                })
                ->take($remaining);

            foreach ($hubs as $hub) {
                $options[] = [
                    'provider' => 'institution',
                    'id' => $hub->id,
                    'name' => $hub->name,
                    'slug' => $hub->slug,
                    'type_label' => $hub->type ?? 'Transport Hub',
                    'type_emoji' => '🚏',
                    'price' => null,
                    'unit' => null,
                    'description' => $hub->description ?? 'Transport hub serving the area.',
                    'image_url' => $hub->cover_image_url ?? $hub->logo_url ?? null,
                    'booking_url' => $hub->website ?? null,
                    'eta_minutes' => null,
                    'distance_km' => ($lat && $lng && $hub->lat && $hub->lng)
                        ? round($this->haversine($lat, $lng, (float) $hub->lat, (float) $hub->lng), 1)
                        : null,
                ];
            }
        }

        // --- Phase 2: Uber / ride provider estimate (stub) ---
        $uber = $this->rideEstimates($lat, $lng);
        if (!empty($uber)) {
            $options = array_merge($options, $uber);
        }

        // --- Phase 3: private exhibitor taxi drivers (stub) ---
        $exhibitorDrivers = $this->exhibitorDrivers($countyId);
        if (!empty($exhibitorDrivers)) {
            $options = array_merge($options, $exhibitorDrivers);
        }

        return array_slice($options, 0, $limit);
    }

    protected function marketplaceTransportProducts(?int $countyId = null, int $limit = 4): \Illuminate\Support\Collection
    {
        $query = Product::with(['category', 'variants', 'images'])
            ->active()
            ->whereHas('category', fn ($q) => $q->whereIn('slug', self::TRANSPORT_CATEGORY_SLUGS));
        if ($countyId) {
            $query->where('county_id', $countyId);
        }
        return $query->take(8)->get();
    }

    protected function isTransportHub(\App\Models\CountyInstitution $inst): bool
    {
        $type = $inst->type ?? '';
        foreach (self::TRANSPORT_HUB_TYPES as $pattern) {
            if (stripos($type, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Uber-style ride estimate.
     *
     * Stub: reads config('transport.uber.enabled'), and returns static
     * estimate rows when a provider token is configured. Swap the body with
     * a real SDK call (Uber v1.2 / Lyft / Bolt) keeping this exact shape:
     *
     *  [
     *    'provider' => 'ride',
     *    'name' => 'UberX',
     *    'price_estimate' => {...},
     *    'eta_minutes' => 4,
     *  ]
     */
    public function rideEstimates(float $lat, float $lng): array
    {
        if (!config('transport.uber.enabled', false)) {
            return [];
        }
        // Provider URL + auth token live in config('transport.uber').
        //   HTTP::withToken(config('transport.uber.token'))
        //       ->get(config('transport.uber.endpoint') . '/estimates', [
        //           'start_lat' => $lat, 'start_lng' => $lng,
        //       ])
        return [
            [
                'provider' => 'ride',
                'name' => 'UberX',
                'type_label' => 'On-demand ride',
                'type_emoji' => '🚕',
                'price' => null,
                'eta_minutes' => 4,
                'description' => 'Booked on-demand via Uber.',
            ],
        ];
    }

    /**
     * Private exhibitor taxi drivers.
     *
     * Stub: users with role exhibiting transport. Filter by county and a
     * "has_vehicle" preference flag. Returns an empty set until drivers are
     * onboarded, so callers already render nothing when empty.
     */
    public function exhibitorDrivers(?int $countyId = null): array
    {
        $drivers = \App\Models\User::where('account_type', 'exhibitor')
            ->whereHas('roles', fn ($q) => $q->where('name', 'transport'))
            ->when($countyId, fn ($q) => $q->where('county_id', $countyId))
            ->get();

        $results = [];
        foreach ($drivers as $d) {
            $results[] = [
                'provider' => 'exhibitor',
                'id' => $d->id,
                'name' => $d->name . ' — Private Driver',
                'type_label' => 'Private Exhibitor Transport',
                'type_emoji' => '🚐',
                'price' => null,
                'eta_minutes' => null,
                'description' => 'Private transport provider. Contact for rates.',
                'booking_url' => $d->website ?? null,
                'distance_km' => null,
            ];
        }
        return $results;
    }

    public function classifyInstitutionType(string $type): array
    {
        return [
            'is_hub' => $this->isTransportHub((object) ['type' => $type]),
        ];
    }

    protected function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}