<?php
namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class SignedUrlService
{
    private string $secret;

    public function __construct()
    {
        $this->secret = (string) env('SIGNED_URL_SECRET', config('app.key'));
    }

    /**
     * Generate an expiring signed URL for stream/asset playback.
     * Prevents hotlinking and unauthorized viewing.
     */
    public function sign(string $path, int $ttlSeconds = 3600, array $params = []): string
    {
        $expires = now()->addSeconds($ttlSeconds)->timestamp;
        $params['expires'] = $expires;
        ksort($params);

        $query = http_build_query($params);
        $token = hash_hmac('sha256', $path . '?' . $query, $this->secret);

        $base = rtrim(env('APP_URL', url('/')), '/');
        return $base . $path . '?' . $query . '&token=' . $token;
    }

    /**
     * Verify a signed URL. Returns true if valid and not expired.
     */
    public function verify(string $path, array $params): bool
    {
        $expires = $params['expires'] ?? null;
        $token = $params['token'] ?? null;

        if (!$expires || !$token || (int) $expires < now()->timestamp) {
            return false;
        }

        $check = $params;
        unset($check['token']);
        ksort($check);

        $expected = hash_hmac('sha256', $path . '?' . http_build_query($check), $this->secret);

        return hash_equals($expected, $token);
    }

    /**
     * Sign a Cloudflare Stream HLS manifest with a short expiry.
     */
    public function signStreamManifest(string $streamUid, int $ttlSeconds = 900): string
    {
        $path = "/live/$streamUid/manifest/video.m3u8";
        $expires = now()->addSeconds($ttlSeconds)->timestamp;
        $token = hash_hmac('sha256', $streamUid . $expires, $this->secret);

        return "https://cloudflarestream.com/$streamUid/manifest/video.m3u8?token=$token&expires=$expires";
    }
}