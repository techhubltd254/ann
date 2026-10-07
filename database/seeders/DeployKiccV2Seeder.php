<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DeployKiccV2Seeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('=== KICC V2 Deployment Seeder ===');

        // Step 1: Create the kicc_v2 database
        try {
            DB::statement('CREATE DATABASE IF NOT EXISTS kicc_v2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $this->command->info('✓ Database kicc_v2 ready');
        } catch (\Throwable $e) {
            $this->command->warn('DB create: ' . $e->getMessage());
        }

        // Step 2: Create tables in kicc_v2 using raw SQL
        $this->createV2Tables();

        // Step 3: Run ReferenceContentSeeder against kicc_v2
        $this->seedReferenceContent();

        // Step 4: Run legacy data import
        $this->importLegacyData();

        // Step 5: Create admin user in kicc_v2
        $this->createAdmin();

        $this->command->info('=== Deployment Complete ===');
        $this->command->info('Admin: admin@kicc.go.ke / KICC@Admin2026');
        $this->command->info('Deploy V2 seeder complete. Next: configure Nginx vhost to /var/www/kicc-experience/current/public');
    }

    private function createV2Tables(): void
    {
        $this->command->info('--- Creating kicc_v2 tables ---');

        // Add is_admin to users
        DB::statement('ALTER TABLE users ADD COLUMN IF NOT EXISTS is_admin TINYINT(1) NOT NULL DEFAULT 0');

        // Create records table
        DB::statement('CREATE TABLE IF NOT EXISTS records (
            id CHAR(36) PRIMARY KEY,
            type VARCHAR(32) NOT NULL,
            slug VARCHAR(180) NOT NULL,
            name VARCHAR(240) NOT NULL,
            description LONGTEXT NULL,
            status VARCHAR(16) NOT NULL DEFAULT "draft",
            payload JSON NULL,
            parent_id CHAR(36) NULL,
            revision INT UNSIGNED NOT NULL DEFAULT 1,
            updated_by BIGINT UNSIGNED NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            INDEX records_type_index (type),
            INDEX records_status_index (status),
            UNIQUE records_type_slug_unique (type, slug),
            FOREIGN KEY (parent_id) REFERENCES records(id) ON DELETE SET NULL,
            FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
        )');

        // Create media_assets table
        DB::statement('CREATE TABLE IF NOT EXISTS media_assets (
            id CHAR(36) PRIMARY KEY,
            record_id CHAR(36) NOT NULL,
            disk VARCHAR(16) NOT NULL,
            path VARCHAR(500) NOT NULL,
            original_name VARCHAR(500) NOT NULL,
            mime VARCHAR(120) NOT NULL,
            bytes BIGINT UNSIGNED NOT NULL,
            title VARCHAR(240) NOT NULL,
            description TEXT NULL,
            status VARCHAR(16) NOT NULL DEFAULT "draft",
            format VARCHAR(16) NOT NULL DEFAULT "standard",
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            INDEX media_assets_status_index (status),
            FOREIGN KEY (record_id) REFERENCES records(id) ON DELETE CASCADE
        )');

        // Create audit_events table
        DB::statement('CREATE TABLE IF NOT EXISTS audit_events (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NULL,
            action VARCHAR(80) NOT NULL,
            subject_id VARCHAR(64) NULL,
            details JSON NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )');

        // Create enquiries table
        DB::statement('CREATE TABLE IF NOT EXISTS enquiries (
            id CHAR(36) PRIMARY KEY,
            record_id CHAR(36) NOT NULL,
            name VARCHAR(180) NOT NULL,
            email VARCHAR(240) NOT NULL,
            phone VARCHAR(60) NULL,
            message TEXT NOT NULL,
            status VARCHAR(16) NOT NULL DEFAULT "new",
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            FOREIGN KEY (record_id) REFERENCES records(id) ON DELETE CASCADE
        )');

        $this->command->info('✓ Tables created');
    }

    private function seedReferenceContent(): void
    {
        $this->command->info('--- Seeding reference content ---');

        // Seed counties from JSON
        $countiesJson = file_get_contents(database_path('data/counties.json'));
        $counties = json_decode($countiesJson, true);
        foreach ($counties as $c) {
            $c['source_id'] = $c['id'];
            unset($c['id']);
            $payload = $c;
            unset($payload['name'], $payload['slug'], $payload['description'], $payload['desc']);
            $payload['source_file'] = 'database/data/counties.json';
            $payload['requires_editorial_review'] = true;
            DB::table('records')->updateOrInsert(
                ['type' => 'counties', 'slug' => $c['slug']],
                [
                    'id' => (string) Str::uuid(),
                    'name' => $c['name'],
                    'description' => $c['description'] ?? $c['desc'] ?? null,
                    'status' => 'published',
                    'payload' => json_encode($payload),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
        $this->command->info('  ✓ Counties seeded');

        // Seed sectors from JSON
        $sectorsJson = file_get_contents(database_path('data/sectors.json'));
        $sectors = json_decode($sectorsJson, true);
        foreach ($sectors as $s) {
            $s['source_id'] = $s['id'];
            $s['county_ids'] = $s['counties'] ?? [];
            unset($s['id'], $s['counties']);
            $payload = $s;
            unset($payload['name'], $payload['slug'], $payload['description']);
            $payload['source_file'] = 'database/data/sectors.json';
            DB::table('records')->updateOrInsert(
                ['type' => 'sectors', 'slug' => $s['slug']],
                [
                    'id' => (string) Str::uuid(),
                    'name' => $s['name'],
                    'description' => $s['description'] ?? null,
                    'status' => 'published',
                    'payload' => json_encode($payload),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
        $this->command->info('  ✓ Sectors seeded');
    }

    private function importLegacyData(): void
    {
        $this->command->info('--- Importing legacy data ---');

        // Import institutions
        $institutions = DB::table('county_institutions')->orderBy('name')->get();
        $count = 0;
        $idMap = [];

        foreach ($institutions as $inst) {
            $countyRecord = DB::table('records')
                ->where('type', 'counties')
                ->where('payload->source_id', $inst->county_id)
                ->first();

            $payload = [
                'type' => $inst->type ?? null,
                'location' => $inst->location ?? null,
                'phone' => $inst->phone ?? null,
                'email' => $inst->email ?? null,
                'website' => $inst->website ?? null,
                'headquarters' => $inst->headquarters ?? null,
                'founded_year' => $inst->founded_year ?? null,
                'story' => $inst->story ?? null,
                'latitude' => $inst->lat ?? null,
                'longitude' => $inst->lng ?? null,
                'sector_mappings' => is_string($inst->sector_mappings ?? null) ? json_decode($inst->sector_mappings, true) : ($inst->sector_mappings ?? null),
                'products' => is_string($inst->products ?? null) ? json_decode($inst->products, true) : ($inst->products ?? null),
                'county_id' => $inst->county_id ?? null,
                'source_id' => (int) $inst->id,
                'source_file' => 'kicc.county_institutions',
            ];

            $uuid = (string) Str::uuid();
            DB::table('records')->insert([
                'id' => $uuid,
                'type' => 'institutions',
                'slug' => $inst->slug,
                'name' => $inst->name,
                'description' => $inst->description ?? null,
                'status' => ($inst->isPublished ?? false) ? 'published' : 'draft',
                'parent_id' => $countyRecord?->id,
                'payload' => json_encode($payload),
                'created_at' => $inst->created_at ?? now(),
                'updated_at' => $inst->updated_at ?? now(),
            ]);
            $idMap[$inst->id] = $uuid;
            $count++;
        }
        $this->command->info("  ✓ {$count} institutions imported");

        // Import media assets (videos)
        $assets = DB::table('media_assets')
            ->where('kind', 'video')
            ->where('status', 'ready')
            ->get();

        $mediaCount = 0;
        foreach ($assets as $asset) {
            $recordId = null;
            if (in_array($asset->owner_type, ['App\\Models\\County', 'county'])) {
                $recordId = DB::table('records')
                    ->where('type', 'counties')
                    ->where('payload->source_id', $asset->owner_id)
                    ->value('id');
            } elseif (in_array($asset->owner_type, ['App\\Models\\CountyInstitution', 'institution'])) {
                $recordId = $idMap[$asset->owner_id] ?? null;
            }
            if (!$recordId) continue;

            try {
                DB::table('media_assets')->insert([
                    'id' => (string) Str::uuid(),
                    'record_id' => $recordId,
                    'disk' => $asset->disk ?? 'r2',
                    'path' => $asset->path,
                    'original_name' => $asset->original_name ?? basename($asset->path),
                    'mime' => $asset->mime ?? 'video/mp4',
                    'bytes' => $asset->size_bytes ?? 0,
                    'title' => $asset->original_name ?? 'Video',
                    'description' => $asset->slot ?? null,
                    'status' => 'published',
                    'format' => 'standard',
                    'created_at' => $asset->created_at ?? now(),
                    'updated_at' => $asset->updated_at ?? now(),
                ]);
                $mediaCount++;
            } catch (\Throwable $e) {
                $this->command->warn("  ⚠ Media #{$asset->id}: " . $e->getMessage());
            }
        }
        $this->command->info("  ✓ {$mediaCount} media assets imported");

        // Import products from institutions
        $prodCount = 0;
        foreach ($institutions as $inst) {
            $instRecordId = $idMap[$inst->id] ?? null;
            if (!$instRecordId) continue;
            $products = is_string($inst->products) ? json_decode($inst->products, true) : $inst->products;
            if (!is_array($products)) continue;
            foreach ($products as $prod) {
                $name = $prod['name'] ?? 'Product';
                $slug = Str::slug($name . '-' . substr($instRecordId, 0, 6));
                $exists = DB::table('records')
                    ->where('type', 'products')
                    ->where('name', $name)
                    ->where('parent_id', $instRecordId)
                    ->exists();
                if ($exists) continue;
                DB::table('records')->insert([
                    'id' => (string) Str::uuid(),
                    'type' => 'products',
                    'slug' => $slug,
                    'name' => $name,
                    'description' => $prod['description'] ?? null,
                    'status' => 'published',
                    'parent_id' => $instRecordId,
                    'payload' => json_encode([
                        'price' => $prod['price'] ?? null,
                        'unit' => $prod['unit'] ?? null,
                        'category' => $prod['category'] ?? null,
                        'stock' => $prod['stock'] ?? null,
                        'source_file' => 'kicc.county_institutions.products',
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $prodCount++;
            }
        }
        $this->command->info("  ✓ {$prodCount} products imported");
    }

    private function createAdmin(): void
    {
        $exists = DB::table('users')->where('email', 'admin@kicc.go.ke')->exists();
        if (!$exists) {
            DB::table('users')->insert([
                'name' => 'Administrator',
                'email' => 'admin@kicc.go.ke',
                'password' => bcrypt('KICC@Admin2026'),
                'is_admin' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info('✓ Admin user created');
        } else {
            DB::table('users')->where('email', 'admin@kicc.go.ke')->update(['is_admin' => true]);
            $this->command->info('✓ Admin user updated (is_admin=true)');
        }
    }
}