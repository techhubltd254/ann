<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escrow_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->string('escrow_id')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('KES');
            $table->string('status', 30)->default('pending');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->json('steps')->nullable();
            $table->integer('current_step')->default(0);
            $table->timestamp('buyer_confirmed_at')->nullable();
            $table->timestamp('seller_confirmed_at')->nullable();
            $table->timestamp('delivery_confirmed_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
        });

        Schema::create('dispute_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('escrow_transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('raised_by')->constrained('users');
            $table->string('reason', 100);
            $table->text('description');
            $table->string('status', 30)->default('open');
            $table->string('resolution')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('courier_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('escrow_transaction_id')->constrained()->cascadeOnDelete();
            $table->string('tracking_number')->unique();
            $table->string('courier_name');
            $table->string('status', 30)->default('pending');
            $table->text('origin_address');
            $table->text('destination_address');
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('estimated_delivery')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('courier_tracking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('courier_shipment_id')->constrained()->cascadeOnDelete();
            $table->string('status', 50);
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_tracking_events');
        Schema::dropIfExists('courier_shipments');
        Schema::dropIfExists('dispute_cases');
        Schema::dropIfExists('escrow_transactions');
    }
};
