<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  if(!Schema::hasTable('experience_records'))Schema::create('experience_records',function(Blueprint $t){$t->string('id',190)->primary();$t->string('entity_type',32)->index();$t->string('status',16)->index();$t->json('payload');$t->unsignedInteger('revision')->default(1);$t->string('actor_id',190)->nullable();$t->timestamps();});
  if(!Schema::hasTable('experience_assets'))Schema::create('experience_assets',function(Blueprint $t){$t->uuid('id')->primary();$t->string('path');$t->string('mime',100);$t->string('kind',16);$t->string('name');$t->unsignedBigInteger('bytes');$t->string('sha256',64);$t->string('actor_id',190);$t->timestamps();});
  if(!Schema::hasTable('experience_audit'))Schema::create('experience_audit',function(Blueprint $t){$t->bigIncrements('id');$t->string('actor_id',190)->nullable();$t->string('action',32);$t->string('entity_type',32);$t->string('record_id',190)->nullable();$t->json('detail')->nullable();$t->timestamp('created_at')->useCurrent();});
 }
 public function down():void {Schema::dropIfExists('experience_audit');Schema::dropIfExists('experience_assets');Schema::dropIfExists('experience_records');}
};
