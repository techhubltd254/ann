<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('wishlists')) {
            return;
        }

        Schema::create('wishlists', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->morphs('wishlistable');
            $t->timestamps();
            $t->unique(['user_id', 'wishlistable_id', 'wishlistable_type']);
        });
    }
    public function down(): void { Schema::dropIfExists('wishlists'); }
};