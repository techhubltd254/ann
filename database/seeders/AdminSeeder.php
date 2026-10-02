<?php

namespace Database\Seeders;

use App\Models\County;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $password = Str::random(24);
        $seeded = [];

        // 1. KICC Mother Admin
        $mother = User::firstOrCreate(
            ['email' => 'admin@kicc.go.ke'],
            [
                'name' => 'KICC Mother Admin',
                'fullName' => 'KICC Mother Admin',
                'email' => 'admin@kicc.go.ke',
                'password' => bcrypt($password),
                'passwordHash' => bcrypt($password),
                'account_type' => 'superadmin',
                'mfaEnabled' => false,
                'tier' => 'KICC',
                'status' => 'active',
            ]
        );
        $mother->assignRole('kicc_admin');
        $seeded[] = ['admin@kicc.go.ke', 'KICC Mother Admin', $password, 'kicc_admin'];

        // 2. National Government Admin
        $nationalPassword = Str::random(24);
        $national = User::firstOrCreate(
            ['email' => 'admin@national.kicc.go.ke'],
            [
                'name' => 'National Government Admin',
                'fullName' => 'National Government Admin',
                'email' => 'admin@national.kicc.go.ke',
                'password' => bcrypt($nationalPassword),
                'passwordHash' => bcrypt($nationalPassword),
                'account_type' => 'admin',
                'mfaEnabled' => false,
                'tier' => 'NATIONAL',
                'status' => 'active',
            ]
        );
        $national->assignRole('national_admin');
        $seeded[] = ['admin@national.kicc.go.ke', 'National Government Admin', $nationalPassword, 'national_admin'];

        // 3. Per-county admins
        $counties = County::orderBy('slug')->get();
        $countyCount = 0;
        foreach ($counties as $county) {
            $countyPassword = Str::random(24);
            $email = "admin@{$county->slug}.kicc.go.ke";
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => "{$county->name} County Admin",
                    'fullName' => "{$county->name} County Admin",
                    'email' => $email,
                    'password' => bcrypt($countyPassword),
                    'passwordHash' => bcrypt($countyPassword),
                    'account_type' => 'admin',
                    'mfaEnabled' => false,
                    'tier' => 'COUNTY',
                    'county_id' => $county->id,
                    'status' => 'active',
                ]
            );
            $user->assignRole('county_admin');
            $seeded[] = [$email, "{$county->name} County Admin", $countyPassword, 'county_admin'];
            $countyCount++;
        }

        $this->command->info("\n=== Seeded Admin Credentials ===");
        $this->command->info("Email                  | Name                       | Password                  | Role");
        $this->command->info("-----------------------|----------------------------|---------------------------|----------------");
        foreach ($seeded as $row) {
            $this->command->info(
                Str::padRight($row[0], 22) . ' | ' .
                Str::padRight(Str::limit($row[1], 26), 26) . ' | ' .
                Str::padRight($row[2], 26) . ' | ' .
                $row[3]
            );
        }
        $this->command->info("\n" . count($seeded) . " admin accounts seeded. PASSWORDS GENERATED — save them now. They will NOT be shown again.");
        $this->command->info("County admins: {$countyCount} counties");
    }
}