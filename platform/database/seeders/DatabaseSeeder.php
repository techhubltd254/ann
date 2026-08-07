<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            CountySeeder::class,
            SectorSeeder::class,
            MinistrySeeder::class,
            BlueprintSeeder::class,
            ScreenSeeder::class,
            MarketplaceSeeder::class,
            TradeSeeder::class,
        ]);
    }
}
