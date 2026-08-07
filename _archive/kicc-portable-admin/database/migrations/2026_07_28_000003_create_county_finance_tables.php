<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'county_financial_config' => function (Blueprint $t) {
                $t->id(); $t->foreignId('county_id')->constrained()->cascadeOnDelete();
                $t->decimal('revenue_share_pct', 5, 2)->default(70);
                $t->decimal('wallet_balance', 14, 2)->default(0);
                $t->decimal('lifetime_earnings', 14, 2)->default(0);
                $t->decimal('total_payouts', 14, 2)->default(0);
                $t->decimal('min_payout', 10, 2)->default(1000);
                $t->integer('settlement_day')->default(5);
                $t->timestamps();
            },
            'county_wallet_transactions' => function (Blueprint $t) {
                $t->id(); $t->foreignId('county_id')->constrained()->cascadeOnDelete();
                $t->decimal('amount', 14, 2); $t->string('type'); // credit/debit
                $t->string('description'); $t->string('status')->default('completed');
                $t->nullableMorphs('reference'); $t->decimal('running_balance', 14, 2)->default(0);
                $t->timestamps();
            },
            'county_bulk_slot_allocations' => function (Blueprint $t) {
                $t->id(); $t->foreignId('county_id')->constrained()->cascadeOnDelete();
                $t->integer('total_slots')->default(0); $t->integer('used_slots')->default(0);
                $t->string('tier')->default('basic'); $t->timestamps();
            },
            'county_subscribers' => function (Blueprint $t) {
                $t->id(); $t->foreignId('county_id')->constrained()->cascadeOnDelete();
                $t->foreignId('user_id')->constrained()->cascadeOnDelete();
                $t->foreignId('plan_id')->nullable()->constrained('county_subscription_plans')->nullOnDelete();
                $t->foreignId('slot_id')->nullable();
                $t->dateTime('starts_at'); $t->dateTime('ends_at')->nullable();
                $t->string('status')->default('active'); $t->timestamps();
            },
        ];

        foreach ($tables as $name => $schema) {
            if (!Schema::hasTable($name)) {
                Schema::create($name, $schema);
                echo "  Created: $name\n";
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('county_subscribers');
        Schema::dropIfExists('county_bulk_slot_allocations');
        Schema::dropIfExists('county_wallet_transactions');
        Schema::dropIfExists('county_financial_config');
    }
};
