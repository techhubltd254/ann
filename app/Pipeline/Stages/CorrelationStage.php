<?php

namespace App\Pipeline\Stages;

use App\Services\CorrelationService;
use App\Models\CountyTourismAttraction;
use App\Models\Travel\Attraction;
use App\Models\Travel\Hotel;
use App\Models\Marketplace\Product;
use App\Models\CountyInstitution;

class CorrelationStage
{
    public function handle(array $context): array
    {
        $anchor = $context['anchor'];
        $correlations = ['places_to_visit' => [], 'places_to_stay' => [], 'transport' => []];
        $service = app(CorrelationService::class);

        // Get correlations from the existing engine
        $correlationResult = match (true) {
            $anchor instanceof CountyInstitution => $service->forInstitution($anchor),
            $anchor instanceof Attraction || $anchor instanceof CountyTourismAttraction => $service->forAttraction($anchor),
            $anchor instanceof Product => $service->forProduct($anchor),
            default => null,
        };

        if ($correlationResult) {
            $correlations = [
                'places_to_visit' => array_slice($correlationResult['places_to_visit'] ?? [], 0, config('experience.correlation.max_places_to_visit', 4)),
                'places_to_stay' => array_slice($correlationResult['places_to_stay'] ?? [], 0, config('experience.correlation.max_places_to_stay', 3)),
                'transport' => array_slice($correlationResult['transport'] ?? [], 0, config('experience.correlation.max_transport_options', 3)),
            ];
        }

        // Also get same-county attractions as additional recommendations
        if ($context['county'] && count($correlations['places_to_visit']) < 2) {
            $attractions = Attraction::where('county_id', $context['county']->id)
                ->where('is_active', true)->take(4)->get();
            foreach ($attractions as $a) {
                $correlations['places_to_visit'][] = [
                    'type' => 'culture',
                    'type_label' => 'Attraction',
                    'id' => $a->id,
                    'name' => $a->name,
                    'description' => $a->description ?? '',
                    'image_url' => is_string($a->images ?? null) ? (json_decode($a->images, true)[0] ?? null) : (is_array($a->images ?? null) ? ($a->images[0] ?? null) : null),
                    'entry_fee' => $a->entry_fee ?? 0,
                    'latitude' => $a->latitude,
                    'longitude' => $a->longitude,
                ];
            }
        }

        // Same-county hotels as stay options
        if ($context['county'] && count($correlations['places_to_stay']) < 2) {
            $hotels = Hotel::where('county_id', $context['county']->id)
                ->where('is_active', true)->take(3)->get();
            foreach ($hotels as $h) {
                $correlations['places_to_stay'][] = [
                    'type' => 'hotel',
                    'type_label' => 'Hotel',
                    'id' => $h->id,
                    'name' => $h->name,
                    'description' => $h->description ?? '',
                    'image_url' => is_string($h->images ?? null) ? (json_decode($h->images, true)[0] ?? null) : (is_array($h->images ?? null) ? ($h->images[0] ?? null) : null),
                    'price_per_night' => \Illuminate\Support\Facades\DB::table('hotel_rooms')->where('hotel_id', $h->id)->min('price_per_night') ?? 0,
                    'latitude' => $h->latitude,
                    'longitude' => $h->longitude,
                ];
            }
        }

        $context['correlations'] = $correlations;
        return $context;
    }
}