<?php

namespace App\Services;

use App\Models\LiveStream;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudflareStreamService
{
    protected string $accountId;
    protected string $token;

    public function __construct()
    {
        $this->accountId = 'c8416e05ed0a3554806be51aac862ec4';
        $this->token = 'cfat_O5sm7cBn09iAcOrgkassBRO4yNElj1PhBdmh2r4K0f9e27ef';
    }

    protected function api(string $method, string $path, array $data = []): ?array
    {
        $url = "https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/stream/{$path}";
        try {
            $response = Http::withToken($this->token)->$method($url, $data);
            $body = $response->json();
            if (($body['success'] ?? false)) {
                return $body['result'] ?? null;
            }
            Log::warning('Cloudflare Stream API error', ['path' => $path, 'errors' => $body['errors'] ?? []]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Cloudflare Stream API exception', ['path' => $path, 'error' => $e->getMessage()]);
            return null;
        }
    }

    public function uploadVideo(string $filePath, string $name = ''): ?array
    {
        try {
            $url = "https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/stream";
            $response = Http::withToken($this->token)
                ->attach('file', file_get_contents($filePath), basename($filePath))
                ->post($url, ['name' => $name ?: basename($filePath)]);
            $body = $response->json();
            if ($body['success'] ?? false) {
                return $body['result'];
            }
            Log::warning('Stream upload failed', ['errors' => $body['errors'] ?? []]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Stream upload exception: ' . $e->getMessage());
            return null;
        }
    }

    public function getVideo(string $uid): ?array
    {
        return $this->api('get', $uid);
    }

    public function listVideos(int $perPage = 50): ?array
    {
        return $this->api('get', "?per_page={$perPage}");
    }

    public function deleteVideo(string $uid): bool
    {
        return $this->api('delete', $uid) !== null;
    }

    public function getHlsUrl(string $uid): ?string
    {
        return "https://customer-{m}.cloudflarestream.com/{$uid}/manifest/video.m3u8";
    }

    public function getThumbnailUrl(string $uid): string
    {
        return "https://customer-{m}.cloudflarestream.com/{$uid}/thumbnails/thumbnail.jpg";
    }

    public function getPreviewUrl(string $uid): string
    {
        return "https://customer-{m}.cloudflarestream.com/{$uid}/thumbnails/thumbnail.gif";
    }

    public function recordLiveStream(LiveStream $stream): ?array
    {
        $result = $this->api('post', 'live_inputs', [
            'name' => $stream->name,
            'recording' => ['mode' => 'automatic'],
        ]);
        if ($result) {
            $stream->update([
                'stream_url' => $result['rtmps_url'] ?? $result['rtmps_url'] ?? null,
                'playback_url' => $result['playback']['hls'] ?? null,
                'hls_url' => $result['playback']['hls'] ?? null,
            ]);
        }
        return $result;
    }

    public function createLiveInput(string $name): ?array
    {
        $result = $this->api('post', 'live_inputs', [
            'name' => $name,
            'recording' => ['mode' => 'automatic'],
        ]);
        return $result;
    }

    public function generateLiveInput(string $name): ?array
    {
        return $this->createLiveInput($name);
    }
}