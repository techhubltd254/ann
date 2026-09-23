<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_runs', function (Blueprint $table) {
            $table->id();
            $table->string('node_key')->index();
            $table->string('node_name');
            $table->string('workflow')->nullable();
            $table->string('status')->default('running')->index();
            $table->string('trigger')->default('manual');
            $table->json('root_pipeline_ids')->nullable();
            $table->json('settled_pipeline_ids')->nullable();
            $table->json('failed_pipeline_ids')->nullable();
            $table->unsignedInteger('settled_count')->default(0);
            $table->unsignedInteger('dlq_count')->default(0);
            $table->string('correlation_id')->nullable()->index();
            $table->unsignedInteger('duration_ms')->default(0);
            $table->text('error')->nullable();
            $table->foreignId('triggered_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_runs');
    }
};
