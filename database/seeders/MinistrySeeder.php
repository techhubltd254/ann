<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Ministry;
use Illuminate\Database\Seeder;

class MinistrySeeder extends Seeder
{
    public function run(): void
    {
        $ministries = [
            ['name' => 'Ministry of Tourism and Wildlife', 'code' => 'MOT', 'color' => '#0B1E57', 'description' => 'Tourism promotion, wildlife conservation, and hospitality development'],
            ['name' => 'Ministry of Agriculture, Livestock and Fisheries', 'code' => 'MOA', 'color' => '#2D6A4F', 'description' => 'Agricultural development, food security, and livestock management'],
            ['name' => 'Ministry of Health', 'code' => 'MOH', 'color' => '#901C1E', 'description' => 'Healthcare services, public health, and medical research'],
            ['name' => 'Ministry of Trade, Investment and Industry', 'code' => 'MOTI', 'color' => '#E76F51', 'description' => 'Trade promotion, industrialization, and investment facilitation'],
            ['name' => 'Ministry of Information, Communications and Digital Economy', 'code' => 'MICDE', 'color' => '#11820B', 'description' => 'ICT infrastructure, digital economy, and communications'],
            ['name' => 'Ministry of Transport and Infrastructure', 'code' => 'MOTI2', 'color' => '#334155', 'description' => 'Transport systems, roads, railways, and infrastructure development'],
            ['name' => 'Ministry of Energy and Petroleum', 'code' => 'MOEP', 'color' => '#FFCD05', 'description' => 'Energy generation, petroleum, and renewable energy'],
            ['name' => 'Ministry of Education', 'code' => 'MOE', 'color' => '#8a6b00', 'description' => 'Education policy, curriculum, and institutional management'],
            ['name' => 'Ministry of Environment, Climate Change and Forestry', 'code' => 'MOECCF', 'color' => '#11820B', 'description' => 'Environmental protection, climate action, and forest conservation'],
            ['name' => 'Ministry of Water, Sanitation and Irrigation', 'code' => 'MOWSI', 'color' => '#0693e3', 'description' => 'Water resources, sanitation, and irrigation systems'],
        ];

        foreach ($ministries as $m) {
            $ministry = Ministry::firstOrCreate(
                ['code' => $m['code']],
                [
                    'name' => $m['name'],
                    'slug' => \Illuminate\Support\Str::slug($m['name']),
                    'code' => $m['code'],
                    'color' => $m['color'],
                    'description' => $m['description'],
                    'is_active' => true,
                ]
            );
        }

        // Create agencies under ministries
        $agencies = [
            ['ministry' => 'MOT', 'name' => 'Kenya Tourism Board', 'code' => 'KTB'],
            ['ministry' => 'MOT', 'name' => 'Kenya Wildlife Service', 'code' => 'KWS'],
            ['ministry' => 'MOA', 'name' => 'Agriculture and Food Authority', 'code' => 'AFA'],
            ['ministry' => 'MOA', 'name' => 'Kenya Plant Health Inspectorate Service', 'code' => 'KEPHIS'],
            ['ministry' => 'MOH', 'name' => 'Kenya Medical Research Institute', 'code' => 'KEMRI'],
            ['ministry' => 'MOH', 'name' => 'Pharmacy and Poisons Board', 'code' => 'PPB'],
            ['ministry' => 'MOTI', 'name' => 'Kenya Export Promotion and Branding Agency', 'code' => 'KEPROBA'],
            ['ministry' => 'MICDE', 'name' => 'Communications Authority of Kenya', 'code' => 'CA'],
            ['ministry' => 'MOE', 'name' => 'Teachers Service Commission', 'code' => 'TSC'],
            ['ministry' => 'MOTI2', 'name' => 'Kenya National Highways Authority', 'code' => 'KeNHA'],
        ];

        foreach ($agencies as $a) {
            $ministry = Ministry::where('code', $a['ministry'])->first();
            if ($ministry) {
                Agency::firstOrCreate(
                    ['code' => $a['code']],
                    [
                        'ministry_id' => $ministry->id,
                        'name' => $a['name'],
                        'slug' => \Illuminate\Support\Str::slug($a['name']),
                        'code' => $a['code'],
                        'description' => $a['name'] . ' — under ' . $ministry->name,
                        'is_active' => true,
                    ]
                );
            }
        }

        $this->command->info('Ministries and agencies seeded: ' . Ministry::count() . ' ministries, ' . Agency::count() . ' agencies');
    }
}
