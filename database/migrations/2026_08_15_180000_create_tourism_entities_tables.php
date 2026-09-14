<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tour_guides')) {
            return;
        }

        Schema::create('tour_guides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('county_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('bio')->nullable();
            $table->json('languages')->nullable();
            $table->string('certification')->nullable();
            $table->json('service_types')->nullable(); // hiking, safari, cultural, city, water
            $table->decimal('price_per_day', 10, 2)->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('photo_url')->nullable();
            $table->boolean('is_available')->default(true);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('car_rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('county_id')->nullable()->constrained()->nullOnDelete();
            $table->string('company_name');
            $table->string('vehicle_type', 50); // sedan, SUV, van, bus, motorbike
            $table->string('model')->nullable();
            $table->integer('capacity')->nullable();
            $table->decimal('price_per_day', 10, 2);
            $table->decimal('price_per_km', 10, 2)->nullable();
            $table->boolean('with_driver')->default(true);
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('location')->nullable();
            $table->boolean('is_available')->default(true);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('county_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('cuisine_type', 100)->nullable();
            $table->string('price_range', 20)->nullable(); // budget, mid, premium
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('location')->nullable();
            $table->string('photo_url')->nullable();
            $table->json('opening_hours')->nullable();
            $table->boolean('has_reservations')->default(false);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('event_organizers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('business_name');
            $table->string('contact_email');
            $table->string('contact_phone')->nullable();
            $table->text('description')->nullable();
            $table->json('event_types')->nullable(); // conferences, weddings, exhibitions, concerts, sports
            $table->string('website')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_organizers');
        Schema::dropIfExists('restaurants');
        Schema::dropIfExists('car_rentals');
        Schema::dropIfExists('tour_guides');
    }
};