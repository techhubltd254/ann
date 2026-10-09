<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MediaAsset;
use App\Models\County;
use App\Models\Marketplace\Product;
use Illuminate\Support\Facades\Storage;

echo "== hero_video assets (model constant, no escaping) ==\n";
foreach ([1, 21, 27] as $cid) {
    $a = MediaAsset::where('owner_type', County::class)->where('owner_id', $cid)
        ->where('slot', 'hero_video')->with('derivatives')->get();
    echo "county $cid rows=" . $a->count() . "\n";
    foreach ($a as $r) {
        echo "  id={$r->id} st={$r->status} path={$r->path}\n";
        foreach ($r->derivatives as $d) echo "     der {$d->kind} => {$d->path}\n";
        echo "     posterUrl=" . ($r->posterUrl() ?? 'null') . "\n";
    }
    $fi = MediaAsset::where('owner_type', County::class)->where('owner_id', $cid)
        ->whereIn('slot', ['fallback_image', 'hero_image'])->where('status', 'ready')->latest('id')->first();
    echo "  fallback_image: " . ($fi ? $fi->path : 'none') . "\n";
    $c = County::find($cid);
    echo "  county profile_image: " . ($c->profile_image ?? 'null') . "\n";
}

echo "\n== R2 existence checks ==\n";
foreach ([
    'counties/mombasa/video/hero/poster/hero.webp',
    'counties/muranga/video/hero/poster/hero.webp',
    'landing/hero/poster/seedance-hero.webp',
] as $k) {
    echo "  $k => " . (Storage::disk('r2')->exists($k) ? 'EXISTS' : 'MISSING') . "\n";
}

echo "\n== resolver trace: products in county 21 ==\n";
$svc = app(App\Services\MediaFallbackResolver::class);
foreach (Product::where('county_id', 21)->limit(4)->get() as $p) {
    $own = $svc->ownMedia($p);
    $inst = $svc->parentInstitution($p);
    $sec = $svc->entitySectorSlug($p);
    $cp = $svc->countyHeroPoster($p);
    $pf = $svc->peerInstitutionFrame($p);
    echo "  #{$p->id} own=" . ($own ? 'Y' : 'N')
        . " inst=" . ($inst ? $inst->id . '/' . $inst->slug : 'N')
        . " sector=" . ($sec ?: '-')
        . " countyPoster=" . ($cp ?: 'null')
        . " peer=" . ($pf ?: 'null') . "\n";
    echo "     final=" . substr($p->image_url, 0, 95) . "\n";
}

echo "\n== resolver trace: products in county 1 (Mombasa) ==\n";
foreach (Product::where('county_id', 1)->limit(3)->get() as $p) {
    echo "  #{$p->id} own=" . ($svc->ownMedia($p) ? 'Y' : 'N')
        . " countyPoster=" . ($svc->countyHeroPoster($p) ?: 'null')
        . " final=" . substr($p->image_url, 0, 95) . "\n";
}
