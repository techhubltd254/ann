<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {Schema::table('media_assets',function(Blueprint $table){$table->string('slot',191)->nullable()->change();});}
 // Widening is deliberately retained on rollback: shrinking can destroy live slots.
 public function down():void {}
};
