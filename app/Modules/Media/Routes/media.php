<?php

use Illuminate\Support\Facades\Route;

// Media module routes — registered by MediaServiceProvider.
// Public API surface for the Media bounded context.

Route::prefix('api/media')->middleware('auth:sanctum')->group(function () {
    Route::post('/upload', function () {
        $contract = app(\App\Modules\Media\Contracts\MediaServiceContract::class);
        $id = $contract->upload(
            request('owner_type'),
            (int) request('owner_id'),
            request('slot', 'default'),
            request()->file('file')
        );
        return response()->json(['id' => $id], 201);
    });

    Route::get('/{id}/derivatives', function (int $id) {
        return app(\App\Modules\Media\Contracts\MediaServiceContract::class)->derivatives($id);
    });
});