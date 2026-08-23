<?php

namespace Database\Seeders;

use App\Models\County;
use App\Models\CountyInstitution;
use App\Models\User;
use App\Services\InstitutionSyncService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class InstitutionSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure the role exists
        $role = Role::findOrCreate('institution_admin', 'web');

        $county = County::where('slug', 'muranga')->first();
        if (!$county) {
            $this->command->error('Muranga county not found. Aborting.');
            return;
        }

        $this->seedKakuzi($county, $role);
        $this->command->info('Institutions seeded.');
    }

    protected function seedKakuzi(County $county, Role $role): void
    {
        // Skip if already exists
        if (CountyInstitution::where('county_id', $county->id)->where('name', 'like', '%Kakuzi%')->exists()) {
            $this->command->info('Kakuzi already seeded. Skipping.');
            return;
        }

        // Admin user
        $admin = User::where('email', 'kakuzi@kicctest.org')->first();
        if (!$admin) {
            $admin = User::create([
                'name' => 'Kakuzi PLC',
                'email' => 'kakuzi@kicctest.org',
                'password' => bcrypt('Kakuzi@2025'),
                'account_type' => 'institution',
                'county_id' => $county->id,
                'status' => 'active',
            ]);
        }
        if (!$admin->hasRole('institution_admin')) {
            $admin->assignRole($role);
        }

        $institution = CountyInstitution::create([
            'county_id' => $county->id,
            'user_id' => $admin->id,
            'slug' => 'kakuzi-plc',
            'name' => 'Kakuzi PLC',
            'type' => 'Agricultural Corporation (PLC)',
            'description' => 'One of Africa\'s largest macadamia producers — full value-chain from orchard to export.',
            'location' => 'Makuyu, Murang\'a County',
            'headquarters' => 'Makuyu, Murang\'a County',
            'phone' => '+254 722 205895',
            'email' => 'info@kakuzi.co.ke',
            'website' => 'https://www.kakuzi.co.ke',
            'founded_year' => 1928,
            'lat' => -0.9956,
            'lng' => 37.1167,
            'is_published' => true,
            'story' => 'Kakuzi PLC is one of Kenya\'s oldest and largest agricultural corporations, operating 1,356 hectares of macadamia orchards in the fertile highlands of Murang\'a County. The company is one of the TOP 5 global macadamia producers, with 2% of total global production coming from its Maclands estate. Their macadamia production is packed and sold under the Maclands brand — the largest macadamia brand in Africa, offering economies of scale, flexibility and supply consistency across three countries of origin.

Kakuzi maintains full control over the entire value chain, from seedling to fork, ensuring complete traceability and a high-quality product. Their production is Halal, FSSC 22000, Kosher, SMETA and KEBS Standardization Mark certified. The company also produces avocados, blueberries, tea, commercial forestry, and operates livestock and butchery operations.

Macadamia is a core strategic crop for Kakuzi — total volumes are planned to rise by 139% by 2025. Their partnership with Green & Gold, a specialist macadamia marketing company, provides access to a wider global market while maintaining their aligned vision for value add and vertical integration.

Visitors can tour the vast macadamia plantations, see the harvesting, cracking, sizing & sorting, packing, cooling and dispatch processes, and experience the agro-economy of Murang\'a County firsthand.',
            'production_chain' => [
                ['step' => 'Seedling', 'description' => 'Macadamia trees are propagated in nurseries and planted across 1,356 hectares of orchards.'],
                ['step' => 'Orchard Cultivation', 'description' => 'Temperate climate of Murang\'a highlands provides ideal growing conditions for macadamia.'],
                ['step' => 'Harvesting', 'description' => 'Mature nuts are harvested from the orchards at peak ripeness.'],
                ['step' => 'Cracking', 'description' => 'The hard outer shells are cracked to extract the raw kernels.'],
                ['step' => 'Sizing & Sorting', 'description' => 'Kernels are graded by size and quality for export or further processing.'],
                ['step' => 'Packing', 'description' => 'Nuts are packed into cartons of 11.34kg under the Maclands brand.'],
                ['step' => 'Cooling', 'description' => 'Packed product is cooled to preserve freshness and shelf life.'],
                ['step' => 'Dispatch', 'description' => 'Fully traceable, certified product is dispatched to global markets.'],
            ],
            'sector_mappings' => [
                [
                    'sector_slug' => 'tourism',
                    'entry_name' => 'Kakuzi Macadamia Plantation Tour',
                    'entry_type' => 'Agro-tourism',
                    'entry_fee' => 500,
                    'location' => 'Makuyu, Murang\'a County',
                    'description' => 'Tour the vast 1,356-hectare macadamia plantations. See harvesting, cracking, sizing, packing and dispatch — the full journey from orchard to export, plus the story of the Maclands brand.',
                ],
                [
                    'sector_slug' => 'agriculture',
                    'entry_name' => 'Kakuzi PLC — Macadamia & Avocado Farm',
                    'entry_type' => 'Farm',
                    'entry_fee' => 0,
                    'location' => 'Makuyu, Murang\'a County',
                    'description' => 'One of Africa\'s largest macadamia producers. TOP 5 globally, 2% of world production, 1,356 ha orchards, FSSC 22000 / Halal / Kosher / KEBS certified.',
                ],
                [
                    'sector_slug' => 'commerce',
                    'entry_name' => 'Kakuzi Macadamia Oil & Nuts',
                    'entry_type' => 'Trade',
                    'entry_fee' => 0,
                    'location' => 'Makuyu, Murang\'a County',
                    'description' => 'Maclands brand macadamia products — oil, raw and roasted nuts — directly from the producer, fully traceable.',
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
            ],
        ]);

        $admin->update(['institution_id' => $institution->id]);

        // Run the sync algorithm immediately
        try {
            $summary = app(InstitutionSyncService::class)->sync($institution);
            $this->command->info('Kakuzi synced: ' . json_encode($summary));
        } catch (\Throwable $e) {
            $this->command->error('Kakuzi sync failed: ' . $e->getMessage());
        }
    }
}
