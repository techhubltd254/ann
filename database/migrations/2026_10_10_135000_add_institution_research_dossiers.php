<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration {public function up():void {Schema::table('county_institutions',function(Blueprint $t){$t->text('expected_video_description')->nullable();$t->json('research_dossier')->nullable();});}public function down():void {Schema::table('county_institutions',fn(Blueprint $t)=>$t->dropColumn(['expected_video_description','research_dossier']));}};
