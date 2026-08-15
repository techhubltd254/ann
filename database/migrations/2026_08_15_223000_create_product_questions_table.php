<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('product_questions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained((new \App\Models\Marketplace\Product)->getTable())->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->text('question');
            $t->text('answer')->nullable();
            $t->timestamp('answered_at')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('product_questions'); }
};