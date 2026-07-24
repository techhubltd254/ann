<?php

use App\Http\Controllers\Api\AgenticLoopController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\BoothController;
use App\Http\Controllers\Api\CountyController;
use App\Http\Controllers\Api\CountySectorController;
use App\Http\Controllers\Api\ExhibitionController;
use App\Http\Controllers\Api\PipelineController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\TicketTypeController;
use App\Http\Controllers\Api\VenueController;
use Illuminate\Support\Facades\Route;

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
