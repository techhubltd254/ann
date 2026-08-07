<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Counties
            'view_county', 'create_county', 'edit_county', 'delete_county',
            // Products
            'view_product', 'create_product', 'edit_product', 'delete_product',
            // Sectors
            'view_sector', 'create_sector', 'edit_sector', 'delete_sector',
            // Sector Entities
            'view_sector_entity', 'create_sector_entity', 'edit_sector_entity', 'delete_sector_entity',
            // Exhibitions
            'view_exhibition', 'create_exhibition', 'edit_exhibition', 'delete_exhibition',
            // Venues
            'view_venue', 'create_venue', 'edit_venue', 'delete_venue',
            // Screens
            'view_screen', 'create_screen', 'edit_screen', 'delete_screen',
            // Users
            'view_user', 'create_user', 'edit_user', 'delete_user',
            // Bookings
            'view_booking', 'create_booking', 'edit_booking', 'delete_booking',
            // Payments
            'view_payment', 'edit_payment',
            // Subscriptions
            'view_subscription', 'create_subscription', 'edit_subscription',
            // Room3D
            'view_room3d', 'create_room3d', 'edit_room3d', 'delete_room3d',
            // Settings
            'view_setting', 'edit_setting',
            // Ministries
            'view_ministry', 'create_ministry', 'edit_ministry', 'delete_ministry',
            // Agencies
            'view_agency', 'create_agency', 'edit_agency', 'delete_agency',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // KICC Admin — full access to everything
        $kiccAdmin = Role::firstOrCreate(['name' => 'kicc_admin']);
        $kiccAdmin->syncPermissions(Permission::all());

        // National Government Admin — view/edit all content, no delete
        $nationalAdmin = Role::firstOrCreate(['name' => 'national_admin']);
        $nationalAdmin->syncPermissions([
            'view_county', 'edit_county',
            'view_product', 'edit_product',
            'view_sector', 'edit_sector',
            'view_sector_entity', 'edit_sector_entity',
            'view_exhibition', 'edit_exhibition',
            'view_venue', 'edit_venue',
            'view_screen', 'edit_screen',
            'view_booking', 'edit_booking',
            'view_payment', 'edit_payment',
            'view_subscription', 'edit_subscription',
            'view_ministry', 'edit_ministry',
            'view_agency', 'edit_agency',
            'view_user', 'edit_user',
        ]);

        // County Admin — scoped to their county
        $countyAdmin = Role::firstOrCreate(['name' => 'county_admin']);
        $countyAdmin->syncPermissions([
            'view_county', 'edit_county',
            'view_product', 'create_product', 'edit_product', 'delete_product',
            'view_sector', 'edit_sector',
            'view_sector_entity', 'create_sector_entity', 'edit_sector_entity', 'delete_sector_entity',
            'view_exhibition', 'edit_exhibition',
            'view_booking', 'edit_booking',
            'view_payment',
            'view_subscription',
        ]);

        $this->command->info('Roles and permissions seeded successfully');
    }
}
