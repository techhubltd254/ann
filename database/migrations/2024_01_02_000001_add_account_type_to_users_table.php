<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('account_type')->nullable()->after('email');
            $table->string('phone')->nullable()->after('account_type');
            $table->unsignedInteger('county_id')->nullable()->after('phone');
            $table->string('id_number')->nullable()->after('county_id');
            $table->string('kra_pin')->nullable()->after('id_number');
            $table->string('business_reg')->nullable()->after('kra_pin');
            $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at');
            $table->boolean('mfa_enabled')->default(false)->after('phone_verified_at');
            $table->text('mfa_secret')->nullable()->after('mfa_enabled');
            $table->string('status')->default('active')->after('mfa_secret');
            $table->json('metadata')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'account_type',
                'phone',
                'county_id',
                'id_number',
                'kra_pin',
                'business_reg',
                'phone_verified_at',
                'mfa_enabled',
                'mfa_secret',
                'status',
                'metadata',
            ]);
        });
    }
};
