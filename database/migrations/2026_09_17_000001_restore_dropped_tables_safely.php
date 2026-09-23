<?php

/*
|--------------------------------------------------------------------------
| RESTORE of tables dropped in the 2026-09-13 TiDB incident (idempotent — guarded with hasTable, never drops or overwrites existing tables)
|--------------------------------------------------------------------------
| Generated for the KICC Pipeline Kit. Every table is guarded with
| Schema::hasTable() so this migration is IDEMPOTENT and non-destructive:
| existing tables (including anything already live in TiDB) are never touched.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // RESTORE — dropped in the 2026-09-13 TiDB incident (name verified from restore commit)
        if (!Schema::hasTable('product_questions')) {
            Schema::create('product_questions', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('user_id')->index()->nullable();
            $table->text('question');
            $table->text('answer')->nullable();
            $table->dateTime('answered_at')->nullable();
            $table->string('status')->default('open');
                $table->timestamps();
            });
        }

        // RESTORE — dropped ecommerce table
        if (!Schema::hasTable('wishlists')) {
            Schema::create('wishlists', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('user_id')->index()->nullable();
            $table->string('session_id')->nullable();
            $table->string('name')->default('My wishlist');
                $table->timestamps();
            });
        }

        // RESTORE — dropped ecommerce table
        if (!Schema::hasTable('wishlist_items')) {
            Schema::create('wishlist_items', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('wishlist_id')->index();
            $table->unsignedBigInteger('product_id')->index();
                $table->timestamps();
            });
        }

        // RESTORE — dropped ecommerce table
        if (!Schema::hasTable('rfqs')) {
            Schema::create('rfqs', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('user_id')->index()->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('category_id')->index()->nullable();
            $table->decimal('budget', 18, 2)->nullable();
            $table->date('deadline')->nullable();
            $table->string('status')->default('open');
                $table->timestamps();
            });
        }

        // RESTORE — dropped ecommerce table
        if (!Schema::hasTable('rfq_items')) {
            Schema::create('rfq_items', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('rfq_id')->index();
            $table->unsignedBigInteger('product_id')->index()->nullable();
            $table->string('description')->nullable();
            $table->unsignedBigInteger('quantity')->default(1);
            $table->string('unit')->nullable();
                $table->timestamps();
            });
        }

        // RESTORE — dropped ecommerce table
        if (!Schema::hasTable('rfq_quotes')) {
            Schema::create('rfq_quotes', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('rfq_id')->index();
            $table->unsignedBigInteger('seller_user_id')->index()->nullable();
            $table->decimal('quote_amount', 18, 2)->default(0);
            $table->date('valid_until')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_questions');
        Schema::dropIfExists('wishlists');
        Schema::dropIfExists('wishlist_items');
        Schema::dropIfExists('rfqs');
        Schema::dropIfExists('rfq_items');
        Schema::dropIfExists('rfq_quotes');
    }
};
