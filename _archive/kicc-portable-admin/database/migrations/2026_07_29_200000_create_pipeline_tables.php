<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pipeline_logs')) {
            Schema::create('pipeline_logs', function (Blueprint $t) {
                $t->id();
                $t->string('source', 64);
                $t->string('signal', 128);
                $t->text('value')->nullable();
                $t->string('severity', 32)->default('info');
                $t->text('metadata')->nullable();
                $t->timestamp('created_at')->useCurrent();
                $t->index(['source', 'signal']);
                $t->index('created_at');
            });
        }

        if (!Schema::hasTable('pipeline_jobs')) {
            Schema::create('pipeline_jobs', function (Blueprint $t) {
                $t->id();
                $t->string('type', 64);
                $t->string('status', 32)->default('pending');
                $t->unsignedTinyInteger('priority')->default(5);
                $t->text('payload')->nullable();
                $t->text('result')->nullable();
                $t->timestamp('started_at')->nullable();
                $t->timestamp('completed_at')->nullable();
                $t->timestamps();
                $t->index(['type', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pipeline_jobs');
        Schema::dropIfExists('pipeline_logs');
    }
};
