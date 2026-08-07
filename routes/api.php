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
use Illuminate\Support\Facades\Route;

// NOTE: the 'api' rate limiter is defined in AppServiceProvider::boot() —
// defining it here breaks once routes are cached (this file is never reloaded).

// Auth
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Public county routes
Route::get('/counties', [CountyController::class, 'index']);
Route::get('/counties/{slug}', [CountyController::class, 'show']);
Route::get('/counties/{slug}/sectors', [CountyController::class, 'sectors']);
Route::get('/counties/{slug}/weather', [CountyController::class, 'weather']);
Route::get('/national-hub', [CountyController::class, 'nationalHub']);
Route::post('/match-destination', [CountyController::class, 'matchDestination']);

// Semantic vector search (embeddings via OpenRouter; cosine-ranked)
Route::get('/search/semantic', [\App\Http\Controllers\Api\SemanticSearchController::class, 'search']);

// Ad serving + click tracking (Sponsored placements)
Route::get('/ads/serve/{placement}', [\App\Http\Controllers\Api\AdController::class, 'serve']);
Route::get('/ads/click/{creative}', [\App\Http\Controllers\Api\AdController::class, 'click']);

// Stripe webhook (signature-verified, idempotent)
Route::post('/webhooks/stripe', [\App\Http\Controllers\Api\StripeWebhookController::class, 'handle'])
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

// Courier tracking webhook (HMAC, replay-safe, idempotent)
Route::post('/webhooks/courier', [\App\Http\Controllers\Api\CourierWebhookController::class, 'handle'])
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

// Public exhibition & venue routes
Route::get('/exhibitions', [ExhibitionController::class, 'index']);
Route::get('/exhibitions/{slug}', [ExhibitionController::class, 'show']);
Route::get('/venues', [VenueController::class, 'index']);
Route::get('/venues/{slug}', [VenueController::class, 'show']);

// Public booth listing
Route::get('/booths', [BoothController::class, 'index']);
Route::get('/booths/{booth}', [BoothController::class, 'show']);

// Public ticket lookup
Route::get('/tickets/lookup/{ticketCode}', [TicketController::class, 'lookup']);

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

    // County sector data management
    Route::get('/county-sector/{county}/data', [CountySectorController::class, 'getCountyData']);
    Route::get('/county-sector/{county}/export', [CountySectorController::class, 'exportCountyJson']);
    Route::post('/county-sector/{county}/media/{sector}/{entityId?}', [CountySectorController::class, 'uploadMedia']);
});

// MCP Protocol endpoints
Route::get('/mcp', [McpController::class, 'discovery']);
Route::get('/mcp/resources', [McpController::class, 'listResources']);
Route::get('/mcp/resources/{type}', [McpController::class, 'readResource']);
Route::post('/mcp/tools/{name}', [McpController::class, 'executeTool']);

// Engine → platform media publish webhook (HMAC-SHA256 + nonce + idempotent).
// Signed by the Kotlin admin engine when a transcode job finishes; warms the
// derivative and purges Cloudflare cache-tags so new media appears on the site.
Route::post('/media/publish', [\App\Http\Controllers\Api\MediaPublishController::class, 'handle'])
    ->middleware('throttle:60,1');

// OTA update manifest for installed admin apps (mother / county / exhibitor servers).
// Public by design — integrity comes from the ed25519 signature, not secrecy.
Route::get('/updates/manifest', [\App\Http\Controllers\Api\UpdateManifestController::class, 'show'])
    ->middleware('throttle:300,1');
