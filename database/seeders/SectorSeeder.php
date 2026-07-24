<?php

namespace Database\Seeders;

use App\Models\County;
use App\Models\Sector;
use Illuminate\Database\Seeder;

class SectorSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/sectors.json');
        if (!file_exists($path)) {
            $this->command->error("File not found at: $path");
            return;
        }

        $sectors = json_decode(file_get_contents($path), true);
        if (empty($sectors)) {
            $this->command->error('sectors.json not found or empty');
            return;
        }

        foreach ($sectors as $i => $data) {
            $sector = Sector::updateOrCreate(
                ['slug' => $data['id']],
                [
                    'name' => $data['name'],
                    'code' => strtoupper($data['id']),
                    'emoji' => $data['emoji'] ?? null,
                    'description' => $data['description'] ?? null,
                    'is_active' => true,
                    'sort_order' => $i * 10,
                ]
            );

            if (!empty($data['counties'])) {
                $counties = County::whereIn('code', array_map(
                    fn($id) => 'KE-' . str_pad($id, 3, '0', STR_PAD_LEFT),
                    $data['counties']
                ))->pluck('id')->toArray();

                $sector->counties()->syncWithoutDetaching($counties);
            }
        }

        $this->command->info('Seeded ' . count($sectors) . ' sectors');
    }
}
