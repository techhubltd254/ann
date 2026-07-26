<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view_county', 'create_county', 'edit_county', 'delete_county',
            'view_product', 'create_product', 'edit_product', 'delete_product',
            'view_sector', 'create_sector', 'edit_sector', 'delete_sector',
            'view_exhibition', 'create_exhibition', 'edit_exhibition', 'delete_exhibition',
            'view_venue', 'create_venue', 'edit_venue', 'delete_venue',
            'view_screen', 'create_screen', 'edit_screen', 'delete_screen',
            'view_user', 'create_user', 'edit_user', 'delete_user',
            'view_booking', 'edit_booking', 'delete_booking',
            'view_payment', 'edit_payment',
            'view_subscription', 'edit_subscription',
            'view_room3d', 'create_room3d', 'edit_room3d', 'delete_room3d',
            'view_setting', 'edit_setting',
            'view_sector_entity', 'create_sector_entity', 'edit_sector_entity', 'delete_sector_entity',
        ];

        foreach ($permissions as $perm) {
            Permission::findOrCreate($perm);
        }

        $kiccAdmin = Role::findOrCreate('kicc_admin');
        $kiccAdmin->syncPermissions(Permission::all());

        $nationalAdmin = Role::findOrCreate('national_admin');
        $nationalAdmin->syncPermissions([
            'view_county', 'edit_county',
            'view_product', 'edit_product',
            'view_sector', 'edit_sector',
            'view_exhibition', 'edit_exhibition',
            'view_venue', 'edit_venue',
            'view_screen', 'edit_screen',
            'view_booking', 'edit_booking',
            'view_payment', 'edit_payment',
            'view_subscription', 'edit_subscription',
            'view_room3d', 'edit_room3d',
            'view_setting', 'edit_setting',
            'view_sector_entity', 'edit_sector_entity',
        ]);

        $countyAdmin = Role::findOrCreate('county_admin');
        $countyAdmin->syncPermissions([
            'view_county', 'edit_county',
            'view_product', 'create_product', 'edit_product',
            'view_sector', 'view_sector_entity', 'create_sector_entity', 'edit_sector_entity',
            'view_booking', 'edit_booking',
        ]);

        $this->command->info('Roles and permissions seeded successfully');
    }
}