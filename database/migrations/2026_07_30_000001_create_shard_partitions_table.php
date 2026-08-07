<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shard_partitions', function (Blueprint $table) {
            $table->smallInteger('partition_id')->unsigned()->primary();
            $table->tinyInteger('shard_index')->unsigned();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->index('shard_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shard_partitions');
    }
};
