<?php

namespace App\Services;

use App\Models\County;
use App\Models\CountyFinancialConfig;
use Illuminate\Support\Facades\DB;

class RevenueShareService
{
    // Per revenue source splits (platform : county)
    private array $defaultSplits = [
        'booth_booking' => ['platform' => 30, 'county' => 70],
        'marketplace' => ['platform' => 30, 'county' => 70],
        'travel' => ['platform' => 50, 'county' => 50],
        'local_ads' => ['platform' => 20, 'county' => 80],
        'subscription' => ['platform' => 70, 'county' => 30],
        'escrow' => ['platform' => 50, 'county' => 50],
    ];

    public function split(float $amount, string $source, ?County $county = null): array
    {
        $split = $this->getSplit($source, $county);

        $platformShare = round($amount * $split['platform'] / 100, 2);
        $countyShare = round($amount * $split['county'] / 100, 2);

        // Handle rounding remainder
        $remainder = round($amount - $platformShare - $countyShare, 2);
        $countyShare += $remainder;

        return [
            'total' => $amount,
            'platform' => $platformShare,
            'county' => $countyShare,
            'platform_pct' => $split['platform'],
            'county_pct' => $split['county'],
            'source' => $source,
        ];
    }

    public function getSplit(string $source, ?County $county = null): array
    {
        $default = $this->defaultSplits[$source] ?? ['platform' => 50, 'county' => 50];

        if (!$county) return $default;

        // Check if county has an override for this source
        $config = CountyFinancialConfig::where('county_id', $county->id)
            ->where('revenue_source', $source)
            ->first();

        if ($config) {
            return [
                'platform' => $config->platform_pct,
                'county' => $config->county_pct,
            ];
        }

        return $default;
    }

    public function getDefaultSplits(): array
    {
        return $this->defaultSplits;
    }
}
