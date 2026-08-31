<?php

use App\Http\Controllers\Api\AgenticLoopController;
use App\Http\Controllers\Api\AiAssistController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\BoothController;
use App\Http\Controllers\Api\CountyController;
use App\Http\Controllers\Api\CountySectorController;
use App\Http\Controllers\Api\ExhibitionController;
use App\Http\Controllers\Api\MediaApiController;
use App\Http\Controllers\Api\PipelineController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\TicketTypeController;
use App\Http\Controllers\Api\VenueController;
use App\Http\Controllers\Api\McpController;
use App\Http\Controllers\Api\CorrelationController;
use Illuminate\Support\Facades\Route;

// NOTE: the 'api' rate limiter is defined in AppServiceProvider::boot() —
// defining it here breaks once routes are cached (this file is never reloaded).

// Auth
Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:20,1');
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:20,1');
Route::post('/auth/token', [\App\Http\Controllers\Api\AuthController::class, 'token']);

// Public county routes
Route::get('/counties', [CountyController::class, 'index'])->middleware(\App\Http\Middleware\CachePublicResponse::class);
Route::get('/counties/{slug}', [CountyController::class, 'show'])->middleware(\App\Http\Middleware\CachePublicResponse::class);
Route::get('/counties/{slug}/sectors', [CountyController::class, 'sectors'])->middleware(\App\Http\Middleware\CachePublicResponse::class);
Route::get('/counties/{slug}/weather', [CountyController::class, 'weather'])->middleware(\App\Http\Middleware\CachePublicResponse::class);
Route::get('/national-hub', [CountyController::class, 'nationalHub'])->middleware(\App\Http\Middleware\CachePublicResponse::class);
Route::post('/match-destination', [CountyController::class, 'matchDestination']);

// Semantic vector search (embeddings via OpenRouter; cosine-ranked)
Route::get('/search/semantic', [\App\Http\Controllers\Api\SemanticSearchController::class, 'search']);

// Voice search — transcribe audio via server fallback (primary path: Web Speech API on-device)
Route::post('/search/voice', [\App\Http\Controllers\Api\VoiceSearchController::class, 'transcribe'])
    ->middleware('throttle:10,1');

// Ad serving + click tracking (Sponsored placements)
Route::get('/ads/serve/{placement}', [\App\Http\Controllers\Api\AdController::class, 'serve']);
Route::get('/ads/click/{creative}', [\App\Http\Controllers\Api\AdController::class, 'click']);

// Stripe webhook (signature-verified, idempotent)
Route::post('/webhooks/stripe', [\App\Http\Controllers\Api\StripeWebhookController::class, 'handle'])
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

// Courier tracking webhook (HMAC, replay-safe, idempotent)
Route::post('/webhooks/courier', [\App\Http\Controllers\Api\CourierWebhookController::class, 'handle'])
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

// Africa's Talking USSD callback (token-protected; plain-text CON/END response)
Route::post('/ussd/callback', [\App\Http\Controllers\Api\UssdController::class, 'handle'])
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

// Public exhibition & venue routes
Route::get('/exhibitions', [ExhibitionController::class, 'index'])->middleware(\App\Http\Middleware\CachePublicResponse::class);
Route::get('/exhibitions/{slug}', [ExhibitionController::class, 'show'])->middleware(\App\Http\Middleware\CachePublicResponse::class);
Route::get('/venues', [VenueController::class, 'index'])->middleware(\App\Http\Middleware\CachePublicResponse::class);
Route::get('/venues/{slug}', [VenueController::class, 'show'])->middleware(\App\Http\Middleware\CachePublicResponse::class);

// Public booth listing
Route::get('/booths', [BoothController::class, 'index'])->middleware(\App\Http\Middleware\CachePublicResponse::class);
Route::get('/booths/{booth}', [BoothController::class, 'show'])->middleware(\App\Http\Middleware\CachePublicResponse::class);

// Public ticket lookup
Route::get('/tickets/lookup/{ticketCode}', [TicketController::class, 'lookup'])->middleware(\App\Http\Middleware\CachePublicResponse::class);

// Public read-only county sector data (mobile/web browsing without auth)
Route::get('/county-sector/{county}/data', [\App\Http\Controllers\Api\CountySectorController::class, 'getCountyData'])
    ->middleware(\App\Http\Middleware\CachePublicResponse::class);

// Image search — public utility (no auth required)
Route::post('/image-search', [\App\Http\Controllers\Api\ImageSearchController::class, 'search']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    Route::post('/pipeline/upload', [PipelineController::class, 'upload']);
    Route::get('/pipeline/status/{jobId}', [PipelineController::class, 'status']);

// AI-assisted UX research
    Route::get('/ai/ux-assist', [AiAssistController::class, 'uxAssist']);

    // Media library (kicc-web SPA)
    Route::get('/media', [MediaApiController::class, 'index']);
    Route::post('/media', [MediaApiController::class, 'store']);
    Route::get('/media/jobs/{job}/status', [MediaApiController::class, 'jobStatus']);
    Route::get('/media/{asset}', [MediaApiController::class, 'show']);
    Route::post('/media/{asset}/pipeline', [MediaApiController::class, 'dispatch']);
    Route::post('/media/{asset}/attach', [MediaApiController::class, 'attach']);
    Route::post('/media/{asset}/detach', [MediaApiController::class, 'detach']);
    Route::delete('/media/{asset}', [MediaApiController::class, 'destroy']);

    Route::post('/agentic/trigger', [AgenticLoopController::class, 'trigger']);
    Route::get('/agentic/status', [AgenticLoopController::class, 'status']);

    // Exhibition management
    Route::post('/exhibitions', [ExhibitionController::class, 'store']);
    Route::put('/exhibitions/{slug}', [ExhibitionController::class, 'update']);
    Route::delete('/exhibitions/{slug}', [ExhibitionController::class, 'destroy']);

    // Venue management
    Route::post('/venues', [VenueController::class, 'store']);
    Route::put('/venues/{slug}', [VenueController::class, 'update']);
    Route::delete('/venues/{slug}', [VenueController::class, 'destroy']);

    // Booth management
    Route::post('/booths', [BoothController::class, 'store']);
    Route::put('/booths/{booth}', [BoothController::class, 'update']);
    Route::delete('/booths/{booth}', [BoothController::class, 'destroy']);

    // Booking
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/{booking}', [BookingController::class, 'show']);
    Route::post('/bookings/booth', [BookingController::class, 'storeBoothBooking']);
    Route::post('/bookings/ticket', [BookingController::class, 'storeTicketBooking']);
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);

    // Disputes (buyer raises against an escrow transaction; 2-tier auto/human)
    Route::post('/disputes', [\App\Http\Controllers\Api\DisputeController::class, 'store']);

    // Ticket management
    Route::get('/tickets', [TicketController::class, 'index']);
    Route::get('/tickets/{ticket}', [TicketController::class, 'show']);
    Route::post('/tickets/{ticketCode}/check-in', [TicketController::class, 'checkIn']);

    // Ticket types
    Route::get('/ticket-types', [TicketTypeController::class, 'index']);
    Route::get('/ticket-types/{ticketType}', [TicketTypeController::class, 'show']);
    Route::post('/ticket-types', [TicketTypeController::class, 'store']);
    Route::put('/ticket-types/{ticketType}', [TicketTypeController::class, 'update']);
    Route::delete('/ticket-types/{ticketType}', [TicketTypeController::class, 'destroy']);

    // County sector data management (auth'd write ops only)
    Route::get('/county-sector/{county}/export', [CountySectorController::class, 'exportCountyJson']);
    Route::post('/county-sector/{county}/media/{sector}/{entityId?}', [CountySectorController::class, 'uploadMedia']);
});

// MCP Protocol endpoints
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/mcp', [McpController::class, 'discovery']);
    Route::get('/mcp/resources', [McpController::class, 'listResources']);
    Route::get('/mcp/resources/{type}', [McpController::class, 'readResource']);
    Route::post('/mcp/tools/{name}', [McpController::class, 'executeTool']);
});

// Engine → platform media publish webhook (HMAC-SHA256 + nonce + idempotent).
// Signed by the Kotlin admin engine when a transcode job finishes; warms the
// derivative and purges Cloudflare cache-tags so new media appears on the site.
Route::post('/media/publish', [\App\Http\Controllers\Api\MediaPublishController::class, 'handle'])
    ->middleware('throttle:60,1');

// Unity Mobile App OTA update manifest
Route::get('/unity/manifest', [\App\Http\Controllers\Api\UnityManifestController::class, 'show'])
    ->middleware('throttle:300,1');
Route::post('/unity/manifest', [\App\Http\Controllers\Api\UnityManifestController::class, 'store'])
    ->middleware('auth:sanctum');
Route::post('/webhooks/n8n', [\App\Http\Controllers\Api\N8nWebhookController::class, 'handle'])
    ->middleware('throttle:60,1');
Route::get('/updates/manifest', [\App\Http\Controllers\Api\UpdateManifestController::class, 'show'])
    ->middleware('throttle:300,1');

// ── Trip Correlation Engine ──
Route::get('/correlations/institution/{id}', [CorrelationController::class, 'forInstitution'])
    ->middleware(\App\Http\Middleware\CachePublicResponse::class);
Route::get('/correlations/product/{id}', [CorrelationController::class, 'forProduct'])
    ->middleware(\App\Http\Middleware\CachePublicResponse::class);
Route::get('/correlations/attraction/{id}', [CorrelationController::class, 'forAttraction'])
    ->middleware(\App\Http\Middleware\CachePublicResponse::class);

// ── Escrow API ──
Route::middleware('auth:sanctum')->prefix('escrow')->group(function () {
    Route::post('/', [\App\Http\Controllers\Api\EscrowController::class, 'store']);
    Route::post('/{id}/hold', [\App\Http\Controllers\Api\EscrowController::class, 'hold']);
    Route::post('/{id}/seller-confirm', [\App\Http\Controllers\Api\EscrowController::class, 'sellerConfirm']);
    Route::post('/{id}/ship', [\App\Http\Controllers\Api\EscrowController::class, 'ship']);
    Route::post('/{id}/delivered', [\App\Http\Controllers\Api\EscrowController::class, 'delivered']);
    Route::post('/{id}/buyer-confirm', [\App\Http\Controllers\Api\EscrowController::class, 'buyerConfirm']);
    Route::post('/{id}/dispute', [\App\Http\Controllers\Api\EscrowController::class, 'raiseDispute']);
    Route::get('/{id}/tracking', [\App\Http\Controllers\Api\EscrowController::class, 'tracking']);
    Route::post('/{id}/release', [\App\Http\Controllers\Api\EscrowController::class, 'releaseFunds']);
});

// ── Verification routes ──
Route::middleware('auth:sanctum')->prefix('verification')->group(function () {
    Route::post('/kra-pin', [\App\Http\Controllers\Api\VerificationController::class, 'submitKraPin']);
    Route::post('/national-id', [\App\Http\Controllers\Api\VerificationController::class, 'submitNationalId']);
    Route::get('/status', [\App\Http\Controllers\Api\VerificationController::class, 'status']);
});
Route::get('/test-public', function() { return response()->json(['status' => 'ok']); });
Route::get('/openapi', [\App\Http\Controllers\Api\OpenApiController::class, 'spec']);
