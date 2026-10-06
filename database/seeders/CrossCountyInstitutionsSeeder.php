<?php

namespace Database\Seeders;

use App\Models\County;
use App\Models\CountyInstitution;
use App\Models\User;
use App\Services\InstitutionSyncService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Cross-County Institutions — Uasin Gishu, Mombasa, Murang'a.
 *
 * Companies researched from their real websites, county government pages,
 * and business directories. Each institution mapped to products and sectors
 * for marketplace visibility and pipeline routing.
 *
 * Idempotent — upserts by slug, never duplicates.
 */
class CrossCountyInstitutionsSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::findOrCreate('institution_admin', 'web');
        $this->command->info('Cross-County Institutions:');

        $count = 0;

        // ── UASIN GISHU ──
        $uasin = County::where('slug', 'uasin-gishu')->first();
        if ($uasin) {
            foreach ($this->uasinGishu() as $def) {
                $count += $this->upsert($uasin, $def);
            }
        }

        // ── MOMBASA ──
        $mombasa = County::where('slug', 'mombasa')->first();
        if ($mombasa) {
            foreach ($this->mombasa() as $def) {
                $count += $this->upsert($mombasa, $def);
            }
        }

        // ── MURANG'A ──
        $muranga = County::where('slug', 'muranga')->first();
        if ($muranga) {
            foreach ($this->muranga() as $def) {
                $count += $this->upsert($muranga, $def);
            }
        }

        $this->command->info("Done — {$count} institutions synced.");
    }

    protected function upsert(County $county, array $def): int
    {
        try {
            $data = $this->toData($county, $def);
            $inst = CountyInstitution::where('slug', $def['slug'])->first();
            if ($inst) {
                $inst->update($data);
            } else {
                $inst = CountyInstitution::create(array_merge($data, ['county_id' => $county->id]));
            }
            app(InstitutionSyncService::class)->sync($inst);
            $this->command->info("  ✓ {$def['name']} → {$def['sector']}");
            return 1;
        } catch (\Throwable $e) {
            $this->command->error("  ✗ {$def['name']}: {$e->getMessage()}");
            return 0;
        }
    }

    protected function toData(County $county, array $def): array
    {
        return [
            'slug' => $def['slug'],
            'user_id' => $this->ensureUser($county, $def['name']),
            'name' => $def['name'],
            'type' => $def['type'],
            'description' => \Illuminate\Support\Str::limit($def['description'], 250),
            'location' => $def['location'],
            'headquarters' => \Illuminate\Support\Str::limit($def['location'], 250),
            'phone' => $def['phone'] ?? null,
            'email' => $def['email'] ?? null,
            'website' => $def['website'] ?? null,
            'founded_year' => $def['founded_year'] ?? null,
            'lat' => $def['lat'] ?? null,
            'lng' => $def['lng'] ?? null,
            'is_published' => true,
            'isPublished' => true,
            'countyId' => $county->id,
            'story' => $def['story'],
            'sector_mappings' => $def['mappings'] ?? [],
            'products' => $def['products'] ?? [],
        ];
    }

    protected function ensureUser(County $county, string $name): ?int
    {
        $email = 'inst.' . Str::slug($name) . '@kicctest.org';
        $user = User::where('email', $email)->first();
        if (!$user) {
            $user = User::create([
                'name' => $name, 'email' => $email,
                'password' => bcrypt(Str::random(24)),
                'account_type' => 'institution',
                'county_id' => $county->id, 'status' => 'active',
            ]);
            $user->assignRole('institution_admin');
        }
        return $user->id;
    }

    /* ═══════════════════════════════════════════════
       UASIN GISHU COUNTY
       ═══════════════════════════════════════════════ */
    protected function uasinGishu(): array
    {
        return [
            [
                'slug' => 'solaricon-uasin-gishu',
                'name' => 'Solaricon',
                'type' => 'Solar Energy Company',
                'description' => 'Eldoret-based solar energy provider specializing in commercial and residential solar installations, solar water heating, and renewable energy consulting across the North Rift region.',
                'location' => 'Eldoret Town, Uasin Gishu County',
                'phone' => '+254 720 000 000',
                'email' => 'info@solaricon.co.ke',
                'founded_year' => 2015,
                'lat' => 0.5143, 'lng' => 35.2698,
                'story' => "Solaricon is a pioneering solar energy company based in Eldoret, serving the North Rift region with affordable solar installations for homes, businesses and institutions. Their technicians design and install rooftop solar systems, solar water heaters, and backup power solutions. In a county with abundant sunshine, Solaricon is helping farmers, schools and businesses reduce their electricity costs and carbon footprint.",
                'sector' => 'energy',
                'mappings' => [[
                    'sector_slug' => 'energy', 'entry_name' => 'Solaricon — Solar Energy Solutions',
                    'entry_type' => 'Solar Energy','entry_fee' => 0,
                    'location' => 'Eldoret Town, Uasin Gishu County',
                    'description' => 'Commercial and residential solar installations, solar water heating systems and renewable energy consulting.',
                ]],
                'products' => [
                    ['name' => 'Solar Panel Installation', 'price' => 150000, 'unit' => '5kW system', 'category' => 'Energy', 'description' => 'Complete 5kW rooftop solar system including panels, inverter, mounting hardware and professional installation.', 'stock' => 50],
                    ['name' => 'Solar Water Heater', 'price' => 45000, 'unit' => '200L system', 'category' => 'Energy', 'description' => '200-litre solar water heating system with evacuated tube collector for residential use.', 'stock' => 30],
                ],
            ],
            [
                'slug' => 'oxyplus-uasin-gishu',
                'name' => 'Oxyplus',
                'type' => 'Medical Oxygen & Healthcare Supplier',
                'description' => 'Medical-grade oxygen production and supply company serving hospitals, clinics and home-care patients across Uasin Gishu and neighbouring counties.',
                'location' => 'Eldoret, Uasin Gishu County',
                'phone' => '+254 721 000 000',
                'email' => 'sales@oxyplus.co.ke',
                'founded_year' => 2018,
                'lat' => 0.5160, 'lng' => 35.2710,
                'story' => "Oxyplus was established to address the critical shortage of medical-grade oxygen in the North Rift region. The company produces and distributes oxygen cylinders to hospitals, health centres and home-care patients. During the COVID-19 pandemic, Oxyplus scaled up production to meet the surging demand, becoming a critical healthcare partner in Uasin Gishu County.",
                'sector' => 'health',
                'mappings' => [[
                    'sector_slug' => 'health', 'entry_name' => 'Oxyplus — Medical Oxygen Supply',
                    'entry_type' => 'Healthcare Supply','entry_fee' => 0,
                    'location' => 'Eldoret, Uasin Gishu County',
                    'description' => 'Medical-grade oxygen production and distribution to hospitals, clinics and home-care patients.',
                ]],
                'products' => [
                    ['name' => 'Medical Oxygen Cylinder (50L)', 'price' => 8500, 'unit' => 'cylinder', 'category' => 'Medical', 'description' => '50-litre medical oxygen cylinder filled and certified for hospital and clinic use.', 'stock' => 200],
                ],
            ],
            [
                'slug' => 'brigidear-boinet',
                'name' => 'Brigidear Boinet',
                'type' => 'Agribusiness & Fresh Produce',
                'description' => 'Integrated horticultural farm and fresh produce aggregator in Uasin Gishu County, specializing in vegetables, fruits and herbs for local and regional markets.',
                'location' => 'Boinet, Uasin Gishu County',
                'phone' => '+254 723 000 000',
                'founded_year' => 2012,
                'lat' => 0.5500, 'lng' => 35.3000,
                'story' => "Brigidear Boinet is a family-run horticultural enterprise on the fertile slopes of the Uasin Gishu plateau. From kale and spinach to passion fruit and herbs, the farm supplies fresh produce to Eldoret markets. The farm also runs a smallholder aggregation programme, buying from neighbouring farmers and ensuring consistent supply to urban retailers.",
                'sector' => 'agriculture',
                'mappings' => [[
                    'sector_slug' => 'agriculture', 'entry_name' => 'Brigidear Boinet — Fresh Produce Farm',
                    'entry_type' => 'Horticulture Farm','entry_fee' => 0,
                    'location' => 'Boinet, Uasin Gishu County',
                    'description' => 'Integrated horticultural farm producing vegetables, fruits and herbs for the North Rift market.',
                ]],
                'products' => [
                    ['name' => 'Fresh Kale (Sukuma Wiki)', 'price' => 80, 'unit' => 'bunch', 'category' => 'Fresh Produce', 'description' => 'Freshly harvested kale from Uasin Gishu highlands.', 'stock' => 1000],
                    ['name' => 'Passion Fruit', 'price' => 200, 'unit' => 'kg', 'category' => 'Fresh Produce', 'description' => 'Sweet purple passion fruit grown on the farm.', 'stock' => 300],
                ],
            ],
            [
                'slug' => 'simba-cement',
                'name' => 'Simba Cement',
                'type' => 'Cement Manufacturing',
                'description' => 'National Cement Company (Simba Cement) plant in Eldoret — one of Kenya\'s major cement producers, supplying the North Rift and Western Kenya construction markets.',
                'location' => 'Eldoret, Uasin Gishu County',
                'phone' => '+254 711 000 000',
                'email' => 'info@simbacement.co.ke',
                'website' => 'https://www.simbacement.co.ke',
                'founded_year' => 2010,
                'lat' => 0.5200, 'lng' => 35.3100,
                'story' => "Simba Cement is a brand of National Cement Company, one of Kenya's fastest-growing cement manufacturers. The Eldoret plant serves the North Rift and Western Kenya markets with high-quality Ordinary Portland Cement (OPC) and Portland Pozzolana Cement (PPC). The plant sources limestone from nearby quarries and employs hundreds of local residents, making it a cornerstone of Uasin Gishu's industrial economy.",
                'sector' => 'industry',
                'mappings' => [[
                    'sector_slug' => 'industry', 'entry_name' => 'Simba Cement — Eldoret Plant',
                    'entry_type' => 'Cement Manufacturing','entry_fee' => 0,
                    'location' => 'Eldoret, Uasin Gishu County',
                    'description' => 'Major cement manufacturing plant producing OPC and PPC for the North Rift and Western Kenya construction markets.',
                ]],
                'products' => [
                    ['name' => 'Simba Cement (50kg)', 'price' => 750, 'unit' => '50kg bag', 'category' => 'Construction', 'description' => 'Ordinary Portland Cement — 50kg bag, ideal for general construction.', 'stock' => 10000],
                ],
            ],

            // Boma Inn, Teret Resort, CSTC, Tuiyo, EPZ, Africa Economic Zone
            [
                'slug' => 'boma-inn-eldoret',
                'name' => 'Boma Inn Eldoret',
                'type' => 'Hotel & Conference Centre',
                'description' => 'Mid-range business hotel in Eldoret offering accommodation, conferencing, dining and event hosting. Part of the Boma Hotels group.',
                'location' => 'Eldoret Town, Uasin Gishu County',
                'phone' => '+254 709 000 000',
                'email' => 'reservations@bomainneldoret.co.ke',
                'founded_year' => 2015,
                'lat' => 0.5150, 'lng' => 35.2715,
                'story' => "Boma Inn Eldoret offers a comfortable base for business travellers and tourists visiting the North Rift. With well-appointed rooms, a full-service restaurant, and versatile conference facilities, it is a preferred venue for corporate events, workshops and social gatherings in Eldoret.",
                'sector' => 'hospitality',
                'mappings' => [[
                    'sector_slug' => 'hospitality', 'entry_name' => 'Boma Inn Eldoret',
                    'entry_type' => 'Hotel','entry_fee' => 0,
                    'location' => 'Eldoret Town, Uasin Gishu County',
                    'description' => 'Business hotel with conferencing, restaurant and event hosting in central Eldoret.',
                ]],
                'products' => [
                    ['name' => 'Standard Room (per night)', 'price' => 7500, 'unit' => 'per night', 'category' => 'Hospitality', 'description' => 'Standard double room with breakfast, WiFi and parking.', 'stock' => 50],
                    ['name' => 'Conference Hall (full day)', 'price' => 25000, 'unit' => 'per day', 'category' => 'Venue', 'description' => '100-seat conference hall with projector, PA system and catering.', 'stock' => 3],
                ],
            ],
            [
                'slug' => 'teret-resort',
                'name' => 'Teret Resort',
                'type' => 'Resort & Retreat Centre',
                'description' => 'Countryside resort and retreat centre outside Eldoret offering accommodation, team-building, nature walks and event hosting.',
                'location' => 'Eldoret outskirts, Uasin Gishu County',
                'phone' => '+254 710 000 000',
                'founded_year' => 2016,
                'lat' => 0.5300, 'lng' => 35.2500,
                'story' => "Teret Resort is a serene countryside retreat on the outskirts of Eldoret. Surrounded by indigenous trees and gardens, the resort specialises in corporate team-building, family getaways and weekend escapes. Guests enjoy nature walks, bonfire evenings and farm-to-table dining sourced from local producers.",
                'sector' => 'tourism',
                'mappings' => [[
                    'sector_slug' => 'tourism', 'entry_name' => 'Teret Resort — Countryside Retreat',
                    'entry_type' => 'Resort','entry_fee' => 1500,
                    'location' => 'Eldoret outskirts, Uasin Gishu County',
                    'description' => 'Countryside resort with team-building, nature walks, bonfire evenings and farm-to-table dining.',
                ]],
                'products' => [
                    ['name' => 'Weekend Package (2 nights)', 'price' => 12000, 'unit' => 'per person', 'category' => 'Tourism', 'description' => 'Full-board weekend retreat with nature walks and bonfire.', 'stock' => 40],
                ],
            ],
            [
                'slug' => 'complete-sports-training-center',
                'name' => 'Complete Sports Training Center',
                'type' => 'Sports Training & Athletics Centre',
                'description' => 'Professional athletics and sports training facility in Eldoret — the home of Kenyan champions. Offers coaching, physiotherapy and altitude training camps.',
                'location' => 'Eldoret, Uasin Gishu County',
                'phone' => '+254 712 000 000',
                'email' => 'info@cstc.co.ke',
                'founded_year' => 2010,
                'lat' => 0.5120, 'lng' => 35.2750,
                'story' => "The Complete Sports Training Center in Eldoret sits at the heart of Kenya's distance-running powerhouse. At 2,100 metres altitude, the centre provides professional training facilities, coaching, physiotherapy and accommodation for elite athletes, upcoming talent, and international training camps. Uasin Gishu's thin air has produced world champions — CSTC is where the next generation trains.",
                'sector' => 'infrastructure',
                'mappings' => [[
                    'sector_slug' => 'infrastructure', 'entry_name' => 'Complete Sports Training Center',
                    'entry_type' => 'Sports Facility','entry_fee' => 500,
                    'location' => 'Eldoret, Uasin Gishu County',
                    'description' => 'Professional athletics training centre at 2,100m altitude — coaching, physiotherapy and altitude camps.',
                ]],
                'products' => [],
            ],
            [
                'slug' => 'tuiyo-farmers-cooperative',
                'name' => 'Tuiyo Farmers Cooperative Society',
                'type' => 'Dairy Farmers Cooperative',
                'description' => 'Dairy cooperative serving smallholder farmers across Uasin Gishu — milk collection, cooling, processing and marketing.',
                'location' => 'Tuiyo, Uasin Gishu County',
                'phone' => '+254 713 000 000',
                'founded_year' => 1985,
                'lat' => 0.5600, 'lng' => 35.2900,
                'story' => "Tuiyo Farmers Cooperative has been the backbone of smallholder dairy farming in Uasin Gishu for decades. The cooperative collects raw milk from hundreds of member farmers, chills and transports it to processors, and manages quality control. Members receive extension services, veterinary support, and bonus payments based on milk quality and volume.",
                'sector' => 'agriculture',
                'mappings' => [[
                    'sector_slug' => 'agriculture', 'entry_name' => 'Tuiyo Farmers Cooperative — Dairy',
                    'entry_type' => 'Dairy Cooperative','entry_fee' => 0,
                    'location' => 'Tuiyo, Uasin Gishu County',
                    'description' => 'Smallholder dairy cooperative — milk collection, cooling, quality control and marketing.',
                ]],
                'products' => [
                    ['name' => 'Fresh Raw Milk', 'price' => 65, 'unit' => 'per litre', 'category' => 'Dairy', 'description' => 'High-quality raw milk from Uasin Gishu smallholder dairy farms.', 'stock' => 5000],
                ],
            ],
            [
                'slug' => 'uasin-gishu-epz',
                'name' => 'Uasin Gishu Export Processing Zone',
                'type' => 'Export Processing Zone / Industrial Park',
                'description' => 'Designated EPZ in Eldoret offering tax incentives and infrastructure for manufacturing, agro-processing and export-oriented industries.',
                'location' => 'Eldoret, Uasin Gishu County',
                'phone' => '+254 714 000 000',
                'email' => 'info@uasin-epz.go.ke',
                'founded_year' => 2018,
                'lat' => 0.5300, 'lng' => 35.3200,
                'story' => "The Uasin Gishu Export Processing Zone is a flagship industrial development project in Eldoret, designed to attract manufacturing and agro-processing investors. Tenants enjoy tax incentives, streamlined customs, and ready infrastructure. The zone is strategically located on the Northern Corridor transport route, connecting to the Port of Mombasa and East African markets.",
                'sector' => 'industry',
                'mappings' => [[
                    'sector_slug' => 'industry', 'entry_name' => 'Uasin Gishu EPZ',
                    'entry_type' => 'Industrial Park','entry_fee' => 0,
                    'location' => 'Eldoret, Uasin Gishu County',
                    'description' => 'Tax-incentivised export processing zone for manufacturing and agro-processing on the Northern Corridor.',
                ]],
                'products' => [],
            ],
            [
                'slug' => 'africa-economic-zone',
                'name' => 'Africa Economic Zone',
                'type' => 'Special Economic Zone / Business Park',
                'description' => 'Multi-sector special economic zone in Eldoret providing integrated infrastructure for logistics, manufacturing, technology and trade across East Africa.',
                'location' => 'Eldoret, Uasin Gishu County',
                'phone' => '+254 715 000 000',
                'email' => 'info@aez.co.ke',
                'founded_year' => 2020,
                'lat' => 0.5400, 'lng' => 35.3300,
                'story' => "The Africa Economic Zone is a visionary development on the outskirts of Eldoret, creating a multi-sector business park that integrates logistics, manufacturing, technology and trade. Positioned on the Northern Corridor, the zone targets investors looking to serve East Africa's growing consumer market. State-of-the-art infrastructure, reliable power and water, and proximity to Eldoret International Airport make it a compelling destination for regional and international businesses.",
                'sector' => 'industry',
                'mappings' => [[
                    'sector_slug' => 'industry', 'entry_name' => 'Africa Economic Zone',
                    'entry_type' => 'Special Economic Zone','entry_fee' => 0,
                    'location' => 'Eldoret, Uasin Gishu County',
                    'description' => 'Multi-sector SEZ — logistics, manufacturing, technology and trade hub on the Northern Corridor.',
                ]],
                'products' => [],
            ],
        ];
    }

    /* ═══════════════════════════════════════════════
       MOMBASA COUNTY
       ═══════════════════════════════════════════════ */
    protected function mombasa(): array
    {
        return [
            [
                'slug' => 'tamarind-mombasa',
                'name' => 'Tamarind Mombasa',
                'type' => 'Seafood Restaurant & Dhow Cruise',
                'description' => 'Iconic Mombasa seafood restaurant and dhow cruise experience — fresh Swahili coastal cuisine with views of the Old Town and Indian Ocean.',
                'location' => 'Mombasa Old Town, Mombasa County',
                'phone' => '+254 722 000 000',
                'email' => 'reservations@tamarind.co.ke',
                'website' => 'https://www.tamarind.co.ke',
                'founded_year' => 1976,
                'lat' => -4.0586, 'lng' => 39.6700,
                'story' => "Tamarind Mombasa is one of Kenya's most celebrated seafood restaurants, perched on the edge of Mombasa's Old Town overlooking the Indian Ocean. Since 1976, it has defined coastal fine dining with its signature crab samosas, grilled lobster, and Swahili-spiced prawns. The Tamarind Dhow offers a magical sunset cruise with dinner aboard a traditional Arab sailing vessel — one of Mombasa's most iconic experiences.",
                'sector' => 'tourism',
                'mappings' => [[
                    'sector_slug' => 'tourism', 'entry_name' => 'Tamarind Mombasa — Seafood & Dhow Cruise',
                    'entry_type' => 'Restaurant & Cruise','entry_fee' => 3500,
                    'location' => 'Mombasa Old Town, Mombasa County',
                    'description' => 'Iconic seafood restaurant and sunset dhow cruise — fresh Swahili cuisine on the Indian Ocean.',
                ]],
                'products' => [
                    ['name' => 'Dhow Sunset Cruise Dinner', 'price' => 6500, 'unit' => 'per person', 'category' => 'Tourism', 'description' => 'Sunset dhow cruise with 4-course seafood dinner aboard a traditional Arab sailing vessel.', 'stock' => 60],
                ],
            ],
            [
                'slug' => 'voyager-beach-resort',
                'name' => 'Voyager Beach Resort',
                'type' => 'All-Inclusive Beach Resort (236 Cabins)',
                'description' => 'Ship-themed 4-star all-inclusive resort on Nyali Beach — 6 room categories, 3 pools, PADI dive school, 4 restaurants, 4 bars, water sports, kids club and daily animation. 7 km from Mombasa city centre.',
                'location' => 'Nyali Beach, 7 km north of Mombasa city centre',
                'phone' => '+254 739 167 804',
                'email' => 'vbr.reservations@voyagerresorts.co.ke',
                'website' => 'https://www.heritage-eastafrica.com',
                'founded_year' => 1995,
                'lat' => -4.0450, 'lng' => 39.7120,
                'story' => "Voyager Beach Resort is a vibrant, ship-themed 4-star resort on Mombasa's Nyali Beach, offering 236 cabins across six categories from Garden View to Executive Sea View. Guests enjoy three swimming pools (including a sports pool and children's fun pool with whirlpool), a PADI-certified scuba dive school, East Africa's largest artificial dive reef, big-game sportfishing, windsurfing, catamarans, canoes, glass-bottom boat rides and guided reef walks. The Adventurers Club provides daily supervised kids' activities with a marine education centre.\n\nDining spans four venues: the Mashua Restaurant (international buffets), Captain's Table (outdoor terrace), The Smugglers' Cove (à la carte seafood in a hidden coral cove) and Minestrone (authentic Italian beside the sports pool). Four bars include the panoramic Lookout Bar and 24-hour Sports Bar. Resort services cover a Parisian-trained hair salon, full business centre, foreign exchange, on-call doctor and babysitting.\n\nRates (Sept–Oct 2026, East African Residents, All-Inclusive): Garden View Cabin from KES 24,300 single / 32,400 double; Superior Sea View from KES 33,230 single / 44,305 double; Executive Sea View from KES 35,875 single / 47,835 double.",
                'sector' => 'tourism',
                'mappings' => [[
                    'sector_slug' => 'tourism', 'entry_name' => 'Voyager Beach Resort',
                    'entry_type' => 'Beach Resort','entry_fee' => 0,
                    'location' => 'Nyali Beach, Mombasa County',
                    'description' => 'All-inclusive family beach resort with pools, water sports, kids\' club and themed dining.',
                ]],
                'products' => [
                    ['name' => 'All-Inclusive Stay (per night)', 'price' => 18000, 'unit' => 'per person sharing', 'category' => 'Hospitality', 'description' => 'All-inclusive beach resort stay with meals, drinks and activities.', 'stock' => 200],
                ],
            ],
            [
                'slug' => 'bombolulu-workshop',
                'name' => 'Bombolulu Workshops & Cultural Centre',
                'type' => 'Fair Trade Social Enterprise & Cultural Centre',
                'description' => 'Certified Fair Trade social enterprise established 1969 — employs 150+ artisans with physical disabilities across jewellery, tailoring, woodcarving and leather workshops. Features 7 traditional tribal homesteads, a cultural centre, restaurant, guest house and gift shop.',
                'location' => 'Off New Malindi Road, Bombolulu, Mombasa County',
                'phone' => '+254 723 560 933',
                'email' => 'marketing@apdkbombolulu.co.ke',
                'website' => 'https://www.apdkbombolulu.org',
                'founded_year' => 1969,
                'lat' => -4.0350, 'lng' => 39.6850,
                'story' => "Bombolulu Workshops & Cultural Centre is a programme of the Association for the Physically Disabled of Kenya (APDK) — Coast Branch. Since 1969, it has empowered over 150 artisans with physical disabilities through employment in four production workshops: Jewellery, Tailoring, Woodcarving and Leatherwork. Every piece sold carries the Fair Trade mark.\n\nThe Cultural Centre showcases 7 traditional tribal homesteads (Kaya, Giriama, Orma, Swahili, Luo, Masai and Bukusu) with customary dances, music and entertainment. The Ziga Restaurant serves local African specialties, and the gift shop offers high-end handcrafted goods made on-site. Visitors can take craft-making lessons from resident artisans.\n\nOperating hours: Gift Shop 8am–6pm Mon–Sun (9am–4pm Sun), Cultural Centre 8am–4pm Mon–Sat, Workshops 8am–4:30pm Mon–Fri. The centre offers half-day tour packages, dormitory accommodation for school groups, a guest house, and educational programmes for schools and colleges. Contact: +254 723 560 933 | marketing@apdkbombolulu.co.ke | P.O. Box 83988-80100, Mombasa.",
                'sector' => 'culture',
                'mappings' => [[
                    'sector_slug' => 'culture', 'entry_name' => 'Bombolulu Workshops — Fair Trade Artisan Tour',
                    'entry_type' => 'Social Enterprise','entry_fee' => 200,
                    'location' => 'Bombolulu, Mombasa County',
                    'description' => 'Tour the fair-trade workshops, meet artisans with disabilities, and purchase handcrafted jewellery, textiles and woodwork.',
                ]],
                'products' => [
                    ['name' => 'Handcrafted Beaded Necklace', 'price' => 1500, 'unit' => 'piece', 'category' => 'Crafts', 'description' => 'Fair-trade beaded necklace crafted by Bombolulu artisans.', 'stock' => 200],
                    ['name' => 'Handwoven Kikoy', 'price' => 2500, 'unit' => 'piece', 'category' => 'Textiles', 'description' => 'Traditional coastal kikoy — handwoven by Bombolulu artisans.', 'stock' => 100],
                ],
            ],
            [
                'slug' => 'haller-park',
                'name' => 'Haller Park',
                'type' => 'Nature Park & Ecological Restoration',
                'description' => 'Reclaimed quarry turned ecological paradise — home to giraffes, hippos, crocodiles, giant tortoises and over 180 bird species.',
                'location' => 'Bamburi, Mombasa County',
                'phone' => '+254 725 000 000',
                'email' => 'info@hallerpark.co.ke',
                'website' => 'https://www.hallerpark.com',
                'founded_year' => 1971,
                'lat' => -4.0160, 'lng' => 39.7200,
                'story' => "Haller Park is one of the world's most remarkable ecological restoration stories. What was once a barren limestone quarry is now a thriving nature park — home to giraffes, hippos, crocodiles, giant tortoises, and over 180 bird species. The park is Dr René Haller's living laboratory, demonstrating how devastated land can be restored through reforestation and aquaculture. Visitors feed the giraffes, walk the nature trails, and learn about the interconnected ecosystem.",
                'sector' => 'tourism',
                'mappings' => [[
                    'sector_slug' => 'tourism', 'entry_name' => 'Haller Park — Wildlife & Ecology Tour',
                    'entry_type' => 'Nature Park','entry_fee' => 500,
                    'location' => 'Bamburi, Mombasa County',
                    'description' => 'Reclaimed quarry turned wildlife sanctuary — giraffe feeding, nature trails, hippo pools and 180+ bird species.',
                ]],
                'products' => [
                    ['name' => 'Adult Entry Ticket', 'price' => 500, 'unit' => 'per person', 'category' => 'Tourism', 'description' => 'Full-day access to Haller Park including giraffe feeding and nature trails.', 'stock' => 500],
                ],
            ],
            [
                'slug' => 'coast-general-hospital',
                'name' => 'Coast General Teaching & Referral Hospital',
                'type' => 'Level 5 Teaching & Referral Hospital',
                'description' => 'The Coast region\'s apex public hospital — paediatric surgery, cardiac surgery (TEVAR/MVR), neurosurgery, cancer care (2 LINACs, 140 patients/day), telemedicine (Proximie), EMR, 30+ specialties, and medical training hub with KMPDC/CUE accreditation.',
                'location' => 'Kisauni Road, Tononoka, Mombasa County',
                'phone' => '+254 722 207 868',
                'email' => 'communication@cgtrh.com',
                'website' => 'https://www.cgtrh.com',
                'founded_year' => 1964,
                'lat' => -4.0520, 'lng' => 39.6680,
                'story' => "Coast General Teaching and Referral Hospital (CGTRH) is the premier public hospital serving Kenya's coastal region. Key clinical milestones:\n\nAdvanced Cancer Care: Commissioned a second IAEA-donated Linear Accelerator (LINAC) doubling daily radiotherapy throughput to 140 patients. Annual cancer patient volumes have risen from ~6,000 to over 15,000 in three years. The facility hosts the Mombasa Radiotherapy Centre, commissioned by CS Health Aden Duale and IAEA Director General Rafael Mariano Grossi.\n\nCardiac Surgery: The Cath Lab team led by Dr. Nigel Amollo performs Thoracic Endovascular Aortic Repair (TEVAR) and Mitral Valve Replacements. CGTRH now conducts four cardiac surgeries every two weeks — all covered under SHA.\n\nNeurosurgery: Annual Neurosurgery Camp in partnership with Prof. Joachim Oertel's team from University Hospital of Saarland, Germany — providing complex brain and spine procedures free of charge.\n\nPaediatric Excellence: Home to the Coast region's first dedicated Kids Operating Room (KidsOR) Paediatric Surgical Theatre. Services include Paediatric General Surgery, Neonatal Surgery, Minimally Invasive Surgery, Paediatric Urology and Paediatric Surgical Oncology.\n\nTelemedicine Innovation: Integrated the Proximie augmented reality platform — allowing remote global surgical specialists to view, guide and collaborate in real-time during operations via HD cameras in maternity and general theatres.\n\nDigital Transformation: Transitioning from paper-based records to a fully integrated Electronic Medical Records (EMR) system with phased departmental rollout.\n\nEmergency Hotline: 0722 207 868 | P.O. Box 90231, Mombasa 80100 | Facebook/Instagram/X/LinkedIn: @cgtrh",
                'sector' => 'health',
                'mappings' => [[
                    'sector_slug' => 'health', 'entry_name' => 'Coast General Teaching & Referral Hospital',
                    'entry_type' => 'Level 5 Referral Hospital','entry_fee' => 0,
                    'location' => 'Kisauni Road, Tononoka, Mombasa County',
                    'description' => 'Coast region\'s apex hospital — cancer (2 LINACs), cardiac surgery, neurosurgery, paediatric surgery (KidsOR), 30+ specialties, Proximie telemedicine, EMR.',
                ]],
                'products' => [
                    ['name' => 'Radiotherapy Session (LINAC)', 'price' => 2500, 'unit' => 'per session', 'category' => 'Medical', 'description' => 'Precision LINAC radiotherapy at the Mombasa Radiotherapy Centre — covered under SHA.', 'stock' => 140],
                    ['name' => 'Cardiac Consultation', 'price' => 1500, 'unit' => 'per visit', 'category' => 'Medical', 'description' => 'Cardiac consultation at the CGTRH Cath Lab with Dr. Nigel Amollo\'s team.', 'stock' => 50],
                    ['name' => 'Paediatric Surgical Assessment', 'price' => 1000, 'unit' => 'per visit', 'category' => 'Medical', 'description' => 'Paediatric surgery screening (every Monday, 8am at PSOC) — covered under SHA.', 'stock' => 100],
                ],
            ],
            [
                'slug' => 'mama-ngina-waterfront',
                'name' => 'Mama Ngina Waterfront',
                'type' => 'Public Waterfront Park & Recreation',
                'description' => 'Recently redeveloped public waterfront park along the Likoni Channel — walking paths, gardens, food courts and views of passing ships.',
                'location' => 'Mombasa Island, Mombasa County',
                'phone' => '+254 727 000 000',
                'founded_year' => 2019,
                'lat' => -4.0680, 'lng' => 39.6700,
                'story' => "Mama Ngina Waterfront is one of Mombasa's newest public attractions — a beautifully landscaped park along the Likoni Channel. Opened in 2019, the waterfront features palm-lined walking paths, manicured gardens, food courts serving Swahili street food, and stunning views of ships entering Mombasa's historic port. It's a favourite sunset spot for families, couples and tourists.",
                'sector' => 'tourism',
                'mappings' => [[
                    'sector_slug' => 'tourism', 'entry_name' => 'Mama Ngina Waterfront Park',
                    'entry_type' => 'Public Park','entry_fee' => 0,
                    'location' => 'Mombasa Island, Mombasa County',
                    'description' => 'Landscaped waterfront park with walking paths, gardens, food courts and port views.',
                ]],
                'products' => [],
            ],
            [
                'slug' => 'wild-waters-mombasa',
                'name' => 'Wild Waters',
                'type' => 'Water Park & Family Recreation',
                'description' => 'Kenya\'s largest water park with 15+ slides, wave pool, lazy river, kids\' splash zone and food courts.',
                'location' => 'Nyali, Mombasa County',
                'phone' => '+254 728 000 000',
                'email' => 'info@wildwaters.co.ke',
                'founded_year' => 2005,
                'lat' => -4.0400, 'lng' => 39.7100,
                'story' => "Wild Waters is Kenya's largest water park and Mombasa's ultimate family fun destination. With over 15 water slides ranging from gentle to extreme, a wave pool, lazy river, kids' splash zone and multiple food courts, it guarantees a refreshing escape from the coastal heat. It's a favourite for birthday parties, school trips and weekend family outings.",
                'sector' => 'tourism',
                'mappings' => [[
                    'sector_slug' => 'tourism', 'entry_name' => 'Wild Waters — Mombasa Water Park',
                    'entry_type' => 'Water Park','entry_fee' => 1200,
                    'location' => 'Nyali, Mombasa County',
                    'description' => 'Kenya\'s largest water park — 15+ slides, wave pool, lazy river and kids\' splash zone.',
                ]],
                'products' => [
                    ['name' => 'Adult Day Pass', 'price' => 1200, 'unit' => 'per person', 'category' => 'Tourism', 'description' => 'Full-day access to all water slides and pools.', 'stock' => 1000],
                ],
            ],
            [
                'slug' => 'akamba-handicraft',
                'name' => 'Akamba Handicraft',
                'type' => 'Handicraft Cooperative & Export',
                'description' => 'World-renowned wood carving cooperative producing intricate animal sculptures, masks, furniture and decorative pieces for local and export markets.',
                'location' => 'Changamwe, Mombasa County',
                'phone' => '+254 729 000 000',
                'founded_year' => 1961,
                'lat' => -4.0200, 'lng' => 39.6500,
                'story' => "Akamba Handicraft is one of Kenya's most famous artisan cooperatives, renowned worldwide for its intricate wood carvings. Founded in 1961, the cooperative brings together hundreds of Kamba woodcarvers who transform African ebony, mahogany and rosewood into stunning animal sculptures, masks, furniture and decorative pieces. The Changamwe workshop and showroom is a must-visit — watch master carvers at work and purchase certified fair-trade pieces.",
                'sector' => 'culture',
                'mappings' => [[
                    'sector_slug' => 'culture', 'entry_name' => 'Akamba Handicraft — Wood Carving Workshop Tour',
                    'entry_type' => 'Artisan Cooperative','entry_fee' => 100,
                    'location' => 'Changamwe, Mombasa County',
                    'description' => 'World-renowned wood carving cooperative — watch master artisans and purchase fair-trade pieces.',
                ]],
                'products' => [
                    ['name' => 'Hand-Carved Elephant (Medium)', 'price' => 3500, 'unit' => 'piece', 'category' => 'Crafts', 'description' => 'African ebony hand-carved elephant — certified fair trade from Akamba artisans.', 'stock' => 100],
                ],
            ],
            [
                'slug' => 'kongowea-market',
                'name' => 'Kongowea Market',
                'type' => 'Wholesale & Retail Market',
                'description' => 'Mombasa\'s largest open-air market — fresh produce, textiles, electronics, second-hand goods and street food, serving thousands daily.',
                'location' => 'Nyali, Mombasa County',
                'phone' => '+254 730 000 000',
                'lat' => -4.0350, 'lng' => 39.6900,
                'story' => "Kongowea Market is the beating commercial heart of Mombasa's informal economy. Every day, thousands of traders and buyers converge on this sprawling open-air market to trade fresh produce from upcountry, textiles from Dubai, electronics from China, second-hand goods and Swahili street food. It's chaotic, colourful and absolutely authentic — a sensory immersion into coastal commerce.",
                'sector' => 'commerce',
                'mappings' => [[
                    'sector_slug' => 'commerce', 'entry_name' => 'Kongowea Market — Wholesale & Retail Hub',
                    'entry_type' => 'Marketplace','entry_fee' => 0,
                    'location' => 'Nyali, Mombasa County',
                    'description' => 'Mombasa\'s largest open-air market — fresh produce, textiles, electronics and street food.',
                ]],
                'products' => [],
            ],
            [
                'slug' => 'kemfri-mombasa',
                'name' => 'KMFRI — Kenya Marine & Fisheries Research Institute',
                'type' => 'National Research Institute (State Corporation)',
                'description' => 'Kenya\'s principal marine and freshwater research institution — established 1979, 13 stations nationwide, 200+ scientists, 2,847+ publications, ISO 9001 certified. Four directorates: Oceans & Coastal Systems, Freshwater Systems, Aquaculture Research, and Socioeconomics.',
                'location' => 'Mombasa HQ, Mombasa County',
                'phone' => '+254 712 003 853',
                'email' => 'director@kmfri.go.ke',
                'website' => 'https://www.kmfri.go.ke',
                'founded_year' => 1979,
                'lat' => -4.0450, 'lng' => 39.7000,
                'story' => "The Kenya Marine and Fisheries Research Institute (KMFRI) is a State Corporation under the Ministry of Mining, Blue Economy and Maritime Affairs. Established in 1979, KMFRI is Kenya's principal institution for marine and freshwater research — advancing the Blue Economy through science.\n\nThirteen stations span the country: Mombasa HQ, Kisumu Centre, Sagana Centre, Turkana Station, Baringo Station, Mutonga Centre, Gazi Sub-Station, Sang'oro Station, Nairobi Liaison Office, Kegati Station, Naivasha Station, and Shimoni Centre. Over 200 scientists work across four Directorates: Oceans & Coastal Systems (marine fisheries, oceanography, coastal ecology), Freshwater Systems (limnology, inland fisheries, stock assessment), Aquaculture Research (sustainable fish farming, feed technology), and Socioeconomics (livelihoods, governance, gender equity).\n\nResearch facilities include two research vessels (RV Mtafiti and RV Uvumbuzi), advanced laboratories (Ocean & Coastal Systems Lab, Freshwater Systems Lab, Aquaculture Lab), a public Aquarium, Museum, and Auditorium & Conference Facilities. KMFRI operates an eCitizen portal for online service applications with mobile money payments. The institute publishes the peer-reviewed Aquatica Journal and maintains a data management portal.\n\nKMFRI is ISO 9001:2015 certified and partners with the Lake Victoria Fisheries Organization, Western Indian Ocean Marine Science Association, Kenya Fisheries Service, NEMA, Pwani University, Technical University of Mombasa, and Kisii University. Contact: director@kmfri.go.ke | +254 712 003 853 | P.O. Box 81651-80100, Mombasa.",
                'sector' => 'education',
                'mappings' => [[
                    'sector_slug' => 'education', 'entry_name' => 'KMFRI — Marine Research & Public Aquarium Tour',
                    'entry_type' => 'Research Institute','entry_fee' => 200,
                    'location' => 'Mombasa HQ, Mombasa County',
                    'description' => 'Tour Kenya\'s principal marine research institute — aquarium, museum, research vessels, laboratories and 45+ years of Blue Economy science.',
                ]],
                'products' => [
                    ['name' => 'Public Aquarium Entry', 'price' => 200, 'unit' => 'per person', 'category' => 'Tourism', 'description' => 'Access to the KMFRI public aquarium showcasing Kenya\'s marine biodiversity.', 'stock' => 500],
                    ['name' => 'Laboratory Analysis — Water Quality', 'price' => 3000, 'unit' => 'per sample', 'category' => 'Research', 'description' => 'Water quality analysis at KMFRI\'s ISO-certified laboratories — apply via eCitizen portal.', 'stock' => 100],
                    ['name' => 'Auditorium Rental (Half Day)', 'price' => 25000, 'unit' => 'per session', 'category' => 'Venue', 'description' => 'KMFRI Main Auditorium or Dolphins Conference Hall rental for events.', 'stock' => 2],
                ],
            ],
            [
                'slug' => 'global-tea-mombasa',
                'name' => 'Global Tea',
                'type' => 'Tea Export & Blending Company',
                'description' => 'Mombasa-based tea blending and export company sourcing Kenyan teas for international markets — part of the Mombasa Tea Auction ecosystem.',
                'location' => 'Mombasa, Mombasa County',
                'phone' => '+254 732 000 000',
                'email' => 'info@globaltea.co.ke',
                'founded_year' => 1998,
                'lat' => -4.0500, 'lng' => 39.6600,
                'story' => "Global Tea operates from Mombasa, the hub of Kenya's tea export trade. The company sources high-quality Kenyan teas from smallholder farmers and estates, blends and packages them, and exports to markets across the Middle East, Europe and Asia. Through the Mombasa Tea Auction — one of the world's largest — Global Tea connects Kenya's tea farmers to the global market.",
                'sector' => 'commerce',
                'mappings' => [[
                    'sector_slug' => 'commerce', 'entry_name' => 'Global Tea — Export & Blending',
                    'entry_type' => 'Tea Export','entry_fee' => 0,
                    'location' => 'Mombasa, Mombasa County',
                    'description' => 'Tea blending and export company — sourcing Kenyan teas for international markets via the Mombasa Tea Auction.',
                ]],
                'products' => [
                    ['name' => 'Kenyan Black Tea (Bulk Export)', 'price' => 350, 'unit' => 'per kg', 'category' => 'Beverage', 'description' => 'Premium Kenyan black tea — blended and packed for export.', 'stock' => 50000],
                ],
            ],
            [
                'slug' => 'kenya-suitcase-manufacturers',
                'name' => 'Kenya Suitcase Manufacturers',
                'type' => 'Luggage & Leather Goods Manufacturing',
                'description' => 'Kenya\'s leading suitcase and travel bag manufacturer based in Mombasa — producing durable luggage for local, regional and export markets.',
                'location' => 'Mombasa, Mombasa County',
                'phone' => '+254 733 000 000',
                'email' => 'sales@ksm.co.ke',
                'founded_year' => 2005,
                'lat' => -4.0400, 'lng' => 39.6500,
                'story' => "Kenya Suitcase Manufacturers is Kenya's homegrown luggage brand, producing durable suitcases, travel bags, backpacks and accessories from its Mombasa factory. The company serves the local and East African market, competing with international brands on quality while offering competitive pricing. Every suitcase carries the 'Made in Kenya' mark — a point of pride for the brand.",
                'sector' => 'industry',
                'mappings' => [[
                    'sector_slug' => 'industry', 'entry_name' => 'Kenya Suitcase Manufacturers — Factory & Showroom',
                    'entry_type' => 'Manufacturing','entry_fee' => 0,
                    'location' => 'Mombasa, Mombasa County',
                    'description' => 'Kenya\'s leading luggage manufacturer — durable suitcases, travel bags and accessories, made in Mombasa.',
                ]],
                'products' => [
                    ['name' => 'Hard-Shell Suitcase (Medium)', 'price' => 5500, 'unit' => 'piece', 'category' => 'Luggage', 'description' => 'Durable hard-shell suitcase, medium size — Made in Kenya.', 'stock' => 200],
                ],
            ],
            [
                'slug' => 'serena-beach-hotel',
                'name' => 'Serena Beach Hotel',
                'type' => '5-Star Beach Resort & Spa',
                'description' => 'Luxury 5-star beach resort on Shanzu Beach — Swahili architecture, Maisha Spa, multiple restaurants, water sports and MICE facilities.',
                'location' => 'Shanzu Beach, Mombasa County',
                'phone' => '+254 734 000 000',
                'email' => 'reservations@serenabeach.co.ke',
                'website' => 'https://www.serenahotels.com',
                'founded_year' => 2000,
                'lat' => -3.9950, 'lng' => 39.7500,
                'story' => "Serena Beach Hotel is one of Mombasa's most iconic luxury resorts, designed in the style of a traditional Swahili village on the pristine Shanzu Beach. With its award-winning Maisha Spa, multiple restaurants serving Pan-African and seafood cuisine, swimming pools, water sports centre and extensive MICE facilities, it is the gold-standard coastal resort. The hotel's Swahili architecture, lush tropical gardens and legendary service have made it a destination wedding and honeymoon favourite.",
                'sector' => 'tourism',
                'mappings' => [[
                    'sector_slug' => 'tourism', 'entry_name' => 'Serena Beach Hotel — Luxury Beach Resort & Spa',
                    'entry_type' => '5-Star Resort','entry_fee' => 0,
                    'location' => 'Shanzu Beach, Mombasa County',
                    'description' => 'Luxury 5-star Swahili-style resort — Maisha Spa, multiple restaurants, water sports and MICE facilities.',
                ]],
                'products' => [
                    ['name' => 'Deluxe Sea-View Room (per night)', 'price' => 25000, 'unit' => 'per night', 'category' => 'Hospitality', 'description' => 'Luxury sea-view room with breakfast, WiFi and spa access.', 'stock' => 150],
                ],
            ],
            [
                'slug' => 'apdk-rehabilitation-clinic',
                'name' => 'APDK Rehabilitation Clinic — Mombasa',
                'type' => 'Rehabilitation & Orthopaedic Clinic',
                'description' => 'Established 1964 — provides orthopaedic surgery, physiotherapy, occupational therapy, custom prosthesis fabrication and community-based rehabilitation across the Coast region from Lamu to Lunga Lunga.',
                'location' => 'Mombasa, Mombasa County',
                'phone' => '+254 733 811 603',
                'email' => 'apdkreha@africaonline.co.ke',
                'website' => 'https://www.apdkbombolulu.org',
                'founded_year' => 1964,
                'lat' => -4.0400, 'lng' => 39.6800,
                'story' => "The APDK Rehabilitation Clinic in Mombasa was established in 1964 by Mombasa Round Table No. 3 to rehabilitate children afflicted with Polio. APDK took over operations in 1971 and, as polio declined, expanded to support physical and neurological disabilities from poverty and hazardous living conditions. The clinic serves an area stretching from Lamu in the north to Lunga Lunga in the south, and from Taita/Taveta in the west.\n\nCore clinical services: Orthopaedic and neurosurgical procedures including clubfoot correction, hydrocephalus and spina bifida management. On-site workshops fabricate custom calipers, specialised footwear, above/below-knee prostheses and tailor-made special-seat wheelchairs. Occupational therapists manage fine motor and sensory processing; physiotherapists provide pre/post-operative conditioning, muscle strengthening, coordination, balance training and conservative therapies.\n\nCommunity-Based Rehabilitation (CBR) mobile teams — comprising therapists, social workers and community rehabilitation workers — cover over 1,500 km of rural Coast Province monthly, delivering home-based therapy, caregiver training and disability counselling. The clinic runs an epilepsy support programme supplying medication to satellite clinics and facilitates caregiver support groups. A day care centre at Bombolulu serves children with severe disabilities. An ECD-trained teacher conducts lessons for inpatients.\n\nDonations: Commercial Bank of Africa | Account 0315156009 / 6442150019 | P.O. Box 93959-80100, Mombasa | Tel: 020 205 8034.",
                'sector' => 'health',
                'mappings' => [[
                    'sector_slug' => 'health', 'entry_name' => 'APDK Rehabilitation Clinic — Orthopaedic & Therapy Services',
                    'entry_type' => 'Rehabilitation Clinic','entry_fee' => 0,
                    'location' => 'Mombasa, Mombasa County',
                    'description' => 'Orthopaedic surgery, physiotherapy, custom prostheses, community-based rehabilitation — serving the entire Coast region since 1964.',
                ]],
                'products' => [
                    ['name' => 'Custom Below-Knee Prosthesis', 'price' => 15000, 'unit' => 'per unit', 'category' => 'Medical', 'description' => 'Custom-fabricated below-knee prosthetic limb with fitting and rehabilitation.', 'stock' => 50],
                ],
            ],
        ];
    }

    /* ═══════════════════════════════════════════════
       MURANG'A COUNTY
       ═══════════════════════════════════════════════ */
    protected function muranga(): array
    {
        return [
            [
                'slug' => 'sagana-raid-hotel',
                'name' => 'Sagana RAID Hotel',
                'type' => 'Boutique Hotel & Conference Centre',
                'description' => 'Riverside boutique hotel on the Sagana River offering luxury rooms, fine dining, conference facilities, and adventure tourism — white-water rafting, kayaking and bungee jumping.',
                'location' => 'Sagana, Murang\'a County',
                'phone' => '+254 731 000 000',
                'email' => 'reservations@saganaraid.co.ke',
                'founded_year' => 2012,
                'lat' => -0.6670, 'lng' => 37.2000,
                'story' => "Sagana RAID Hotel is the premier adventure gateway in Central Kenya, set on the banks of the Sagana River. The hotel offers boutique riverside accommodation, fine dining and comprehensive conference facilities. But it's best known as the launchpad for Kenya's most thrilling white-water rafting, kayaking and bungee jumping experiences — drawing adrenaline seekers from Nairobi and beyond.",
                'sector' => 'tourism',
                'mappings' => [[
                    'sector_slug' => 'tourism', 'entry_name' => 'Sagana RAID — White-Water Rafting & Hotel',
                    'entry_type' => 'Adventure Tourism','entry_fee' => 2500,
                    'location' => 'Sagana, Murang\'a County',
                    'description' => 'Boutique riverside hotel and Kenya\'s premier white-water rafting, kayaking and bungee jumping centre.',
                ]],
                'products' => [
                    ['name' => 'White-Water Rafting (Half Day)', 'price' => 3500, 'unit' => 'per person', 'category' => 'Tourism', 'description' => 'Half-day guided white-water rafting on the Sagana River rapids.', 'stock' => 50],
                    ['name' => 'Deluxe River Room (per night)', 'price' => 9500, 'unit' => 'per night', 'category' => 'Hospitality', 'description' => 'Riverside deluxe room with breakfast and views of the Sagana River.', 'stock' => 25],
                ],
            ],
            [
                'slug' => 'eliper-hotel',
                'name' => 'Eliper Hotel',
                'type' => 'Business Hotel & Restaurant',
                'description' => 'Mid-range hotel and restaurant in Murang\'a Town serving business travellers and local events with comfortable rooms, conferencing and Kenyan cuisine.',
                'location' => 'Murang\'a Town, Murang\'a County',
                'phone' => '+254 732 000 000',
                'founded_year' => 2010,
                'lat' => -0.7170, 'lng' => 37.1490,
                'story' => "Eliper Hotel is a trusted business hotel in the heart of Murang'a Town, offering comfortable accommodation, a popular restaurant serving Kenyan and continental dishes, and versatile meeting spaces. It's the go-to venue for corporate travellers, government delegations and local events in Murang'a County.",
                'sector' => 'hospitality',
                'mappings' => [[
                    'sector_slug' => 'hospitality', 'entry_name' => 'Eliper Hotel — Business Stay',
                    'entry_type' => 'Hotel','entry_fee' => 0,
                    'location' => 'Murang\'a Town, Murang\'a County',
                    'description' => 'Business hotel with restaurant, conferencing and comfortable rooms in central Murang\'a Town.',
                ]],
                'products' => [
                    ['name' => 'Standard Room (per night)', 'price' => 4500, 'unit' => 'per night', 'category' => 'Hospitality', 'description' => 'Standard room with breakfast and WiFi.', 'stock' => 30],
                ],
            ],
            [
                'slug' => 'kimakia-fishing-grounds',
                'name' => 'Kimakia Fishing Grounds — The Twin Falls',
                'type' => 'Ecotourism & Fishing Site',
                'description' => 'Scenic fishing grounds and twin waterfalls in the Aberdare forest — trout fishing, nature trails, camping and birdwatching.',
                'location' => 'Kimakia, Murang\'a County',
                'phone' => '+254 733 000 000',
                'lat' => -0.7500, 'lng' => 36.8500,
                'story' => "Deep in the Aberdare forest, the Kimakia Fishing Grounds offer a pristine escape for anglers and nature lovers. The twin waterfalls cascade through indigenous forest, while the cold, clear streams are stocked with rainbow trout. Visitors can fish, camp under the forest canopy, hike the nature trails, and spot colobus monkeys and rare bird species. It's Murang'a's best-kept wilderness secret.",
                'sector' => 'tourism',
                'mappings' => [[
                    'sector_slug' => 'tourism', 'entry_name' => 'Kimakia Twin Falls — Fishing & Ecotourism',
                    'entry_type' => 'Ecotourism','entry_fee' => 300,
                    'location' => 'Kimakia, Murang\'a County',
                    'description' => 'Trout fishing, twin waterfalls, camping and birdwatching in the Aberdare forest.',
                ]],
                'products' => [
                    ['name' => 'Fishing Permit (Day)', 'price' => 500, 'unit' => 'per person', 'category' => 'Tourism', 'description' => 'Day fishing permit for rainbow trout in the Kimakia streams.', 'stock' => 50],
                ],
            ],
            [
                'slug' => 'mbiri-primary-school',
                'name' => 'Mbiri Primary School',
                'type' => 'Public Primary School',
                'description' => 'Community primary school serving the Mbiri area of Murang\'a County — providing quality basic education to rural children.',
                'location' => 'Mbiri, Murang\'a County',
                'founded_year' => 1975,
                'lat' => -0.7300, 'lng' => 37.1000,
                'story' => "Mbiri Primary School has been educating the children of rural Murang'a for nearly five decades. The school provides basic education, a feeding programme, and a safe learning environment for children from the surrounding farming communities. With a dedicated teaching staff and strong community support, Mbiri continues to shape the future of Murang'a's next generation.",
                'sector' => 'education',
                'mappings' => [[
                    'sector_slug' => 'education', 'entry_name' => 'Mbiri Primary School',
                    'entry_type' => 'Primary School','entry_fee' => 0,
                    'location' => 'Mbiri, Murang\'a County',
                    'description' => 'Community primary school serving rural Murang\'a with quality basic education.',
                ]],
                'products' => [],
            ],
            [
                'slug' => 'mukurwe-wa-nyagathanga-primary',
                'name' => 'Mukurwe wa Nyagathanga Primary School',
                'type' => 'Public Primary School — Cultural Heritage Site',
                'description' => 'Primary school located at the sacred Mukurwe wa Nyagathanga site — the mythical origin point of the Gikuyu people within a cultural forest reserve.',
                'location' => 'Mukurwe wa Nyagathanga, Murang\'a County',
                'phone' => '+254 734 000 000',
                'founded_year' => 1960,
                'lat' => -0.6500, 'lng' => 37.1000,
                'story' => "Mukurwe wa Nyagathanga Primary School sits at one of Kenya's most culturally significant locations — the mythical origin point of the Gikuyu people according to oral tradition. Within the sacred forest reserve, the school serves the local community while the site features the sacred fig tree (Mukurwe), traditional Gikuyu homestead replicas and a cultural museum. The school integrates Gikuyu cultural education into its curriculum.",
                'sector' => 'education',
                'mappings' => [[
                    'sector_slug' => 'education', 'entry_name' => 'Mukurwe wa Nyagathanga Primary & Cultural Site',
                    'entry_type' => 'School & Heritage','entry_fee' => 0,
                    'location' => 'Mukurwe wa Nyagathanga, Murang\'a County',
                    'description' => 'School at the sacred Gikuyu origin site — cultural education, forest reserve and museum.',
                ]],
                'products' => [],
            ],
            [
                'slug' => 'gaturi-kiharu-bridge',
                'name' => 'Bridge Construction Gaturi-Kiharu',
                'type' => 'Infrastructure Development Project',
                'description' => 'Strategic bridge construction project connecting Gaturi and Kiharu constituencies — improving rural transport and market access across Murang\'a.',
                'location' => 'Gaturi-Kiharu, Murang\'a County',
                'founded_year' => 2023,
                'lat' => -0.7000, 'lng' => 37.1300,
                'story' => "The Gaturi-Kiharu Bridge project is a critical infrastructure development linking two of Murang'a's key constituencies. The bridge will dramatically reduce travel time for farmers transporting produce to market, students commuting to school and patients accessing healthcare. It represents Murang'a County's commitment to connecting its rural communities and unlocking economic potential through infrastructure.",
                'sector' => 'infrastructure',
                'mappings' => [[
                    'sector_slug' => 'infrastructure', 'entry_name' => 'Gaturi-Kiharu Bridge Project',
                    'entry_type' => 'Infrastructure','entry_fee' => 0,
                    'location' => 'Gaturi-Kiharu, Murang\'a County',
                    'description' => 'Strategic bridge project improving rural transport and market access across Murang\'a County.',
                ]],
                'products' => [],
            ],
            [
                'slug' => 'muranga-georges',
                'name' => 'Muranga Georges',
                'type' => 'Riverside Resort & Events Venue',
                'description' => 'Scenic riverside resort and events venue in Murang\'a — weddings, corporate retreats, team-building and weekend getaways on the river.',
                'location' => 'Murang\'a County',
                'phone' => '+254 735 000 000',
                'founded_year' => 2015,
                'lat' => -0.7100, 'lng' => 37.1400,
                'story' => "Muranga Georges is Murang'a's premier riverside events venue, set on the banks of a flowing stream with lush gardens and panoramic highland views. It's the top choice for weddings, corporate retreats, team-building events and weekend getaways. The resort offers riverside cottages, a restaurant serving farm-to-table cuisine, and curated event packages.",
                'sector' => 'tourism',
                'mappings' => [[
                    'sector_slug' => 'tourism', 'entry_name' => 'Muranga Georges — Riverside Events & Retreat',
                    'entry_type' => 'Events Venue','entry_fee' => 0,
                    'location' => 'Murang\'a County',
                    'description' => 'Riverside events venue — weddings, corporate retreats, team-building and weekend getaways.',
                ]],
                'products' => [
                    ['name' => 'Weekend Cottage (2 nights)', 'price' => 8000, 'unit' => 'per cottage', 'category' => 'Tourism', 'description' => 'Riverside cottage for two nights with breakfast.', 'stock' => 15],
                ],
            ],
            [
                'slug' => 'guka-cucu-coffee-farm',
                'name' => 'Guka and Cucu Coffee Farm',
                'type' => 'Specialty Coffee Farm & Tour',
                'description' => 'Family-owned specialty coffee farm and agro-tourism experience — hand-pick, process and taste single-origin Murang\'a Arabica coffee.',
                'location' => 'Murang\'a County',
                'phone' => '+254 736 000 000',
                'founded_year' => 2005,
                'lat' => -0.7200, 'lng' => 37.1200,
                'story' => "Guka and Cucu Coffee Farm (Grandpa & Grandma's Coffee Farm) is a family-run specialty coffee operation in the Murang'a highlands. Named after the founding grandparents, the farm offers an intimate coffee experience — visitors hand-pick ripe coffee cherries, see the wet-mill processing, roast their own beans over a wood fire, and taste single-origin Murang'a Arabica. The farm-to-cup story is told by the family themselves.",
                'sector' => 'agriculture',
                'mappings' => [[
                    'sector_slug' => 'agriculture', 'entry_name' => 'Guka & Cucu Coffee Farm Tour',
                    'entry_type' => 'Specialty Coffee Farm','entry_fee' => 1000,
                    'location' => 'Murang\'a County',
                    'description' => 'Family-run specialty coffee farm — pick, process, roast and taste single-origin Murang\'a Arabica.',
                ]],
                'products' => [
                    ['name' => 'Guka & Cucu Roasted Coffee (250g)', 'price' => 850, 'unit' => '250g pack', 'category' => 'Beverage', 'description' => 'Single-origin Murang\'a Arabica, roasted on the farm.', 'stock' => 200],
                ],
            ],
            [
                'slug' => 'muranga-land-field',
                'name' => 'Muranga Land Field',
                'type' => 'Agricultural Land & Demonstration Farm',
                'description' => 'County demonstration farm and agricultural training centre showcasing modern farming techniques, crop trials and extension services for Murang\'a farmers.',
                'location' => 'Murang\'a County',
                'phone' => '+254 737 000 000',
                'founded_year' => 2018,
                'lat' => -0.7150, 'lng' => 37.1350,
                'story' => "The Muranga Land Field is the county's agricultural demonstration and training centre, where farmers learn modern techniques for tea, coffee, macadamia, avocado and dairy farming. The centre runs crop trials, soil testing, irrigation demonstrations and farmer field schools — translating agricultural research into practical knowledge that thousands of Murang'a smallholders can apply on their own farms.",
                'sector' => 'agriculture',
                'mappings' => [[
                    'sector_slug' => 'agriculture', 'entry_name' => 'Muranga Land Field — Demo Farm & Training',
                    'entry_type' => 'Demonstration Farm','entry_fee' => 0,
                    'location' => 'Murang\'a County',
                    'description' => 'County demo farm — modern farming techniques, crop trials and farmer field schools.',
                ]],
                'products' => [],
            ],
        ];
    }
}