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
Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:20,1')->name('api.auth.register');
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:20,1')->name('api.auth.login');
Route::post('/auth/token', [\App\Http\Controllers\Api\AuthController::class, 'token'])->name('api.auth.token');

// Public county routes
Route::get('/counties', [CountyController::class, 'index'])->middleware(\App\Http\Middleware\CachePublicResponse::class)->name('api.counties.index');
Route::get('/counties/{slug}', [CountyController::class, 'show'])->middleware(\App\Http\Middleware\CachePublicResponse::class)->name('api.counties.show');
Route::get('/counties/{slug}/sectors', [CountyController::class, 'sectors'])->middleware(\App\Http\Middleware\CachePublicResponse::class)->name('api.counties.sectors');
Route::get('/counties/{slug}/weather', [CountyController::class, 'weather'])->middleware(\App\Http\Middleware\CachePublicResponse::class)->name('api.counties.weather');
Route::get('/national-hub', [CountyController::class, 'nationalHub'])->middleware(\App\Http\Middleware\CachePublicResponse::class)->name('api.national-hub');
Route::post('/match-destination', [CountyController::class, 'matchDestination'])->name('api.match-destination');

// Semantic vector search (embeddings via OpenRouter; cosine-ranked)
Route::get('/search/semantic', [\App\Http\Controllers\Api\SemanticSearchController::class, 'search'])->name('api.search.semantic');

// Voice search — transcribe audio via server fallback (primary path: Web Speech API on-device)
Route::post('/search/voice', [\App\Http\Controllers\Api\VoiceSearchController::class, 'transcribe'])
    ->middleware('throttle:10,1')->name('api.search.voice');

// Ad serving + click tracking (Sponsored placements)
Route::get('/ads/serve/{placement}', [\App\Http\Controllers\Api\AdController::class, 'serve'])->name('api.ads.serve');
Route::get('/ads/click/{creative}', [\App\Http\Controllers\Api\AdController::class, 'click'])->name('api.ads.click');

// Stripe webhook (signature-verified, idempotent)
Route::post('/webhooks/stripe', [\App\Http\Controllers\Api\StripeWebhookController::class, 'handle'])
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)->name('api.webhooks.stripe');

// Courier tracking webhook (HMAC, replay-safe, idempotent)
Route::post('/webhooks/courier', [\App\Http\Controllers\Api\CourierWebhookController::class, 'handle'])
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)->name('api.webhooks.courier');

// Africa's Talking USSD callback (token-protected; plain-text CON/END response)
Route::post('/ussd/callback', [\App\Http\Controllers\Api\UssdController::class, 'handle'])
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)->name('api.ussd.callback');

// Public exhibition & venue routes
Route::get('/exhibitions', [ExhibitionController::class, 'index'])->middleware(\App\Http\Middleware\CachePublicResponse::class)->name('api.exhibitions.index');
Route::get('/exhibitions/{slug}', [ExhibitionController::class, 'show'])->middleware(\App\Http\Middleware\CachePublicResponse::class)->name('api.exhibitions.show');
Route::get('/venues', [VenueController::class, 'index'])->middleware(\App\Http\Middleware\CachePublicResponse::class)->name('api.venues.index');
Route::get('/venues/{slug}', [VenueController::class, 'show'])->middleware(\App\Http\Middleware\CachePublicResponse::class)->name('api.venues.show');

// Public booth listing
Route::get('/booths', [BoothController::class, 'index'])->middleware(\App\Http\Middleware\CachePublicResponse::class)->name('api.booths.index');
Route::get('/booths/{booth}', [BoothController::class, 'show'])->middleware(\App\Http\Middleware\CachePublicResponse::class)->name('api.booths.show');

// Public ticket lookup
Route::get('/tickets/lookup/{ticketCode}', [TicketController::class, 'lookup'])->middleware(\App\Http\Middleware\CachePublicResponse::class)->name('api.tickets.lookup');

// Public read-only county sector data (mobile/web browsing without auth)
Route::get('/county-sector/{county}/data', [\App\Http\Controllers\Api\CountySectorController::class, 'getCountyData'])
    ->middleware(\App\Http\Middleware\CachePublicResponse::class)->name('api.county-sector.data');

// Image search — public utility (no auth required)
Route::post('/image-search', [\App\Http\Controllers\Api\ImageSearchController::class, 'search'])->name('api.image-search');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
    Route::get('/auth/me', [AuthController::class, 'me'])->name('api.auth.me');

    Route::post('/pipeline/upload', [PipelineController::class, 'upload'])->name('api.pipeline.upload');
    Route::get('/pipeline/status/{jobId}', [PipelineController::class, 'status'])->name('api.pipeline.status');

// AI-assisted UX research
    Route::get('/ai/ux-assist', [AiAssistController::class, 'uxAssist'])->name('api.ai.ux-assist');

    // Media library (kicc-web SPA)
    Route::get('/media', [MediaApiController::class, 'index'])->name('api.media.index');
    Route::post('/media', [MediaApiController::class, 'store'])->name('api.media.store');
    Route::get('/media/jobs/{job}/status', [MediaApiController::class, 'jobStatus'])->name('api.media.jobs.status');
    Route::get('/media/{asset}', [MediaApiController::class, 'show'])->name('api.media.show');
    Route::post('/media/{asset}/pipeline', [MediaApiController::class, 'dispatch'])->name('api.media.pipeline');
    Route::post('/media/{asset}/attach', [MediaApiController::class, 'attach'])->name('api.media.attach');
    Route::post('/media/{asset}/detach', [MediaApiController::class, 'detach'])->name('api.media.detach');
    Route::delete('/media/{asset}', [MediaApiController::class, 'destroy'])->name('api.media.destroy');

    Route::post('/agentic/trigger', [AgenticLoopController::class, 'trigger'])->name('api.agentic.trigger');
    Route::get('/agentic/status', [AgenticLoopController::class, 'status'])->name('api.agentic.status');

    // Exhibition management
    Route::post('/exhibitions', [ExhibitionController::class, 'store'])->name('api.exhibitions.store');
    Route::put('/exhibitions/{slug}', [ExhibitionController::class, 'update'])->name('api.exhibitions.update');
    Route::delete('/exhibitions/{slug}', [ExhibitionController::class, 'destroy'])->name('api.exhibitions.destroy');

    // Venue management
    Route::post('/venues', [VenueController::class, 'store'])->name('api.venues.store');
    Route::put('/venues/{slug}', [VenueController::class, 'update'])->name('api.venues.update');
    Route::delete('/venues/{slug}', [VenueController::class, 'destroy'])->name('api.venues.destroy');

    // Booth management
    Route::post('/booths', [BoothController::class, 'store'])->name('api.booths.store');
    Route::put('/booths/{booth}', [BoothController::class, 'update'])->name('api.booths.update');
    Route::delete('/booths/{booth}', [BoothController::class, 'destroy'])->name('api.booths.destroy');

    // Booking
    Route::get('/bookings', [BookingController::class, 'index'])->name('api.bookings.index');
    Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('api.bookings.show');
    Route::post('/bookings/booth', [BookingController::class, 'storeBoothBooking'])->name('api.bookings.booth.store');
    Route::post('/bookings/ticket', [BookingController::class, 'storeTicketBooking'])->name('api.bookings.ticket.store');
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('api.bookings.cancel');

    // Disputes (buyer raises against an escrow transaction; 2-tier auto/human)
    Route::post('/disputes', [\App\Http\Controllers\Api\DisputeController::class, 'store'])->name('api.disputes.store');

    // Ticket management
    Route::get('/tickets', [TicketController::class, 'index'])->name('api.tickets.index');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('api.tickets.show');
    Route::post('/tickets/{ticketCode}/check-in', [TicketController::class, 'checkIn'])->name('api.tickets.check-in');

    // Ticket types
    Route::get('/ticket-types', [TicketTypeController::class, 'index'])->name('api.ticket-types.index');
    Route::get('/ticket-types/{ticketType}', [TicketTypeController::class, 'show'])->name('api.ticket-types.show');
    Route::post('/ticket-types', [TicketTypeController::class, 'store'])->name('api.ticket-types.store');
    Route::put('/ticket-types/{ticketType}', [TicketTypeController::class, 'update'])->name('api.ticket-types.update');
    Route::delete('/ticket-types/{ticketType}', [TicketTypeController::class, 'destroy'])->name('api.ticket-types.destroy');

    // County sector data management (auth'd write ops only)
    Route::get('/county-sector/{county}/export', [CountySectorController::class, 'exportCountyJson'])->name('api.county-sector.export');
    Route::post('/county-sector/{county}/media/{sector}/{entityId?}', [CountySectorController::class, 'uploadMedia'])->name('api.county-sector.media.upload');
});

// MCP Protocol endpoints — publicly accessible for AI agent discovery
Route::get('/mcp', [McpController::class, 'discovery'])->name('api.mcp.discovery');
Route::get('/mcp/resources', [McpController::class, 'listResources'])->name('api.mcp.resources');
Route::get('/mcp/resources/{type}', [McpController::class, 'readResource'])->name('api.mcp.resources.show');
Route::post('/mcp/tools/{name}', [McpController::class, 'executeTool'])->name('api.mcp.tools.execute');

// Engine → platform media publish webhook (HMAC-SHA256 + nonce + idempotent).
// Signed by the Kotlin admin engine when a transcode job finishes; warms the
// derivative and purges Cloudflare cache-tags so new media appears on the site.
Route::post('/media/publish', [\App\Http\Controllers\Api\MediaPublishController::class, 'handle'])
    ->middleware('throttle:60,1')->name('api.media.publish');

// Unity Mobile App OTA update manifest
Route::get('/unity/manifest', [\App\Http\Controllers\Api\UnityManifestController::class, 'show'])
    ->middleware('throttle:300,1')->name('api.unity.manifest.show');
Route::post('/unity/manifest', [\App\Http\Controllers\Api\UnityManifestController::class, 'store'])
    ->middleware('auth:sanctum')->name('api.unity.manifest.store');
Route::post('/webhooks/n8n', [\App\Http\Controllers\Api\N8nWebhookController::class, 'handle'])
    ->middleware('throttle:10,1')->name('api.webhooks.n8n');
Route::get('/updates/manifest', [\App\Http\Controllers\Api\UpdateManifestController::class, 'show'])
    ->middleware('throttle:300,1')->name('api.updates.manifest');

// ── Experience Pricing API ──
Route::post('/experience/pricing-preview', [\App\Http\Controllers\Api\ExperiencePricingController::class, 'preview'])
    ->middleware(\App\Http\Middleware\CachePublicResponse::class)->name('api.experience.pricing-preview');

// ── Trip Correlation Engine ──
Route::get('/correlations/institution/{id}', [CorrelationController::class, 'forInstitution'])
    ->middleware(\App\Http\Middleware\CachePublicResponse::class)->name('api.correlations.institution');
Route::get('/correlations/product/{id}', [CorrelationController::class, 'forProduct'])
    ->middleware(\App\Http\Middleware\CachePublicResponse::class)->name('api.correlations.product');
Route::get('/correlations/attraction/{id}', [CorrelationController::class, 'forAttraction'])
    ->middleware(\App\Http\Middleware\CachePublicResponse::class)->name('api.correlations.attraction');

// ── Escrow API ──
Route::middleware('auth:sanctum')->prefix('escrow')->group(function () {
    Route::post('/', [\App\Http\Controllers\Api\EscrowController::class, 'store'])->name('api.escrow.store');
    Route::post('/{id}/hold', [\App\Http\Controllers\Api\EscrowController::class, 'hold'])->name('api.escrow.hold');
    Route::post('/{id}/seller-confirm', [\App\Http\Controllers\Api\EscrowController::class, 'sellerConfirm'])->name('api.escrow.seller-confirm');
    Route::post('/{id}/ship', [\App\Http\Controllers\Api\EscrowController::class, 'ship'])->name('api.escrow.ship');
    Route::post('/{id}/delivered', [\App\Http\Controllers\Api\EscrowController::class, 'delivered'])->name('api.escrow.delivered');
    Route::post('/{id}/buyer-confirm', [\App\Http\Controllers\Api\EscrowController::class, 'buyerConfirm'])->name('api.escrow.buyer-confirm');
    Route::post('/{id}/dispute', [\App\Http\Controllers\Api\EscrowController::class, 'raiseDispute'])->name('api.escrow.dispute');
    Route::get('/{id}/tracking', [\App\Http\Controllers\Api\EscrowController::class, 'tracking'])->name('api.escrow.tracking');
    Route::post('/{id}/release', [\App\Http\Controllers\Api\EscrowController::class, 'releaseFunds'])->name('api.escrow.release');
});

// ── Verification routes ──
Route::middleware('auth:sanctum')->prefix('verification')->group(function () {
    Route::post('/kra-pin', [\App\Http\Controllers\Api\VerificationController::class, 'submitKraPin'])->name('api.verification.kra-pin');
    Route::post('/national-id', [\App\Http\Controllers\Api\VerificationController::class, 'submitNationalId'])->name('api.verification.national-id');
    Route::get('/status', [\App\Http\Controllers\Api\VerificationController::class, 'status'])->name('api.verification.status');
});
Route::get('/test-public', function() { return response()->json(['status' => 'ok']); })->name('api.test-public');
Route::get('/openapi', [\App\Http\Controllers\Api\OpenApiController::class, 'spec'])->name('api.openapi');

// ── Marketplace API ──
Route::prefix('marketplace')->name('api.marketplace.')->group(function () {
    Route::get('/products', [\App\Http\Controllers\Api\MarketplaceApiController::class, 'products'])->name('products');
    Route::get('/products/{id}', [\App\Http\Controllers\Api\MarketplaceApiController::class, 'productShow'])->name('product.show');
    Route::get('/categories', [\App\Http\Controllers\Api\MarketplaceApiController::class, 'categories'])->name('categories');
    Route::get('/flash-sales', [\App\Http\Controllers\Api\MarketplaceApiController::class, 'flashSales'])->name('flash-sales');
    Route::get('/auctions', [\App\Http\Controllers\Api\MarketplaceApiController::class, 'auctions'])->name('auctions');
    Route::get('/stats', [\App\Http\Controllers\Api\MarketplaceApiController::class, 'orderStats'])->name('stats');
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/my-orders', [\App\Http\Controllers\Api\MarketplaceApiController::class, 'myOrders'])->name('my-orders');
        Route::get('/my-orders/{orderNumber}', [\App\Http\Controllers\Api\MarketplaceApiController::class, 'orderShow'])->name('my-order.show');
    });
});
