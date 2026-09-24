<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('search_analytics')) return;

        Schema::create('search_analytics', function (Blueprint $table) {
            $table->id();
            $table->string('query', 200)->index();
            $table->string('engine', 30)->default('direct');  // google, bing, direct, etc.
            $table->string('source', 50)->default('direct');   // utm_source
            $table->string('medium', 30)->default('none');     // utm_medium
            $table->string('campaign', 100)->nullable();       // utm_campaign
            $table->string('country', 5)->nullable();          // CF-IPCountry
            $table->string('city', 100)->nullable();           // CF-IPCity
            $table->string('landing_page', 500)->nullable();   // first page they hit
            $table->boolean('converted')->default(false);      // did they place an order?
            $table->string('pipeline', 20)->nullable();        // which pipeline the search mapped to
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_analytics');
    }
};