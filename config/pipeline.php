<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pipeline Engine Registry
    |--------------------------------------------------------------------------
    | Every engine maps to a class implementing PipelineEngineContract.
    | 'local' engines run on this server; 'api' engines call external providers.
    | Enabled = shown as a choice in the admin pipeline wizard (Hick's Law:
    | disabled engines are hidden, not greyed out).
    */

    'engines' => [

        'ffmpeg_kenburns' => [
            'class' => \App\Services\Pipeline\Engines\FfmpegKenburnsEngine::class,
            'type' => 'local',
            'enabled' => true,
            'pipeline' => ['cinematic_video'],
            'label' => 'Ken Burns Cinemagraph',
            'description' => 'Zero-cost cinematic camera motion (pan/zoom) rendered with ffmpeg. Works offline, instant preview.',
            'cost' => 'free',
        ],

        'wan2gp' => [
            'class' => \App\Services\Pipeline\Engines\Wan2GpEngine::class,
            'type' => 'local',
            'enabled' => env('PIPELINE_WAN2GP_ENABLED', false),
            'pipeline' => ['cinematic_video'],
            'label' => 'Wan2GP (Wan 2.1) — Image to Video',
            'description' => 'Runs the Wan2GP diffusion model locally (python worker). Requires GPU worker & model weights.',
            'cost' => 'local',
        ],

        'hailuo' => [
            'class' => \App\Services\Pipeline\Engines\HailuoEngine::class,
            'type' => 'api',
            'enabled' => env('PIPELINE_HAILUO_ENABLED', false),
            'pipeline' => ['cinematic_video'],
            'label' => 'Hailuo AI (Minimax) — Cinematic 3D Video',
            'description' => 'Cinematic lighting, camera paths (pan/tilt/zoom), fluid motion & depth-of-field from a single 2D image.',
            'cost' => 'api',
        ],

        'kling' => [
            'class' => \App\Services\Pipeline\Engines\KlingEngine::class,
            'type' => 'api',
            'enabled' => env('PIPELINE_KLING_ENABLED', false),
            'pipeline' => ['cinematic_video'],
            'label' => 'Kling AI — Cinematic Video Generation',
            'description' => 'Multi-shot storytelling, camera motion controls, lip-sync. Best for dynamic cinematic scenes.',
            'cost' => 'api',
        ],

        'wan' => [
            'class' => \App\Services\Pipeline\Engines\WanEngine::class,
            'type' => 'api',
            'enabled' => env('PIPELINE_WAN_ENABLED', false),
            'pipeline' => ['cinematic_video'],
            'label' => 'Wan Video (Alibaba) — Open-Weight Video Generation',
            'description' => 'High prompt fidelity, multi-language, self-hostable. Best for enterprise pipelines.',
            'cost' => 'api',
        ],

        'tripo3d' => [
            'class' => \App\Services\Pipeline\Engines\Tripo3dEngine::class,
            'type' => 'api',
            'enabled' => env('PIPELINE_TRIPO3D_ENABLED', false),
            'pipeline' => ['image_to_3d'],
            'label' => 'Tripo3D — Image to 3D Mesh',
            'description' => 'Generates a ready-for-web .glb mesh with textures from a single image.',
            'cost' => 'api',
        ],

        'road' => [
            'class' => \App\Services\Pipeline\Engines\RoadEngine::class,
            'type' => 'local',
            'enabled' => env('PIPELINE_ROAD_ENABLED', false),
            'pipeline' => ['image_to_3d'],
            'label' => 'ROAD — Reconstruct Object Assets (Local)',
            'description' => 'Runs the h-embodvis/road image-to-3D pipeline locally.',
            'cost' => 'local',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Attachment Registry — where media can be placed on pages
    |--------------------------------------------------------------------------
    | Entity class => allowed slots. Each slot is a named placement on a page
    | (hero video, gallery item, etc). Admin attaches an asset via the library;
    | pages read the registry, nothing is hardcoded.
    */

    'attachments' => [
        \App\Models\County::class => [
            'hero_video' => 'Hero video',
            'hero_image' => 'Hero image',
            'gallery' => 'Gallery item',
        ],
        \App\Models\CountyTourismAttraction::class => [
            '4d_video' => '4D immersive video',
            'gallery' => 'Gallery item',
        ],
        \App\Models\CountyHotel::class => [
            '4d_video' => '4D immersive video',
            'gallery' => 'Gallery item',
        ],
        \App\Models\CountyProduct::class => [
            '4d_video' => '4D immersive video',
            'gallery' => 'Gallery item',
        ],
        \App\Models\Product::class => [
            'featured_video' => 'Featured video',
            'gallery' => 'Gallery item',
        ],
        \App\Models\Venue::class => [
            'hero_video' => 'Hero video',
            'hero_image' => 'Hero image',
        ],
        \App\Models\Exhibition::class => [
            'cover_video' => 'Cover video',
            'cover_image' => 'Cover image',
        ],

        // Landing page hero — managed via MediaAsset pipeline, not hardcoded.
        // Landing page hero — managed via MediaAsset pipeline, not hardcoded.
        // Uses a string key (not a model FQCN) since the landing page is a
        // singleton without a DB table. The HomeController resolves it via
        // MediaAsset::forSlot('landing_page', 1, 'hero_video').
        'landing_page' => [
            'hero_video' => 'Hero video',
            'hero_image' => 'Hero image',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Options
    |--------------------------------------------------------------------------
    */

    'video' => [
        'width' => env('PIPELINE_VIDEO_WIDTH', 1920),
        'height' => env('PIPELINE_VIDEO_HEIGHT', 1080),
        'fps' => env('PIPELINE_VIDEO_FPS', 30),
        'codec' => env('PIPELINE_VIDEO_CODEC', 'vp9'), // vp9 | av1 | h264
        'crf' => (int) env('PIPELINE_VIDEO_CRF', 32),
        'fallback_codec' => 'h264',
        'duration_sec' => 8,
    ],

    'delivery' => [
        'lazy_intersection_threshold' => 0.15,
        'poster_first' => true,     // always show image/poster before video starts
        'autoplay_muted' => true,
        'loop' => true,
        'prefer_hls' => false,      // switch to true once HLS segments are enabled
    ],

    '3d' => [
        'lod_levels' => ['lod0', 'lod1', 'lod2'],
        'max_polygons' => 120_000,
        'texture' => 'ktx2',        // basis universal | webp fallback
        'compression' => 'draco',   // draco | meshopt
    ],

    /*
    |--------------------------------------------------------------------------
    | External API Credentials
    |--------------------------------------------------------------------------
    */

    'providers' => [
        'hailuo' => [
            'base_url' => env('HAILUO_BASE_URL', 'https://api.minimax.chat'),
            'api_key' => env('HAILUO_API_KEY'),
            'group_id' => env('HAILUO_GROUP_ID'),
            'webhook_url' => env('HAILUO_WEBHOOK_URL'),
        ],
        'kling' => [
            'base_url' => env('KLING_BASE_URL', 'https://api.klingai.com/v1'),
            'api_key' => env('KLING_API_KEY'),
        ],
        'wan' => [
            'base_url' => env('WAN_BASE_URL', 'https://api.wan.video/v1'),
            'api_key' => env('WAN_API_KEY'),
        ],
        'tripo3d' => [
            'base_url' => env('TRIPO3D_BASE_URL', 'https://api.tripo3d.ai/v2'),
            'api_key' => env('TRIPO3D_API_KEY'),
        ],
    ],
];
