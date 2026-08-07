<?php

namespace Database\Seeders;

use App\Models\Booth;
use App\Models\County;
use App\Models\Exhibition;
use App\Models\TicketType;
use App\Models\Venue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ExhibitionSeeder extends Seeder
{
    public function run(): void
    {
        $venues = Venue::all();
        if ($venues->isEmpty()) {
            $this->command->warn('No venues found — run venue seeder first.');
            return;
        }

        $counties = County::all();
        $exhibitions = [
            [
                'name' => 'Kenya International Trade Fair 2026',
                'tagline' => 'East Africa\'s biggest multi-sector trade exhibition',
                'description' => 'The flagship national exhibition bringing together all 47 counties, 500+ exhibitors, and 100,000+ visitors. Sectors include agriculture, manufacturing, technology, tourism, and creative economy.',
                'start_date' => now()->addDays(30),
                'end_date' => now()->addDays(37),
                'is_featured' => true,
                'status' => 'published',
            ],
            [
                'name' => 'Magical Kenya Travel Expo',
                'tagline' => 'Tourism, hospitality, and travel trade show',
                'description' => 'Kenya\'s premier tourism expo connecting hotels, tour operators, airlines, and county tourism boards with international buyers and travel agents.',
                'start_date' => now()->addDays(60),
                'end_date' => now()->addDays(63),
                'is_featured' => true,
                'status' => 'published',
            ],
            [
                'name' => 'Nairobi Tech & Innovation Summit',
                'tagline' => 'Silicon Savannah showcase — startups, AI, fintech',
                'description' => 'A three-day summit for Kenya\'s tech ecosystem: startup demos, investor panels, AI workshops, and the county digital economy awards.',
                'start_date' => now()->addDays(90),
                'end_date' => now()->addDays(92),
                'is_featured' => true,
                'status' => 'published',
            ],
            [
                'name' => 'Kenya Agri-Food Expo',
                'tagline' => 'Farm to fork — the future of Kenyan agriculture',
                'description' => 'County producers, agro-processors, and exporters showcase Kenya\'s agricultural value chain with live demos, tastings, and B2B matchmaking.',
                'start_date' => now()->addDays(120),
                'end_date' => now()->addDays(123),
                'is_featured' => false,
                'status' => 'published',
            ],
            [
                'name' => 'East Africa Manufacturing Week',
                'tagline' => 'Industry 4.0 for East African manufacturers',
                'description' => 'Machinery, automation, and industrial innovation expo for manufacturers across the EAC region.',
                'start_date' => now()->addDays(150),
                'end_date' => now()->addDays(154),
                'is_featured' => false,
                'status' => 'published',
            ],
            [
                'name' => 'Kenya Creative Economy Festival',
                'tagline' => 'Music, film, fashion, and digital arts',
                'description' => 'Celebrating Kenya\'s creative industries with live performances, film screenings, fashion shows, and creator economy workshops.',
                'start_date' => now()->addDays(180),
                'end_date' => now()->addDays(183),
                'is_featured' => false,
                'status' => 'published',
            ],
        ];

        foreach ($exhibitions as $i => $data) {
            $venue = $venues[$i % $venues->count()];
            $county = $counties->isNotEmpty() ? $counties->random() : null;

            $exhibition = Exhibition::firstOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'name' => $data['name'],
                    'tagline' => $data['tagline'],
                    'description' => $data['description'],
                    'venue_id' => $venue->id,
                    'county_id' => $county?->id,
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'open_time' => '09:00',
                    'close_time' => '18:00',
                    'is_featured' => $data['is_featured'],
                    'status' => $data['status'],
                ]
            );

            // Booths
            $categories = ['standard', 'premium', 'corner', 'island'];
            for ($b = 1; $b <= 12; $b++) {
                Booth::firstOrCreate(
                    ['exhibition_id' => $exhibition->id, 'booth_number' => 'B' . str_pad($b, 3, '0', STR_PAD_LEFT)],
                    [
                        'name' => 'Booth ' . $b,
                        'size' => ['3x3', '3x6', '6x6'][$b % 3],
                        'category' => $categories[$b % 4],
                        'price' => [50000, 85000, 120000, 200000][$b % 4],
                        'max_quantity' => 1,
                        'booked_quantity' => 0,
                        'status' => 'available',
                    ]
                );
            }

            // Ticket types
            $tickets = [
                ['name' => 'General Admission', 'price' => 500, 'quantity' => 5000],
                ['name' => 'Business Pass', 'price' => 2500, 'quantity' => 1000],
                ['name' => 'VIP All-Access', 'price' => 7500, 'quantity' => 200],
            ];
            foreach ($tickets as $t) {
                TicketType::firstOrCreate(
                    ['exhibition_id' => $exhibition->id, 'slug' => Str::slug($exhibition->slug . '-' . $t['name'])],
                    [
                        'name' => $t['name'],
                        'price' => $t['price'],
                        'quantity' => $t['quantity'],
                        'sold' => 0,
                        'max_per_order' => 10,
                        'is_active' => true,
                    ]
                );
            }

            $this->command->info("Seeded: {$exhibition->name}");
        }
    }
}