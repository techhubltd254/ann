<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Publish a signed OTA release of the admin engine.
 *
 *   php artisan ota:release 1.1.0 /path/to/kicc-engine.jar --channel=stable --mandatory
 *
 * Computes SHA-256, signs the digest with the ed25519 release key
 * (infra/ota/release_ed25519.secret.hex — the private key NEVER lives on the
 * web server in production; run this in CI and commit only releases.json + the
 * uploaded artifact), uploads the jar to the public disk (R2 in prod), and
 * updates storage/app/ota/releases.json which UpdateManifestController serves.
 */
class OtaReleaseCommand extends Command
{
    protected $signature = 'ota:release
        {version : semver of the new release, e.g. 1.1.0}
        {jar : path to kicc-engine.jar}
        {--channel=stable : stable|beta|county}
        {--min-version=0.0.0 : oldest version allowed to skip this update}
        {--mandatory : force update even below min-version}';

    protected $description = 'Sign and publish an OTA engine release (ed25519 + SHA-256)';

    public function handle(): int
    {
        $version = ltrim((string) $this->argument('version'), 'v');
        $jar = (string) $this->argument('jar');
        if (! is_file($jar)) {
            $this->error("jar not found: {$jar}");
            return self::FAILURE;
        }

        $bytes = (string) file_get_contents($jar);
        $digest = hash('sha256', $bytes, true);
        $sha256 = bin2hex($digest);

        $keyPath = env('OTA_SIGNING_KEY', base_path('../infra/ota/release_ed25519.secret.hex'));
        if (! is_file($keyPath)) {
            $this->error("signing key not found: {$keyPath} (set OTA_SIGNING_KEY)");
            return self::FAILURE;
        }
        $secret = sodium_hex2bin(trim((string) file_get_contents($keyPath)));
        $signature = bin2hex(sodium_crypto_sign_detached($digest, $secret));
        sodium_memzero($secret);

        // Upload artifact to the public disk (R2/S3 in prod, local in dev).
        $channel = (string) $this->option('channel');
        $key = "releases/{$channel}/kicc-engine-{$version}.jar";
        Storage::disk('s3')->put($key, $bytes, ['visibility' => 'public']);
        $url = rtrim((string) config('filesystems.disks.s3.url', ''), '/')."/{$key}";

        // Update the releases registry.
        $path = storage_path('app/ota/releases.json');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        $releases = is_file($path) ? (json_decode((string) file_get_contents($path), true) ?: []) : [];
        $releases[$channel] = array_filter([
            'version' => $version,
            'url' => $url,
            'sha256' => $sha256,
            'signature' => $signature,
            'min_version' => (string) $this->option('min-version'),
            'mandatory' => (bool) $this->option('mandatory'),
            'published_at' => now()->toIso8601String(),
        ], fn ($v) => $v !== null);
        file_put_contents($path, json_encode($releases, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info("OTA release {$version} published on channel '{$channel}'");
        $this->line("  sha256:    {$sha256}");
        $this->line("  signature: {$signature}");
        $this->line("  url:       {$url}");
        return self::SUCCESS;
    }
}
