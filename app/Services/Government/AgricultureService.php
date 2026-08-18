<?php

namespace App\Services\Government;

/**
 * Ministry of Agriculture — crop prices, market data, co-op registry.
 */
class AgricultureService extends GovernmentIntegrationService
{
    public function __construct()
    {
        parent::__construct('Ministry of Agriculture', 'government.agriculture');
    }

    public function getCropPrices(string $crop = ''): ?array
    {
        return $this->get('crop-prices', $crop ? ['crop' => $crop] : []);
    }

    public function getMarketData(string $county = ''): ?array
    {
        return $this->get('markets', $county ? ['county' => $county] : []);
    }
}