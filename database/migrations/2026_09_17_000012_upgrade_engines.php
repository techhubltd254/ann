<?php

/*
|--------------------------------------------------------------------------
| Engine upgrade: gl_periods + default chart of accounts
|--------------------------------------------------------------------------
| Idempotent (hasTable / updateOrInsert guards). Never drops anything.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('gl_periods')) {
            Schema::create('gl_periods', function (Blueprint $table) {
                $table->id();
                $table->date('period_start')->unique();
                $table->date('period_end');
                $table->decimal('total_debits', 18, 2)->default(0);
                $table->decimal('total_credits', 18, 2)->default(0);
                $table->string('status')->default('open');
                $table->dateTime('closed_at')->nullable();
                $table->timestamps();
            });
        }

        // default chart of accounts used by LedgerService postings
        $accounts = [
            ['code' => 'escrow_clearing',    'name' => 'Escrow Clearing',          'type' => 'asset'],
            ['code' => 'customer_payable',   'name' => 'Customer Payable',         'type' => 'liability'],
            ['code' => 'merchant_payable',   'name' => 'Merchant Payable',         'type' => 'liability'],
            ['code' => 'platform_revenue',   'name' => 'Platform Revenue',         'type' => 'revenue'],
            ['code' => 'fees_income',        'name' => 'Fees Income',              'type' => 'revenue'],
            ['code' => 'holdback_reserve',   'name' => 'Holdback Reserve (10%)',   'type' => 'liability'],
            ['code' => 'equalisation_reserve', 'name' => 'Equalisation Reserve (0.5%)', 'type' => 'liability'],
            ['code' => 'treasury',           'name' => 'Treasury',                 'type' => 'asset'],
        ];
        foreach ($accounts as $a) {
            if (!Schema::hasTable('gl_accounts')) continue; // core migration must run first
            $exists = DB::table('gl_accounts')->where('code', $a['code'])->exists();
            if (!$exists) {
                DB::table('gl_accounts')->insert($a + ['currency' => 'KES', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gl_periods');
    }
};
