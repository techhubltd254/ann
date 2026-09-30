<?php

namespace App\Events;

/**
 * Fired when a media asset is published (video HLS ready, image processed).
 * Replaces N8nService::fire('video_hls_ready', ...).
 */
class MediaPublished extends DomainEvent
{
    public function __construct(
        public int $assetId,
        public string $type,  // 'video_hls', 'image', 'derivative'
        public ?string $url = null,
    ) {
        parent::__construct([
            'asset_id' => $assetId,
            'type' => $type,
            'url' => $url,
        ]);
    }

    public function eventName(): string { return "media.{$this->type}"; }
    public function auditLabel(): string { return "Media #{$this->assetId} published ({$this->type})"; }
    public function n8nEvent(): ?string { return 'video_hls_ready'; }
    public function cacheTags(): array { return ['page:home']; }
}