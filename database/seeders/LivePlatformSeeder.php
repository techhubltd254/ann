<?php
namespace Database\Seeders;

use App\Models\Booth;
use App\Models\BoothAuthorization;
use App\Models\Exhibition;
use App\Models\County;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LivePlatformSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'techhubltd254@gmail.com')->first();
        if (!$admin) {
            $admin = User::factory()->create([
                'name' => 'Lolen Mwangi',
                'email' => 'techhubltd254@gmail.com',
                'password' => bcrypt('password'),
            ]);
            $admin->assignRole('kicc_admin');
        }

        $exhibition = Exhibition::firstOrCreate(
            ['slug' => 'national-government-exhibition'],
            [
                'name' => 'National Government Exhibition',
                'description' => 'Showcasing Kenya\'s development pillars across counties',
                'status' => 'active',
                'is_featured' => true,
                'start_date' => now(),
                'end_date' => now()->addYear(),
            ]
        );

        $muranga = County::where('slug', 'muranga')->first();
        $mombasa = County::where('slug', 'mombasa')->first();

        $pillars = [
            ['name' => 'Affordable Housing', 'county_id' => $muranga?->id],
            ['name' => 'Trade & Commerce', 'county_id' => $muranga?->id],
            ['name' => 'Healthcare', 'county_id' => $mombasa?->id],
            ['name' => 'Security & Community', 'county_id' => $muranga?->id],
            ['name' => 'Infrastructure & Roads', 'county_id' => $mombasa?->id],
        ];

        foreach ($pillars as $pillar) {
            $slug = Str::slug($pillar['name']) . '-' . Str::random(4);
            $booth = Booth::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $pillar['name'],
                    'description' => "National Government Exhibition — {$pillar['name']} pillar. Showcasing Kenya's progress.",
                    'exhibition_id' => $exhibition->id,
                    'county_id' => $pillar['county_id'],
                    'user_id' => $admin->id,
                    'status' => 'active',
                    'stream_status' => 'offline',
                    'contact_email' => 'techhubltd254@gmail.com',
                    'contact_phone' => '+254728066733',
                ]
            );

            BoothAuthorization::firstOrCreate(
                ['booth_id' => $booth->id],
                [
                    'user_id' => $admin->id,
                    'status' => 'AUTHORIZED',
                    'authorized_at' => now(),
                ]
            );

            $this->command->info("Created booth: {$pillar['name']}");
        }

        $this->command->info('Live Platform seeder complete: 5 National Exhibition booths created.');
    }
}