<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            if (!Schema::hasColumn('users', 'verification_tier')) $t->tinyInteger('verification_tier')->default(0)->after('trust_grade');
            if (!Schema::hasColumn('users', 'verification_status')) $t->string('verification_status', 30)->nullable()->after('verification_tier');
            if (!Schema::hasColumn('users', 'verification_document_path')) $t->string('verification_document_path')->nullable()->after('verification_status');
            if (!Schema::hasColumn('users', 'verification_rejection_reason')) $t->text('verification_rejection_reason')->nullable()->after('verification_document_path');
            if (!Schema::hasColumn('users', 'verified_at')) $t->timestamp('verified_at')->nullable()->after('verification_rejection_reason');
            if (!Schema::hasColumn('users', 'verified_by')) $t->foreignId('verified_by')->nullable()->constrained('users')->after('verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn(['verification_tier', 'verification_status', 'verification_document_path', 'verification_rejection_reason', 'verified_at', 'verified_by']);
        });
    }
};
