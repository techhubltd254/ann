<?php

namespace Database\Seeders;

use App\Models\County;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MurangaTourismServicesSeeder extends Seeder
{
    public function run(): void
    {
        $county = County::where('slug', 'muranga')->first();
        if (!$county) { $this->command->warn('Muranga county not found'); return; }

        $cat = ProductCategory::firstOrCreate(
            ['slug' => 'tourism-services'],
            ['name' => 'Tourism & Experiences', 'icon' => 'tourism', 'sort_order' => 6, 'is_active' => true]
        );

        $services = [
            // 1. MURANGA REFERRAL HOSPITAL TOUR
            ['name' => 'Muranga Referral Hospital — Guided Tour', 'desc' => 'A guided tour of the Muranga County Referral Hospital facility. Visit the ICU Centre, casualty wing, outpatient clinics, and maternity block. Learn about the hospital\'s history and services. Includes a meet-and-greet with the medical superintendent.',
             'slug' => 'muranga-hospital-guided-tour', 'unit' => 'person', 'featured' => true,
             'variants' => [
                ['name' => 'Individual Tour (1 pax)', 'price' => 1500, 'stock' => 20],
                ['name' => 'Group Tour (2-5 pax)', 'price' => 800, 'stock' => 10],
                ['name' => 'Student/Research Visit', 'price' => 500, 'stock' => 30],
             ]],
            // 2. MURANGA UNIVERSITY CAMPUS TOUR
            ['name' => 'Murang\'a University — Campus Experience', 'desc' => 'Explore the Murang\'a University campus with a student ambassador. Visit the main gate, administration block, lecture halls, library, student centre, and sports grounds. Capture the iconic 4D holographic moment at the university gate.',
             'slug' => 'muranga-university-campus-tour', 'unit' => 'person', 'featured' => true,
             'variants' => [
                ['name' => 'Standard Campus Tour (1 pax)', 'price' => 1000, 'stock' => 50],
                ['name' => 'Group Tour (5-10 pax)', 'price' => 600, 'stock' => 15],
                ['name' => 'VIP Tour with VC Meeting', 'price' => 5000, 'stock' => 5],
             ]],
            // 3. TWIN FALLS WATERFALL HIKE
            ['name' => 'Twin Falls — 3km Waterfall Hike & Picnic', 'desc' => 'A guided 3km hike through indigenous forest and tea plantations to the majestic Twin Falls in Murang\'a. Enjoy the cascading waterfalls, natural plunge pool, and a picnic lunch with views of the gorge. Professional guide included.',
             'slug' => 'twin-falls-waterfall-hike', 'unit' => 'person', 'featured' => true,
             'variants' => [
                ['name' => 'Half-Day Hike (1 pax)', 'price' => 2500, 'stock' => 25],
                ['name' => 'Half-Day Hike (Group of 4)', 'price' => 2000, 'stock' => 10],
                ['name' => 'Full-Day Experience + Lunch', 'price' => 4500, 'stock' => 15],
             ]],
            // 4. MURANGA GORGES & RIVER TREK
            ['name' => 'Murang\'a Gorges — River Trek & Photography', 'desc' => 'Explore the stunning Murang\'a Gorges with a professional trekking guide. Walk along the riverbed, through rocky gorges, and capture breathtaking 4D splat photography at designated viewpoints. Suitable for adventure enthusiasts.',
             'slug' => 'muranga-gorges-river-trek', 'unit' => 'person', 'featured' => true,
             'variants' => [
                ['name' => 'Morning Trek (1 pax)', 'price' => 3000, 'stock' => 20],
                ['name' => 'Morning Trek (Group of 3)', 'price' => 2200, 'stock' => 8],
                ['name' => 'Photography Special (with guide)', 'price' => 5000, 'stock' => 10],
             ]],
            // 5. TEA HIGHLANDS TOUR
            ['name' => 'Murang\'a Tea Highlands — Plantation Tour & Tasting', 'desc' => 'Visit the lush tea plantations of Murang\'a highlands. Tour the tea factory, walk through the rolling green tea fields, and enjoy a premium tea tasting session. Includes transport from the hotel.',
             'slug' => 'muranga-tea-highlands-tour', 'unit' => 'person', 'featured' => true,
             'variants' => [
                ['name' => 'Half-Day Tea Tour (1 pax)', 'price' => 2000, 'stock' => 30],
                ['name' => 'Half-Day Tea Tour (Couple)', 'price' => 3500, 'stock' => 15],
                ['name' => 'Full-Day + Lunch + Tasting', 'price' => 5000, 'stock' => 10],
             ]],
            // 6. SAGANA RAFTING EXPERIENCE
            ['name' => 'Sagana River — White Water Rafting', 'desc' => 'Experience the thrill of white water rafting on the Sagana River. Grade 2-3 rapids through beautiful gorges, with professional safety guides, equipment, and lunch by the river. Unforgettable adventure in Murang\'a.',
             'slug' => 'sagana-river-rafting', 'unit' => 'person', 'featured' => true,
             'variants' => [
                ['name' => 'Half-Day Rafting (1 pax)', 'price' => 3500, 'stock' => 20],
                ['name' => 'Half-Day Rafting (Group of 4)', 'price' => 2800, 'stock' => 8],
                ['name' => 'Full-Day + BBQ Riverside', 'price' => 6000, 'stock' => 12],
             ]],
            // 7. FOREST CANOPY WALK
            ['name' => 'Aberdare Forest — Canopy Walk & Nature Trail', 'desc' => 'A guided nature walk through the Aberdare Forest canopy. Spot indigenous birds, monkeys, and rare plant species. The trail includes a canopy walkway with panoramic views of the forest and Murang\'a highlands.',
             'slug' => 'aberdare-forest-canopy-walk', 'unit' => 'person', 'featured' => true,
             'variants' => [
                ['name' => 'Nature Walk (1 pax)', 'price' => 1800, 'stock' => 25],
                ['name' => 'Nature Walk (Group of 3)', 'price' => 1400, 'stock' => 10],
                ['name' => 'Private Guided Tour', 'price' => 4000, 'stock' => 8],
             ]],
            // 8. HOTEL & RESORT STAY
            ['name' => 'Murang\'a Resort — Overnight Stay & Spa', 'desc' => 'Relax at a premier Murang\'a resort with scenic views of the tea highlands. Includes accommodation, breakfast, access to the spa, swimming pool, and evening cultural entertainment. The perfect end to your Murang\'a adventure.',
             'slug' => 'muranga-resort-stay', 'unit' => 'night', 'featured' => true,
             'variants' => [
                ['name' => 'Standard Room (1 night)', 'price' => 6500, 'stock' => 15],
                ['name' => 'Deluxe Room (1 night)', 'price' => 9500, 'stock' => 10],
                ['name' => 'Executive Suite (1 night)', 'price' => 15000, 'stock' => 5],
                ['name' => 'Weekend Package (2 nights)', 'price' => 18000, 'stock' => 8],
             ]],
            // 9. VENUE HALL BOOKING
            ['name' => 'Murang\'a Venue — Event Hall & Conference Booking', 'desc' => 'Book a modern event venue in Murang\'a for conferences, weddings, or corporate events. Fully equipped hall with sound system, catering, and 4D holographic display capabilities.',
             'slug' => 'muranga-venue-booking', 'unit' => 'day', 'featured' => false,
             'variants' => [
                ['name' => 'Conference Hall (Full Day)', 'price' => 25000, 'stock' => 5],
                ['name' => 'Banquet Hall (Full Day)', 'price' => 35000, 'stock' => 3],
                ['name' => 'Outdoor Garden (Full Day)', 'price' => 20000, 'stock' => 4],
             ]],
            // 10. AIRPORT TRANSFER
            ['name' => 'Murang\'a — Airport Transfer (NBO to Murang\'a)', 'desc' => 'Comfortable private transfer from Jomo Kenyatta International Airport (NBO) to your Murang\'a hotel or destination. Air-conditioned vehicle with driver. Approx 1.5 hour drive.',
             'slug' => 'muranga-airport-transfer', 'unit' => 'trip', 'featured' => false,
             'variants' => [
                ['name' => 'Private Sedan (1-3 pax)', 'price' => 5500, 'stock' => 20],
                ['name' => 'Private SUV (1-6 pax)', 'price' => 8000, 'stock' => 15],
                ['name' => 'Minibus (1-12 pax)', 'price' => 12000, 'stock' => 8],
             ]],
        ];

        foreach ($services as $i => $data) {
            $product = Product::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'county_id' => $county->id,
                    'category_id' => $cat->id,
                    'name' => $data['name'],
                    'description' => $data['desc'],
                    'short_description' => Str::limit($data['desc'], 150),
                    'sku' => 'MU-' . strtoupper(Str::random(6)),
                    'unit' => $data['unit'],
                    'status' => 'active',
                    'is_featured' => $data['featured'] ?? false,
                ]
            );

            foreach ($data['variants'] as $vi => $v) {
                $product->variants()->updateOrCreate(
                    ['name' => $v['name']],
                    [
                        'sku' => $product->sku . '-V' . ($vi + 1),
                        'price' => $v['price'],
                        'stock' => $v['stock'] ?? rand(10, 50),
                        'is_active' => true,
                        'sort_order' => $vi,
                        'image_url' => media("counties/muranga/tourism.jpeg"),
                    ]
                );
            }

            $product->images()->firstOrCreate(
                ['url' => media("counties/muranga/tourism.jpeg")],
                ['alt_text' => $product->name, 'sort_order' => 0, 'is_primary' => true]
            );
        }

        $this->command->info('Created ' . count($services) . ' Muranga tourism services with pricing.');
    }
}