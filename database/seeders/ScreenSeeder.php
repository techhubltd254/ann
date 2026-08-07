<?php

namespace Database\Seeders;

use App\Models\Screen;
use Illuminate\Database\Seeder;

class ScreenSeeder extends Seeder
{
    public function run(): void
    {
        $screens = config('screens.default_screens');

        foreach ($screens as $s) {
            Screen::updateOrCreate(
                ['id' => $s['id']],
                [
                    'label' => $s['label'],
                    'location' => $s['location'],
                    'county_id' => $s['county_id'],
                    'sector_id' => $s['sector_id'],
                    'target_duration_sec' => $s['duration'],
                    'min_images' => $s['min'],
                    'max_images' => $s['max'],
                    'refresh_interval_min' => $s['refresh'],
                    'active' => true,
                ]
            );
        }

        $this->command->info('Seeded ' . count($screens) . ' exhibition screens.');
    }
}
