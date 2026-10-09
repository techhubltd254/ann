<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MediaAsset;
use App\Models\CountyInstitution;
use Illuminate\Support\Facades\Storage;

echo "== CountyInstitution media_assets (by class constant) ==\n";
$rows = MediaAsset::where('owner_type', CountyInstitution::class)->get(['id', 'owner_id', 'slot', 'path', 'status']);
echo "total institution asset rows: " . $rows->count() . "\n";
foreach ($rows as $r) {
    $inst = CountyInstitution::find($r->owner_id);
    echo sprintf("  inst#%s %-28s slot=%-18s st=%-8s %s\n",
        $r->owner_id, substr($inst->slug ?? '?', 0, 28), $r->slot, $r->status, substr($r->path, 0, 62));
}

echo "\n== institution media_assets with owner_type 'institution' ==\n";
foreach (MediaAsset::where('owner_type', 'institution')->get(['id', 'owner_id', 'slot', 'path', 'status']) as $r) {
    echo "  owner={$r->owner_id} slot={$r->slot} st={$r->status} {$r->path}\n";
}

echo "\n== institution video files in R2 ==\n";
foreach (Storage::disk('r2')->allFiles() as $k) {
    if (str_starts_with($k, 'institutions/')) echo "  $k\n";
}

echo "\n== resolveSlot + posterUrl per institution that has assets ==\n";
foreach ($rows->pluck('owner_id')->unique() as $oid) {
    foreach (['hero_video', 'institution_video', 'product_video'] as $slot) {
        $a = MediaAsset::resolveSlot(CountyInstitution::class, (int) $oid, $slot);
        if ($a) {
            $p = $a->posterUrl() ?? $a->thumbnailUrl();
            echo "  inst#$oid slot=$slot path={$a->path} poster=" . ($p ?: 'null')
                . " r2poster=" . (($a->path && Storage::disk('r2')->exists(dirname($a->path) . '/poster/' . basename($a->path, '.mp4') . '.webp')) ? 'YES' : 'no') . "\n";
        }
    }
}

echo "\n== product -> institution (user_id join) for county 21 ==\n";
foreach (App\Models\Marketplace\Product::where('county_id', 21)->get() as $p) {
    $inst = CountyInstitution::where('user_id', $p->user_id)->first();
    echo sprintf("  #%d %-34s user=%s inst=%s\n", $p->id, substr($p->name, 0, 34), $p->user_id, $inst ? $inst->id . '/' . $inst->slug : 'NONE');
}
