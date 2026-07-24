<?php

namespace Database\Seeders;

use App\Models\County;
use App\Models\Sector;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BlueprintSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSubscriptionPlans();
        $this->seedSectorEntities();
        $this->seedCountyTourism();
        $this->seedCountyHotels();
        $this->seedCountyProducts();
        $this->seedCountyInstitutions();
        $this->seedCountyFarms();
        $this->seedCountyTransport();
        $this->seedCountyHealth();
        $this->seedCountyCulture();
        $this->seedAdvertisements();
    }

    private function seedSubscriptionPlans(): void
    {
        $plans = [
            ['name' => 'Basic', 'slug' => 'basic', 'price' => 500, 'max_booths' => 1, 'max_exhibitions' => 2, 'max_media_files' => 10, 'has_livestream' => false, 'has_analytics' => false, 'sort_order' => 1],
            ['name' => 'Premium', 'slug' => 'premium', 'price' => 2500, 'max_booths' => 3, 'max_exhibitions' => 5, 'max_media_files' => 50, 'has_livestream' => true, 'has_analytics' => true, 'sort_order' => 2],
            ['name' => 'Enterprise', 'slug' => 'enterprise', 'price' => 10000, 'max_booths' => 10, 'max_exhibitions' => 20, 'max_media_files' => 200, 'has_livestream' => true, 'has_analytics' => true, 'has_priority_support' => true, 'sort_order' => 3],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }

        $this->command->info('Seeded ' . count($plans) . ' subscription plans');
    }

    private function seedSectorEntities(): void
    {
        $counties = County::all();
        $sectors = Sector::all();

        $entityTypes = [
            'tourism' => ['National Park', 'Game Reserve', 'Waterfall', 'Mountain', 'Lake', 'Beach', 'Cultural Village', 'Museum', 'Botanical Garden', 'Viewpoint'],
            'trade_sme' => ['Market', 'Shopping Centre', 'Craft Centre', 'Business Hub', 'Export Processing Zone', 'Industrial Park', 'Tech Hub', 'Manufacturer', 'Wholesaler'],
            'education' => ['University', 'College', 'Technical Institute', 'Primary School', 'Secondary School', 'Vocational Centre', 'Research Institute', 'Library'],
            'agriculture' => ['Farm', 'Cooperative', 'Processing Plant', 'Market', 'Research Station', 'Seed Centre', 'Dairy Farm', 'Fishery', 'Tea Estate', 'Coffee Farm'],
            'transport' => ['Airport', 'Bus Station', 'Railway Station', 'Port', 'Highway Junction', 'Truck Terminal', 'Taxi Hub'],
            'health' => ['Hospital', 'Health Centre', 'Clinic', 'Pharmacy', 'Laboratory', 'Maternity Centre', 'Mental Health Facility'],
            'culture' => ['Heritage Site', 'Monument', 'Art Gallery', 'Cultural Centre', 'Music Venue', 'Festival Ground', 'Craft Workshop', 'Historical Building'],
        ];

        $inserts = [];
        foreach ($counties as $county) {
            foreach ($sectors as $sector) {
                $types = $entityTypes[$sector->slug] ?? ['General'];
                $numEntities = rand(2, 5);
                for ($i = 0; $i < $numEntities; $i++) {
                    $inserts[] = [
                        'county_id' => $county->id,
                        'sector_id' => $sector->id,
                        'entity_type' => 'App\Models\CountyTourismAttraction',
                        'entity_id' => 0,
                        'name' => $types[array_rand($types)] . ' - ' . $county->name . ' ' . ($i + 1),
                        'description' => 'Description for ' . $county->name . ' ' . $sector->name . ' entity ' . ($i + 1),
                        'sector_type' => $sector->slug,
                        'is_published' => true,
                        'capture_status' => ['none', 'tier_c', 'tier_b', 'tier_a'][array_rand(['none', 'tier_c', 'tier_b', 'tier_a'])],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        foreach (array_chunk($inserts, 100) as $chunk) {
            DB::table('sector_entities')->insert($chunk);
        }

        $this->command->info('Seeded ' . count($inserts) . ' sector entities across all counties');
    }

    private function seedCountyTourism(): void
    {
        $counties = County::all();
        $attractions = [
            'National Park', 'Game Reserve', 'Nature Trail', 'Waterfall', 'Mountain Peak',
            'Scenic Viewpoint', 'Lake Shore', 'Beach', 'Coral Reef', 'Bird Sanctuary',
            'Botanical Garden', 'Cave System', 'Hot Springs', 'Cultural Village',
        ];

        $inserts = [];
        foreach ($counties as $county) {
            $num = rand(3, 6);
            for ($i = 0; $i < $num; $i++) {
                $inserts[] = [
                    'county_id' => $county->id,
                    'name' => $county->name . ' ' . $attractions[array_rand($attractions)],
                    'description' => 'A beautiful attraction in ' . $county->name . '. Perfect for visitors and tourists.',
                    'category' => ['nature', 'adventure', 'cultural', 'wildlife', 'scenic'][array_rand(['nature', 'adventure', 'cultural', 'wildlife', 'scenic'])],
                    'is_published' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($inserts, 100) as $chunk) {
            DB::table('county_tourism_attractions')->insert($chunk);
        }
        $this->command->info('Seeded ' . count($inserts) . ' tourism attractions');
    }

    private function seedCountyHotels(): void
    {
        $counties = County::all();
        $inserts = [];
        foreach ($counties as $county) {
            $num = rand(2, 5);
            for ($i = 0; $i < $num; $i++) {
                $inserts[] = [
                    'county_id' => $county->id,
                    'name' => $county->name . ' ' . ['Lodge', 'Hotel', 'Resort', 'Guest House', 'Inn'][array_rand(['Lodge', 'Hotel', 'Resort', 'Guest House', 'Inn'])],
                    'category' => ['hotel', 'lodge', 'resort', 'guesthouse'][array_rand(['hotel', 'lodge', 'resort', 'guesthouse'])],
                    'star_rating' => rand(2, 5),
                    'is_published' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($inserts, 100) as $chunk) {
            DB::table('county_hotels')->insert($chunk);
        }
        $this->command->info('Seeded ' . count($inserts) . ' hotels');
    }

    private function seedCountyProducts(): void
    {
        $counties = County::all();
        $products = ['Fresh Produce', 'Handicrafts', 'Textiles', 'Honey', 'Coffee', 'Tea', 'Maize', 'Beans'];
        $inserts = [];
        foreach ($counties as $county) {
            $num = rand(2, 4);
            for ($i = 0; $i < $num; $i++) {
                $inserts[] = [
                    'county_id' => $county->id,
                    'name' => $county->name . ' ' . $products[array_rand($products)],
                    'category' => ['agriculture', 'handicraft', 'processed', 'fresh'][array_rand(['agriculture', 'handicraft', 'processed', 'fresh'])],
                    'price' => rand(50, 5000),
                    'is_published' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($inserts, 100) as $chunk) {
            DB::table('county_products')->insert($chunk);
        }
        $this->command->info('Seeded ' . count($inserts) . ' products');
    }

    private function seedCountyInstitutions(): void
    {
        $counties = County::all();
        $types = ['University', 'College', 'Technical Institute', 'Secondary School', 'Primary School', 'Vocational Centre'];
        $inserts = [];
        foreach ($counties as $county) {
            $num = rand(2, 4);
            for ($i = 0; $i < $num; $i++) {
                $inserts[] = [
                    'county_id' => $county->id,
                    'name' => $county->name . ' ' . $types[array_rand($types)],
                    'type' => $types[array_rand($types)],
                    'is_published' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($inserts, 100) as $chunk) {
            DB::table('county_institutions')->insert($chunk);
        }
        $this->command->info('Seeded ' . count($inserts) . ' institutions');
    }

    private function seedCountyFarms(): void
    {
        $counties = County::all();
        $types = ['Dairy Farm', 'Crop Farm', 'Mixed Farm', 'Fish Farm', 'Poultry Farm', 'Tea Estate', 'Coffee Farm'];
        $inserts = [];
        foreach ($counties as $county) {
            $num = rand(1, 3);
            for ($i = 0; $i < $num; $i++) {
                $inserts[] = [
                    'county_id' => $county->id,
                    'name' => $county->name . ' ' . $types[array_rand($types)],
                    'type' => $types[array_rand($types)],
                    'size_acres' => rand(1, 500),
                    'is_published' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($inserts, 100) as $chunk) {
            DB::table('county_farms')->insert($chunk);
        }
        $this->command->info('Seeded ' . count($inserts) . ' farms');
    }

    private function seedCountyTransport(): void
    {
        $counties = County::all();
        $inserts = [];
        foreach ($counties as $county) {
            $countyTransports = [
                ['name' => $county->name . ' Bus Terminal', 'type' => 'bus_station'],
                ['name' => $county->name . ' Taxi Hub', 'type' => 'taxi_hub'],
                ['name' => $county->name . ' Airstrip', 'type' => 'airstrip'],
            ];
            foreach ($countyTransports as $t) {
                $inserts[] = [
                    'county_id' => $county->id,
                    'name' => $t['name'],
                    'type' => $t['type'],
                    'is_published' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('county_transport')->insert($inserts);
        $this->command->info('Seeded ' . count($inserts) . ' transport entries');
    }

    private function seedCountyHealth(): void
    {
        $counties = County::all();
        $types = ['Hospital', 'Health Centre', 'Dispensary', 'Clinic'];
        $levels = ['Level 2', 'Level 3', 'Level 4', 'Level 5', 'Level 6'];
        $inserts = [];
        foreach ($counties as $county) {
            $num = rand(2, 4);
            for ($i = 0; $i < $num; $i++) {
                $inserts[] = [
                    'county_id' => $county->id,
                    'name' => $county->name . ' ' . $types[array_rand($types)],
                    'type' => $types[array_rand($types)],
                    'level' => $levels[array_rand($levels)],
                    'is_published' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($inserts, 100) as $chunk) {
            DB::table('county_health_facilities')->insert($chunk);
        }
        $this->command->info('Seeded ' . count($inserts) . ' health facilities');
    }

    private function seedCountyCulture(): void
    {
        $counties = County::all();
        $types = ['Heritage Site', 'Monument', 'Art Gallery', 'Cultural Centre', 'Festival Ground', 'Historical Building'];
        $inserts = [];
        foreach ($counties as $county) {
            $num = rand(1, 3);
            for ($i = 0; $i < $num; $i++) {
                $inserts[] = [
                    'county_id' => $county->id,
                    'name' => $county->name . ' ' . $types[array_rand($types)],
                    'type' => $types[array_rand($types)],
                    'is_published' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($inserts, 100) as $chunk) {
            DB::table('county_culture_sites')->insert($chunk);
        }
        $this->command->info('Seeded ' . count($inserts) . ' culture sites');
    }

    private function seedAdvertisements(): void
    {
        $counties = County::all();
        $inserts = [];
        foreach ($counties->random(5) as $county) {
            $inserts[] = [
                'name' => $county->name . ' Promotional',
                'type' => 'banner',
                'placement' => 'county_page',
                'target_url' => '/counties/' . $county->slug,
                'is_active' => true,
                'budget' => rand(10000, 100000),
                'starts_at' => now(),
                'ends_at' => now()->addMonths(3),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('advertisements')->insert($inserts);
        $this->command->info('Seeded ' . count($inserts) . ' advertisements');
    }
}
