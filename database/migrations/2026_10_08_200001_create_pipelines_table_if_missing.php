<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('pipelines')) return;
        Schema::create('pipelines', function (Blueprint $t) {
            $t->id(); $t->uuid('uuid')->nullable()->unique();
            $t->string('code')->nullable()->unique(); $t->string('name');
            $t->string('sector')->nullable(); $t->string('status')->default('active');
            $t->text('description')->nullable(); $t->timestamps();
        });
    }
    public function down(): void {}
};
