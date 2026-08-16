<?php

namespace Database\Seeders;

use App\Models\County;
use App\Models\County\FinancialConfig;
use App\Models\Subscription\CountyBulkSlotAllocation;
use App\Models\Subscription\CountySubscriptionPlan;
use Illuminate\Database\Seeder;

class CountySubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        // Blueprint v2 subscription tiers (sold to counties/enterprises)
        $tiers = [
            ['name' => 'County Basic',     'price' => 10000,  'max_booths' => 10,  'max_products' => 20,  'has_analytics' => false, 'has_livestream' => false, 'has_priority_support' => false, 'description' => '10 bulk slots, basic dashboard'],
            ['name' => 'County Premium',   'price' => 50000,  'max_booths' => 50,  'max_products' => 100, 'has_analytics' => true,  'has_livestream' => true,  'has_priority_support' => false, 'description' => '50 bulk slots, sub-portal, custom plans, analytics'],
            ['name' => 'County Enterprise','price' => 200000, 'max_booths' => 200, 'max_products' => 500, 'has_analytics' => true,  'has_livestream' => true,  'has_priority_support' => true,  'description' => 'Unlimited slots, custom domain, API, dedicated support'],
            ['name' => 'Corporate',         'price' => 500000, 'max_booths' => -1,  'max_products' => -1,  'has_analytics' => true,  'has_livestream' => true,  'has_priority_support' => true,  'description' => 'White-label, multi-county, AI pipeline access'],
        ];

        foreach ($tiers as $t) {
            CountySubscriptionPlan::updateOrCreate(['name' => $t['name']], $t + ['is_active' => true, 'billing_interval' => 'monthly']);
        }

        // Assign Mombasa and Kilifi as demo subscribers with County Premium
        foreach (['mombasa', 'kilifi'] as $slug) {
            $county = County::where('slug', $slug)->first();
            if (!$county) continue;

            // Financial config
            FinancialConfig::updateOrCreate(
                ['county_id' => $county->id],
                [
                    'revenue_share_pct' => 70,
                    'settlement_period' => 'monthly',
                    'mpesa_paybill' => '123456',
                    'wallet_balance' => 0,
                    'lifetime_earnings' => 0,
                    'total_payouts' => 0,
                ]
            );

            // Bulk slot allocation from County Premium
            $plan = CountySubscriptionPlan::where('slug', 'county-premium')->first();
            CountyBulkSlotAllocation::updateOrCreate(
                ['county_id' => $county->id, 'slot_type' => 'exhibition_booth'],
                [
                    'total_slots' => $plan->max_booths,
                    'used_slots' => rand(0, 5),
                    'price_per_slot' => $plan->price / $plan->max_booths,
                    'purchase_date' => now()->subMonth(),
                    'expiry_date' => now()->addYear(),
                    'status' => 'active',
                ]
            );
        }

        $this->command->info('Seeded ' . CountySubscriptionPlan::count() . ' subscription tiers + demo configs for Mombasa & Kilifi');
    }
}