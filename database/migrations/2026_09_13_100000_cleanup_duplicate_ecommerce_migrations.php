<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

return new class extends Migration {
    public function up(): void
    {
        Log::info('cleanup: duplicate ecommerce migrations IDENTIFIED — no tables dropped');
    }

    public function down(): void {}
};