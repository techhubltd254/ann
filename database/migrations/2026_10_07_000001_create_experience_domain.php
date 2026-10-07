<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::table('users',fn(Blueprint $t)=>$t->boolean('is_admin')->default(false));
  Schema::create('records',function(Blueprint $t){$t->uuid('id')->primary();$t->string('type',32)->index();$t->string('slug',180);$t->string('name',240);$t->longText('description')->nullable();$t->string('status',16)->default('draft')->index();$t->json('payload')->nullable();$t->uuid('parent_id')->nullable();$t->unsignedInteger('revision')->default(1);$t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();$t->timestamps();$t->unique(['type','slug']);$t->foreign('parent_id')->references('id')->on('records')->nullOnDelete();});
  Schema::create('media_assets',function(Blueprint $t){$t->uuid('id')->primary();$t->uuid('record_id');$t->foreign('record_id')->references('id')->on('records')->cascadeOnDelete();$t->string('disk',16);$t->string('path');$t->string('original_name');$t->string('mime',120);$t->unsignedBigInteger('bytes');$t->string('title',240);$t->text('description')->nullable();$t->string('status',16)->default('draft')->index();$t->string('format',16)->default('standard');$t->timestamps();});
  Schema::create('audit_events',function(Blueprint $t){$t->id();$t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();$t->string('action',80);$t->string('subject_id',64)->nullable();$t->json('details')->nullable();$t->timestamp('created_at')->useCurrent();});
  Schema::create('enquiries',function(Blueprint $t){$t->uuid('id')->primary();$t->uuid('record_id');$t->foreign('record_id')->references('id')->on('records')->cascadeOnDelete();$t->string('name',180);$t->string('email',240);$t->string('phone',60)->nullable();$t->text('message');$t->string('status',16)->default('new');$t->timestamps();});
 }
 public function down():void {Schema::dropIfExists('enquiries');Schema::dropIfExists('audit_events');Schema::dropIfExists('media_assets');Schema::dropIfExists('records');Schema::table('users',fn(Blueprint $t)=>$t->dropColumn('is_admin'));}
};
