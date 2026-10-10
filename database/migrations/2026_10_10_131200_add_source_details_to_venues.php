<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {if(!Schema::hasColumn('venues','source_details'))Schema::table('venues',fn(Blueprint $t)=>$t->json('source_details')->nullable());}
 public function down():void {if(Schema::hasColumn('venues','source_details'))Schema::table('venues',fn(Blueprint $t)=>$t->dropColumn('source_details'));}
};
