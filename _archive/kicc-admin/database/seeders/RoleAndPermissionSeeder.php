<?php

namespace Database\Seeders;

use App\Models\County;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // County-level permissions
        $countyPermissions = [
            'view own county',
            'edit own county',
            'manage own county tourism',
            'manage own county hotels',
            'manage own county products',
            'manage own county institutions',
            'manage own county farms',
            'manage own county transport',
            'manage own county health',
            'manage own county culture',
            'upload own county media',
        ];

        // Super admin permissions (all)
        $superAdminPermissions = [
            'view any county',
            'edit any county',
            'manage users',
            'manage roles',
            'view exhibitions',
            'manage exhibitions',
            'view venues',
            'manage venues',
            'view bookings',
            'manage bookings',
            'view all county data',
            'edit all county data',
        ];

        foreach (array_merge($countyPermissions, $superAdminPermissions) as $perm) {
            Permission::findOrCreate($perm);
        }

        // Create roles
        $superAdmin = Role::findOrCreate('super-admin');
        $superAdmin->syncPermissions(Permission::all());

        $countyAdmin = Role::findOrCreate('county-admin');
        $countyAdmin->syncPermissions($countyPermissions);

        $viewer = Role::findOrCreate('viewer');
        $viewer->syncPermissions(['view any county']);

        // Assign super-admin to existing admin user
        $admin = User::where('email', 'admin@kicc.go.ke')->first();
        if ($admin) {
            $admin->assignRole('super-admin');
        }

        $this->command->info('Roles and permissions seeded successfully');
    }
}
