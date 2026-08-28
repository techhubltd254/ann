<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('review_seeds')) {
            Schema::create('review_seeds', function (Blueprint $table) {
                $table->id();
                $table->string('owner_type', 120);
                $table->unsignedBigInteger('owner_id');
                $table->string('source', 30)->default('google');
                $table->decimal('rating', 3, 2)->default(0);
                $table->unsignedInteger('review_count')->default(0);
                $table->string('external_url', 500)->nullable();
                $table->timestamps();

                $table->index(['owner_type', 'owner_id']);
                $table->unique(['owner_type', 'owner_id', 'source']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('review_seeds');
    }
};