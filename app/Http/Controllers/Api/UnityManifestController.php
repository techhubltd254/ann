<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Unity Mobile App OTA update manifest.
 *
 * Addressable asset bundles and app binaries are stored on R2 at:
 *   https://kicc-r2-media.techhubltd254.workers.dev/unity/{version}/
 *
 * The build pipeline (scripts/build-unity.sh) uploads bundles and
 * updates this manifest after each release.
 */
class UnityManifestController extends Controller
{
    public function show(): JsonResponse
    {
        $path = storage_path('app/unity-manifest.json');
        if (! is_file($path)) {
            return response()->json([
                'version' => 1,
                'bundles' => [],
                'app_url' => null,
            ]);
        }

        $manifest = json_decode((string) file_get_contents($path), true);
        return response()->json($manifest)
            ->header('Cache-Control', 'public, s-maxage=300, stale-while-revalidate=3600');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'version' => 'required|integer|min:1',
            'bundles' => 'nullable|array',
            'bundles.*' => 'string',
            'app_url' => 'nullable|url',
        ]);

        $path = storage_path('app/unity-manifest.json');
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));

        return response()->json(['status' => 'ok']);
    }
}