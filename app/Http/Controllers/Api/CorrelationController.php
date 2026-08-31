<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CountyInstitution;
use App\Models\Marketplace\Product;
use App\Services\CorrelationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CorrelationController extends Controller
{
    public function forInstitution(int $id): JsonResponse
    {
        $institution = CountyInstitution::with('county')
            ->where('is_published', true)
            ->find($id);

        if (!$institution) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $recs = app(CorrelationService::class)->forInstitution($institution, 6);

        return response()->json($recs);
    }

    public function forProduct(int $id): JsonResponse
    {
        $product = Product::with(['county', 'variants', 'images'])
            ->active()
            ->find($id);

        if (!$product) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $recs = app(CorrelationService::class)->forProduct($product);

        return response()->json($recs);
    }

    public function forAttraction(int $id): JsonResponse
    {
        $attraction = \App\Models\CountyTourismAttraction::with('county')
            ->where('is_published', true)
            ->find($id);

        if (!$attraction) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $recs = app(CorrelationService::class)->forAttraction($attraction);

        return response()->json($recs);
    }
}