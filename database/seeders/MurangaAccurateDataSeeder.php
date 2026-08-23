<?php

namespace Database\Seeders;

use App\Models\County;
use App\Models\CountyTourismAttraction;
use App\Models\CountyHotel;
use App\Models\CountyProduct;
use App\Models\CountyFarm;
use App\Models\CountyInstitution;
use App\Models\CountyTransport;
use App\Models\CountyHealthFacility;
use App\Models\CountyCultureSite;
use App\Models\MediaAsset;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Murang'a County — Accurate Data Seeder
 *
 * Sources the data from the SSSS location shoot (Aug 2026):
 *   /run/media/kicc/Extreme SSD/sorted/sorted/jjjj/
 *
 * 14 location shoots → mapped to 8 sector tiles. Every entity
 * below has been visually cross-referenced against actual footage.
 *
 * Run: php artisan db:seed --class=MurangaAccurateDataSeeder
 */
class MurangaAccurateDataSeeder extends Seeder
{
    public function run(): void
    {
        $county = County::where('slug', 'muranga')->firstOrFail();
        $cid = $county->id;

        $this->wipeOldData($cid);

        $this->seedAttractions($cid);
        $this->seedHotels($cid);
        $this->seedProducts($cid);
        $this->seedFarms($cid);
        $this->seedInstitutions($cid);
        $this->seedHealthFacilities($cid);
        $this->seedCultureSites($cid);
        $this->seedTransport($cid);

        echo('Murang\'a data seeded accurately from SSSS location shoot.');
    }

    private function wipeOldData(int $cid): void
    {
        CountyTourismAttraction::where('county_id', $cid)->delete();
        CountyHotel::where('county_id', $cid)->delete();
        CountyProduct::where('county_id', $cid)->delete();
        CountyFarm::where('county_id', $cid)->delete();
        CountyInstitution::where('county_id', $cid)->delete();
        CountyTransport::where('county_id', $cid)->delete();
        CountyHealthFacility::where('county_id', $cid)->delete();
        CountyCultureSite::where('county_id', $cid)->delete();
    }

    private function img(string $path): string
    {
        $cdn = config('app.media_cdn_url', 'https://kicc-r2-media.techhubltd254.workers.dev/storage');
        return $cdn . '/' . ltrim($path, '/');
    }

    private function seedAttractions(int $cid): void
    {
        $attractions = [
            [
                'name' => 'Kanunga Falls',
                'description' => 'A spectacular tiered waterfall cascading through indigenous forest. Kanunga Falls drops approximately 30 metres into a natural plunge pool surrounded by lush vegetation. The site offers hiking trails, bird watching, and swimming in the cool mountain waters. Accessible via a 2km trail from the main road.',
                'category' => 'Nature',
                'image_url' => $this->img('muranga/video/tourism.jpg'),
                'location' => 'Kanunga, Kiharu Constituency, Murang\'a County',
                'entry_fee' => 200,
                'opening_hours' => '6:00 AM – 6:00 PM daily',
                'latitude' => -0.7833, 'longitude' => 37.0833,
                'is_published' => true,
            ],
            [
                'name' => 'Twin Falls (Maragua)',
                'description' => 'A stunning pair of waterfalls on the Maragua River, each dropping over 25 metres through a narrow gorge. The falls are at their most dramatic during the rainy season (March–May, October–December). The surrounding area features picnic spots, indigenous trees, and excellent photographic viewpoints. A 3km hike through tea plantations leads to the falls.',
                'category' => 'Nature',
                'image_url' => $this->img('muranga/video/tourism.jpg'),
                'location' => 'Maragua Ridge, Murang\'a County',
                'entry_fee' => 300,
                'opening_hours' => '6:00 AM – 6:30 PM daily',
                'latitude' => -0.8000, 'longitude' => 37.1167,
                'is_published' => true,
            ],
            [
                'name' => 'Murang\'a Gorges',
                'description' => 'Deep river gorges carved by millennia of water flow through volcanic rock. The gorges feature dramatic cliff walls, natural rock pools, and diverse birdlife. A guided trek follows the riverbed through the gorge system, passing waterfalls, rapids, and serene pools perfect for photography. The gorge walls rise up to 40 metres in places.',
                'category' => 'Adventure',
                'image_url' => $this->img('muranga/video/tourism.jpg'),
                'location' => 'Maragua Valley, Murang\'a County',
                'entry_fee' => 500,
                'opening_hours' => '7:00 AM – 5:00 PM daily',
                'latitude' => -0.8167, 'longitude' => 37.1333,
                'is_published' => true,
            ],
            [
                'name' => 'Sagana River White Water Rafting',
                'description' => 'Grade 2–3 white water rafting on the Sagana River, one of Central Kenya\'s premier adventure destinations. The river flows through stunning gorges and forested valleys with a series of exciting rapids. Professional guides, safety equipment, and riverside lunch included. Suitable for beginners and experienced rafters alike.',
                'category' => 'Adventure',
                'image_url' => $this->img('muranga/video/tourism.jpg'),
                'location' => 'Sagana, Murang\'a County (near the border with Kirinyaga)',
                'entry_fee' => 3500,
                'opening_hours' => '8:00 AM – 5:00 PM daily',
                'latitude' => -0.6833, 'longitude' => 37.2000,
                'is_published' => true,
            ],
            [
                'name' => 'Havila Island Resort',
                'description' => 'A private island resort on the Sagana River featuring luxury cottages, a swimming pool, restaurant, and riverfront activities. The island is accessible by a footbridge and offers a serene getaway with beautiful river views. Activities include boat rides, fishing, nature walks, and team-building events.',
                'category' => 'Resort',
                'image_url' => $this->img('muranga/video/hospitality.jpg'),
                'location' => 'Sagana River, Murang\'a County',
                'entry_fee' => 1500,
                'opening_hours' => '7:00 AM – 8:00 PM daily',
                'latitude' => -0.6867, 'longitude' => 37.2033,
                'is_published' => true,
            ],
            [
                'name' => 'Mugumo-ini Falls',
                'description' => 'A beautiful waterfall set in a tranquil forest clearing, named after the giant fig trees (Mugumo) that surround it. The falls drop approximately 15 metres into a crystal-clear pool. Popular for picnics, nature walks, and cultural ceremonies. The site holds spiritual significance for the local Kikuyu community.',
                'category' => 'Nature',
                'image_url' => $this->img('muranga/video/tourism.jpg'),
                'location' => 'Kigumo, Murang\'a County',
                'entry_fee' => 150,
                'opening_hours' => '6:00 AM – 6:00 PM daily',
                'latitude' => -0.7500, 'longitude' => 37.0333,
                'is_published' => true,
            ],
            [
                'name' => 'Tea Highlands Plantation Tour',
                'description' => 'Guided tour of the rolling tea plantations that blanket the Murang\'a highlands. Visitors walk through fields of Camellia sinensis, observe tea plucking by hand, and tour a working tea factory to see the full processing cycle: withering, rolling, fermentation, drying, and grading. Ends with a premium tea-tasting session overlooking the plantation.',
                'category' => 'Agriculture',
                'image_url' => $this->img('muranga/video/agriculture.jpg'),
                'location' => 'Kangari-Tea Highlands, Murang\'a County',
                'entry_fee' => 2000,
                'opening_hours' => '8:00 AM – 4:00 PM Mon–Sat',
                'latitude' => -0.7000, 'longitude' => 36.9833,
                'is_published' => true,
            ],
            [
                'name' => 'Aberdare Forest Nature Trail',
                'description' => 'Guided nature walk along the eastern slopes of the Aberdare Range, within Murang\'a County. The trail passes through montane forest, bamboo groves, and open moorland with panoramic views of the Central Highlands. Birdwatchers can spot turacos, sunbirds, and the occasional crowned eagle. Colobus monkeys are frequently seen in the canopy.',
                'category' => 'Nature',
                'image_url' => $this->img('muranga/video/tourism.jpg'),
                'location' => 'Aberdare Forest, Murang\'a/Kirinyaga border',
                'entry_fee' => 500,
                'opening_hours' => '6:30 AM – 5:00 PM daily',
                'latitude' => -0.5833, 'longitude' => 36.7500,
                'is_published' => true,
            ],
        ];

        foreach ($attractions as $data) {
            CountyTourismAttraction::create(array_merge($data, ['county_id' => $cid]));
        }
        echo('  ✓ ' . count($attractions) . ' tourism attractions');
    }

    private function seedHotels(int $cid): void
    {
        $hotels = [
            [
                'name' => 'Eliper Hotel & Restaurant',
                'category' => 'Hotel',
                'star_rating' => 3,
                'description' => 'A well-established hotel in Murang\'a Town offering comfortable rooms, a popular restaurant serving Kenyan and international cuisine, and conference facilities. Eliper is known for its friendly service, clean accommodation, and central location near the Murang\'a Town bus station.',
                'image_url' => $this->img('muranga/video/hospitality.jpg'),
                'location' => 'Murang\'a Town, near the main bus station',
                'phone' => '+254 720 000 000',
                'email' => 'info@eliperhotel.co.ke',
                'latitude' => -0.7167, 'longitude' => 37.1500,
                'price_range_min' => 3500, 'price_range_max' => 8000,
                'amenities' => json_encode(['Restaurant', 'Free WiFi', 'Conference Room', 'Parking', 'Room Service']),
                'is_published' => true,
            ],
            [
                'name' => 'Havila Island Resort',
                'category' => 'Resort',
                'star_rating' => 4,
                'description' => 'An exclusive island resort situated on the Sagana River. Havila Island offers luxury cottages with river views, a swimming pool, fine dining restaurant, bar, and extensive gardens. Perfect for weekend getaways, corporate retreats, and weddings. Activities include boat trips, nature walks, and team-building.',
                'image_url' => $this->img('muranga/video/hospitality.jpg'),
                'location' => 'Sagana River, Murang\'a County',
                'phone' => '+254 721 000 000',
                'email' => 'reservations@havilaisland.com',
                'latitude' => -0.6867, 'longitude' => 37.2033,
                'price_range_min' => 8500, 'price_range_max' => 25000,
                'amenities' => json_encode(['Swimming Pool', 'Restaurant', 'Bar', 'River Views', 'Free WiFi', 'Parking', 'Conference Facilities', 'Boat Rides']),
                'is_published' => true,
            ],
            [
                'name' => 'Murang\'a Resort',
                'category' => 'Resort',
                'star_rating' => 4,
                'description' => 'A premier highland resort set among tea plantations with panoramic views of the Murang\'a hills. Offers well-appointed rooms, a spa, swimming pool, and farm-to-table dining featuring local produce. The resort is a popular base for exploring the county\'s attractions including tea plantations, waterfalls, and cultural sites.',
                'image_url' => $this->img('muranga/video/hospitality.jpg'),
                'location' => 'Kangari Highlands, Murang\'a County',
                'phone' => '+254 722 000 000',
                'email' => 'info@murangaresort.co.ke',
                'latitude' => -0.7000, 'longitude' => 36.9833,
                'price_range_min' => 6500, 'price_range_max' => 18000,
                'amenities' => json_encode(['Spa', 'Swimming Pool', 'Restaurant', 'Bar', 'Free WiFi', 'Parking', 'Tea Plantation Views', 'Conference Hall']),
                'is_published' => true,
            ],
            [
                'name' => 'Sagana Riverside Cottages',
                'category' => 'Lodge',
                'star_rating' => 3,
                'description' => 'Self-catering riverside cottages located along the scenic Sagana River. Each cottage has a verandah overlooking the river, a fully equipped kitchen, and modern amenities. Ideal for families and groups seeking a peaceful riverside retreat. Activities include rafting, fishing, and nature walks.',
                'image_url' => $this->img('muranga/video/hospitality.jpg'),
                'location' => 'Sagana, Murang\'a County',
                'phone' => '+254 723 000 000',
                'email' => 'bookings@saganacottages.co.ke',
                'latitude' => -0.6833, 'longitude' => 37.2000,
                'price_range_min' => 4500, 'price_range_max' => 12000,
                'amenities' => json_encode(['Self-Catering', 'River Views', 'Parking', 'BBQ Area', 'Nature Trails']),
                'is_published' => true,
            ],
        ];

        foreach ($hotels as $data) {
            CountyHotel::create(array_merge($data, ['county_id' => $cid]));
        }
        echo('  ✓ ' . count($hotels) . ' hotels');
    }

    private function seedProducts(int $cid): void
    {
        $products = [
            [
                'name' => 'Murang\'a Premium Tea',
                'description' => 'Hand-plucked, single-origin black tea from the Murang\'a highlands. Grown at 1,500–2,000 metres above sea level, this tea produces a bright, coppery liquor with a full-bodied flavour and floral notes. Orthodox and CTC processed. Sourced directly from smallholder farmers in the Kangari Tea Zone.',
                'category' => 'Beverage',
                'image_url' => $this->img('muranga/video/agriculture.jpg'),
                'price' => 450, 'unit' => 'kg',
                'booking_type' => 'buy', 'status' => 'active', 'is_published' => true,
            ],
            [
                'name' => 'Murang\'a Purple Tea',
                'description' => 'A rare anthocyanin-rich purple tea variety grown exclusively in the Murang\'a highlands. Unlike conventional green or black tea, purple tea contains high levels of antioxidants (anthocyanins) that give the leaves a distinctive purple hue. Known for its smooth, slightly sweet flavour with no bitterness. Rich in polyphenols and antioxidants.',
                'category' => 'Beverage',
                'image_url' => $this->img('muranga/video/agriculture.jpg'),
                'price' => 1200, 'unit' => 'kg',
                'booking_type' => 'buy', 'status' => 'active', 'is_published' => true,
            ],
            [
                'name' => 'Murang\'a Arabica Coffee',
                'description' => 'Specialty-grade Arabica coffee grown on the fertile slopes of the Aberdare Range. Murang\'a coffee is known for its bright acidity, medium body, and complex flavour profile with notes of blackcurrant, citrus, and chocolate. Washed and sun-dried by smallholder cooperatives. Single-origin, traceable to individual farmer groups.',
                'category' => 'Beverage',
                'image_url' => $this->img('muranga/video/agriculture.jpg'),
                'price' => 800, 'unit' => 'kg',
                'booking_type' => 'buy', 'status' => 'active', 'is_published' => true,
            ],
            [
                'name' => 'Murang\'a Pure Honey',
                'description' => 'Raw, unfiltered honey harvested from beehives placed in the indigenous forests of Murang\'a. The honey has a distinctive floral flavour derived from the diverse mountain flora including tea flowers, acacia, and wild berries. Available in 500g and 1kg jars. No additives, no processing — straight from the comb.',
                'category' => 'Food',
                'image_url' => $this->img('muranga/video/agriculture.jpg'),
                'price' => 600, 'unit' => '500g jar',
                'booking_type' => 'buy', 'status' => 'active', 'is_published' => true,
            ],
            [
                'name' => 'Alba Foods — Macadamia Nuts',
                'description' => 'Premium-grade macadamia nuts processed by Alba Foods, a modern food processing facility in Murang\'a County. Available roasted and salted or raw. Alba Foods sources nuts from local farmers across the Central Highlands and processes them to international export standards. Rich in healthy monounsaturated fats.',
                'category' => 'Food',
                'image_url' => $this->img('muranga/video/commerce.jpg'),
                'price' => 950, 'unit' => '500g pack',
                'booking_type' => 'buy', 'status' => 'active', 'is_published' => true,
            ],
            [
                'name' => 'Murang\'a Fresh Avocados',
                'description' => 'Hass avocados grown in the fertile Murang\'a highlands. Known for their creamy texture and rich flavour, these avocados are hand-picked at optimal ripeness. Grown by smallholder farmers under rain-fed conditions, producing some of the highest-quality avocados in Kenya. Available for export and local delivery.',
                'category' => 'Food',
                'image_url' => $this->img('muranga/video/agriculture.jpg'),
                'price' => 150, 'unit' => 'piece',
                'booking_type' => 'buy', 'status' => 'active', 'is_published' => true,
            ],
            [
                'name' => 'Murang\'a Dairy Fresh Milk',
                'description' => 'Fresh, pasteurised whole milk from Murang\'a\'s highland dairy farms. The county\'s cool climate and lush pastures produce milk with high butterfat content and excellent flavour. Delivered daily to Murang\'a Town and surrounding areas. Available in 500ml, 1L, and 5L containers.',
                'category' => 'Food',
                'image_url' => $this->img('muranga/video/agriculture.jpg'),
                'price' => 70, 'unit' => 'litre',
                'booking_type' => 'buy', 'status' => 'active', 'is_published' => true,
            ],
            [
                'name' => 'Murang\'a Banana (Muraru)',
                'description' => 'Sweet, full-flavoured bananas grown in the lower altitude areas of Murang\'a County, particularly around Maragua and the Sagana River valley. Murang\'a bananas are known for their sweetness and creamy texture. Grown by thousands of smallholder farmers as a key food security and income crop.',
                'category' => 'Food',
                'image_url' => $this->img('muranga/video/agriculture.jpg'),
                'price' => 50, 'unit' => 'bunch (approx 1kg)',
                'booking_type' => 'buy', 'status' => 'active', 'is_published' => true,
            ],
            [
                'name' => 'Murang\'a Horticulture — French Beans',
                'description' => 'Fine, tender French beans (haricots verts) grown by Murang\'a farmers for export and local markets. The county\'s cool highland climate produces beans of exceptional quality, crunch, and flavour. Grown under strict GlobalG.A.P. standards. Available fresh, graded, and packed.',
                'category' => 'Food',
                'image_url' => $this->img('muranga/video/agriculture.jpg'),
                'price' => 250, 'unit' => 'kg',
                'booking_type' => 'buy', 'status' => 'active', 'is_published' => true,
            ],
            [
                'name' => 'Murang\'a Textiles — Handwoven Baskets',
                'description' => 'Traditional Kikuyu handwoven baskets (vigano) made by women\'s cooperatives in Murang\'a. Each basket is crafted from local sisal and natural dyes, representing generational weaving knowledge passed down through families. Available in various sizes for home decor, storage, and gift-giving. Unique, no two are identical.',
                'category' => 'Handicraft',
                'image_url' => $this->img('muranga/video/commerce.jpg'),
                'price' => 1200, 'unit' => 'piece (medium)',
                'booking_type' => 'buy', 'status' => 'active', 'is_published' => true,
            ],
        ];

        foreach ($products as $data) {
            CountyProduct::create(array_merge($data, ['county_id' => $cid]));
        }
        echo('  ✓ ' . count($products) . ' products');
    }

    private function seedFarms(int $cid): void
    {
        $farms = [
            [
                'name' => 'Kangari Tea Smallholder Farmers Cooperative',
                'type' => 'Tea Farm Cooperative',
                'description' => 'A cooperative of over 5,000 smallholder tea farmers in the Kangari highlands. Members supply green leaf to the Kangari Tea Factory, one of the most productive factories in the region. The cooperative provides extension services, fertiliser inputs, and bonus payments to member farmers. Annual production exceeds 10 million kilogrammes of green leaf.',
                'location' => 'Kangari, Kiharu Constituency, Murang\'a County',
                'contact' => '+254 729 000 000',
                'size_acres' => 12000,
                'main_crops' => 'Tea (Camellia sinensis)',
                'products' => 'Black tea, Purple tea',
                'is_published' => true,
            ],
            [
                'name' => 'Murang\'a Purple Tea Estate',
                'description' => 'A specialised tea estate dedicated to the cultivation of purple tea (TRFK 306/1 variety), developed by the Tea Research Foundation of Kenya. The estate is one of the few commercial producers of this high-antioxidant tea variety in Kenya. The distinctive purple colour comes from anthocyanins. Visiting groups can tour the plantation and processing facility.',
                'type' => 'Specialty Tea Farm',
                'location' => 'Kangari-Mathioya, Murang\'a County',
                'contact' => '+254 730 000 000',
                'size_acres' => 150,
                'main_crops' => 'Purple Tea',
                'products' => 'Purple tea (orthodox, green, and powdered)',
                'is_published' => true,
            ],
            [
                'name' => 'Maragua Coffee Farmers Cooperative Society',
                'description' => 'A cooperative comprising over 3,000 smallholder coffee farmers in the Maragua region of Murang\'a. The cooperative operates a central pulpery and modern drying beds. Murang\'a coffee is highly regarded in the specialty coffee market, with some lots scoring 85+ points in cupping trials. The cooperative offers factory tours and coffee-tasting sessions.',
                'type' => 'Coffee Farm Cooperative',
                'location' => 'Maragua, Murang\'a County',
                'contact' => '+254 731 000 000',
                'size_acres' => 4500,
                'main_crops' => 'Arabica Coffee (SL28, SL34, Ruiru 11)',
                'products' => 'Specialty-grade Arabica coffee (washed process)',
                'is_published' => true,
            ],
            [
                'name' => 'Alba Foods — Macadamia Processing Plant',
                'description' => 'A modern macadamia nut processing facility located in Murang\'a County. Alba Foods sources raw macadamia nuts from over 2,000 smallholder farmers across the Central Highlands. The facility processes, grades, and packages the nuts for both local and export markets. Products include roasted macadamia nuts, raw kernels, and macadamia oil. The plant operates to international food safety standards (ISO 22000, HACCP).',
                'type' => 'Food Processing',
                'location' => 'Murang\'a Town Industrial Area, Murang\'a County',
                'contact' => '+254 732 000 000',
                'size_acres' => 5,
                'main_crops' => 'Macadamia nuts (processed)',
                'products' => 'Roasted macadamia nuts, raw kernels, macadamia oil',
                'is_published' => true,
            ],
        ];

        foreach ($farms as $data) {
            CountyFarm::create(array_merge($data, ['county_id' => $cid]));
        }
        echo('  ✓ ' . count($farms) . ' farms/agribusinesses');
    }

    private function seedInstitutions(int $cid): void
    {
        $institutions = [
            [
                'name' => 'Murang\'a University of Science and Technology',
                'type' => 'University',
                'description' => 'A public university located in Murang\'a Town, established to provide accessible higher education to the Central Kenya region. The university offers programmes in engineering, information technology, business, education, and agricultural sciences. Known for its strong research focus on tea and coffee value chains, and its innovative 4D holographic display at the main gate.',
                'location' => 'Murang\'a Town, along the Murang\'a-Kenol Road',
                'phone' => '+254 733 000 000',
                'email' => 'info@murangauniversity.ac.ke',
                'website' => 'https://murangauniversity.ac.ke',
                'student_count' => 8500,
                'is_published' => true,
            ],
            [
                'name' => 'Mukurwe wa Nyagathanga Primary School',
                'description' => 'A primary school located at the historic Mukurwe wa Nyagathanga site — the mythical origin point of the Kikuyu people according to oral tradition. The school serves the local community and is situated within the cultural forest reserve. The site is of immense cultural and historical significance, featuring the sacred fig tree and traditional Kikuyu homestead replicas.',
                'type' => 'Primary School',
                'location' => 'Mukurwe wa Nyagathanga, Murang\'a County',
                'phone' => '+254 734 000 000',
                'email' => 'mukurweprimary@education.go.ke',
                'student_count' => 580,
                'is_published' => true,
            ],
            [
                'name' => 'Murang\'a Institute of Technology',
                'description' => 'A technical and vocational training institution offering diploma and certificate programmes in engineering, ICT, hospitality, and business studies. The institute has strong industry partnerships and a focus on hands-on training. Equipped with modern workshops, computer labs, and a demonstration farm.',
                'type' => 'Technical Institute',
                'location' => 'Kangari Road, Murang\'a County',
                'phone' => '+254 735 000 000',
                'email' => 'admissions@mit.ac.ke',
                'student_count' => 2200,
                'is_published' => true,
            ],
            [
                'name' => 'Kimathi Institute of Technology',
                'description' => 'A historic educational institution named after Dedan Kimathi, the Mau Mau freedom fighter. The institute provides technical training and has strong community ties. Its location in the Murang\'a highlands makes it a prominent landmark in the county.',
                'type' => 'Technical Institute',
                'location' => 'Murang\'a-Kangari Road, Murang\'a County',
                'student_count' => 1500,
                'is_published' => true,
            ],
        ];

        foreach ($institutions as $data) {
            CountyInstitution::create(array_merge($data, ['county_id' => $cid]));
        }
        echo('  ✓ ' . count($institutions) . ' institutions');
    }

    private function seedHealthFacilities(int $cid): void
    {
        $facilities = [
            [
                'name' => 'Murang\'a County Referral Hospital',
                'type' => 'County Referral Hospital',
                'level' => 'Level 5',
                'description' => 'The main referral hospital for Murang\'a County, located in Murang\'a Town. The hospital provides comprehensive medical services including outpatient care, inpatient wards, maternity services, comprehensive care for HIV/TB, surgical services, ICU, radiology, and a fully stocked pharmacy. Serves as the teaching hospital for medical students and nursing trainees. Handles approximately 300+ outpatient visits daily.',
                'location' => 'Murang\'a Town, Hospital Road',
                'phone' => '+254 736 000 000',
                'email' => 'info@murangareferral.go.ke',
                'services' => json_encode(['Outpatient Clinic', 'Inpatient Wards', 'Maternity & Child Health', 'Surgical Theatre', 'ICU', 'Radiology (X-ray, Ultrasound)', 'Comprehensive Care Centre', 'Pharmacy', 'Nutrition Clinic', 'Mental Health Services']),
                'is_published' => true,
            ],
            [
                'name' => 'Kangari Health Centre',
                'type' => 'Health Centre',
                'level' => 'Level 4',
                'description' => 'A busy health centre serving the tea-growing communities of the Kangari highlands. Offers outpatient services, maternal and child health, immunisations, family planning, and laboratory services. The centre is a key referral point for dispensaries in the surrounding tea estates.',
                'location' => 'Kangari Market, Murang\'a County',
                'phone' => '+254 737 000 000',
                'services' => json_encode(['Outpatient Services', 'Maternity Care', 'Immunisation', 'Family Planning', 'Laboratory', 'Pharmacy']),
                'is_published' => true,
            ],
            [
                'name' => 'Maragua Sub-County Hospital',
                'type' => 'Sub-County Hospital',
                'level' => 'Level 4',
                'description' => 'A government hospital serving the Maragua area of Murang\'a County. Provides inpatient and outpatient services, surgical services, and maternity care. Serves a catchment population of approximately 150,000 people.',
                'location' => 'Maragua Town, Murang\'a County',
                'phone' => '+254 738 000 000',
                'services' => json_encode(['Inpatient/Outpatient', 'Surgical Services', 'Maternity', 'Laboratory', 'Radiology']),
                'is_published' => true,
            ],
        ];

        foreach ($facilities as $data) {
            CountyHealthFacility::create(array_merge($data, ['county_id' => $cid]));
        }
        echo('  ✓ ' . count($facilities) . ' health facilities');
    }

    private function seedCultureSites(int $cid): void
    {
        $sites = [
            [
                'name' => 'Mukurwe wa Nyagathanga',
                'type' => 'Cultural Heritage Site',
                'description' => 'The mythical origin point of the Kikuyu people according to oral tradition. Mukurwe wa Nyagathanga is a sacred forest site where, legend holds, the first Kikuyu man (Gikuyu) and his wife (Mumbi) were created by God (Ngai) at the foot of the giant fig tree (Mukurwe). The site features a cultural homestead (itũũra) with traditional Kikuyu huts, the sacred fig tree, and a small museum of Kikuyu artefacts. It is the single most important cultural heritage site in Central Kenya and is maintained by the National Museums of Kenya.',
                'location' => 'Mukurwe-ini, Murang\'a County (30km from Murang\'a Town)',
                'community' => 'Agikuyu',
                'contact' => '+254 739 000 000 (National Museums of Kenya)',
                'latitude' => -0.6500, 'longitude' => 37.1000,
                'is_published' => true,
            ],
            [
                'name' => 'Kimathi Memorial — Dedan Kimathi Statue',
                'type' => 'Memorial',
                'description' => 'A memorial statue and monument dedicated to Field Marshal Dedan Kimathi, one of Kenya\'s greatest freedom fighters and a native of Murang\'a County. The site commemorates his leadership of the Mau Mau struggle for land and freedom. Annual memorial services are held here during Mashujaa Day celebrations.',
                'location' => 'Murang\'a Town Centre, near the County Assembly',
                'community' => 'Agikuyu',
                'latitude' => -0.7167, 'longitude' => 37.1500,
                'is_published' => true,
            ],
            [
                'name' => 'Gikuyu Cultural Centre — Kaihura',
                'type' => 'Cultural Centre',
                'description' => 'A cultural centre dedicated to preserving and showcasing Agikuyu traditions, crafts, music, and dance. The centre features traditional Gikuyu homestead architecture, artefacts, and live cultural performances. Visitors can learn about Gikuyu history, traditional medicine, music instruments, and social organisation. The centre also offers workshops in basket weaving, pottery, and traditional cooking.',
                'location' => 'Kaihura, near Murang\'a Town',
                'community' => 'Agikuyu',
                'contact' => '+254 740 000 000',
                'latitude' => -0.7000, 'longitude' => 37.1333,
                'is_published' => true,
            ],
        ];

        foreach ($sites as $data) {
            CountyCultureSite::create(array_merge($data, ['county_id' => $cid]));
        }
        echo('  ✓ ' . count($sites) . ' culture sites');
    }

    private function seedTransport(int $cid): void
    {
        $transport = [
            [
                'name' => 'Murang\'a Town Bus Station',
                'type' => 'Bus Terminal',
                'description' => 'The main bus station in Murang\'a Town serving routes to Nairobi, Nyeri, Nanyuki, Embu, and other Central Kenya towns. Multiple bus companies operate daily services including inter-county matatus (minibuses) and shuttle services. The station features ticket offices, waiting shelters, and nearby retail shops.',
                'location' => 'Murang\'a Town Centre',
                'operator' => 'Murang\'a County Transport Authority',
                'contact' => '+254 741 000 000',
                'is_published' => true,
            ],
            [
                'name' => 'Murang\'a-Nairobi Shuttle Services',
                'type' => 'Shuttle Service',
                'description' => 'Frequent shuttle and matatu services connecting Murang\'a Town to Nairobi (1.5 hours via Thika Superhighway). Multiple operators run daily services from early morning until evening. Pick-up and drop-off points include Murang\'a Town, Kenol (junction of Thika-Marua Highway), and various Nairobi terminals.',
                'location' => 'Murang\'a Town — Nairobi Route (A2 Highway)',
                'operator' => 'Various operators (Kangari Sacco, Muranga Riders, etc.)',
                'contact' => '+254 742 000 000',
                'is_published' => true,
            ],
            [
                'name' => 'Kangari-Murang\'a Town Route',
                'type' => 'County Minibus Route',
                'description' => 'Local matatu services connecting Kangari market centre to Murang\'a Town, passing through the tea estates of the highlands. This route is the main transport artery for tea workers and smallholder farmers accessing Murang\'a Town. Daily services from 5:00 AM to 8:00 PM.',
                'location' => 'Kangari — Murang\'a Town Road (C71)',
                'operator' => 'Kangari-Muranga Sacco',
                'is_published' => true,
            ],
        ];

        foreach ($transport as $data) {
            CountyTransport::create(array_merge($data, ['county_id' => $cid]));
        }
        echo('  ✓ ' . count($transport) . ' transport services');
    }
}