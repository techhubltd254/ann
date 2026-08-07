<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exhibition_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->string('session_type')->default('talk');
            $table->string('speaker')->nullable();
            $table->string('speaker_title')->nullable();
            $table->string('speaker_photo')->nullable();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->string('location')->nullable();
            $table->integer('max_attendees')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('status')->default('scheduled');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_sessions');
    }
};
