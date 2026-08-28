<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('image_variants')) {
            Schema::create('image_variants', function (Blueprint $table) {
                $table->id();
                $table->string('owner_type', 120)->nullable();
                $table->unsignedBigInteger('owner_id')->nullable();
                $table->string('source_url', 500)->nullable();
                $table->string('source_hash', 40)->nullable()->index();
                $table->string('thumb_key', 255)->nullable();
                $table->string('card_key', 255)->nullable();
                $table->string('hero_key', 255)->nullable();
                $table->text('blur')->nullable();
                $table->integer('width')->nullable();
                $table->integer('height')->nullable();
                $table->timestamps();

                $table->index(['owner_type', 'owner_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('image_variants');
    }
};