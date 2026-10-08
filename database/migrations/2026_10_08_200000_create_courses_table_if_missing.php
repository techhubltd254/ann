<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('courses')) return;
        Schema::create('courses', function (Blueprint $t) {
            $t->id(); $t->string('title'); $t->string('slug')->unique();
            $t->text('description')->nullable(); $t->string('instructor_name')->nullable();
            $t->string('duration')->nullable(); $t->decimal('price', 12, 2)->default(0);
            $t->boolean('is_published')->default(true);
            $t->timestamps(); $t->softDeletes();
        });
    }
    public function down(): void {}
};
