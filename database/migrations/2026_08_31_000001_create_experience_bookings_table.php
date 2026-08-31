<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('experience_bookings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('booking_reference', 50)->unique();

            $table->string('destination_type', 100)->nullable();
            $table->unsignedBigInteger('destination_id')->nullable();

            $table->string('origin_location', 255)->nullable();
            $table->unsignedBigInteger('origin_county_id')->nullable();

            $table->date('departure_date');
            $table->date('return_date');
            $table->integer('guest_count')->default(1);

            $table->string('transport_mode', 50)->nullable()->comment('road/train/air+rail/mixed');

            $table->json('transport_out')->nullable();
            $table->json('transport_back')->nullable();
            $table->json('addons')->nullable();
            $table->json('recommended_items')->nullable();
            $table->json('pricing_breakdown')->nullable();

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);

            $table->string('status', 30)->default('pending')->comment('pending/confirmed/cancelled');
            $table->text('notes')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('origin_county_id')->references('id')->on('counties')->onDelete('set null');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experience_bookings');
    }
};