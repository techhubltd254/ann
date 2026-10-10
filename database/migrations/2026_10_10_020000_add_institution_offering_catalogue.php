<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::table('products',function(Blueprint $t){
   $t->string('offering_kind',20)->default('product')->index();
   $t->string('price_mode',20)->default('fixed');
   $t->string('sync_key',120)->nullable();$t->unique(['institution_id','sync_key']);
   $t->text('source_url')->nullable();$t->timestamp('source_verified_at')->nullable();
   $t->text('booking_url')->nullable();$t->json('offering_details')->nullable();
  });
  Schema::create('institution_experiences',function(Blueprint $t){
   $t->id();$t->unsignedBigInteger('institution_id')->index();$t->unsignedBigInteger('product_id')->unique();
   $t->unsignedInteger('duration_minutes')->nullable();$t->unsignedInteger('max_guests')->nullable();
   $t->json('inclusions')->nullable();$t->json('requirements')->nullable();$t->timestamps();
  });
  Schema::create('institution_offers',function(Blueprint $t){
   $t->id();$t->unsignedBigInteger('institution_id')->index();$t->unsignedBigInteger('product_id')->index();
   $t->string('title');$t->text('terms')->nullable();$t->decimal('price',12,2)->nullable();
   $t->string('source_key',120)->nullable();$t->text('source_url')->nullable();
   $t->timestamp('starts_at')->nullable();$t->timestamp('ends_at')->nullable();
   $t->boolean('is_published')->default(false);$t->timestamps();
   $t->unique(['institution_id','source_key']);
  });
 }
 public function down():void {
  Schema::dropIfExists('institution_offers');Schema::dropIfExists('institution_experiences');
  Schema::table('products',fn(Blueprint $t)=>$t->dropColumn(['offering_kind','price_mode','sync_key','source_url','source_verified_at','booking_url','offering_details']));
 }
};
