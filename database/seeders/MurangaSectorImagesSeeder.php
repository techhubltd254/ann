<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\County;
use App\Models\CountyTourismAttraction;
use App\Models\CountyHotel;
use App\Models\CountyProduct;

/**
 * Murang'a sector images — every image was visually verified against the
 * SSSS location shoot (kenya-3d-platform/SSSS) and the county's original
 * attributed photo library (storage/app/public/counties/muranga).
 *
 * Mapping rationale (image decides the entity, not the other way round):
 *  - hero/waterfall.jpg  = Mugumo-ini Falls (tall single-drop jungle falls)
 *  - muranga/farms.jpeg  = Maragua waterfall (wide cascade) — "Murang'a Waterfall"
 *  - hero/rafting.jpg    = Sagana rafting site gate (Rafting Worldcup Series banner)
 *  - hero/tea-farms.jpg  = tea highlands terraces
 *  - hero/forest.jpg     = Aberdare-edge highland forest
 *  - muranga/tourism.jpeg= Murang'a hills panorama (Wikimedia COSV)
 *  - hero/hotel.jpg      = Eliper Hotel building (signage visible)
 *  - hero/market.jpg     = Murang'a town market street
 */
class MurangaSectorImagesSeeder extends Seeder
{
    public function run(): void
    {
        $county = County::where('slug', 'muranga')->first();
        if (!$county) return;

        $hero = '/storage/kicc/hero';
        $cty  = '/storage/counties/muranga';

        $attractionImages = [
            'Aberdare Forest Trails'       => "$hero/forest.jpg",
            'Aberdare Canopy Walk'         => "$hero/forest.jpg",
            'Mugumo-ini Falls'             => "$hero/waterfall.jpg",
            'Mugumo-ini Falls Canyoning'   => "$hero/waterfall.jpg",
            'Murang\'a Waterfall'          => "$cty/farms.jpeg",
            'Sagana Rafting Experience'    => "$hero/rafting.jpg",
            'Sagana River Trek'            => "$hero/rafting.jpg",
            'Tea Highlands Tour'           => "$hero/tea-farms.jpg",
            'Murang\'a Hills Viewpoint'    => "$cty/tourism.jpeg",
            'Highland Garden Retreat'      => "$hero/tea-farms.jpg",
        ];
        foreach ($attractionImages as $name => $img) {
            CountyTourismAttraction::where('county_id', $county->id)->where('name', $name)
                ->update(['image_url' => $img]);
        }

        $hotelImages = [
            'Eliper Hotel & Restaurant'          => "$hero/hotel.jpg",
            'Murang\'a Hotel'                   => "$hero/market.jpg",
            'Murang\'a Guest House'             => "$hero/market.jpg",
            'Murang\'a Resort'                  => "$hero/forest.jpg",
            'Super Hotel'                       => "$hero/market.jpg",
            'Highland Conference & Events Centre' => "$hero/market.jpg",
            'Garden Court Restaurant & Lounge'  => "$hero/market.jpg",
        ];
        foreach ($hotelImages as $name => $img) {
            CountyHotel::where('county_id', $county->id)->where('name', $name)
                ->update(['image_url' => $img]);
        }

        $productImages = [
            'Murang\'a Tea'           => "$hero/tea-farms.jpg",
            'Murang\'a Coffee'        => "$hero/forest.jpg",
            'Murang\'a Honey'         => "$hero/forest.jpg",
            'Murang\'a Fresh Produce' => "$hero/market.jpg",
            'Murang\'a Textiles'      => "$hero/market.jpg",
            'Murang\'a Beans'         => "$cty/products.jpeg",
            'Murang\'a Town Market'   => "$hero/market.jpg",
        ];
        foreach ($productImages as $name => $img) {
            CountyProduct::where('county_id', $county->id)->where('name', $name)
                ->update(['image_url' => $img]);
        }
    }
}
