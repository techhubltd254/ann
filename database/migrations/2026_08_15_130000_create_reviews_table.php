<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reviews')) {
            return;
        }

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->morphs('reviewable'); // Product, Attraction, Hotel, TourGuide, Restaurant, CarRental
            $table->tinyInteger('rating')->unsigned(); // 1-5
            $table->text('content')->nullable();
            $table->json('photos')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->string('status', 20)->default('pending'); // pending, approved, rejected
            $table->text('vendor_response')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};