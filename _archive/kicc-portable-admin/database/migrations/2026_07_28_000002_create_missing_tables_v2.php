<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'payment_intents' => function (Blueprint $t) {
                $t->id(); $t->string('intent_id')->unique(); $t->decimal('amount', 12, 2);
                $t->string('status')->default('pending'); $t->string('currency')->default('KES');
                $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $t->nullableMorphs('reference'); $t->timestamps();
            },
            'flight_inventory' => function (Blueprint $t) {
                $t->id(); $t->string('flight_number'); $t->string('class');
                $t->integer('total_seats'); $t->integer('booked_seats')->default(0);
                $t->decimal('price', 12, 2); $t->boolean('is_active')->default(true);
                $t->timestamps();
            },
            'hotel_rooms' => function (Blueprint $t) {
                $t->id(); $t->string('name'); $t->foreignId('hotel_id')->nullable();
                $t->string('room_type'); $t->integer('capacity'); $t->decimal('price_per_night', 12, 2);
                $t->boolean('is_active')->default(true); $t->timestamps();
            },
            'airport_transfers' => function (Blueprint $t) {
                $t->id(); $t->string('provider_name'); $t->string('vehicle_type');
                $t->decimal('price', 12, 2); $t->boolean('is_active')->default(true);
                $t->timestamps();
            },
            'flights' => function (Blueprint $t) {
                $t->id(); $t->string('flight_number'); $t->string('airline');
                $t->string('origin'); $t->string('destination');
                $t->dateTime('departure'); $t->dateTime('arrival');
                $t->decimal('base_price', 12, 2); $t->string('status')->default('scheduled');
                $t->timestamps();
            },
        ];

        foreach ($tables as $name => $schema) {
            if (!Schema::hasTable($name)) {
                Schema::create($name, $schema);
                echo "  Created table: $name\n";
            }
        }
    }

    public function down(): void
    {
        foreach (['flights', 'airport_transfers', 'hotel_rooms', 'flight_inventory', 'payment_intents'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
