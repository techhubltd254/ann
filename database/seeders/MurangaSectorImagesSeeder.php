<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\County;
use App\Models\CountyTourismAttraction;
use App\Models\CountyHotel;
use App\Models\CountyProduct;

class MurangaSectorImagesSeeder extends Seeder
{
    public function run(): void
    {
        $county = County::where('slug', 'muranga')->first();
        if (!$county) return;

        $base = '/storage/kicc';

        $attractions = [
            ['name' => 'Aberdare Forest Trails', 'image_url' => "$base/aberdares.jpg", 'category' => 'Nature'],
            ['name' => 'Murang\'a Waterfall', 'image_url' => "$base/aberdare.jpg", 'category' => 'Nature'],
            ['name' => 'Sagana Rafting Experience', 'image_url' => "$base/lawn.jpg", 'category' => 'Adventure'],
            ['name' => 'Tea Highlands Tour', 'image_url' => "$base/lenana-hills.jpg", 'category' => 'Agriculture'],
            ['name' => 'Murang\'a Hills Viewpoint', 'image_url' => "$base/tower-night.jpg", 'category' => 'Nature'],
            ['name' => 'Mugumo-ini Falls', 'image_url' => "$base/aberdare.jpg", 'category' => 'Nature'],
            ['name' => 'Mugumo-ini Falls Canyoning', 'image_url' => "$base/aberdare.jpg", 'category' => 'Adventure'],
            ['name' => 'Sagana River Trek', 'image_url' => "$base/lawn.jpg", 'category' => 'Adventure'],
            ['name' => 'Highland Garden Retreat', 'image_url' => "$base/courtyard.jpg", 'category' => 'Nature'],
        ];

        foreach ($attractions as $data) {
            $record = CountyTourismAttraction::firstOrNew([
                'county_id' => $county->id,
                'name' => $data['name'],
            ]);
            $record->forceFill(array_merge($data, [
                'county_id' => $county->id,
                'countyId' => $county->id,
                'is_published' => true,
                'isPublished' => true,
            ]));
            $record->save();
        }

        $hotels = [
            ['name' => 'Murang\'a Hotel', 'image_url' => "$base/exterior-1.jpg", 'category' => 'Hotel', 'star_rating' => 3],
            ['name' => 'Murang\'a Guest House', 'image_url' => "$base/exterior-2.jpg", 'category' => 'Guest House', 'star_rating' => 2],
            ['name' => 'Murang\'a Resort', 'image_url' => "$base/hall-interior-1.jpg", 'category' => 'Resort', 'star_rating' => 4],
            ['name' => 'Super Hotel', 'image_url' => "$base/hall-interior-2.jpg", 'category' => 'Hotel', 'star_rating' => 3],
            ['name' => 'Eliper Hotel & Restaurant', 'image_url' => "$base/hall-interior-3.jpg", 'category' => 'Hotel', 'star_rating' => 3],
            ['name' => 'Highland Conference & Events Centre', 'image_url' => "$base/tsavo-hall.jpg", 'category' => 'Conference', 'star_rating' => 4],
            ['name' => 'Garden Court Restaurant & Lounge', 'image_url' => "$base/courtyard.jpg", 'category' => 'Restaurant', 'star_rating' => 4],
        ];

        foreach ($hotels as $data) {
            $record = CountyHotel::firstOrNew([
                'county_id' => $county->id,
                'name' => $data['name'],
            ]);
            $record->forceFill(array_merge($data, [
                'county_id' => $county->id,
                'countyId' => $county->id,
                'is_published' => true,
                'isPublished' => true,
            ]));
            $record->save();
        }

        $products = [
            ['name' => 'Murang\'a Tea', 'image_url' => "$base/kicc_Tsavo-1.jpg", 'category' => 'Beverage', 'price' => 450, 'unit' => 'kg'],
            ['name' => 'Murang\'a Coffee', 'image_url' => "$base/kicc_Catering-2.jpg", 'category' => 'Beverage', 'price' => 600, 'unit' => 'kg'],
            ['name' => 'Murang\'a Honey', 'image_url' => "$base/kicc_G4.jpg", 'category' => 'Food', 'price' => 800, 'unit' => 'jar'],
            ['name' => 'Murang\'a Fresh Produce', 'image_url' => "$base/kicc_Lawn-1.jpg", 'category' => 'Produce', 'price' => 300, 'unit' => 'crate'],
            ['name' => 'Murang\'a Textiles', 'image_url' => "$base/kicc_KICC-2.jpg", 'category' => 'Textile', 'price' => 1200, 'unit' => 'piece'],
            ['name' => 'Murang\'a Beans', 'image_url' => "$base/kicc_DSC_7866.jpg", 'category' => 'Produce', 'price' => 250, 'unit' => 'kg'],
            ['name' => 'Murang\'a Town Market', 'image_url' => "$base/kicc_DSC_8892.jpg", 'category' => 'Market', 'price' => 500, 'unit' => 'bundle'],
        ];

        foreach ($products as $data) {
            $record = CountyProduct::firstOrNew([
                'county_id' => $county->id,
                'name' => $data['name'],
            ]);
            $record->forceFill(array_merge($data, [
                'county_id' => $county->id,
                'countyId' => $county->id,
                'booking_type' => 'order',
                'status' => 'available',
                'is_published' => true,
                'isPublished' => true,
            ]));
            $record->save();
        }
    }
}
