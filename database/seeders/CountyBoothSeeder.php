<?php
namespace Database\Seeders;

use App\Models\Booth;
use App\Models\BoothAuthorization;
use App\Models\County;
use App\Models\Exhibition;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CountyBoothSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'techhubltd254@gmail.com')->first();
        if (!$admin) return;

        $exhibition = Exhibition::firstOrCreate(
            ['slug' => 'county-live-exhibition'],
            ['name' => 'County Live Exhibition', 'description' => 'Live streaming booths for all 47 Kenyan counties', 'status' => 'active', 'is_featured' => true, 'start_date' => now(), 'end_date' => now()->addYear()]
        );

        $counties = County::all();
        $murangaBooth = Booth::where('slug', 'affordable-housing')->first();

        $pillarSlugs = ['affordable-housing', 'trade-commerce', 'healthcare', 'security-community', 'infrastructure-roads'];
        $pillarNames = ['Affordable Housing', 'Trade & Commerce', 'Healthcare', 'Security & Community', 'Infrastructure & Roads'];

        $created = 0;
        foreach ($counties as $county) {
            foreach ($pillarSlugs as $i => $slug) {
                $boothSlug = $slug . '-' . $county->slug;
                if (Booth::where('slug', $boothSlug)->exists()) continue;

                $booth = Booth::create([
                    'name' => $county->name . ' - ' . $pillarNames[$i],
                    'slug' => $boothSlug,
                    'exhibition_id' => $exhibition->id,
                    'user_id' => $admin->id,
                    'county_id' => $county->id,
                    'description' => "{$pillarNames[$i]} showcase for {$county->name}. Part of the National Government Exhibition.",
                    'status' => 'active',
                    'stream_status' => 'offline',
                    'booth_number' => strtoupper(substr($county->slug, 0, 3)) . '-' . ($i + 1),
                    'price' => 0,
                    'max_quantity' => 1,
                    'booked_quantity' => 0,
                    'contact_email' => 'techhubltd254@gmail.com',
                    'contact_phone' => '+254728066733',
                    'gps_lat' => $county->latitude ?? null,
                    'gps_lng' => $county->longitude ?? null,
                    'physical_address' => $county->name . ' County, Kenya',
                    'meta' => json_encode(['county_slug' => $county->slug, 'pillar' => $pillarNames[$i]]),
                ]);

                BoothAuthorization::create([
                    'booth_id' => $booth->id,
                    'user_id' => $admin->id,
                    'status' => 'AUTHORIZED',
                    'authorized_at' => now(),
                ]);

                $created++;
            }
        }

        $this->command->info("Created {$created} county booths across {$counties->count()} counties.");
    }
}