<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Phase 9: LMS
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category', 50)->nullable(); // tourism, hospitality, business, language
            $table->string('level', 30)->default('beginner'); // beginner, intermediate, advanced
            $table->integer('duration_hours')->nullable();
            $table->string('instructor')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('video_url')->nullable();
            $table->string('document_url')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });
        Schema::create('course_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('enrolled'); // enrolled, in_progress, completed
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // Phase 9: Safety & Security
        Schema::create('safety_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('county_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('type', 30)->default('advisory'); // advisory, warning, emergency, weather
            $table->string('severity', 20)->default('info'); // info, caution, warning, danger
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('incident_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('county_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 50); // theft, accident, harassment, medical, lost, other
            $table->text('description');
            $table->string('location')->nullable();
            $table->string('status', 20)->default('reported'); // reported, investigating, resolved
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_reports');
        Schema::dropIfExists('safety_alerts');
        Schema::dropIfExists('course_enrollments');
        Schema::dropIfExists('courses');
    }
};