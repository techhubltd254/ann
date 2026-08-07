<?php

namespace Database\Seeders;

use App\Models\County;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MarketplaceSeeder extends Seeder
{
    public function run(): void
    {
        $cats = collect([
            ['name' => 'Agriculture & Produce', 'slug' => 'agriculture-produce', 'icon' => 'farm', 'sort_order' => 1],
            ['name' => 'Crafts & Artisan', 'slug' => 'crafts-artisan', 'icon' => 'craft', 'sort_order' => 2],
            ['name' => 'Textiles & Apparel', 'slug' => 'textiles-apparel', 'icon' => 'shirt', 'sort_order' => 3],
            ['name' => 'Food & Beverage', 'slug' => 'food-beverage', 'icon' => 'food', 'sort_order' => 4],
            ['name' => 'Home & Decor', 'slug' => 'home-decor', 'icon' => 'home', 'sort_order' => 5],
        ])->mapWithKeys(fn ($c) => [
            $c['slug'] => ProductCategory::updateOrCreate(['slug' => $c['slug']], $c + ['is_active' => true]),
        ]);

        $items = [
            ['county' => 'kilifi', 'cat' => 'agriculture-produce', 'name' => 'Kilifi Cashew Nuts (Raw)', 'desc' => 'Premium sun-dried cashew nuts from smallholder farms in Kilifi County. Rich, buttery taste.', 'unit' => 'kg', 'variants' => [['name' => '1 kg pack', 'price' => 950, 'compare' => 1200], ['name' => '500 g pack', 'price' => 520]], 'featured' => true],
            ['county' => 'mombasa', 'cat' => 'food-beverage', 'name' => 'Mombasa Spice Blend (Pilau Masala)', 'desc' => 'Authentic Swahili pilau masala ground fresh at Kongowea spice market. Cardamom, cumin, cloves, cinnamon.', 'unit' => 'jar', 'variants' => [['name' => '250 g jar', 'price' => 450], ['name' => '500 g jar', 'price' => 800]], 'featured' => true],
            ['county' => 'kisii', 'cat' => 'crafts-artisan', 'name' => 'Kisii Soapstone Sculpture', 'desc' => 'Hand-carved soapstone by Gusii artisans of Tabaka. Each piece is unique.', 'unit' => 'piece', 'variants' => [['name' => 'Small (15 cm)', 'price' => 1500], ['name' => 'Large (30 cm)', 'price' => 3800]], 'featured' => true],
            ['county' => 'narok', 'cat' => 'crafts-artisan', 'name' => 'Maasai Beaded Necklace', 'desc' => 'Traditional Maasai beadwork made by women cooperatives in Narok. Ceremonial patterns.', 'unit' => 'piece', 'variants' => [['name' => 'Single strand', 'price' => 1200], ['name' => 'Triple strand', 'price' => 2600]]],
            ['county' => 'nyeri', 'cat' => 'agriculture-produce', 'name' => 'Nyeri AA Coffee Beans', 'desc' => 'High-altitude AA-grade Arabica from the slopes of Mt. Kenya. Washed process, bright acidity.', 'unit' => 'kg', 'variants' => [['name' => '1 kg bag', 'price' => 1400, 'compare' => 1650], ['name' => '250 g bag', 'price' => 420]]],
            ['county' => 'kericho', 'cat' => 'agriculture-produce', 'name' => 'Kericho Purple Tea', 'desc' => 'Rare purple tea cultivar grown only in Kericho highlands. Antioxidant-rich, smooth finish.', 'unit' => 'pack', 'variants' => [['name' => '100 g pack', 'price' => 900]]],
            ['county' => 'kitui', 'cat' => 'food-beverage', 'name' => 'Kitui Wild Honey', 'desc' => 'Raw, unfiltered honey from acacia woodlands of Kitui. Harvested by community beekeepers.', 'unit' => 'jar', 'variants' => [['name' => '500 g jar', 'price' => 650], ['name' => '1 kg jar', 'price' => 1150]]],
            ['county' => 'lamu', 'cat' => 'home-decor', 'name' => 'Lamu Swahili Carved Door Frame', 'desc' => 'Hand-carved mvule hardwood frame in the Lamu style by island master carpenters.', 'unit' => 'piece', 'variants' => [['name' => 'Standard (60 cm)', 'price' => 18500]]],
            ['county' => 'turkana', 'cat' => 'crafts-artisan', 'name' => 'Turkana Woven Basket (Ekiongo)', 'desc' => 'Durable palm-leaf basket woven by Turkana women. Natural dyes, traditional patterns.', 'unit' => 'piece', 'variants' => [['name' => 'Medium', 'price' => 1800], ['name' => 'Large', 'price' => 2900]]],
            ['county' => 'kajiado', 'cat' => 'textiles-apparel', 'name' => 'Maasai Shuka Blanket', 'desc' => 'Genuine Maasai shuka in classic red check. Heavyweight cotton, all-weather wrap.', 'unit' => 'piece', 'variants' => [['name' => 'Single', 'price' => 900], ['name' => 'Pair', 'price' => 1600]]],
            ['county' => 'kisumu', 'cat' => 'food-beverage', 'name' => 'Lake Victoria Tilapia (Sun-Dried)', 'desc' => 'Sun-dried tilapia from Dunga beach, Kisumu. Clean-packed for long shelf life.', 'unit' => 'pack', 'variants' => [['name' => '500 g pack', 'price' => 750]]],
            ['county' => 'uasin-gishu', 'cat' => 'agriculture-produce', 'name' => 'Eldoret White Maize (Dry)', 'desc' => 'Grade-1 dry white maize from Uasin Gishu plateau farms. Milling quality.', 'unit' => 'bag', 'variants' => [['name' => '10 kg bag', 'price' => 700], ['name' => '25 kg bag', 'price' => 1550]]],
            ['county' => 'kakamega', 'cat' => 'food-beverage', 'name' => 'Kakamega Banana Crisps', 'desc' => 'Crispy dried banana chips from Western Kenya. No added sugar, kettle-cooked.', 'unit' => 'pack', 'variants' => [['name' => '200 g pack', 'price' => 350]]],
            ['county' => 'baringo', 'cat' => 'crafts-artisan', 'name' => 'Baringo Sisal Kiondo Bag', 'desc' => 'Handwoven sisal kiondo with leather straps from Baringo weavers. Everyday carry.', 'unit' => 'piece', 'variants' => [['name' => 'Classic', 'price' => 2200]]],
        ];

        foreach ($items as $i => $data) {
            $county = County::where('slug', $data['county'])->first();
            if (!$county) continue;

            $product = Product::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'county_id' => $county->id,
                    'category_id' => $cats[$data['cat']]->id,
                    'name' => $data['name'],
                    'description' => $data['desc'],
                    'short_description' => Str::limit($data['desc'], 120),
                    'sku' => 'KICC-' . strtoupper(Str::random(6)),
                    'unit' => $data['unit'],
                    'status' => 'active',
                    'is_featured' => $data['featured'] ?? false,
                ]
            );

            foreach ($data['variants'] as $vi => $v) {
                $product->variants()->updateOrCreate(
                    ['name' => $v['name']],
                    [
                        'sku' => $product->sku . '-V' . ($vi + 1),
                        'price' => $v['price'],
                        'compare_at_price' => $v['compare'] ?? null,
                        'stock' => rand(15, 120),
                        'is_active' => true,
                        'sort_order' => $vi,
                        'image_url' => media("counties/{$county->slug}/products.jpeg"),
                    ]
                );
            }

            $product->images()->firstOrCreate(
                ['url' => media("counties/{$county->slug}/products.jpeg")],
                ['alt_text' => $product->name, 'sort_order' => 0, 'is_primary' => true]
            );
        }

        $this->command->info('Seeded ' . Product::count() . ' marketplace products with variants.');
    }
}
