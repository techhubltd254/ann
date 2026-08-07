<?php

namespace Database\Seeders;

use App\Models\County;
use App\Models\SeasonalCalendar;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CountySeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/counties.json');
        if (!file_exists($path)) {
            $this->command->error("File not found at: $path");
            return;
        }

        $counties = json_decode(file_get_contents($path), true);
        if (empty($counties)) {
            $this->command->error('counties.json not found or empty');
            return;
        }

        foreach ($counties as $data) {
            County::updateOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'capital' => $data['capital'],
                    'slug' => Str::slug($data['name']),
                    'former_province' => $data['former_province'],
                    'economic_zone' => $data['economic_zone'],
                    'region' => $data['region'] ?? null,
                    'population_2024' => is_numeric($data['population_2024'])
                        ? (int) str_replace(',', '', (string) $data['population_2024'])
                        : null,
                    'area_km2' => $data['area_km2'],
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'weather_station_id' => $data['weather_station_id'],
                    'primary_sectors' => $data['primary_sectors'],
                    'icon_emoji' => $data['icon_emoji'],
                    'tagline' => $data['tagline'],
                    'description' => $data['description'],
                    'tourism_highlights' => $data['tourism_highlights'],
                    'warmest_month' => $data['warmest_month'],
                    'coolest_month' => $data['coolest_month'],
                    'rainy_season' => $data['rainy_season'],
                    'dry_season' => $data['dry_season'],
                    'weather_tags' => ['warm', 'sunny'],
                ]
            );
        }

        $this->command->info('Seeded ' . count($counties) . ' counties');
        $this->seedSeasonalCalendars();
    }

    private function seedSeasonalCalendars(): void
    {
        $path = database_path('data/seasonal_calendar.csv');

        if (!file_exists($path)) {
            $this->command->warn('seasonal_calendar.csv not found');
            return;
        }

        $rows = array_map('str_getcsv', file($path));
        $header = array_shift($rows);
        $count = 0;

        foreach ($rows as $row) {
            $data = array_combine($header, $row);
            $county = County::where('code', sprintf('KE-%03d', (int) $data['county_id']))->first();
            if (!$county) continue;

            SeasonalCalendar::updateOrCreate(
                ['county_id' => $county->id, 'month' => (int) $data['month']],
                [
                    'avg_temp_c' => (float) $data['avg_temp_c'],
                    'rainfall_mm' => (float) $data['rainfall_mm'],
                    'tourism_season' => $data['tourism_season'],
                    'agri_season' => $data['agri_season'],
                    'weather_tag' => $data['weather_tag'],
                ]
            );
            $count++;
        }

        $this->command->info("Seeded {$count} seasonal calendar entries");
    }
}
