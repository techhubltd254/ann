<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OperationsSeeder extends Seeder
{
    public function run(): void
    {
        // ── Advertising ──
        DB::table('ad_campaigns')->insert([
            ['name' => 'Mombasa Tourism Drive', 'type' => 'display', 'budget' => 500000, 'start_date' => now(), 'end_date' => now()->addMonths(3), 'status' => 'active', 'targeting' => '{"regions":["coast"],"demographics":["tourists"]}', 'is_active' => true],
            ['name' => 'County Premium Launch', 'type' => 'banner', 'budget' => 200000, 'start_date' => now(), 'end_date' => now()->addMonths(2), 'status' => 'active', 'targeting' => '{"counties":["mombasa","kilifi","nairobi"]}', 'is_active' => true],
        ]);
        DB::table('ad_creatives')->insert([
            ['campaign_id' => 1, 'headline' => 'Discover Mombasa', 'body' => 'Book your coastal experience today', 'cta' => 'Explore', 'image_url' => null, 'is_active' => true],
            ['campaign_id' => 2, 'headline' => 'Promote Your County', 'body' => 'Get 50 booths for KES 50K/mo', 'cta' => 'Learn More', 'image_url' => null, 'is_active' => true],
        ]);

        // ── SEO ──
        DB::table('seo_metadata')->insert([
            ['pageable_type' => 'county', 'pageable_id' => 1, 'meta_title' => 'Mombasa County — KICC Exhibition Platform', 'meta_description' => 'Explore Mombasa County\'s tourism, trade and investment opportunities on Kenya\'s premier exhibition platform.', 'keywords' => 'Mombasa, tourism, coast, Kenya, exhibition'],
            ['pageable_type' => 'county', 'pageable_id' => 3, 'meta_title' => 'Kilifi County — Discover the Coast', 'meta_description' => 'Kilifi County\'s beaches, resorts and agricultural products featured on KICC.', 'keywords' => 'Kilifi, beach, cashew, Watamu, Kenya'],
        ]);
        DB::table('content_pages')->insert([
            ['slug' => 'about', 'title' => 'About KICC Platform', 'body' => '<p>The KICC Digital Economy Platform connects businesses across all 47 counties of Kenya. From exhibition bookings to marketplace sales, we power Kenya\'s trade.</p>', 'is_published' => true],
            ['slug' => 'terms', 'title' => 'Terms & Conditions', 'body' => '<p>Standard terms for using the KICC Exhibition and Marketplace platform.</p>', 'is_published' => true],
        ]);

        // ── Logistics ──
        DB::table('courier_partners')->insert([
            ['name' => 'Aramex Kenya', 'code' => 'ARX', 'website' => 'aramex.com', 'is_active' => true],
            ['name' => 'Wells Fargo', 'code' => 'WFC', 'website' => 'wellsfargo.co.ke', 'is_active' => true],
            ['name' => 'Kenya Postal Corporation', 'code' => 'KPC', 'website' => 'posta.co.ke', 'is_active' => true],
        ]);
        DB::table('shipping_zones')->insert([
            ['name' => 'Nairobi Metro', 'code' => 'NBO', 'countries' => '["Kenya"]', 'regions' => '["Nairobi"]', 'is_active' => true],
            ['name' => 'Coast Region', 'code' => 'CST', 'countries' => '["Kenya"]', 'regions' => '["Mombasa","Kilifi","Kwale"]', 'is_active' => true],
            ['name' => 'International', 'code' => 'INT', 'countries' => '["*"]', 'regions' => '[]', 'is_active' => true],
        ]);
        DB::table('shipping_rates')->insert([
            ['zone_id' => 1, 'min_weight' => 0, 'max_weight' => 1, 'rate' => 250],
            ['zone_id' => 1, 'min_weight' => 1, 'max_weight' => 5, 'rate' => 500],
            ['zone_id' => 2, 'min_weight' => 0, 'max_weight' => 1, 'rate' => 350],
            ['zone_id' => 2, 'min_weight' => 1, 'max_weight' => 5, 'rate' => 700],
            ['zone_id' => 3, 'min_weight' => 0, 'max_weight' => 1, 'rate' => 2500],
            ['zone_id' => 3, 'min_weight' => 1, 'max_weight' => 5, 'rate' => 5000],
        ]);

        $this->command->info('Seeded advertising, SEO, and logistics data');
    }
}