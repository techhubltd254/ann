<?php

namespace App\Services;

use App\Models\ExperienceBooking;
use App\Models\CountyInstitution;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductVariant;
use App\Services\DisplayPriorityService;
use Illuminate\Support\Facades\DB;

class ExperiencePricingService
{
    const PEAK_SEASONS = [
        ['start' => '07-01', 'end' => '08-31'],
        ['start' => '12-15', 'end' => '01-15'],
    ];

    const OFF_PEAK_SEASONS = [
        ['start' => '03-01', 'end' => '05-31'],
        ['start' => '10-01', 'end' => '11-30'],
    ];

    public function calculate(ExperienceBooking $booking): array
    {
        $breakdown = [];
        $subtotal = 0.0;

        $ratingMult = $this->ratingMultiplier($booking);
        $seasonMult = $this->seasonMultiplier($booking->departure_date);
        $distanceMult = $this->distanceMultiplier($booking);

        $destinationPrice = $this->destinationEntryPrice($booking);
        if ($destinationPrice > 0) {
            $entry = [
                'label' => $booking->destination?->name ?? 'Destination',
                'category' => 'entry',
                'base_price' => $destinationPrice,
                'rating_multiplier' => round($ratingMult, 4),
                'season_multiplier' => round($seasonMult, 4),
                'distance_multiplier' => round($distanceMult, 4),
                'final_price' => round($destinationPrice * $ratingMult * $seasonMult * $distanceMult, 2),
                'qty' => 1,
            ];
            $breakdown[] = $entry;
            $subtotal += $entry['final_price'];
        }

        foreach (['transport_out', 'transport_back'] as $key) {
            $items = $booking->{$key} ?? [];
            if (empty($items)) continue;
            foreach ($items as $item) {
                $bp = (float) ($item['price'] ?? 0);
                $label = $item['name'] ?? $item['type_label'] ?? 'Transport';
                $final = round($bp * $ratingMult * $seasonMult, 2);
                $breakdown[] = [
                    'label' => $label,
                    'category' => $key === 'transport_out' ? 'transport_out' : 'transport_back',
                    'base_price' => $bp,
                    'rating_multiplier' => round($ratingMult, 4),
                    'season_multiplier' => round($seasonMult, 4),
                    'distance_multiplier' => 1.0,
                    'final_price' => $final,
                    'qty' => 1,
                ];
                $subtotal += $final;
            }
        }

        foreach (($booking->addons ?? []) as $addon) {
            $bp = (float) ($addon['price'] ?? 0);
            $final = round($bp * $ratingMult * $seasonMult, 2);
            $breakdown[] = [
                'label' => $addon['label'] ?? 'Add-on',
                'category' => $addon['type'] ?? 'addon',
                'base_price' => $bp,
                'rating_multiplier' => round($ratingMult, 4),
                'season_multiplier' => round($seasonMult, 4),
                'distance_multiplier' => 1.0,
                'final_price' => $final,
                'qty' => $addon['qty'] ?? 1,
            ];
            $subtotal += $final * ($addon['qty'] ?? 1);
        }

        $grandTotal = round($subtotal, 2);

        return [
            'breakdown' => $breakdown,
            'subtotal' => $subtotal,
            'grand_total' => $grandTotal,
            'factors' => [
                'rating_multiplier' => round($ratingMult, 4),
                'season_multiplier' => round($seasonMult, 4),
                'distance_multiplier' => round($distanceMult, 4),
            ],
        ];
    }

    public function preview(float $basePrice, string $date, float $avgRating, float $distanceKm = 0): array
    {
        $dateObj = \Carbon\Carbon::parse($date);
        $ratingMult = $this->computeRatingMultiplier($avgRating);
        $seasonMult = $this->computeSeasonMultiplier($dateObj);
        $distanceMult = $this->computeDistanceMultiplier($distanceKm);

        $final = round($basePrice * $ratingMult * $seasonMult * $distanceMult, 2);

        return [
            'base_price' => $basePrice,
            'rating_multiplier' => round($ratingMult, 4),
            'season_multiplier' => round($seasonMult, 4),
            'distance_multiplier' => round($distanceMult, 4),
            'final_price' => $final,
        ];
    }

    protected function ratingMultiplier(ExperienceBooking $booking): float
    {
        $dest = $booking->destination;
        if (!$dest) return 1.0;

        $avgRating = 0.0;
        if ($dest instanceof CountyInstitution) {
            $rows = DB::table('reviews')
                ->where('reviewable_type', CountyInstitution::class)
                ->where('reviewable_id', $dest->id)
                ->where('status', 'approved')
                ->selectRaw('AVG(rating) as avg_r, COUNT(*) as cnt')
                ->first();
            $avgRating = (float) ($rows->avg_r ?? 0);
        } elseif ($dest instanceof Product) {
            $score = app(DisplayPriorityService::class)->scoreFor($dest->id);
            $avgRating = $score['average'] ?? 0;
        }

        return $this->computeRatingMultiplier($avgRating);
    }

    protected function computeRatingMultiplier(float $avgRating): float
    {
        if ($avgRating <= 0) return 0.9;
        return 0.6 + ($avgRating / 5) * 0.6;
    }

    protected function seasonMultiplier($date): float
    {
        return $this->computeSeasonMultiplier($date);
    }

    protected function computeSeasonMultiplier(\Carbon\Carbon $date): float
    {
        $md = $date->format('m-d');
        foreach (self::PEAK_SEASONS as $s) {
            if ($md >= $s['start'] && $md <= $s['end']) return 1.15;
        }
        foreach (self::OFF_PEAK_SEASONS as $s) {
            if ($md >= $s['start'] && $md <= $s['end']) return 0.9;
        }
        return 1.0;
    }

    protected function distanceMultiplier(ExperienceBooking $booking): float
    {
        if (!$booking->origin_location) return 1.0;
        $dest = $booking->destination;
        if (!$dest || !$dest->lat || !$dest->lng) return 1.0;

        $originCoords = $this->resolveOriginCoords($booking->origin_location, $booking->origin_county_id);
        if (!$originCoords) return 1.0;

        $km = $this->haversine(
            $originCoords[0], $originCoords[1],
            (float) $dest->lat, (float) $dest->lng
        );

        return $this->computeDistanceMultiplier($km);
    }

    protected function computeDistanceMultiplier(float $km): float
    {
        if ($km <= 0) return 1.0;
        if ($km < 10) return 1.0;
        if ($km < 50) return 1.1;
        if ($km < 100) return 1.2;
        if ($km < 300) return 1.3;
        if ($km < 500) return 1.5;
        return min(2.0, 1.5 + ($km / 1000));
    }

    protected function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    protected function destinationEntryPrice(ExperienceBooking $booking): float
    {
        $dest = $booking->destination;
        if ($dest instanceof CountyInstitution) return 0.0;
        if ($dest instanceof Product) return (float) $dest->price;
        return 0.0;
    }

    protected function resolveOriginCoords(string $location, ?int $countyId): ?array
    {
        if ($countyId) {
            $county = \App\Models\County::find($countyId);
            if ($county && $county->latitude && $county->longitude) {
                return [(float) $county->latitude, (float) $county->longitude];
            }
        }
        return null;
    }
}