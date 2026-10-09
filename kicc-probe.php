<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "COLUMNS: ".json_encode(Schema::getColumnListing('media_assets'))."\n\n";

echo "IMG ROWS: ".json_encode(
  DB::table('media_assets')->where('kind','image')->get(['id','owner_type','owner_id','slot','path','status','original_name','mime'])->all()
)."\n\n";

echo "READY VIDEO SAMPLE: ".json_encode(
  DB::table('media_assets')->where('status','ready')->where('kind','video')->limit(4)
    ->get(['id','owner_type','owner_id','slot','path','kind','status'])->all()
)."\n\n";

echo "TOP OWNERS READY: ".json_encode(
  DB::table('media_assets')->where('status','ready')->select('owner_type','owner_id',DB::raw('count(*) c'))
    ->groupBy('owner_type','owner_id')->orderByDesc('c')->limit(8)->get()->all()
)."\n\n";

echo "COUNTY NAMES: ".json_encode(DB::table('counties')->limit(3)->get(['id','name','slug'])->all())."\n";
echo "INST TABLE? ".json_encode(Schema::getColumnListing('county_institutions'))."\n";
echo "SCREENS: ".DB::table('screens')->count()."\n";
echo "EXHIBITIONS(all): ".DB::table('exhibitions')->count()."\n";
echo "HAS owner() method: ".(method_exists('App\\Models\\MediaAsset','owner') ? 'yes' : 'no')."\n";
echo "READY IMAGES WITH OWNER: ".json_encode(
  DB::table('media_assets as m')
    ->leftJoin('counties as c', function($j){ $j->on('c.id','=','m.owner_id')->where('m.owner_type','=','App\\Models\\County'); })
    ->where('m.kind','image')
    ->get(['m.id','m.owner_type','m.owner_id','m.path','m.slot','c.name as county_name'])->all()
)."\n";
