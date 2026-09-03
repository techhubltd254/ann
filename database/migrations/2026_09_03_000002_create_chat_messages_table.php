<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('live_stream_id');
            $table->unsignedBigInteger('user_id')->default(0);
            $table->string('user_name', 100)->default('Guest');
            $table->text('message');
            $table->timestamps();

            $table->foreign('live_stream_id')->references('id')->on('live_streams')->onDelete('cascade');
            $table->index('live_stream_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};