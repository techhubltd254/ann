<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trade_enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('trade_agreement_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('trading_bloc_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('county_id')->nullable()->constrained()->nullOnDelete();
            $table->bigInteger('product_id')->nullable();
            $table->string('product_name')->nullable();
            $table->string('product_category')->nullable();
            $table->string('company_name');
            $table->string('contact_name');
            $table->string('contact_email');
            $table->string('contact_phone')->nullable();
            $table->string('destination')->nullable();
            $table->text('message')->nullable();
            $table->string('hs_code')->nullable();
            $table->decimal('estimated_value', 14, 2)->nullable();
            $table->string('status', 20)->default('submitted'); // submitted|reviewing|approved|rejected|shipped
            $table->text('admin_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_enquiries');
    }
};