<?php

namespace App\Services\Government;

/**
 * Kenya Wildlife Service integration — parks, reserves, conservation data.
 */
class KwService extends GovernmentIntegrationService
{
    public function __construct()
    {
        parent::__construct('KWS', 'government.kws');
    }

    public function getPark(string $slug): ?array
    {
        return $this->get("parks/$slug");
    }

    public function listParks(): ?array
    {
        return $this->get('parks');
    }
}