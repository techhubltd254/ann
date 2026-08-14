<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Duplicates the Muranga county skeleton to all 47 counties:
 * links the 8 canonical economic sector tiles (Tourism, Hospitality,
 * Agriculture, Commerce & End Products, Education, Transport,
 * Healthcare, Culture) to every county with display_on_tile='yes'.
 *
 * Idempotent — safe to run repeatedly.
 */
class AllCountiesSectorTilesSeeder extends Seeder
{
    public function run(): void
    {
        $sectorIds = [90001, 90002, 90003, 90004, 90005, 90006, 90007, 211];

        $counties = DB::table('counties')->select(['id', 'slug'])->orderBy('id')->get();

        $existing = DB::table('county_sector')
            ->select(['county_id', 'sector_id', 'display_on_tile'])
            ->get()
            ->keyBy(fn ($r) => $r->county_id . '-' . $r->sector_id);

        $now = now();
        $inserts = [];
        $updates = 0;

        foreach ($counties as $c) {
            foreach ($sectorIds as $sid) {
                $key = $c->id . '-' . $sid;
                if (!isset($existing[$key])) {
                    $inserts[] = [
                        'county_id' => $c->id,
                        'sector_id' => $sid,
                        'sub_sectors' => null,
                        'display_on_tile' => 'yes',
                        'displayOnTile' => 'yes',
                        'countyId' => $c->id,
                        'sectorId' => $sid,
                        'subSectors' => null,
                    ];
                } elseif ($existing[$key]->display_on_tile !== 'yes') {
                    DB::table('county_sector')
                        ->where('county_id', $c->id)
                        ->where('sector_id', $sid)
                        ->update(['display_on_tile' => 'yes', 'displayOnTile' => 'yes']);
                    $updates++;
                }
            }
        }

        foreach (array_chunk($inserts, 100) as $chunk) {
            DB::table('county_sector')->insert($chunk);
        }

        $linked = DB::table('county_sector')
            ->where('display_on_tile', 'yes')
            ->distinct('county_id')
            ->count('county_id');

        $this->command?->info("Inserted " . count($inserts) . ", updated {$updates}. Counties with tiles: {$linked}/47");
    }
}
