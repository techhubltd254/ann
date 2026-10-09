<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Storage;
use App\Models\County;
use App\Models\MediaAsset;

$files = Storage::disk('r2')->allFiles();
$hero = [];
$hover = [];
$poster = [];
$hls = [];
$sector = [];
foreach ($files as $f) {
    if (preg_match('#^counties/([^/]+)/video/hero/hero\.mp4$#', $f, $m)) $hero[$m[1]] = true;
    if (preg_match('#^counties/([^/]+)/video/hero/hover/hero\.mp4$#', $f, $m)) $hover[$m[1]] = true;
    if (preg_match('#^counties/([^/]+)/video/hero/poster/hero\.webp$#', $f, $m)) $poster[$m[1]] = true;
    if (preg_match('#^counties/([^/]+)/video/hero/hls/hero/master\.m3u8$#', $f, $m)) $hls[$m[1]] = true;
    if (preg_match('#^counties/([^/]+)/sector-videos/(.+)$#', $f, $m)) $sector[$m[1]][] = $m[2];
}

echo "=== R2 INVENTORY ===" . PHP_EOL;
echo "total R2 objects: " . count($files) . PHP_EOL;
echo "counties with hero.mp4: " . count($hero) . PHP_EOL;
echo "  " . implode(', ', array_keys($hero)) . PHP_EOL;
echo "counties with HLS master: " . count($hls) . PHP_EOL;
echo "  " . implode(', ', array_keys($hls)) . PHP_EOL;
echo "counties with hover loop: " . count($hover) . PHP_EOL;
echo "  " . implode(', ', array_keys($hover)) . PHP_EOL;
echo "counties with poster: " . count($poster) . PHP_EOL;
echo "  " . implode(', ', array_keys($poster)) . PHP_EOL;
echo "counties with sector-videos: " . count($sector) . PHP_EOL;
foreach ($sector as $s => $v) echo "  " . $s . ": " . implode(',', $v) . PHP_EOL;

echo PHP_EOL . "=== TOP-LEVEL R2 PREFIXES ===" . PHP_EOL;
$pre = [];
foreach ($files as $f) { $p = explode('/', $f)[0]; $pre[$p] = ($pre[$p] ?? 0) + 1; }
arsort($pre);
foreach ($pre as $p => $n) echo "  " . str_pad($p, 24) . $n . PHP_EOL;

echo PHP_EOL . "=== IMAGE ASSETS (fallback pool) ===" . PHP_EOL;
foreach (MediaAsset::where('kind', 'image')->get(['id', 'owner_type', 'owner_id', 'path', 'status']) as $a) {
    echo "  id=" . $a->id . " " . $a->status . " " . $a->owner_type . "#" . $a->owner_id . " => " . $a->path . PHP_EOL;
}

echo PHP_EOL . "=== COUNTY HERO MAPPING (live) ===" . PHP_EOL;
$rows = MediaAsset::where('kind', 'video')->where('owner_type', 'App\\Models\\County')
    ->where('slot', 'hero_video')->get(['owner_id', 'path', 'status']);
$byOwner = [];
foreach ($rows as $r) $byOwner[$r->owner_id] = $r->path;

$nReal = 0; $nFallback = 0; $nNone = 0;
foreach (County::orderBy('id')->get(['id', 'name', 'slug']) as $c) {
    $has = isset($hero[$c->slug]);
    $cur = $byOwner[$c->id] ?? '(none)';
    if ($has) $nReal++;
    elseif ($cur === '(none)') $nNone++;
    else $nFallback++;
    printf("  %-3d %-22s %-10s r2_hero=%-3s cur=%s\n",
        $c->id, $c->slug, $c->name, $has ? 'YES' : 'no', $cur);
}
echo PHP_EOL . "counties with real R2 hero: " . $nReal . PHP_EOL;
echo "counties wrongly pointing at a shared/fallback file: " . $nFallback . PHP_EOL;
echo "counties with no row at all: " . $nNone . PHP_EOL;
