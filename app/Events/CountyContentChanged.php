<?php

namespace App\Events;

/**
 * Fired when any county data changes — content, media, sector, institution.
 * Replaces all CacheSyncService::county() / ::sector() / ::institution() calls.
 */
class CountyContentChanged extends DomainEvent
{
    public function __construct(
        public int $countyId,
        public ?int $sectorId = null,
        public ?int $institutionId = null,
        public string $changeType = 'updated',
    ) {
        parent::__construct([
            'county_id' => $countyId,
            'sector_id' => $sectorId,
            'institution_id' => $institutionId,
            'change_type' => $changeType,
        ]);
    }

    public function eventName(): string
    {
        return 'county.content_changed';
    }

    public function auditLabel(): string
    {
        return "County #{$this->countyId} {$this->changeType}";
    }

    public function cacheTags(): array
    {
        $tags = ["county:{$this->countyId}"];
        if ($this->sectorId) $tags[] = "sector:{$this->countyId}:{$this->sectorId}";
        if ($this->institutionId) $tags[] = "institution:{$this->institutionId}";
        return $tags;
    }

    public function n8nEvent(): ?string
    {
        return $this->payload['n8n_event'] ?? null;
    }
}