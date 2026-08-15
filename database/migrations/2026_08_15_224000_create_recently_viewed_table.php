<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('recently_viewed', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $t->string('session_id')->nullable()->index();
            $t->morphs('viewable');
            $t->timestamp('viewed_at')->useCurrent();
            $t->index(['user_id', 'viewable_id', 'viewable_type']);
        });
    }
    public function down(): void { Schema::dropIfExists('recently_viewed'); }
};