<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('trading_blocs')) {
            return;
        }

        Schema::create('trading_blocs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('member_states')->nullable();
            $table->string('website')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('status', 20)->default('active');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('trade_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_bloc_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->longText('content')->nullable();
            $table->string('partner_country')->nullable();
            $table->string('agreement_type', 50)->nullable(); // bilateral, multilateral, regional
            $table->date('signed_date')->nullable();
            $table->date('effective_date')->nullable();
            $table->string('status', 20)->default('active'); // active, pending, expired
            $table->string('document_url')->nullable();
            $table->string('document_pdf')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('benefits')->nullable();
            $table->json('sector_coverage')->nullable(); // which sectors/products it covers
            $table->string('county_impact', 50)->nullable(); // which counties benefit
            $table->timestamps();
        });

        Schema::create('trade_bloc_county', function (Blueprint $table) {
            $table->foreignId('trading_bloc_id')->constrained()->cascadeOnDelete();
            $table->integer('county_id');
            $table->string('status', 20)->default('active');
            $table->primary(['trading_bloc_id', 'county_id']);
        });

        Schema::create('trade_agreement_product_category', function (Blueprint $table) {
            $table->foreignId('trade_agreement_id')->constrained()->cascadeOnDelete();
            $table->integer('category_id');
            $table->primary(['trade_agreement_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_agreement_product_category');
        Schema::dropIfExists('trade_bloc_county');
        Schema::dropIfExists('trade_agreements');
        Schema::dropIfExists('trading_blocs');
    }
};