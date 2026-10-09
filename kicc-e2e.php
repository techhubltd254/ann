<?php
/**
 * Live end-to-end proof of the county-media backend, executed on production.
 *
 * Boots the real application, signs in the real admin user, and pushes a real
 * multipart request through the real HTTP kernel into the real registered route
 * -> controller -> R2 + media_assets -> back out again. Nothing is simulated
 * except the browser.
 */

require __DIR__ . '/vendor/autoload.php';

$app    = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\County;
use App\Models\MediaAsset;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

$SLUG = 'kwale';
$county = County::where('slug', $SLUG)->firstOrFail();
$r2 = Storage::disk('r2');

function rows(int $id): array
{
    return MediaAsset::where('owner_type', County::class)
        ->where('owner_id', $id)->where('slot', 'fallback_image')
        ->get(['id', 'path'])->map(fn($a) => $a->id . ':' . $a->path)->all();
}

/** Fire a real request through the real kernel, authenticated + CSRF-tokened. */
function fire($app, $kernel, string $method, string $uri, array $input = [], array $files = []): array
{
    $session = $app['session']->driver();
    $session->start();

    $request = Request::create($uri, $method, $input);
    $request->setLaravelSession($session);
    $request->request->set('_token', $session->token());
    foreach ($files as $k => $v) {
        $request->files->set($k, $v);
    }
    Auth::loginUsingId(1);

    $response = $kernel->handle($request);

    return [$response->getStatusCode(), $response->headers->get('Location')];
}

echo "=== 0. registered routes for the image backend ===\n";
foreach (app('router')->getRoutes() as $r) {
    if (str_contains($r->uri(), 'pro/image')) {
        printf("  %-6s %-52s -> %s\n", implode('|', $r->methods()), $r->uri(),
            $r->getActionName());
    }
}

echo "\n=== 1. BEFORE ===\n";
$before = rows($county->id);
echo "  rows: " . (implode('  ', $before) ?: '(none)') . "\n";
$beforePaths = MediaAsset::where('owner_type', County::class)->where('owner_id', $county->id)
    ->where('slot', 'fallback_image')->pluck('path')->all();
foreach ($beforePaths as $p) {
    echo "  r2 exists({$p}) = " . var_export($r2->exists($p), true) . "\n";
}

/* ── 2. UPLOAD through the live HTTP route ─────────────────────────────── */
echo "\n=== 2. POST /county-admin/{$SLUG}/pro/image  (sector=hero, real bytes) ===\n";
$src = '/tmp/kicc-stills/kwale.webp';
$tmp = sys_get_temp_dir() . '/e2e-upload.webp';
copy($src, $tmp);
$file = new UploadedFile($tmp, 'kwale-county.webp', 'image/webp', null, true);

[$code, $loc] = fire($app, $kernel, 'POST', "/county-admin/{$SLUG}/pro/image",
    ['sector' => 'hero'], ['image' => $file]);
echo "  http status : {$code}\n";
echo "  redirect    : " . ($loc ?: '-') . "\n";

echo "\n=== 3. AFTER UPLOAD ===\n";
$after = rows($county->id);
echo "  rows: " . (implode('  ', $after) ?: '(none)') . "\n";
$newPath = MediaAsset::where('owner_type', County::class)->where('owner_id', $county->id)
    ->where('slot', 'fallback_image')->latest('id')->value('path');
echo "  new r2 path : {$newPath}\n";
echo "  r2 exists   : " . var_export($r2->exists($newPath), true) . "\n";
$bytes = (int) $r2->size($newPath);
echo "  r2 bytes    : {$bytes}\n";
echo "  served url  : " . (MediaAsset::where('path', $newPath)->first()?->thumbnailUrl() ?: '-') . "\n";

/* ── 4. DELETE through the live HTTP route ─────────────────────────────── */
echo "\n=== 4. delete route ===\n";
$delUri = null;
foreach (app('router')->getRoutes() as $r) {
    if (str_contains($r->uri(), 'pro/image') && in_array('POST', $r->methods(), true)
        && str_contains($r->uri(), 'delete')) {
        $delUri = $r->uri();
    }
}
if ($delUri) {
    $uri = str_replace(['{slug}', '{sector}'], [$SLUG, 'hero'], $delUri);
    echo "  POST {$uri}\n";
    [$dcode, $dloc] = fire($app, $kernel, 'POST', $uri);
    echo "  http status : {$dcode}\n";
    $afterDel = rows($county->id);
    echo "  rows after delete: " . (implode('  ', $afterDel) ?: '(none)') . "\n";
    echo "  r2 still exists({$newPath}) = " . var_export($r2->exists($newPath), true) . "\n";
} else {
    echo "  (no delete route matched)\n";
}

/* ── 5. RESTORE via the artisan command (same backend path) ────────────── */
echo "\n=== 5. RESTORE via kicc:county-image ===\n";
echo shell_exec('cd ' . escapeshellarg(dirname(__DIR__) . '/opt') . ' 2>/dev/null; '
    . 'php ' . escapeshellarg(__DIR__ . '/artisan') . ' kicc:county-image ' . $SLUG
    . ' ' . escapeshellarg($src) . ' --slot=fallback_image 2>&1 | tail -3');

echo "\n=== 6. FINAL STATE ===\n";
$final = rows($county->id);
echo "  rows: " . (implode('  ', $final) ?: '(none)') . "\n";
$fp = MediaAsset::where('owner_type', County::class)->where('owner_id', $county->id)
    ->where('slot', 'fallback_image')->latest('id')->value('path');
echo "  path: {$fp}  r2 exists = " . var_export($r2->exists($fp), true) . "\n";
echo "  resolver: " . var_export(App\Support\MediaMapping::countyFallbackImage($county), true) . "\n";
