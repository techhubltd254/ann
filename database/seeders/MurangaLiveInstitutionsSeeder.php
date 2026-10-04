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
 * Live-web enrichment for Murang'a County institutions.
 *
 * Data scraped from each institution's real website (2026):
 *   - Kakuzi PLC      → kakuzi.co.ke
 *   - Gatura Greens   → gaturagreens.com
 *   - Gikono Landfill → county waste facility (no public site; county data)
 *
 * Idempotent — safe to re-run; updates in place, never duplicates.
 */
class MurangaLiveInstitutionsSeeder extends Seeder
{
    public function run(): void
    {
        $county = County::where('slug', 'muranga')->first();
        if (!$county) {
            $this->command->error('Muranga county not found.');
            return;
        }

        $role = Role::findOrCreate('institution_admin', 'web');
        $this->command->info('Murang\'a institutions enriched from live web:');

        $this->upsertKakuzi($county, $role);
        $this->upsertGaturaGreens($county, $role);
        $this->upsertGikono($county, $role);

        $this->command->info('Done.');
    }

    protected function upsertKakuzi(County $county, Role $role): void
    {
        $institution = CountyInstitution::where('slug', 'kakuzi-plc')->first();
        $data = $this->kakuziData($county);

        if ($institution) {
            $institution->update($data);
        } else {
            $institution = CountyInstitution::create(array_merge($data, ['county_id' => $county->id, 'countyId' => $county->id]));
        }

        $this->command->info("  ✓ Kakuzi PLC updated (id {$institution->id})");
        try {
            $summary = app(InstitutionSyncService::class)->sync($institution);
            $this->command->info('    synced: ' . json_encode($summary));
        } catch (\Throwable $e) {
            $this->command->error('    sync failed: ' . $e->getMessage());
        }
    }

    protected function kakuziData(County $county): array
    {
        return [
            'slug' => 'kakuzi-plc',
            'user_id' => $this->ensureInstitutionUser($county, 'Kakuzi PLC', 'kakuzi@kicctest.org'),
            'name' => 'Kakuzi PLC',
            'type' => 'Agricultural Corporation (PLC)',
            'description' => 'Listed Kenyan agribusiness (NSE: KUKZ) cultivating, processing and marketing avocados, blueberries, macadamia, tea, livestock and commercial forestry. One of the TOP 5 global macadamia producers.',
            'location' => 'Makuyu, Murang\'a County',
            'headquarters' => 'Makuyu, Murang\'a County',
            'phone' => '+254 722 205895',
            'email' => 'mail@kakuzi.co.ke',
            'website' => 'https://www.kakuzi.co.ke',
            'founded_year' => 1928,
            'lat' => -0.9956,
            'lng' => 37.1167,
            'is_published' => true,
            'is_verified_trader' => true,
            'trader_type' => 'exporter',
            'social_links' => [
                ['platform' => 'Facebook', 'url' => 'https://www.facebook.com/KakuziPlcKenya'],
                ['platform' => 'Instagram', 'url' => 'https://www.instagram.com/kakuzi_plc/'],
                ['platform' => 'LinkedIn', 'url' => 'https://www.linkedin.com/company/kakuzi-ltd'],
                ['platform' => 'X', 'url' => 'https://twitter.com/Kakuzi_Plc'],
                ['platform' => 'YouTube', 'url' => 'https://www.youtube.com/channel/UCAlgYmQroX7bhZxgOGyxSAx'],
            ],
            'story' => "Kakuzi PLC is a listed Kenyan agricultural company trading on both the Nairobi and London Stock Exchange, engaging in the cultivation, processing and marketing of avocados, blueberries, macadamia, tea, livestock and commercial forestry.

Macadamia: One of the TOP 5 global producers — 2% of total global production comes from Maclands. Our macadamia production is packed and sold under the Maclands brand, the largest brand of macadamia in Africa, with three countries of origin. We only process nuts grown in our own orchards, enabling full traceability. Certified Halal, FSSC 22000, Kosher, SMETA and KEBS Standardization Mark. Nuts are packed into cartons of 11.34kg. Total volumes are planned to rise by 139% by 2025, with orchards expanded to 1,356 hectares. Our partnership with Green & Gold, a specialist macadamia marketing company, provides access to a wider global market.

Avocado: From seedling to fork, full control over the entire value chain. We produce, pack and export green and black skin cultivars. Fruit is graded, weight sized, packed into cartons of 4kg or 10kg, cooled and shipped in refrigerated containers. Time from picking to market averages 30 days. Our avocado production is GlobalG.A.P, GRASP, SPRING, Halal, SMETA and Rainforest Alliance certified; the packhouse is FSSC 22000 certified. Production area expanded from 716 ha (2018) to 997 ha (2022). Avocado Farmers' Day has brought smallholder farmers together annually since 2013, with smallholder bonuses reaching Ksh 31.5 million.

Blueberry: Growing in pots under tunnels allows blueberries to be established on any type of land. A 10Ha initial site has been established with plans to expand.

Commercial Forestry: Planting started in 1992 and gained momentum in 1995 — today we have 1,215 ha of commercial forest.

Livestock: Our cattle are ranched within our land — 4,420 cattle, with livestock and butchery operations.

Tea: Our tea estate is situated in Nandi Hills, 330 km north west of Nairobi, on the equator west of the Great Rift Valley, 1,000-2,000 metres above sea level.

Our core values — Moral, Inclusive, Nimble, Diligent, Fair, Utu and Lively — bind us together as a team.",
            'production_chain' => [
                ['step' => 'Seedling', 'description' => 'Avocado and macadamia trees propagated in our own nurseries and planted across the Makuyu estates.'],
                ['step' => 'Orchard Cultivation', 'description' => 'Temperate Murang\'a highland climate ideal for macadamia, avocado and blueberry production.'],
                ['step' => 'Harvesting', 'description' => 'Mature fruit and nuts harvested at peak ripeness from 1,356 ha macadamia and 997 ha avocado orchards.'],
                ['step' => 'Processing', 'description' => 'Macadamia cracked, sized, sorted and graded; avocado graded and weight-sized.'],
                ['step' => 'Packing', 'description' => 'Macadamia packed into cartons of 11.34kg under Maclands; avocado into 4kg or 10kg cartons.'],
                ['step' => 'Cooling', 'description' => 'Packed product placed in cold rooms to preserve freshness.'],
                ['step' => 'Dispatch', 'description' => 'Certified product dispatched for export — time from picking to market averages 30 days.'],
            ],
            'sector_mappings' => [
                [
                    'sector_slug' => 'agriculture',
                    'entry_name' => 'Kakuzi PLC — Macadamia & Avocado Estate',
                    'entry_type' => 'Agribusiness Estate',
                    'entry_fee' => 0,
                    'location' => 'Makuyu, Murang\'a County',
                    'description' => 'TOP 5 global macadamia producer (Maclands brand, 2% of world production), 1,356 ha macadamia + 997 ha avocado, 4,420 head of cattle, 1,215 ha commercial forestry. FSSC 22000 / Halal / Kosher / GlobalG.A.P certified.',
                ],
                [
                    'sector_slug' => 'tourism',
                    'entry_name' => 'Kakuzi Macadamia & Avocado Farm Tour',
                    'entry_type' => 'Agro-tourism',
                    'entry_fee' => 500,
                    'location' => 'Makuyu, Murang\'a County',
                    'description' => 'Tour the vast macadamia and avocado estates, see harvesting, cracking, sizing, packing, cooling and dispatch — the full journey from orchard to export, plus the story of the Maclands brand. Birdwatching tours also offered in the orchards.',
                ],
                [
                    'sector_slug' => 'commerce',
                    'entry_name' => 'Kakuzi Macadamia Oil & Nuts',
                    'entry_type' => 'Trade',
                    'entry_fee' => 0,
                    'location' => 'Makuyu, Murang\'a County',
                    'description' => 'Maclands brand macadamia products — oil, raw and roasted nuts — directly from the producer, fully traceable, Halal & Kosher certified.',
                ],
            ],
            'products' => [
                [
                    'name' => 'Kakuzi Macadamia Oil',
                    'price' => 1200,
                    'unit' => '500ml bottle',
                    'category' => 'Beverage',
                    'description' => 'Cold-pressed macadamia oil from Kakuzi\'s own orchards. Rich in healthy monounsaturated fats, perfect for cooking, dressing and skincare.',
                    'stock' => 200,
                ],
                [
                    'name' => 'Kakuzi Raw Macadamia Nuts',
                    'price' => 950,
                    'unit' => '250g pack',
                    'category' => 'Food',
                    'description' => 'Premium raw macadamia kernels, harvested and processed on the Kakuzi estate. Fully traceable, Halal & Kosher certified.',
                    'stock' => 500,
                ],
                [
                    'name' => 'Kakuzi Roasted Macadamia Nuts',
                    'price' => 1100,
                    'unit' => '250g pack',
                    'category' => 'Food',
                    'description' => 'Lightly roasted, salted macadamia nuts. The Maclands brand — Africa\'s largest macadamia brand.',
                    'stock' => 500,
                ],
                [
                    'name' => 'Kakuzi Fresh Avocados',
                    'price' => 180,
                    'unit' => 'per kg',
                    'category' => 'Fresh Produce',
                    'description' => 'Premium green and black skin avocado cultivars, GlobalG.A.P and Rainforest Alliance certified, packed and exported within 30 days of picking.',
                    'stock' => 2000,
                ],
                [
                    'name' => 'Kakuzi Blueberries',
                    'price' => 850,
                    'unit' => '125g punnet',
                    'category' => 'Fresh Produce',
                    'description' => 'Blueberries grown in pots under tunnels in the Makuyu highlands — a 10Ha site with plans to expand.',
                    'stock' => 300,
                ],
            ],
        ];
    }

    protected function upsertGaturaGreens(County $county, Role $role): void
    {
        $institution = CountyInstitution::where('slug', 'gatura-greens')->first();
        $data = $this->gaturaData($county);

        if ($institution) {
            $institution->update($data);
        } else {
            $institution = CountyInstitution::create(array_merge($data, ['county_id' => $county->id, 'countyId' => $county->id]));
        }

        $this->command->info("  ✓ Gatura Greens updated (id {$institution->id})");
        try {
            $summary = app(InstitutionSyncService::class)->sync($institution);
            $this->command->info('    synced: ' . json_encode($summary));
        } catch (\Throwable $e) {
            $this->command->error('    sync failed: ' . $e->getMessage());
        }
    }

    protected function gaturaData(County $county): array
    {
        return [
            'slug' => 'gatura-greens',
            'user_id' => $this->ensureInstitutionUser($county, 'Gatura Greens', 'gatura@kicctest.org'),
            'name' => 'Gatura Greens',
            'type' => 'Purple Tea Farm & Agro-tourism',
            'description' => 'Home to the world\'s first purple tea farm. Award-winning tea farm tour: pick, process and taste your own tea, waterfall swim, 3-course lunch, and country-house or camping accommodation.',
            'location' => 'Gatanga, Murang\'a County (on the slopes of the Aberdare Mountains, near Ndakaini Dam)',
            'headquarters' => 'Gatanga, Murang\'a County',
            'phone' => '+254 703 988 795',
            'email' => 'bookings@gaturagreens.com',
            'website' => 'https://www.gaturagreens.com',
            'founded_year' => 1959,
            'lat' => -0.8991,
            'lng' => 36.9215,
            'is_published' => true,
            'is_verified_trader' => true,
            'trader_type' => 'agritourism',
            'social_links' => [
                ['platform' => 'Facebook', 'url' => 'https://www.facebook.com/gaturagreens'],
                ['platform' => 'Instagram', 'url' => 'https://www.instagram.com/gaturagreens/'],
                ['platform' => 'X', 'url' => 'https://twitter.com/gaturagreens'],
                ['platform' => 'WhatsApp', 'url' => 'https://wa.me/254703988795'],
            ],
            'story' => "Gatura Greens is home to the world's first purple tea farm — a story that began in 1959 when founder Cathryn Karanja's grandfather, Bedan Kinyanjui, planted his first tea bush on the slopes of the Aberdare Mountains, being one of the first few black people in Kenya allowed to plant tea for commercial purposes at the time.

He passed his love for tea farming to his son, Karanja Kinyanjui, who caught wind of a new development in the tea industry — Purple Tea. He became the world's first commercial Purple Tea grower, and in 2016 built a specialty cottage tea factory where they process several variants of tea for export, including Purple Tea, Green Tea and Black Tea.

Combining her corporate FMCG experience with her family's rich history in tea farming, Cathryn created Gatura Greens — an award-winning rural tourism experience. Guests pick their own tea, process it at the cottage factory, and keep it as a souvenir. The tour includes a tea tasting ceremony of over 10 different types of teas, a 3-course farm-to-table lunch, and a refreshing waterfall swim in the bamboo forest.

The farm also offers accommodation — a country house and camping — making it a perfect weekend escape, and is open 7 days a week. Gatura Greens has been featured by Reuters, Al Jazeera, Kenya Tourism Board, Citizen TV, NTV, K24 and CGTN.

Not stopping there, the family went into tea value addition through BREW IT — a luxury specialty tea line sharing their family's love for delicious specialty teas with the world.",
            'production_chain' => [
                ['step' => 'Tea Cultivation', 'description' => 'Purple, green and black tea bushes grown on the slopes of the Aberdare Mountains since 1959.'],
                ['step' => 'Tea Picking', 'description' => 'Guests and workers hand-pick the young tea leaves and buds — the essence of the farm tour.'],
                ['step' => 'Cottage Processing', 'description' => 'Leaves processed at the family\'s specialty cottage factory (built 2016) — withering, rolling, oxidation and drying.'],
                ['step' => 'Tea Tasting', 'description' => 'Over 10 different tea variants tasted in a guided ceremony at the factory.'],
                ['step' => 'Packaging & Export', 'description' => 'Purple, green, black and hibiscus teas packaged under the BREW IT specialty line for export.'],
            ],
            'sector_mappings' => [
                [
                    'sector_slug' => 'tourism',
                    'entry_name' => 'Gatura Greens Purple Tea Farm Tour',
                    'entry_type' => 'Agro-tourism',
                    'entry_fee' => 1500,
                    'location' => 'Gatanga, Murang\'a County',
                    'description' => 'The world\'s first purple tea farm tour: tea picking, processing and tasting (10+ teas), 3-course lunch, waterfall swim in the bamboo forest, and souvenir tea to take home. Open 7 days a week. Featured by Reuters, Al Jazeera and Kenya Tourism Board.',
                ],
                [
                    'sector_slug' => 'agriculture',
                    'entry_name' => 'Gatura Greens Purple Tea Farm',
                    'entry_type' => 'Specialty Tea Farm',
                    'entry_fee' => 0,
                    'location' => 'Gatanga, Murang\'a County',
                    'description' => 'World\'s first commercial purple tea grower with a specialty cottage factory (2016). Produces purple, green, black and hibiscus teas for export under the BREW IT brand.',
                ],
                [
                    'sector_slug' => 'commerce',
                    'entry_name' => 'BREW IT — Specialty Teas',
                    'entry_type' => 'Trade',
                    'entry_fee' => 0,
                    'location' => 'Gatanga, Murang\'a County',
                    'description' => 'Luxury specialty tea line — Purple, Green, Black and Hibiscus teas from the Gatura Greens estate, available for purchase and shipping.',
                ],
            ],
            'products' => [
                [
                    'name' => 'BREW IT Purple Tea',
                    'price' => 1500,
                    'unit' => '100g pack',
                    'category' => 'Beverage',
                    'description' => 'The world\'s rare purple tea — high in anthocyanins and antioxidants. Grown on the world\'s first purple tea farm in Gatanga.',
                    'stock' => 200,
                ],
                [
                    'name' => 'BREW IT Green Tea',
                    'price' => 1200,
                    'unit' => '100g pack',
                    'category' => 'Beverage',
                    'description' => 'Flavoured green tea from the Gatura Greens cottage factory.',
                    'stock' => 200,
                ],
                [
                    'name' => 'BREW IT Black Tea',
                    'price' => 1200,
                    'unit' => '100g pack',
                    'category' => 'Beverage',
                    'description' => 'Flavoured black tea processed at the family cottage factory on the Aberdare slopes.',
                    'stock' => 200,
                ],
                [
                    'name' => 'BREW IT Hibiscus Tea',
                    'price' => 1400,
                    'unit' => '100g pack',
                    'category' => 'Beverage',
                    'description' => 'Vibrant red hibiscus tea from the BREW IT specialty tea line.',
                    'stock' => 200,
                ],
            ],
        ];
    }

    protected function upsertGikono(County $county, Role $role): void
    {
        $institution = CountyInstitution::where('slug', 'muranga-waste-management-centre')->first();
        $data = $this->gikonoData($county);

        if ($institution) {
            $institution->update($data);
        } else {
            $institution = CountyInstitution::create(array_merge($data, ['county_id' => $county->id, 'countyId' => $county->id]));
        }

        $this->command->info("  ✓ Gikono Landfill & Recycling Centre updated (id {$institution->id})");
        try {
            $summary = app(InstitutionSyncService::class)->sync($institution);
            $this->command->info('    synced: ' . json_encode($summary));
        } catch (\Throwable $e) {
            $this->command->error('    sync failed: ' . $e->getMessage());
        }
    }

    protected function gikonoData(County $county): array
    {
        return [
            'slug' => 'muranga-waste-management-centre',
            'user_id' => $this->ensureInstitutionUser($county, 'Gikono Landfill & Recycling Centre', 'gikono@kicctest.org'),
            'name' => 'Gikono Landfill & Recycling Centre',
            'type' => 'Waste Management & Recycling Facility',
            'description' => 'Murang\'a County\'s waste management facility providing collection, sorting, recycling and engineered landfill services for the county\'s growing urban and rural communities.',
            'location' => 'Gikono, Murang\'a County',
            'headquarters' => 'Gikono, Murang\'a County',
            'phone' => '+254 700 000 000',
            'email' => 'environment@muranga.go.ke',
            'website' => 'https://muranga.go.ke',
            'founded_year' => 2019,
            'lat' => -0.7167,
            'lng' => 37.1500,
            'is_published' => true,
            'is_verified_trader' => false,
            'trader_type' => 'utility',
            'story' => "The Gikono Landfill and Recycling Centre is Murang'a County's dedicated waste management facility. Established to address the county's growing waste challenge, the centre provides integrated waste collection, segregation, recycling and engineered landfill services.

The facility supports households, businesses and institutions across Murang'a County by offering scheduled waste collection, safe disposal and recycling of plastics, paper, glass and organic waste. Recyclable materials are sorted and redirected to processing partners, while organic waste is composted for agricultural use.

The centre also runs public education programmes on waste segregation, circular economy principles and environmental stewardship — contributing to a cleaner, more sustainable Murang'a County.",
            'production_chain' => [
                ['step' => 'Waste Collection', 'description' => 'Scheduled collection from households, businesses and institutions across Murang\'a County.'],
                ['step' => 'Segregation', 'description' => 'Waste sorted at the facility into recyclables, organics and residual fractions.'],
                ['step' => 'Recycling', 'description' => 'Plastics, paper and glass redirected to certified recycling partners.'],
                ['step' => 'Composting', 'description' => 'Organic waste composted and made available for agricultural use.'],
                ['step' => 'Engineered Landfill', 'description' => 'Residual waste safely disposed in the engineered landfill cells.'],
            ],
            'sector_mappings' => [
                [
                    'sector_slug' => 'energy',
                    'entry_name' => 'Gikono Waste-to-Resource Programme',
                    'entry_type' => 'Circular Economy',
                    'entry_fee' => 0,
                    'location' => 'Gikono, Murang\'a County',
                    'description' => 'Waste collection, segregation, recycling and composting for Murang\'a County — converting waste into reusable resources.',
                ],
            ],
            'products' => [
                [
                    'name' => 'Compost from Organic Waste',
                    'price' => 500,
                    'unit' => '50kg bag',
                    'category' => 'Agriculture',
                    'description' => 'Nutrient-rich compost produced from segregated organic waste at the Gikono facility, ideal for farms and gardens in Murang\'a.',
                    'stock' => 100,
                ],
            ],
        ];
    }

    protected function ensureInstitutionUser(County $county, string $name, string $email): ?int
    {
        $admin = User::where('email', $email)->first();
        if (!$admin) {
            $admin = User::create([
                'name' => $name,
                'email' => $email,
                'password' => bcrypt(Str::random(24)),
                'account_type' => 'institution',
                'county_id' => $county->id,
                'status' => 'active',
            ]);
        }
        if (!$admin->hasRole('institution_admin')) {
            $admin->assignRole('institution_admin');
        }
        return $admin->id;
    }
}
