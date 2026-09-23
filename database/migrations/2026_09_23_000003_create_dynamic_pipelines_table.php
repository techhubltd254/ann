<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dynamic_pipelines')) return;

        Schema::create('dynamic_pipelines', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 200);
            $table->string('slug', 200)->nullable();
            $table->string('sector', 100)->index();
            $table->text('description')->nullable();
            $table->string('mechanism', 50)->default('commission');
            $table->decimal('fee_rate', 5, 2)->default(4.00);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['sector', 'is_active']);
        });

        // Seed Milk & Dairy pipeline family
        $now = now();
        $dairy = [
            ['code' => 'DA1', 'name' => 'Milk Collection (Raw)', 'slug' => 'milk-collection-raw', 'sector' => 'milk-dairy', 'mechanism' => 'commission', 'fee_rate' => 3.00, 'description' => 'Bulk raw milk collection from smallholder farmers via cooling centres'],
            ['code' => 'DA2', 'name' => 'Milk Pasteurisation & Processing', 'slug' => 'milk-pasteurisation-processing', 'sector' => 'milk-dairy', 'mechanism' => 'commission', 'fee_rate' => 4.00, 'description' => 'Pasteurised fresh milk, UHT milk, and extended shelf-life processing'],
            ['code' => 'DA3', 'name' => 'Yoghurt & Fermented Products', 'slug' => 'yoghurt-fermented', 'sector' => 'milk-dairy', 'mechanism' => 'commission', 'fee_rate' => 5.00, 'description' => 'Drinking yoghurt, Greek yoghurt, traditional mursik, lala, and other fermented dairy'],
            ['code' => 'DA4', 'name' => 'Cheese Production', 'slug' => 'cheese-production', 'sector' => 'milk-dairy', 'mechanism' => 'commission', 'fee_rate' => 5.00, 'description' => 'Gouda, cheddar, mozzarella, cottage cheese, and traditional ripened cheese varieties'],
            ['code' => 'DA5', 'name' => 'Butter & Ghee', 'slug' => 'butter-ghee', 'sector' => 'milk-dairy', 'mechanism' => 'commission', 'fee_rate' => 4.00, 'description' => 'Creamery butter, clarified butter (ghee), anhydrous milk fat for cooking and export'],
            ['code' => 'DA6', 'name' => 'Ice Cream & Frozen Desserts', 'slug' => 'ice-cream-frozen', 'sector' => 'milk-dairy', 'mechanism' => 'commission', 'fee_rate' => 6.00, 'description' => 'Premium ice cream, gelato, frozen yoghurt, dairy-based popsicles and novelties'],
            ['code' => 'DA7', 'name' => 'Milk Powder & Concentrates', 'slug' => 'milk-powder-concentrates', 'sector' => 'milk-dairy', 'mechanism' => 'commission', 'fee_rate' => 4.00, 'description' => 'Whole milk powder, skimmed milk powder, evaporated milk, condensed milk for wholesale and food service'],
            ['code' => 'DA8', 'name' => 'Infant & Nutritional Formula', 'slug' => 'infant-nutritional-formula', 'sector' => 'milk-dairy', 'mechanism' => 'licence_gated', 'fee_rate' => 5.00, 'description' => 'Follow-on formula, growing-up milk, maternal nutrition — licence-gated by KEBS/PHO'],
            ['code' => 'DA9', 'name' => 'Whey & By-Product Utilisation', 'slug' => 'whey-byproduct', 'sector' => 'milk-dairy', 'mechanism' => 'commission', 'fee_rate' => 3.00, 'description' => 'Whey protein concentrate, whey powder, lactose, casein, and other dairy co-product streams'],
            ['code' => 'DA10', 'name' => 'Dairy Equipment & Cold Chain', 'slug' => 'dairy-equipment-coldchain', 'sector' => 'milk-dairy', 'mechanism' => 'commission', 'fee_rate' => 4.00, 'description' => 'Milking machines, bulk milk coolers, refrigerated transport, cold storage for dairy value chain'],
            ['code' => 'DA11', 'name' => 'Animal Feed & Fodder (Dairy Inputs)', 'slug' => 'animal-feed-fodder', 'sector' => 'milk-dairy', 'mechanism' => 'commission', 'fee_rate' => 3.00, 'description' => 'Dairy cattle feed formulations, haylage, silage, mineral supplements, and fodder trading'],
            ['code' => 'DA12', 'name' => 'Veterinary & AI Services', 'slug' => 'veterinary-ai-services', 'sector' => 'milk-dairy', 'mechanism' => 'flat_fee', 'fee_rate' => 2.00, 'description' => 'Veterinary consultations, artificial insemination, herd health, and disease testing for dairy farmers'],
        ];

        foreach ($dairy as $row) {
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
            DB::table('dynamic_pipelines')->insert($row);

            // Also register in pipeline_registrations for unified status reporting
            $existing = DB::table('pipeline_registrations')->where('code', $row['code'])->first();
            if (!$existing) {
                DB::table('pipeline_registrations')->insert([
                    'code' => $row['code'],
                    'parent' => 'DA',
                    'sector' => $row['sector'],
                    'slug' => $row['slug'],
                    'phase' => 3,
                    'status' => 'built',
                    'economics' => json_encode([
                        'model' => $row['mechanism'],
                        'take_rate_pct' => $row['fee_rate'],
                        'description' => $row['description'],
                    ]),
                    'regulators' => json_encode([]),
                    'tables' => json_encode([]),
                    'kill_criteria' => json_encode([]),
                    'earning_locked' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dynamic_pipelines');
    }
};