<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add sector column to product_categories
        if (Schema::hasTable('product_categories') && ! Schema::hasColumn('product_categories', 'sector')) {
            Schema::table('product_categories', function (Blueprint $table) {
                $table->string('sector', 50)->nullable()->after('slug')->index();
            });
        }

        // Add pipeline_code column to order_items
        if (Schema::hasTable('order_items') && ! Schema::hasColumn('order_items', 'pipeline_code')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->string('pipeline_code', 20)->nullable()->after('total')->index();
            });
        }

        // Seed sector values for existing categories from their name
        if (Schema::hasTable('product_categories')) {
        $resolver = new \App\Services\PipelineResolver();
        foreach ($categories as $cat) {
            if (empty($cat->sector)) {
                $fakeCategory = new \App\Models\Marketplace\ProductCategory();
                $fakeCategory->name = $cat->name;
                $fakeCategory->slug = $cat->slug ?? $cat->name;
                $sector = $resolver->forCategory($fakeCategory);
                // Map to known sectors via reflection or direct check
                $sectorMap = [
                    'Agriculture' => 'agriculture', 'Farm' => 'agriculture', 'Food' => 'agriculture',
                    'Tourism' => 'tourism', 'Travel' => 'tourism', 'Hotel' => 'tourism',
                    'Energy' => 'energy', 'Power' => 'energy',
                    'Health' => 'health', 'Medical' => 'health',
                    'Education' => 'education', 'School' => 'education',
                    'Creative' => 'creative', 'Media' => 'creative', 'Film' => 'creative',
                    'Transport' => 'mobility', 'Logistics' => 'mobility',
                    'Finance' => 'financing', 'Insurance' => 'financing',
                    'Government' => 'government', 'County' => 'government',
                    'Dairy' => 'milk-dairy', 'Milk' => 'milk-dairy',
                ];
                $found = null;
                foreach ($sectorMap as $keyword => $s) {
                    if (stripos($cat->name, $keyword) !== false || stripos($cat->slug ?? '', $keyword) !== false) {
                        $found = $s; break;
                    }
                }
                DB::table('product_categories')->where('id', $cat->id)->update([
                    'sector' => $found ?? 'trade',
                ]);
            }
        }
        } // end if hasTable
    }

    public function down(): void
    {
        if (Schema::hasColumn('product_categories', 'sector')) {
            Schema::table('product_categories', function (Blueprint $table) {
                $table->dropColumn('sector');
            });
        }
        if (Schema::hasColumn('order_items', 'pipeline_code')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropColumn('pipeline_code');
            });
        }
    }
};