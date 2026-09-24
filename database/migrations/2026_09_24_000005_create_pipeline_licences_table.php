<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pipeline_licences')) return;

        Schema::create('pipeline_licences', function (Blueprint $table) {
            $table->id();
            $table->string('pipeline_code', 20)->index();
            $table->string('licence_type', 30)->default('regulatory');  // cbk | ifmis | ardhisasa | legal
            $table->string('reference_number', 100)->nullable();
            $table->string('issuing_authority', 200)->nullable();
            $table->string('status', 20)->default('pending')->index();  // pending | approved | rejected
            $table->string('document_path', 500)->nullable();           // path to uploaded PDF/image
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pipeline_licences');
    }
};