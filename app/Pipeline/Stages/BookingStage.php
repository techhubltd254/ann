<?php

namespace App\Pipeline\Stages;

use App\Services\PaymentService;
use App\Events\GenericDomainEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BookingStage
{
    public function handle(array $context): array
    {
        $request = $context['request'];
        if (!$request || !$request->has('confirm')) {
            $context['booking'] = null;
            return $context;
        }

        $itinerary = $context['itinerary'] ?? [];
        $selections = $context['selections'] ?? [];
        if (empty($itinerary) || empty($selections)) return $context;

        $booking = [
            'reference' => 'EXP-' . strtoupper(Str::random(8)),
            'status' => 'pending',
            'items' => [],
            'total' => $itinerary['total_estimated'] ?? 0,
            'currency' => 'KES',
        ];

        // Process payment via existing PaymentService
        try {
            $payments = app(PaymentService::class);
            $intent = $payments->charge(
                (object) ['id' => 0, 'booking_ref' => $booking['reference']],
                $booking['total'],
                ['description' => "Experience package {$booking['reference']}", 'phone' => $request->input('phone', '')]
            );
            $booking['payment_intent'] = $intent->id ?? null;
            $booking['status'] = 'confirmed';
        } catch (\Throwable $e) {
            Log::warning('Experience booking payment failed: ' . $e->getMessage());
            $booking['payment_error'] = $e->getMessage();
        }

        // Fire n8n webhook
        try {
            event(new GenericDomainEvent('experience_booked', [
                'reference' => $booking['reference'],
                'anchor' => $context['anchor_name'] ?? '',
                'county' => $context['county']?->name ?? '',
                'items' => $selections,
                'total' => $booking['total'],
            ], n8nEventName: 'experience_booked'));;
        } catch (\Throwable $e) {
            Log::warning('n8n experience webhook: ' . $e->getMessage());
        }

        $context['booking'] = $booking;
        return $context;
    }
}