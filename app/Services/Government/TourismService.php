<?php

namespace App\Services\Government;

/**
 * Ministry of Tourism — star ratings, accommodation classifications.
 */
class TourismService extends GovernmentIntegrationService
{
    public function __construct()
    {
        parent::__construct('Ministry of Tourism', 'government.tourism');
    }

    public function getStarRating(string $establishmentId): ?array
    {
        return $this->get("ratings/$establishmentId");
    }
}