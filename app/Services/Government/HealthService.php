<?php

namespace App\Services\Government;

/**
 * Ministry of Health — facility registry, licensing, inspection data.
 */
class HealthService extends GovernmentIntegrationService
{
    public function __construct()
    {
        parent::__construct('Ministry of Health', 'government.health');
    }

    public function verifyFacility(string $licenseNumber): ?array
    {
        return $this->get("facilities/$licenseNumber");
    }
}