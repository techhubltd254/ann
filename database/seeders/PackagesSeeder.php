<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

/**
 * PackagesSeeder — aligns subscription plans with the BLUEPRINT.md
 * Payments & Subscriptions deep-dive:
 *
 *   Free            0/mo        1 booth listing, basic profile
 *   County Premium  50,000/mo   20 booths, analytics, SEO boost, priority support
 *   Exhibitor Pro   5,000/mo    5 booths, analytics, livestream, multi-event
 *   Enterprise      50,000/mo   Unlimited booths, API, white-label, dedicated support
 */
class PackagesSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free', 'slug' => 'free', 'price' => 0,
                'description' => 'Get listed on the national marketplace at no cost.',
                'max_booths' => 1, 'max_exhibitions' => 1, 'max_media_files' => 5,
                'has_livestream' => false, 'has_analytics' => false, 'has_priority_support' => false,
                'features' => ['1 booth listing', 'Basic exhibitor profile', 'Marketplace visibility', 'Escrow-protected sales'],
                'sort_order' => 1,
            ],
            [
                'name' => 'Exhibitor Pro', 'slug' => 'exhibitor-pro', 'price' => 5000,
                'description' => 'For growing businesses — more booths, analytics and livestream selling.',
                'max_booths' => 5, 'max_exhibitions' => 5, 'max_media_files' => 50,
                'has_livestream' => true, 'has_analytics' => true, 'has_priority_support' => false,
                'features' => ['5 booth listings', 'Sales analytics dashboard', 'Livestream selling', 'Multi-event presence', 'Own storefront website'],
                'sort_order' => 2,
            ],
            [
                'name' => 'County Premium', 'slug' => 'county-premium', 'price' => 50000,
                'description' => 'For county governments — promote up to 50 local businesses under your pavilion.',
                'max_booths' => 20, 'max_exhibitions' => 20, 'max_media_files' => 200,
                'has_livestream' => true, 'has_analytics' => true, 'has_priority_support' => true,
                'features' => ['20 booth listings', 'County analytics & SEO boost', 'Bulk business slots', 'County revenue share (70%)', 'Priority support'],
                'sort_order' => 3,
            ],
            [
                'name' => 'Enterprise', 'slug' => 'enterprise', 'price' => 50000,
                'description' => 'For corporates — unlimited presence, API access and white-label.',
                'max_booths' => 999, 'max_exhibitions' => 999, 'max_media_files' => 1000,
                'has_livestream' => true, 'has_analytics' => true, 'has_priority_support' => true,
                'features' => ['Unlimited booths', 'API access', 'White-label storefront', 'Dedicated account manager', 'Multi-county presence'],
                'sort_order' => 4,
            ],
        ];

        foreach ($plans as $p) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $p['slug']],
                $p + ['currency' => 'KES', 'billing_interval' => 'monthly', 'is_active' => true]
            );
        }
        $this->command->info('Packages: ' . SubscriptionPlan::where('is_active', true)->count() . ' blueprint-aligned plans live.');
    }
}
