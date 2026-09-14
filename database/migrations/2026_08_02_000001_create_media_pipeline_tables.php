<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('media_assets')) {
            return;
        }

        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->unsignedBigInteger('owner_id')->nullable()->index();
            $table->string('owner_type')->nullable();
            $table->string('disk', 32)->default('public');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 100);
            $table->string('kind', 16)->default('image')->index(); // image|video|model|audio
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('status', 20)->default('uploaded')->index(); // uploaded|processing|ready|failed
            $table->string('alt_text')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['owner_type', 'owner_id']);
        });

        Schema::create('media_derivatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_asset_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 32)->index(); // webp|poster|video_mp4|video_webm|video_hls|model_glb|model_gltf|texture_ktx2|thumb
            $table->string('path');
            $table->string('mime', 100);
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('variant', 64)->nullable()->index(); // e.g. lod0, lod1, 1080p, 720p
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['media_asset_id', 'kind']);
        });

        Schema::create('media_pipeline_jobs', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('media_asset_id')->constrained()->cascadeOnDelete();
            $table->string('pipeline', 32)->index(); // cinematic_video|image_to_3d|optimize|agentic
            $table->string('engine', 32)->index();   // wan2gp|hailuo|tripo3d|road|ffmpeg_kenburns
            $table->string('status', 20)->default('queued')->index(); // queued|running|completed|failed|cancelled
            $table->unsignedTinyInteger('progress')->default(0);
            $table->string('stage')->nullable();
            $table->json('options')->nullable();
            $table->foreignId('output_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'engine']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_pipeline_jobs');
        Schema::dropIfExists('media_derivatives');
        Schema::dropIfExists('media_assets');
    }
};
