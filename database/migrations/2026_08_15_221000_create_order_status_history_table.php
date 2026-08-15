<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('order_status_history', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained((new \App\Models\Marketplace\Order)->getTable())->cascadeOnDelete();
            $t->string('status_from')->nullable();
            $t->string('status_to');
            $t->string('notes')->nullable();
            $t->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('order_status_history'); }
};