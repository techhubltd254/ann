<?php

namespace Database\Seeders;

use App\Models\County;
use App\Models\Marketplace\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * ExhibitorSeeder — the 4-tier portal membership layer.
 *
 * 1. Ensures the `exhibitor` role exists (private exhibitors).
 * 2. Gives all 47 county trade-board users the `county_admin` role.
 * 3. Creates demo private exhibitors, each owning real products,
 *    so the private-exhibitor tier is fully demonstrable.
 */
class ExhibitorSeeder extends Seeder
{
    private const EXHIBITORS = [
        ['name' => 'Kilifi Cashew Co.',            'email' => 'sales@kilificashew.co.ke',    'county' => 'kilifi',  'product' => 'kilifi-cashew-nuts-raw',   'phone' => '+254720100001'],
        ['name' => 'Tabaka Soapstone Works',       'email' => 'orders@tabakastone.co.ke',    'county' => 'kisii',   'product' => 'kisii-soapstone-sculpture', 'phone' => '+254720100002'],
        ['name' => 'Narok Beadwork Collective',    'email' => 'hello@narokbeads.co.ke',      'county' => 'narok',   'product' => 'maasai-beaded-necklace',    'phone' => '+254720100003'],
        ['name' => 'Nyeri Highland Coffee Roasters','email' => 'sales@nyericoffee.co.ke',    'county' => 'nyeri',   'product' => 'nyeri-aa-coffee-beans',     'phone' => '+254720100004'],
    ];

    public function run(): void
    {
        // 1. Roles
        $exhibitorRole = Role::firstOrCreate(['name' => 'exhibitor', 'guard_name' => 'web']);
        $countyRole = Role::firstOrCreate(['name' => 'county_admin', 'guard_name' => 'web']);

        // 2. County trade boards → county_admin role
        $boards = User::where('email', 'like', 'trade@%.kicc.go.ke')->get();
        foreach ($boards as $b) {
            if (!$b->hasRole('county_admin')) $b->assignRole($countyRole);
        }
        $this->command->info('County trade boards with county_admin role: ' . $boards->count());

        // 3. Demo private exhibitors, each owning their flagship product
        foreach (self::EXHIBITORS as $e) {
            $county = County::where('slug', $e['county'])->first();
            if (!$county) continue;

            $user = User::updateOrCreate(
                ['email' => $e['email']],
                [
                    'name' => $e['name'],
                    'password' => bcrypt('Exhibitor@2026'),
                    'account_type' => 'exhibitor',
                    'phone' => $e['phone'],
                    'county_id' => $county->id,
                    'email_verified_at' => now(),
                    'phone_verified_at' => now(),
                    'status' => 'active',
                ]
            );
            if (!$user->hasRole('exhibitor')) $user->assignRole($exhibitorRole);

            // Transfer the flagship product to this private exhibitor
            $product = Product::where('slug', $e['product'])->first();
            if ($product && $product->user_id !== $user->id) {
                $product->update(['user_id' => $user->id]);
            }
        }
        $this->command->info('Private exhibitors: ' . User::role('exhibitor')->count() . ' (each owns a flagship product).');
    }
}
