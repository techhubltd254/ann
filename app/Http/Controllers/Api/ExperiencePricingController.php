<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\CountyInstitution;
use App\Models\Marketplace\Product;
use App\Services\ExperiencePricingService;
use App\Services\DisplayPriorityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExperiencePricingController extends Controller
{
    public function preview(Request $request): JsonResponse
    {
        $data = $request->validate([
            'destination_type' => 'required|string|in:institution,product',
            'destination_id' => 'required|integer',
            'departure_date' => 'required|date',
            'origin_location' => 'nullable|string|max:255',
            'origin_county_id' => 'nullable|integer|exists:counties,id',
        ]);

        $avgRating = 0.0;
        $basePrice = 0.0;
        $lat = 0.0;
        $lng = 0.0;

        if ($data['destination_type'] === 'institution') {
            $dest = CountyInstitution::find($data['destination_id']);
            if ($dest) {
                $rows = \DB::table('reviews')
                    ->where('reviewable_type', CountyInstitution::class)
                    ->where('reviewable_id', $dest->id)
                    ->where('status', 'approved')
                    ->selectRaw('AVG(rating) as avg_r, COUNT(*) as cnt')->first();
                $avgRating = (float) ($rows->avg_r ?? 0);
                $lat = (float) ($dest->lat ?? 0);
                $lng = (float) ($dest->lng ?? 0);
            }
        } elseif ($data['destination_type'] === 'product') {
            $dest = Product::find($data['destination_id']);
            if ($dest) {
                $score = app(DisplayPriorityService::class)->scoreFor($dest->id);
                $avgRating = $score['average'] ?? 0;
                $basePrice = (float) $dest->price;
                if ($dest->county) {
                    $lat = (float) ($dest->county->latitude ?? 0);
                    $lng = (float) ($dest->county->longitude ?? 0);
                }
            }
        }

        $distanceKm = 0;
        if ($data['origin_county_id']) {
            $originCounty = County::find($data['origin_county_id']);
            if ($originCounty && $originCounty->latitude && $originCounty->longitude && $lat && $lng) {
                $km = $this->haversine(
                    (float) $originCounty->latitude, (float) $originCounty->longitude,
                    $lat, $lng
                );
                $distanceKm = round($km, 1);
            }
        }

        $pricing = app(ExperiencePricingService::class)->preview(
            max($basePrice, 500),
            $data['departure_date'],
            $avgRating,
            $distanceKm
        );

        return response()->json([
            'preview' => $pricing,
            'distance_km' => $distanceKm,
            'rating' => round($avgRating, 1),
            'origin_counties' => County::orderBy('name')->get(['id', 'name', 'slug']),
        ]);
    }

    protected function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat/2)**2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2)**2;
        return $r * 2 * atan2(sqrt($a), sqrt(1-$a));
    }
}