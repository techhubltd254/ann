<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('county_product_bookings')) {
            return;
        }

        Schema::create('county_product_bookings', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('county_product_id');
            $table->bigInteger('user_id')->nullable();
            $table->string('reference')->unique();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total', 10, 2);
            $table->string('status', 20)->default('pending'); // pending | paid | cancelled
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('county_product_bookings');
    }
};
