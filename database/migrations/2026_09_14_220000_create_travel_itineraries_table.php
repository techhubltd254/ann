<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('travel_itineraries')) {
            Schema::create('travel_itineraries', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $t->string('name', 255)->nullable();
                $t->date('start_date');
                $t->date('end_date');
                $t->string('status', 30)->default('draft');
                $t->text('notes')->nullable();
                $t->boolean('is_public')->default(false);
                $t->decimal('estimated_total', 12, 2)->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('travel_itinerary_items')) {
            Schema::create('travel_itinerary_items', function (Blueprint $t) {
                $t->id();
                $t->foreignId('itinerary_id')->constrained('travel_itineraries')->cascadeOnDelete();
                $t->string('item_type', 50)->nullable();
                $t->unsignedBigInteger('item_id')->nullable();
                $t->string('name', 255);
                $t->integer('day_number')->default(1);
                $t->integer('sort_order')->default(0);
                $t->string('start_time', 10)->nullable();
                $t->text('notes')->nullable();
                $t->decimal('estimated_cost', 12, 2)->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_itinerary_items');
        Schema::dropIfExists('travel_itineraries');
    }
};