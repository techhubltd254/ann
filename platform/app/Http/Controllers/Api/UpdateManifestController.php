<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * OTA update manifest — consumed by installed admin apps (mother, county servers,
 * exhibitor OWN_SERVER) running engine UpdateService.
 *
 * Releases are managed by `php artisan ota:release {version} {jar}` which signs
 * the jar and writes storage/app/ota/releases.json. This endpoint is public
 * (manifests are not secrets; integrity comes from the ed25519 signature).
 */
class UpdateManifestController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $channel = $request->query('channel', 'stable');
        $current = $request->query('current', '0.0.0');

        $releases = $this->releases();
        $release = $releases[$channel] ?? null;

        if (! $release || ! $this->isNewer($release['version'], (string) $current)) {
            // 204: already up to date — keeps polling cheap for the whole fleet.
            return response()->json(null, 204);
        }

        return response()->json([
            'version'    => $release['version'],
            'url'        => $release['url'],
            'sha256'     => $release['sha256'],
            'signature'  => $release['signature'],
            'minVersion' => $release['min_version'] ?? '0.0.0',
            'mandatory'  => (bool) ($release['mandatory'] ?? false),
        ])->header('Cache-Control', 'public, s-maxage=300, stale-while-revalidate=3600');
    }

    /** @return array<string, array<string, mixed>> */
    private function releases(): array
    {
        $path = storage_path('app/ota/releases.json');
        if (! is_file($path)) {
            return [];
        }
        $decoded = json_decode((string) file_get_contents($path), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function isNewer(string $a, string $b): bool
    {
        $pa = array_map('intval', explode('.', $a));
        $pb = array_map('intval', explode('.', $b));
        for ($i = 0; $i < 3; $i++) {
            $d = ($pa[$i] ?? 0) <=> ($pb[$i] ?? 0);
            if ($d !== 0) {
                return $d > 0;
            }
        }
        return false;
    }
}
