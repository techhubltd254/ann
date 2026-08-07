<?php

namespace Database\Seeders;

use App\Models\County;
use App\Models\CountyProduct;
use App\Models\Logistics\CourierPartner;
use App\Models\Logistics\ShippingRate;
use App\Models\Logistics\ShippingZone;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductCategory;
use App\Models\Marketplace\Supplier;
use App\Models\Payment\Gateway;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * TradeSeeder — the commerce & trade bridge.
 *
 * Converts the 283 real county trade products (county_products, all 47 counties)
 * into the live marketplace (products + variants + images), creates one verified
 * supplier per county, and seeds the money/fulfilment rails (payment gateways,
 * shipping zones, shipping rates, courier partners).
 */
class TradeSeeder extends Seeder
{
    /** county_products.category → product_categories.slug */
    private const CATEGORY_MAP = [
        'agriculture' => 'agriculture-produce',
        'fresh'       => 'food-beverage',
        'processed'   => 'food-beverage',
        'handicraft'  => 'crafts-artisan',
        'textile'     => 'textiles-apparel',
        'decor'       => 'home-decor',
    ];

    public function run(): void
    {
        $this->seedGuestBuyer();
        $this->seedSuppliers();
        $this->bridgeCountyProducts();
        $this->seedPaymentGateways();
        $this->seedShipping();
    }

    /**
     * Platform guest-buyer account — used as escrow buyer_id for guest checkouts.
     */
    private function seedGuestBuyer(): void
    {
        \App\Models\User::updateOrCreate(
            ['email' => 'guest@kicc.go.ke'],
            [
                'name' => 'Guest Buyer',
                'password' => bcrypt(Str::random(24)),
                'account_type' => 'guest',
                'status' => 'active',
            ]
        );
    }

    /**
     * One verified trade-board supplier per county, each backed by a real
     * seller login (trade@{slug}.kicc.go.ke) so escrow payouts reference a
     * genuine user account.
     */
    private function seedSuppliers(): void
    {
        foreach (County::orderBy('name')->get() as $county) {
            $user = \App\Models\User::updateOrCreate(
                ['email' => "trade@{$county->slug}.kicc.go.ke"],
                [
                    'name' => "{$county->name} Trade Board",
                    'password' => bcrypt('TradeBoard@2026'),
                    'account_type' => 'county',
                    'phone' => '+254700000' . str_pad($county->id, 3, '0', STR_PAD_LEFT),
                    'county_id' => $county->id,
                    'email_verified_at' => now(),
                    'phone_verified_at' => now(),
                    'status' => 'active',
                ]
            );

            Supplier::updateOrCreate(
                ['county_id' => $county->id, 'business_name' => "{$county->name} County Trade Board"],
                [
                    'user_id' => $user->id,
                    'business_registration' => 'REG-' . strtoupper($county->slug),
                    'contact_phone' => $user->phone,
                    'contact_email' => $user->email,
                    'city' => $county->name,
                    'verification_status' => 'verified',
                    'verified_at' => now(),
                    'commission_rate' => 10.00,
                    'payment_terms' => 'weekly',
                    'is_active' => true,
                ]
            );
        }
        $this->command->info('Suppliers: ' . Supplier::count() . ' county trade boards (with seller logins).');
    }

    /**
     * Bridge county_products (283 real trade items) → marketplace products.
     */
    private function bridgeCountyProducts(): void
    {
        $categories = ProductCategory::pluck('id', 'slug');
        $sellerIds = Supplier::pluck('user_id', 'county_id');
        $created = 0;

        CountyProduct::with('county')->where('is_published', true)->chunk(50, function ($rows) use ($categories, $sellerIds, &$created) {
            foreach ($rows as $cp) {
                $county = $cp->county;
                if (!$county) continue;

                $catSlug = self::CATEGORY_MAP[$cp->category] ?? 'agriculture-produce';
                $categoryId = $categories[$catSlug] ?? $categories->first();
                if (!$categoryId) continue;

                $slug = Str::slug($county->name . ' ' . $cp->name);
                if (Product::where('slug', $slug)->exists()) continue;

                $desc = $cp->description
                    ?: "{$cp->name} — genuine {$county->name} County trade product, supplied through the {$county->name} County Trade Board under the KICC National Exhibition trade programme.";

                $product = Product::create([
                    'user_id' => $sellerIds[$county->id] ?? null,
                    'county_id' => $county->id,
                    'category_id' => $categoryId,
                    'name' => $cp->name,
                    'slug' => $slug,
                    'description' => $desc,
                    'short_description' => Str::limit($desc, 120),
                    'sku' => 'KICC-TD-' . str_pad($cp->id, 5, '0', STR_PAD_LEFT),
                    'unit' => $cp->unit ?: 'unit',
                    'status' => 'active',
                    'is_featured' => false,
                ]);

                $product->variants()->create([
                    'name' => 'Standard ' . ($cp->unit ?: 'unit'),
                    'sku' => $product->sku . '-V1',
                    'price' => max($cp->price, 50),
                    'stock' => rand(20, 200),
                    'is_active' => true,
                    'sort_order' => 0,
                    'image_url' => media("counties/{$county->slug}/products.jpeg"),
                ]);

                $product->images()->create([
                    'url' => media("counties/{$county->slug}/products.jpeg"),
                    'alt_text' => $product->name,
                    'sort_order' => 0,
                    'is_primary' => true,
                ]);

                $created++;
            }
        });

        $this->command->info("Trade bridge: {$created} county trade products now live in the marketplace (total " . Product::count() . ').');

        // Backfill: any product with a county but no seller gets its county trade board
        $backfilled = Product::whereNull('user_id')->whereNotNull('county_id')
            ->whereIn('county_id', $sellerIds->keys())
            ->update(['user_id' => \DB::raw(sprintf(
                "(SELECT user_id FROM suppliers WHERE suppliers.county_id = products.county_id AND suppliers.deleted_at IS NULL LIMIT 1)"
            ))]);
        $this->command->info("Seller backfill: {$backfilled} existing products linked to their county trade board.");
    }

    /**
     * Money rails: M-Pesa + manual fallback.
     */
    private function seedPaymentGateways(): void
    {
        Gateway::updateOrCreate(
            ['code' => 'mpesa'],
            [
                'name' => 'M-Pesa (Safaricom Daraja)',
                'provider_class' => \App\Services\MpesaService::class,
                'is_active' => true,
                'config' => [
                    'consumer_key' => env('MPESA_CONSUMER_KEY', ''),
                    'consumer_secret' => env('MPESA_CONSUMER_SECRET', ''),
                    'shortcode' => env('MPESA_SHORTCODE', '174379'),
                    'passkey' => env('MPESA_PASSKEY', ''),
                    'environment' => env('MPESA_ENV', 'sandbox'),
                ],
                'supported_currencies' => ['KES'],
                'supported_methods' => ['stk_push', 'till', 'paybill'],
                'min_amount' => 1,
                'max_amount' => 300000,
                'fee_percentage' => 1.5,
                'fee_fixed' => 0,
                'sort_order' => 1,
            ]
        );

        Gateway::updateOrCreate(
            ['code' => 'manual'],
            [
                'name' => 'Manual / Bank Transfer',
                'provider_class' => 'App\\Services\\ManualPaymentDriver',
                'is_active' => true,
                'config' => ['instructions' => 'Pay via bank transfer or M-Pesa till; confirmation within 24h.'],
                'supported_currencies' => ['KES', 'USD'],
                'supported_methods' => ['bank_transfer', 'cash'],
                'min_amount' => 0,
                'max_amount' => null,
                'fee_percentage' => 0,
                'fee_fixed' => 0,
                'sort_order' => 9,
            ]
        );

        $this->command->info('Payment gateways: ' . Gateway::count() . ' configured.');
    }

    /**
     * Fulfilment rails: shipping zones, rates, couriers.
     */
    private function seedShipping(): void
    {
        $zones = [
            ['name' => 'Nairobi Metro', 'countries' => ['KE'], 'regions' => ['Nairobi'], 'rates' => [
                ['name' => 'Standard Courier', 'rate' => 250, 'additional_item_rate' => 50, 'estimated_days_min' => 0, 'estimated_days_max' => 1],
                ['name' => 'Express (Same Day)', 'rate' => 500, 'additional_item_rate' => 100, 'estimated_days_min' => 0, 'estimated_days_max' => 0],
            ]],
            ['name' => 'Kenya Nationwide', 'countries' => ['KE'], 'regions' => [], 'rates' => [
                ['name' => 'Standard Courier', 'rate' => 400, 'additional_item_rate' => 80, 'estimated_days_min' => 1, 'estimated_days_max' => 3],
                ['name' => 'Heavy / Bulky (per kg)', 'rate' => 120, 'additional_item_rate' => 0, 'estimated_days_min' => 2, 'estimated_days_max' => 5, 'type' => 'weight'],
            ]],
            ['name' => 'East Africa', 'countries' => ['UG', 'TZ', 'RW', 'BI', 'SS', 'ET'], 'regions' => [], 'rates' => [
                ['name' => 'Regional Freight', 'rate' => 2500, 'additional_item_rate' => 500, 'estimated_days_min' => 3, 'estimated_days_max' => 7],
            ]],
            ['name' => 'International', 'countries' => ['*'], 'regions' => [], 'rates' => [
                ['name' => 'International Air Freight', 'rate' => 5500, 'additional_item_rate' => 1200, 'estimated_days_min' => 5, 'estimated_days_max' => 14],
            ]],
        ];

        foreach ($zones as $z) {
            $zone = ShippingZone::updateOrCreate(
                ['name' => $z['name']],
                ['countries' => json_encode($z['countries']), 'regions' => json_encode($z['regions']), 'is_active' => true]
            );
            foreach ($z['rates'] as $r) {
                ShippingRate::updateOrCreate(
                    ['zone_id' => $zone->id, 'name' => $r['name']],
                    [
                        'type' => $r['type'] ?? 'flat',
                        'rate' => $r['rate'],
                        'additional_item_rate' => $r['additional_item_rate'],
                        'estimated_days_min' => $r['estimated_days_min'],
                        'estimated_days_max' => $r['estimated_days_max'],
                        'is_active' => true,
                    ]
                );
            }
        }

        $couriers = [
            ['name' => 'G4S Kenya', 'slug' => 'g4s-kenya', 'tracking_url_template' => 'https://ke.g4s.com/track/{tracking}'],
            ['name' => 'Fargo Courier', 'slug' => 'fargo-courier', 'tracking_url_template' => 'https://fargocourier.co.ke/track/{tracking}'],
            ['name' => 'Sendy', 'slug' => 'sendy', 'tracking_url_template' => 'https://sendyit.com/track/{tracking}'],
            ['name' => 'Posta Kenya', 'slug' => 'posta-kenya', 'tracking_url_template' => 'https://posta.co.ke/track/{tracking}'],
        ];
        foreach ($couriers as $c) {
            CourierPartner::updateOrCreate(
                ['slug' => $c['slug']],
                $c + ['supported_countries' => json_encode(['KE']), 'is_active' => true]
            );
        }

        $this->command->info('Shipping: ' . ShippingZone::count() . ' zones, ' . ShippingRate::count() . ' rates, ' . CourierPartner::count() . ' couriers.');
    }
}
