<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('county_subscription_plans')) {
            Schema::create('county_subscription_plans', function (Blueprint $t) {
                $t->id(); $t->foreignId('county_id')->constrained()->cascadeOnDelete();
                $t->string('name'); $t->string('slug'); $t->text('description')->nullable();
                $t->decimal('price', 10, 2)->default(0); $t->string('period')->default('monthly');
                $t->integer('max_booths')->default(0); $t->integer('max_products')->default(0);
                $t->boolean('is_active')->default(true); $t->timestamps();
            });
        }
    }
    public function down(): void { Schema::dropIfExists('county_subscription_plans'); }
};
