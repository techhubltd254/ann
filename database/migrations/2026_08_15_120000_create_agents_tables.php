<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agents')) {
            return;
        }

        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('county_id')->nullable()->constrained()->nullOnDelete();
            $table->string('business_name');
            $table->string('registration_number')->nullable()->unique();
            $table->string('license_number')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('contact_email');
            $table->string('contact_phone')->nullable();
            $table->string('website')->nullable();
            $table->text('address')->nullable();
            $table->text('description')->nullable();
            $table->string('logo_url')->nullable();
            $table->json('service_types')->nullable(); // ['tour_operator','accommodation','transport','guide','restaurant','events']
            $table->string('agent_type', 30)->default('local'); // local, international
            $table->string('status', 20)->default('pending'); // pending, approved, rejected, suspended
            $table->decimal('commission_rate', 5, 2)->default(5.00);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('agent_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 50); // business_license, tax_registration, kyc, certification, insurance
            $table->string('file_path');
            $table->string('original_name');
            $table->string('status', 20)->default('pending'); // pending, verified, rejected
            $table->text('notes')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_documents');
        Schema::dropIfExists('agents');
    }
};