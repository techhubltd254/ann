<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // News/Blog
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('category', 50)->default('news'); // news, press_release, article, annual_report
            $table->string('author')->nullable();
            $table->string('featured_image')->nullable();
            $table->json('tags')->nullable();
            $table->string('status', 20)->default('draft'); // draft, published
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        // Jobs
        Schema::create('job_listings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('department')->nullable();
            $table->string('location')->nullable();
            $table->string('type', 30)->default('full_time'); // full_time, part_time, contract
            $table->date('closing_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_listing_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->text('cover_letter')->nullable();
            $table->string('cv_path')->nullable();
            $table->timestamps();
        });

        // Newsletter subscribers
        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Event booking enquiries
        Schema::create('event_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('organization')->nullable();
            $table->string('email');
            $table->string('phone');
            $table->string('event_name')->nullable();
            $table->string('event_type', 50); // conference, seminar, workshop, other
            $table->integer('expected_attendees')->nullable();
            $table->date('event_date')->nullable();
            $table->integer('duration_days')->nullable();
            $table->string('preferred_venue')->nullable();
            $table->boolean('needs_catering')->default(false);
            $table->text('catering_details')->nullable();
            $table->boolean('needs_av')->default(false);
            $table->text('av_requirements')->nullable();
            $table->text('additional_info')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_bookings');
        Schema::dropIfExists('newsletter_subscribers');
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('job_listings');
        Schema::dropIfExists('articles');
    }
};