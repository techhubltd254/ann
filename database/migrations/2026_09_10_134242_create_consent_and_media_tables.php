<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // IDEMPOTENT_GUARD: table may already exist on TiDB (raw schema)
        if (Schema::hasTable('consent_forms')) {
            return;
        }

        Schema::create('consent_forms', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('language', 5)->default('en'); // en, sw, en-sw
            $table->text('content_en')->nullable();  // English waiver text
            $table->text('content_sw')->nullable();  // Swahili waiver text
            $table->string('entity_type', 100);       // App\Models\CountyInstitution, etc
            $table->unsignedBigInteger('entity_id');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('signed_count')->default(0);
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
        });

        Schema::create('consent_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consent_form_id');
            $table->string('signer_name');
            $table->string('signer_id_number')->nullable();
            $table->string('signer_phone')->nullable();
            $table->string('signer_email')->nullable();
            $table->json('agreements')->nullable(); // which clauses agreed to
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();

            $table->index('consent_form_id');
        });

        Schema::create('voice_notes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->unsignedBigInteger('audio_asset_id')->nullable();
            $table->string('entity_type', 100); // App\Models\County, CountyInstitution, HousingProject
            $table->unsignedBigInteger('entity_id');
            $table->string('voice_type', 32)->default('citizen'); // citizen, patient, health-worker, beneficiary
            $table->text('transcript')->nullable();
            $table->json('metadata')->nullable(); // duration, language, topics
            $table->boolean('is_published')->default(false);
            $table->timestamp('transcribed_at')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index('voice_type');
        });

        Schema::create('speech_segments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('voice_note_id');
            $table->float('start_seconds', 8, 2);
            $table->float('end_seconds', 8, 2);
            $table->text('text');
            $table->float('confidence', 4, 3)->nullable();
            $table->string('speaker_label', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('voice_note_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('speech_segments');
        Schema::dropIfExists('voice_notes');
        Schema::dropIfExists('consent_records');
        Schema::dropIfExists('consent_forms');
    }
};