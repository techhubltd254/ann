<?php

return [
    /*
     | Public base URL for media bytes.
     |
     | Order of preference:
     |   1. MEDIA_CDN_URL  — set only when a working CDN/Worker exists.
     |   2. '' (empty)     — MediaAsset::url() then serves through the
     |                        application proxy /media/video/{path}, which
     |                        streams straight from the R2 disk with Range
     |                        support. Verified working on production
     |                        (counties/mombasa/video/hero/hero.mp4 → HTTP 200).
     |
     | Do NOT hard-code the old Worker host here: it returns 404 for every
     | object, which is what made all county heroes look broken.
     */
    'cdn_url' => env('MEDIA_CDN_URL'),

    'disk' => env('MEDIA_DISK', 'r2'),
];
