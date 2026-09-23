<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pipeline_activations')) {
            Schema::create('pipeline_activations', function (Blueprint $t) {
                $t->id();
                $t->foreignId('county_id')->constrained()->cascadeOnDelete();
                $t->string('pipeline_code', 20);
                $t->timestamp('activated_at')->nullable();
                $t->timestamp('deactivated_at')->nullable();
                $t->timestamps();
                $t->unique(['county_id', 'pipeline_code']);
                $t->index('pipeline_code');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pipeline_activations');
    }
};
