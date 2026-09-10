<?php
namespace Database\Seeders;

use App\Models\Booth;
use App\Models\BoothAuthorization;
use App\Models\LiveStream;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EldoretBoothSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'techhubltd254@gmail.com')->first();
        if (!$admin) $admin = User::first();

        // KICC Classic Booth at Eldoret
        $booth = Booth::firstOrCreate(
            ['slug' => 'kicc-classic-eldoret-2026'],
            [
                'name' => 'KICC Classic Booth — Eldoret (Kenny G)',
                'description' => 'KICC Classic Exhibition Booth at Eldoret. Showcasing 4D immersive imaging, live county exhibition feeds, and interactive product marketplace. October 2026.',
                'user_id' => $admin->id,
                'exhibition_id' => 0,
                'status' => 'active',
                'stream_status' => 'offline',
                'booth_number' => 'KICC-CLASSIC-01',
                'price' => 0,
                'max_quantity' => 999,
                'booked_quantity' => 0,
                'contact_email' => 'techhubltd254@gmail.com',
                'contact_phone' => '+254728066733',
                'gps_lat' => 0.5143,
                'gps_lng' => 35.2698,
                'physical_address' => 'Eldoret, Kenya (Kenny G Venue)',
                'meta' => json_encode([
                    'event' => 'KICC Classic Booth',
                    'location' => 'Eldoret',
                    'date' => 'October 2026',
                    'artist' => 'Kenny G',
                    'screens' => [
                        ['venue' => 'KICC Nairobi', 'type' => 'all_screens', 'protocol' => 'Cloudflare Stream RTMP -> HLS'],
                        ['venue' => 'Elite Sounds', 'type' => 'advertisement', 'protocol' => 'Edge cached stream'],
                        ['venue' => 'Digital Mara', 'type' => 'full_block', 'protocol' => 'Multi-stream WebRTC/LL-HLS'],
                        ['venue' => 'President Town Hall', 'type' => 'town_hall', 'protocol' => 'Low-latency WebRTC'],
                    ],
                    'features' => ['4D immersive', 'product marketplace', 'live Q&A', 'drone aerials', 'meeting booking'],
                ]),
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

        $this->command->info("Eldoret Classic Booth ready: {$booth->name}");
    }
}