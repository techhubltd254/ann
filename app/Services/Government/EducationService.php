<?php

namespace App\Services\Government;

/**
 * Ministry of Education — school registration, institution verification.
 */
class EducationService extends GovernmentIntegrationService
{
    public function __construct()
    {
        parent::__construct('Ministry of Education', 'government.education');
    }

    public function verifyInstitution(string $registrationNumber): ?array
    {
        return $this->get("institutions/$registrationNumber");
    }

    public function listInstitutions(string $county = ''): ?array
    {
        return $this->get('institutions', $county ? ['county' => $county] : []);
    }
}