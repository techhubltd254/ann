<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('agency_data_sources')) {
            Schema::create('agency_data_sources', function (Blueprint $t) {
                $t->id();
                $t->string('code', 20)->unique();
                $t->string('name');
                $t->string('endpoint')->nullable();
                $t->string('auth_type', 30)->nullable();
                $t->json('config')->nullable();
                $t->boolean('is_active')->default(false);
                $t->timestamps();
            });
            $sources = [
                ['CBK','Central Bank of Kenya','api_key'],
                ['IFMIS','Integrated Financial Management System','oauth2'],
                ['KEPHIS','Kenya Plant Health Inspectorate','api_key'],
                ['KWS','Kenya Wildlife Service','api_key'],
                ['ARDHISASA','Ministry of Lands — Ardhisasa','api_key'],
                ['SEZA','Special Economic Zones Authority','api_key'],
                ['IATA','International Air Transport Association','api_key'],
                ['TALA','Tourist Agents Licensing Authority','api_key'],
                ['KRA','Kenya Revenue Authority','api_key'],
            ];
            foreach ($sources as [$code, $name, $auth]) {
                DB::table('agency_data_sources')->insert([
                    'code' => $code, 'name' => $name, 'auth_type' => $auth,
                    'config' => json_encode(['endpoint' => null, 'credentials' => null]),
                    'is_active' => false, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
        if (!Schema::hasTable('agency_data_events')) {
            Schema::create('agency_data_events', function (Blueprint $t) {
                $t->id();
                $t->string('agency_code', 20)->index();
                $t->string('pipeline_code', 20)->index();
                $t->string('event_type', 50);
                $t->json('payload');
                $t->string('status', 20)->default('received');
                $t->json('response')->nullable();
                $t->timestamp('processed_at')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_data_events');
        Schema::dropIfExists('agency_data_sources');
    }
};
