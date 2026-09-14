<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('live_streams')) {
            return;
        }

        Schema::create('live_streams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('exhibition_id')->nullable();
            $table->unsignedBigInteger('county_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('status', 30)->default('idle')->comment('idle/live/ended');
            $table->string('stream_url')->nullable();
            $table->string('playback_url')->nullable();
            $table->string('hls_url')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->integer('viewer_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->foreign('exhibition_id')->references('id')->on('exhibitions')->onDelete('set null');
            $table->foreign('county_id')->references('id')->on('counties')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_streams');
    }
};