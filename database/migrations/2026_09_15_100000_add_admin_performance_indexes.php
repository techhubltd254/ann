<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $statements = [
            "CREATE INDEX IF NOT EXISTS county_institutions_is_published_index ON county_institutions (is_published)",
            "CREATE INDEX IF NOT EXISTS county_tourism_attractions_is_published_index ON county_tourism_attractions (is_published)",
            "CREATE INDEX IF NOT EXISTS county_hotels_is_published_index ON county_hotels (is_published)",
            "CREATE INDEX IF NOT EXISTS county_products_is_published_index ON county_products (is_published)",
            "CREATE INDEX IF NOT EXISTS sector_entities_entity_type_index ON sector_entities (entity_type)",
            "CREATE INDEX IF NOT EXISTS sector_entities_is_published_index ON sector_entities (is_published)",
            "CREATE INDEX IF NOT EXISTS county_sector_display_on_tile_index ON county_sector (display_on_tile)",
            "CREATE INDEX IF NOT EXISTS county_sector_county_sector_index ON county_sector (county_id, sector_id)",
        ];
        foreach ($statements as $sql) {
            try { DB::statement($sql); } catch (\Throwable $e) {}
        }
    }
    public function down(): void {}
};