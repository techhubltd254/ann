<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('county_tourism_attractions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('county_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category', 50);
            $table->string('location')->nullable();
            $table->decimal('entry_fee', 10, 2)->nullable();
            $table->string('opening_hours')->nullable();
            $table->string('contact')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('county_hotels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('county_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category', 50);
            $table->tinyInteger('star_rating')->unsigned()->nullable();
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('price_range_min', 10, 2)->nullable();
            $table->decimal('price_range_max', 10, 2)->nullable();
            $table->json('amenities')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('county_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('county_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category', 50);
            $table->decimal('price', 10, 2)->nullable();
            $table->string('unit')->nullable();
            $table->string('status', 20)->default('available');
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('county_institutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('county_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 50);
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->integer('student_count')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('county_farms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('county_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 50);
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('contact')->nullable();
            $table->decimal('size_acres', 10, 2)->nullable();
            $table->string('main_crops')->nullable();
            $table->string('products')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('county_transport', function (Blueprint $table) {
            $table->id();
            $table->foreignId('county_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 50);
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('operator')->nullable();
            $table->string('contact')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('county_health_facilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('county_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 50);
            $table->string('level', 30);
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('services')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('county_culture_sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('county_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 50);
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('community')->nullable();
            $table->string('contact')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('county_culture_sites');
        Schema::dropIfExists('county_health_facilities');
        Schema::dropIfExists('county_transport');
        Schema::dropIfExists('county_farms');
        Schema::dropIfExists('county_institutions');
        Schema::dropIfExists('county_products');
        Schema::dropIfExists('county_hotels');
        Schema::dropIfExists('county_tourism_attractions');
    }
};
