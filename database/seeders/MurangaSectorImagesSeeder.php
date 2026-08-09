<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\County;
use App\Models\CountyTourismAttraction;
use App\Models\CountyHotel;
use App\Models\CountyProduct;

/**
 * Murang'a sector images — every image visually verified against the SSSS
 * location shoot. Primary source = the county's own ID-keyed library
 * (storage/app/public/counties/muranga/{sector}/{entity_id}.jpeg).
 * Fallbacks = SSSS-derived labelled hero shots + attributed Wikimedia set.
 */
class MurangaSectorImagesSeeder extends Seeder
{
    public function run(): void
    {
        $county = County::where('slug', 'muranga')->first();
        if (!$county) return;

        $pub = public_path('storage/counties/muranga');
        $url = '/storage/counties/muranga';
        $hero = '/storage/kicc/hero';

        // Attractions: ID-keyed library first, then verified fallback
        $attractionFallback = [
            'Murang\'a Waterfall' => "$url/farms.jpeg", // Maragua waterfall (attributed)
        ];
        foreach (CountyTourismAttraction::where('county_id', $county->id)->get() as $a) {
            $idKeyed = "{$pub}/tourism/{$a->id}.jpeg";
            if (file_exists($idKeyed)) {
                $a->image_url = "{$url}/tourism/{$a->id}.jpeg";
            } elseif (isset($attractionFallback[$a->name])) {
                $a->image_url = $attractionFallback[$a->name];
            }
            $a->save();
        }

        // Hotels: Eliper has its real photo; town hotels get the town market shot
        $hotelImages = [
            'Eliper Hotel & Restaurant'          => "$hero/hotel.jpg",   // actual Eliper building
            'Murang\'a Resort'                  => "$hero/forest.jpg",  // highland retreat setting
            'Murang\'a Hotel'                   => "$hero/market.jpg",
            'Murang\'a Guest House'             => "$hero/market.jpg",
            'Super Hotel'                       => "$hero/market.jpg",
            'Highland Conference & Events Centre' => "$hero/market.jpg",
            'Garden Court Restaurant & Lounge'  => "$hero/market.jpg",
        ];
        foreach ($hotelImages as $name => $img) {
            CountyHotel::where('county_id', $county->id)->where('name', $name)
                ->update(['image_url' => $img]);
        }

        // Products: ID-keyed first (Town Market=284), else verified fallbacks
        $productFallback = [
            'Murang\'a Tea'           => "$hero/tea-farms.jpg",
            'Murang\'a Coffee'        => "$hero/forest.jpg",
            'Murang\'a Honey'         => "$hero/forest.jpg",
            'Murang\'a Fresh Produce' => "$hero/market.jpg",
            'Murang\'a Textiles'      => "$hero/market.jpg",
            'Murang\'a Beans'         => "$url/products.jpeg", // Maragua bridge (attributed)
        ];
        foreach (CountyProduct::where('county_id', $county->id)->get() as $p) {
            $idKeyed = "{$pub}/products/{$p->id}.jpeg";
            if (file_exists($idKeyed)) {
                $p->image_url = "{$url}/products/{$p->id}.jpeg";
            } elseif (isset($productFallback[$p->name])) {
                $p->image_url = $productFallback[$p->name];
            }
            $p->save();
        }
    }
}
