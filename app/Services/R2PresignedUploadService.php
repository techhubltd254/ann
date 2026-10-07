<?php

namespace App\Services;

use Aws\S3\S3Client;

class R2PresignedUploadService
{
    protected S3Client $client;
    protected string $bucket;

    public function __construct()
    {
        $this->bucket = config('filesystems.disks.r2.bucket', env('R2_BUCKET'));
        $this->client = new S3Client([
            'version' => 'latest',
            'region' => config('filesystems.disks.r2.region', 'auto'),
            'endpoint' => config('filesystems.disks.r2.endpoint', env('R2_ENDPOINT')),
            'credentials' => [
                'key' => config('filesystems.disks.r2.key', env('R2_ACCESS_KEY_ID')),
                'secret' => config('filesystems.disks.r2.secret', env('R2_SECRET_ACCESS_KEY')),
            ],
            'use_path_style_endpoint' => true,
        ]);
    }

    public function generateUploadPresignedUrl(string $path, string $mime = 'video/mp4', int $expires = 900): array
    {
        $cmd = $this->client->getCommand('PutObject', [
            'Bucket' => $this->bucket,
            'Key' => $path,
            'ContentType' => $mime,
            'ACL' => 'public-read',
        ]);

        $request = $this->client->createPresignedRequest($cmd, "+{$expires} seconds");
        $url = (string) $request->getUri();

        return [
            'url' => $url,
            'path' => $path,
            'expires_at' => now()->addSeconds($expires)->toIso8601String(),
        ];
    }
}
