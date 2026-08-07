<?php

namespace App\Services\Logistics;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Courier adapter interface — single exclusive partner per blueprint §Layer 4.
 * Implementations: a real partner (when credentials land) + this null-safe base.
 */
interface CourierAdapter
{
    /** Create a pickup/shipment for an order. Returns ['tracking_no'=>..., 'label_url'=>...] */
    public function createShipment(array $order, array $address): array;

    /** Latest tracking events for a shipment. */
    public function track(string $trackingNo): array;
}

/**
 * Default no-op adapter (until the exclusive partner's API credentials are configured).
 * Logs and returns a local tracking number so the order flow never breaks.
 */
class NullCourier implements CourierAdapter
{
    public function createShipment(array $order, array $address): array
    {
        $tracking = 'KICC-' . strtoupper(uniqid());
        Log::info('courier: null adapter created shipment', ['order' => $order['id'] ?? null, 'tracking' => $tracking]);
        return ['tracking_no' => $tracking, 'label_url' => null, 'stub' => true];
    }

    public function track(string $trackingNo): array
    {
        return ['tracking_no' => $trackingNo, 'events' => [], 'stub' => true];
    }
}

/**
 * Real partner adapter skeleton — activates when COURIER_API_KEY/BASE_URL are set.
 * Drop-in per the partner's REST contract (create/track).
 */
class PartnerCourier implements CourierAdapter
{
    public function createShipment(array $order, array $address): array
    {
        $resp = Http::withToken(config('services.courier.key'))
            ->post(rtrim(config('services.courier.base_url'), '/') . '/shipments', [
                'order_ref' => $order['reference'] ?? null,
                'destination' => $address,
                'items' => $order['items'] ?? [],
            ]);
        return [
            'tracking_no' => $resp->json('tracking_no'),
            'label_url' => $resp->json('label_url'),
            'raw' => $resp->json(),
        ];
    }

    public function track(string $trackingNo): array
    {
        return Http::withToken(config('services.courier.key'))
            ->get(rtrim(config('services.courier.base_url'), '/') . "/shipments/$trackingNo/track")
            ->json() ?? [];
    }
}

class CourierManager
{
    public static function driver(): CourierAdapter
    {
        return config('services.courier.key')
            ? new PartnerCourier()
            : new NullCourier();
    }
}
