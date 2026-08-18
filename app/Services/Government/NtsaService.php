<?php

namespace App\Services\Government;

/**
 * NTSA (National Transport and Safety Authority) integration.
 * Vehicle registration lookups, driving license verification, PSV checks.
 */
class NtsaService extends GovernmentIntegrationService
{
    public function __construct()
    {
        parent::__construct('NTSA', 'government.ntsa');
    }

    public function verifyVehicle(string $registrationNumber): ?array
    {
        return $this->get("vehicles/$registrationNumber");
    }

    public function verifyDrivingLicense(string $licenseNumber): ?array
    {
        return $this->get("drivers/$licenseNumber");
    }
}