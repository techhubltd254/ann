<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exhibition_id')->constrained()->cascadeOnDelete();
            $table->string('booth_number');
            $table->string('name')->nullable();
            $table->string('size')->default('standard');
            $table->string('category')->default('standard');
            $table->text('description')->nullable();
            $table->json('amenities')->nullable();
            $table->decimal('price', 12, 2);
            $table->decimal('discount_price', 12, 2)->nullable();
            $table->integer('max_quantity')->default(1);
            $table->integer('booked_quantity')->default(0);
            $table->string('location_hint')->nullable();
            $table->json('dimensions')->nullable();
            $table->json('images')->nullable();
            $table->string('status')->default('available');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['exhibition_id', 'booth_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booths');
    }
};
