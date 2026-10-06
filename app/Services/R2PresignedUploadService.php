<?php

namespace App\Services;

use Aws\S3\S3Client;
use Illuminate\Support\Str;

/**
 * Generates presigned URLs for direct upload to Cloudflare R2.
 *
 * Bypasses the Cloudflare Worker's 100 MB body-size limit by letting
 * the browser upload directly to R2 via S3-compatible presigned PUT.
 * After the upload finishes, the frontend POSTs the R2 path + metadata
 * to the app so a MediaAsset row is created.
 */
class R2PresignedUploadService
{
    protected S3Client $client;
    protected string $bucket;

    public function __construct()
    {
        $this->bucket = config('filesystems.disks.r2.bucket');
        $this->client = new S3Client([
            'version' => 'latest',
            'region' => config('filesystems.disks.r2.region', 'auto'),
            'endpoint' => config('filesystems.disks.r2.endpoint'),
            'credentials' => [
                'key' => config('filesystems.disks.r2.key'),
                'secret' => config('filesystems.disks.r2.secret'),
            ],
            'use_path_style_endpoint' => true,
        ]);
    }

    /**
     * Generate a presigned PUT URL for a single file upload.
     *
     * @param  string  $path      R2 object key, e.g. "counties/muranga/video/hero/hero.mp4"
     * @param  string  $mime      Content-Type for the upload
     * @param  int     $expires   Link validity in seconds (default 15 min)
     * @return array{url: string, fields: array, path: string}
     */
    public function generateUploadPresignedUrl(string $path, string $mime = 'video/mp4', int $expires = 900): array
    {
        $cmd = $this->client->getCommand('PutObject', [
            'Bucket' => $this->bucket,
            'Key' => $path,
            'ContentType' => $mime,
            'ACL' => 'public-read',
        ]);

        $request = $this->client->createPresignedRequest($cmd, "+{$expires} seconds");

        return [
            'url' => (string) $request->getUri(),
            'path' => $path,
            'expires_at' => now()->addSeconds($expires)->toIso8601String(),
        ];
    }
}