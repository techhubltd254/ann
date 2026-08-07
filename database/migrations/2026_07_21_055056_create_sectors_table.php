<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sectors', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->string('code', 20)->unique();
            $table->string('emoji', 10)->nullable();
            $table->text('description')->nullable();
            $table->integer('parent_id')->nullable()->index();
            $table->string('icon', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('county_sector', function (Blueprint $table) {
            $table->id();
            $table->foreignId('county_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sector_id')->constrained()->cascadeOnDelete();
            $table->string('sub_sectors', 500)->nullable();
            $table->unique(['county_id', 'sector_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('county_sector');
        Schema::dropIfExists('sectors');
    }
};
