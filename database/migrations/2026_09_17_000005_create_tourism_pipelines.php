<?php

/*
|--------------------------------------------------------------------------
| Pipeline tables — cluster: tourism
|--------------------------------------------------------------------------
| Generated for the KICC Pipeline Kit. Every table is guarded with
| Schema::hasTable() so this migration is IDEMPOTENT and non-destructive:
| existing tables (including anything already live in TiDB) are never touched.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // C1 Tourism & Experiences
        if (!Schema::hasTable('experience_bookings')) {
            Schema::create('experience_bookings', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('experience_id')->index()->nullable();
            $table->unsignedBigInteger('user_id')->index()->nullable();
            $table->date('travel_date')->nullable();
            $table->unsignedBigInteger('pax_count')->default(1);
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->decimal('commission', 18, 2)->default(0);
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // C1 availability calendar
        if (!Schema::hasTable('experience_availability')) {
            Schema::create('experience_availability', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('experience_id')->index();
            $table->date('available_date');
            $table->unsignedBigInteger('capacity')->default(0);
            $table->unsignedBigInteger('booked')->default(0);
            $table->decimal('price', 18, 2)->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // C2 Itinerary / Trip Planning
        if (!Schema::hasTable('trip_itineraries')) {
            Schema::create('trip_itineraries', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('user_id')->index()->nullable();
            $table->string('title');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('budget', 18, 2)->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // C2 itinerary items
        if (!Schema::hasTable('trip_itinerary_items')) {
            Schema::create('trip_itinerary_items', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('trip_itinerary_id')->index();
            $table->string('item_type');
            $table->string('ref_type')->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->unsignedBigInteger('sort_order')->default(0);
            $table->decimal('cost', 18, 2)->default(0);
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // C3 Bundled Tourism (Merchant of Record)
        if (!Schema::hasTable('tour_packages')) {
            Schema::create('tour_packages', function (Blueprint $table) {
                $table->id();
            $table->string('title');
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->decimal('flights_cost', 18, 2)->default(0);
            $table->decimal('transfers_cost', 18, 2)->default(0);
            $table->decimal('hotels_cost', 18, 2)->default(0);
            $table->decimal('attractions_cost', 18, 2)->default(0);
            $table->decimal('markup_pct', 5, 2)->default(10);
            $table->decimal('package_price', 18, 2)->default(0);
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // C3 MoR bookings + cancellation waterfall
        if (!Schema::hasTable('tour_package_bookings')) {
            Schema::create('tour_package_bookings', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('tour_package_id')->index();
            $table->unsignedBigInteger('user_id')->index()->nullable();
            $table->date('travel_date')->nullable();
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->decimal('mor_commission', 18, 2)->default(0);
            $table->json('cancellation_waterfall')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // C4 County Homestay
        if (!Schema::hasTable('homestay_listings')) {
            Schema::create('homestay_listings', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->unsignedBigInteger('host_user_id')->index()->nullable();
            $table->string('name');
            $table->unsignedBigInteger('capacity')->default(1);
            $table->decimal('nightly_rate', 18, 2)->nullable();
            $table->string('community_group')->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // C4 bookings
        if (!Schema::hasTable('homestay_bookings')) {
            Schema::create('homestay_bookings', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('homestay_listing_id')->index();
            $table->unsignedBigInteger('guest_user_id')->index()->nullable();
            $table->date('check_in')->nullable();
            $table->date('check_out')->nullable();
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->decimal('commission', 18, 2)->default(0);
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // C5 MICE / Venue
        if (!Schema::hasTable('venue_listings')) {
            Schema::create('venue_listings', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->string('name');
            $table->unsignedBigInteger('capacity')->default(0);
            $table->decimal('daily_rate', 18, 2)->nullable();
            $table->json('dynamic_pricing')->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // C5 venue bookings
        if (!Schema::hasTable('venue_bookings')) {
            Schema::create('venue_bookings', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('venue_listing_id')->index();
            $table->unsignedBigInteger('organiser_user_id')->index()->nullable();
            $table->date('event_date')->nullable();
            $table->decimal('booking_fee', 18, 2)->default(0);
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // C5 booth inventory
        if (!Schema::hasTable('booth_inventory')) {
            Schema::create('booth_inventory', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('venue_listing_id')->index();
            $table->string('booth_code')->nullable();
            $table->string('size')->nullable();
            $table->decimal('price', 18, 2)->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('experience_bookings');
        Schema::dropIfExists('experience_availability');
        Schema::dropIfExists('trip_itineraries');
        Schema::dropIfExists('trip_itinerary_items');
        Schema::dropIfExists('tour_packages');
        Schema::dropIfExists('tour_package_bookings');
        Schema::dropIfExists('homestay_listings');
        Schema::dropIfExists('homestay_bookings');
        Schema::dropIfExists('venue_listings');
        Schema::dropIfExists('venue_bookings');
        Schema::dropIfExists('booth_inventory');
    }
};
