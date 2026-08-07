<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnsureRoom3dsTable extends Command
{
    protected $signature = 'room3d:ensure-table';
    protected $description = 'Create room3ds table if it doesn\'t exist (bypasses migration tracker)';

    public function handle()
    {
        if (Schema::hasTable('room3ds')) {
            $this->info('room3ds table already exists.');
            return Command::SUCCESS;
        }

        $this->info('Creating room3ds table...');
        try {
            DB::statement("CREATE TABLE room3ds (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NULL,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                description TEXT NULL,
                image_paths JSON NULL,
                cover_image VARCHAR(255) NULL,
                status VARCHAR(50) DEFAULT 'draft',
                pipeline VARCHAR(50) DEFAULT 'photo_sphere',
                job_result JSON NULL,
                processed_at TIMESTAMP NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $this->info('room3ds table created successfully.');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed to create room3ds table: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}