<?php

namespace App\Services\Government;

/**
 * National Museums of Kenya — heritage sites, museums, galleries.
 */
class MuseumsService extends GovernmentIntegrationService
{
    public function __construct()
    {
        parent::__construct('National Museums of Kenya', 'government.museums');
    }

    public function getHeritageSite(string $slug): ?array
    {
        return $this->get("sites/$slug");
    }

    public function listSites(string $county = ''): ?array
    {
        return $this->get('sites', $county ? ['county' => $county] : []);
    }
}