<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ministries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('code')->unique();
            $table->string('logo')->nullable();
            $table->string('color')->default('#0B1E57');
            $table->text('description')->nullable();
            $table->string('website')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('agencies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ministry_id')->index();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('code')->unique();
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->string('website')->nullable();
            $table->string('contact_email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ministry_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ministry_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('role')->default('admin');
            $table->timestamps();
            $table->unique(['ministry_id', 'user_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('ministry_id')->nullable()->after('county_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('ministry_id');
        });
        Schema::dropIfExists('ministry_user');
        Schema::dropIfExists('agencies');
        Schema::dropIfExists('ministries');
    }
};
