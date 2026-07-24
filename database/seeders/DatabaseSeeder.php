<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            CountySeeder::class,
            SectorSeeder::class,
            BlueprintSeeder::class,
            ScreenSeeder::class,
            MarketplaceSeeder::class,
        ]);
    }
}
