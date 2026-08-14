<?php

namespace Database\Seeders;

use App\Models\TradeAgreement;
use App\Models\TradingBloc;
use Illuminate\Database\Seeder;

class TradeAgreementsSeeder extends Seeder
{
    public function run(): void
    {
        // ─── TRADING BLOCS ───
        $blocs = [
            ['name' => 'East African Community', 'code' => 'EAC', 'member_states' => '8 (Kenya, Uganda, Tanzania, Rwanda, Burundi, South Sudan, DRC, Somalia)', 'description' => 'The East African Community (EAC) is a regional intergovernmental organisation of 8 partner states. The EAC Customs Union was established in 2005, followed by the Common Market Protocol in 2010. Kenya is the largest economy in the bloc, benefiting from free movement of goods, services, labour and capital across the region.'],
            ['name' => 'African Continental Free Trade Area', 'code' => 'AfCFTA', 'member_states' => '54 (all AU member states except Eritrea)', 'description' => 'The African Continental Free Trade Area (AfCFTA) is the world\'s largest free trade area by number of participating countries, covering 1.4 billion people with a combined GDP of $3.4 trillion. Trading under the AfCFTA commenced in January 2021. Kenya was among the first countries to ratify the agreement and is a key beneficiary for manufactured goods, tea, coffee, horticulture and services exports.'],
            ['name' => 'Common Market for Eastern and Southern Africa', 'code' => 'COMESA', 'member_states' => '21', 'description' => 'COMESA is a regional economic community with 21 member states stretching from Libya to Eswatini. Kenya has been a member since 1994. The COMESA Free Trade Area eliminates tariffs on goods originating from member states. Kenya\'s key exports under COMESA include chemicals, machinery, textiles and agricultural products.'],
            ['name' => 'World Trade Organization', 'code' => 'WTO', 'member_states' => '166', 'description' => 'Kenya has been a WTO member since its founding in 1995. As a signatory to the General Agreement on Tariffs and Trade (GATT), Agreement on Agriculture, General Agreement on Trade in Services (GATS), Agreement on Textiles and Clothing, and Trade-Related Aspects of Intellectual Property Rights (TRIPS), Kenya benefits from MFN treatment, dispute settlement mechanisms, and special & differential treatment provisions for developing countries.'],
        ];

        foreach ($blocs as $b) {
            TradingBloc::firstOrCreate(
                ['code' => $b['code']],
                $b + ['is_active' => true]
            );
        }

        // ─── TRADE AGREEMENTS ───
        $eac = TradingBloc::where('code', 'EAC')->first();
        $afcfta = TradingBloc::where('code', 'AfCFTA')->first();
        $comesa = TradingBloc::where('code', 'COMESA')->first();
        $wto = TradingBloc::where('code', 'WTO')->first();

        $agreements = [
            [
                'trading_bloc_id' => $eac->id,
                'title' => 'EAC Customs Union Protocol',
                'summary' => 'Establishes a customs union among EAC partner states with a common external tariff, eliminating internal tariffs on goods originating from within the EAC.',
                'agreement_type' => 'regional',
                'signed_date' => '2005-03-02',
                'effective_date' => '2005-01-01',
                'status' => 'active',
                'is_featured' => true,
                'benefits' => json_encode(['Duty-free trade within 8 EAC countries', 'Common external tariff protects local industries', 'Simplified customs procedures and documentation', 'Free movement of goods across borders', 'Access to a market of 300+ million consumers']),
                'sector_coverage' => json_encode(['Agriculture', 'Manufacturing', 'Textiles', 'Chemicals', 'Services', 'All goods originating from EAC']),
                'county_impact' => 'all',
            ],
            [
                'trading_bloc_id' => $eac->id,
                'title' => 'EAC Common Market Protocol',
                'summary' => 'Guarantees free movement of labour, services, capital and the right of establishment across EAC partner states, building on the Customs Union.',
                'agreement_type' => 'regional',
                'signed_date' => '2010-11-20',
                'effective_date' => '2010-07-01',
                'status' => 'active',
                'benefits' => json_encode(['Free movement of labour across EAC', 'Right to establish businesses in any partner state', 'Free movement of capital and investments', 'Harmonised professional qualifications', 'Access to regional employment markets']),
                'sector_coverage' => json_encode(['Labour', 'Services', 'Capital', 'Investment', 'Professional services']),
            ],
            [
                'trading_bloc_id' => $afcfta->id,
                'title' => 'African Continental Free Trade Area Agreement',
                'summary' => 'The AfCFTA creates a single continental market for goods and services, aiming to eliminate tariffs on 90% of goods, boost intra-African trade by 52%, and lift 30 million people out of extreme poverty.',
                'agreement_type' => 'multilateral',
                'signed_date' => '2018-03-21',
                'effective_date' => '2019-05-30',
                'status' => 'active',
                'is_featured' => true,
                'benefits' => json_encode(['Duty-free access to 54 African countries', 'Market of 1.4 billion people', 'Elimination of tariffs on 90% of goods', 'Harmonised rules of origin for African products', 'Dispute resolution mechanism', 'Special treatment for Kenyan SMEs and women-owned businesses']),
                'sector_coverage' => json_encode(['Agriculture', 'Manufacturing', 'Textiles & Apparel', 'Automotive', 'Pharmaceuticals', 'Digital Services', 'Financial Services', 'Transport & Logistics']),
                'county_impact' => 'all',
            ],
            [
                'trading_bloc_id' => $afcfta->id,
                'title' => 'AfCFTA Protocol on Trade in Services',
                'summary' => 'Liberalises trade in services across five priority sectors: business services, communications, financial services, tourism, and transport services.',
                'agreement_type' => 'multilateral',
                'signed_date' => '2019-02-10',
                'effective_date' => '2021-01-01',
                'status' => 'active',
                'benefits' => json_encode(['Market access for Kenyan service providers across Africa', 'Liberalised tourism, transport and financial services', 'Mutual recognition of professional qualifications', 'E-commerce and digital trade provisions']),
                'sector_coverage' => json_encode(['Business Services', 'Communications', 'Financial Services', 'Tourism & Travel', 'Transport & Logistics']),
            ],
            [
                'trading_bloc_id' => $comesa->id,
                'title' => 'COMESA Free Trade Area',
                'summary' => 'Establishes a free trade area among COMESA member states with duty-free and quota-free access for goods originating from within the bloc.',
                'agreement_type' => 'regional',
                'signed_date' => '2000-10-31',
                'effective_date' => '2000-10-31',
                'status' => 'active',
                'is_featured' => true,
                'benefits' => json_encode(['Duty-free access to 21 COMESA countries', 'Preferential rules of origin for Kenyan goods', 'COMESA Customs Union simplifies cross-border trade', 'COMESA Competition Commission ensures fair trade', 'Access to COMESA Court of Justice for dispute resolution']),
                'sector_coverage' => json_encode(['Agriculture', 'Manufacturing', 'Textiles', 'Chemicals', 'Machinery', 'All originating goods']),
                'county_impact' => 'all',
            ],
            [
                'trading_bloc_id' => $comesa->id,
                'title' => 'COMESA-EAC-SADC Tripartite Free Trade Area',
                'summary' => 'The Tripartite FTA merges three regional economic communities (COMESA, EAC, SADC) into a single free trade area, creating a market of 29 countries and 800 million people.',
                'agreement_type' => 'regional',
                'signed_date' => '2015-06-10',
                'effective_date' => '2015-06-10',
                'status' => 'active',
                'benefits' => json_encode(['Single market across 29 African countries', 'Harmonised customs procedures across three blocs', 'Reduced non-tariff barriers', 'Joint infrastructure and transport corridors', 'Simplified rules of origin']),
                'sector_coverage' => json_encode(['Agriculture', 'Manufacturing', 'Trade facilitation', 'Infrastructure', 'Transport']),
            ],
            [
                'trading_bloc_id' => null,
                'title' => 'Kenya-United Kingdom Economic Partnership Agreement',
                'summary' => 'The Kenya-UK EPA ensures continued duty-free and quota-free access for Kenyan exports to the United Kingdom post-Brexit, covering all goods originating from Kenya.',
                'partner_country' => 'United Kingdom',
                'agreement_type' => 'bilateral',
                'signed_date' => '2020-12-08',
                'effective_date' => '2021-01-01',
                'status' => 'active',
                'is_featured' => true,
                'benefits' => json_encode(['Duty-free access to UK market for all Kenyan goods', 'Protects Kenyan exports post-Brexit', 'Gradual liberalisation of UK imports to Kenya', 'Development cooperation provisions', 'SPS and TBT cooperation for agricultural exports']),
                'sector_coverage' => json_encode(['Agriculture (tea, coffee, flowers, vegetables)', 'Textiles & Apparel', 'Manufacturing', 'All goods originating from Kenya']),
                'county_impact' => 'all',
            ],
            [
                'trading_bloc_id' => $wto->id,
                'title' => 'WTO Trade Facilitation Agreement',
                'summary' => 'The WTO Trade Facilitation Agreement (TFA) aims to expedite the movement, release and clearance of goods across borders, reducing trade costs by an average of 14.3%.',
                'agreement_type' => 'multilateral',
                'signed_date' => '2013-12-07',
                'effective_date' => '2017-02-22',
                'status' => 'active',
                'benefits' => json_encode(['Simplified customs procedures', 'Reduced border clearance times', 'Transparency in trade regulations', 'Technical assistance for developing countries', 'Lower trade costs for Kenyan exporters']),
                'sector_coverage' => json_encode(['Trade facilitation', 'Customs', 'Border management', 'All traded goods']),
            ],
            [
                'trading_bloc_id' => $wto->id,
                'title' => 'WTO Agreement on Agriculture',
                'summary' => 'The AoA covers market access, domestic support and export competition in agricultural trade, providing special and differential treatment for developing countries like Kenya.',
                'agreement_type' => 'multilateral',
                'signed_date' => '1994-04-15',
                'effective_date' => '1995-01-01',
                'status' => 'active',
                'benefits' => json_encode(['Special & differential treatment for Kenyan farmers', 'Reduced agricultural subsidies in developed countries', 'Improved market access for Kenyan agricultural exports', 'Protection for food security programmes']),
                'sector_coverage' => json_encode(['Agriculture', 'Tea', 'Coffee', 'Horticulture', 'Food security']),
            ],
            [
                'trading_bloc_id' => null,
                'title' => 'South Africa Lifts Duties on Kenyan Tea, Coffee and Spices Under SACU Deal',
                'summary' => 'South Africa has lifted duties on Kenyan tea, coffee and spices under the SACU agreement, opening a major market for Kenyan agricultural exports.',
                'partner_country' => 'South Africa (SACU)',
                'agreement_type' => 'bilateral',
                'signed_date' => '2026-07-01',
                'effective_date' => '2026-07-01',
                'status' => 'active',
                'is_featured' => true,
                'benefits' => json_encode(['Duty-free access for Kenyan tea to South Africa', 'Duty-free access for Kenyan coffee to South Africa', 'New market opportunities for Kenyan spices', 'Strengthened Kenya-SACU trade relations']),
                'sector_coverage' => json_encode(['Tea', 'Coffee', 'Spices', 'Agriculture']),
                'county_impact' => 'muranga,kiambu,kericho,nandi,kisii',
            ],
        ];

        foreach ($agreements as $a) {
            TradeAgreement::firstOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($a['title'])],
                $a + ['is_active' => true]
            );
        }

        $this->command?->info('Seeded ' . count($agreements) . ' trade agreements and ' . count($blocs) . ' trading blocs.');
    }
}
