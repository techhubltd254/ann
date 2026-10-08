<?php

use App\Http\Controllers\Admin\RecordsAdminController;
use App\Http\Controllers\Admin\RecordsHierarchyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Legacy records admin (ported from the kicc-v2 branch)
|--------------------------------------------------------------------------
| The previous admin published through a generic `records` table (type +
| parent_id + payload). That surface was absent from the current platform, so
| it is re-mounted here, collision-free, alongside the current admin.
|
| Auth reuses the platform gate `admin:kicc` — no second role system.
| Route names keep the legacy `admin.*` prefix so the ported views work
| unchanged; they do not clash with `admin.ecommerce.*` / `admin.3d.*`.
|
| URI NOTE: the legacy URI was `/admin`, but on this host nginx already
| proxies `location /admin` to a separate Java admin service (127.0.0.1:8091).
| Serving under `/records-admin` keeps that service untouched while making the
| ported admin reachable. Route NAMES are unchanged, so ported views need no edits.
*/

Route::middleware(['auth', 'admin:kicc'])->prefix('records-admin')->name('admin.')->group(function () {
    Route::get('/', [RecordsAdminController::class, 'index'])->name('index');
    Route::get('/audit', [RecordsAdminController::class, 'auditLog'])->name('audit');
    Route::get('/enquiries', [RecordsAdminController::class, 'enquiries'])->name('enquiries');
    Route::patch('/enquiries/{enquiry}', [RecordsAdminController::class, 'enquiryStatus'])->name('enquiry.status');

    // County -> sectors -> institutions cascade, built on the legacy records model.
    Route::get('/hierarchy', [RecordsHierarchyController::class, 'index'])->name('hierarchy');
    Route::post('/hierarchy/mirror', [RecordsHierarchyController::class, 'mirror'])->name('hierarchy.mirror');
    Route::post('/hierarchy/link', [RecordsHierarchyController::class, 'link'])->name('hierarchy.link');
    Route::post('/hierarchy/unlink', [RecordsHierarchyController::class, 'unlink'])->name('hierarchy.unlink');
    Route::post('/hierarchy/create', [RecordsHierarchyController::class, 'createNode'])->name('hierarchy.create');

    Route::get('/content/{type}', [RecordsAdminController::class, 'listing'])->name('list');
    Route::get('/content/{type}/create', [RecordsAdminController::class, 'create'])->name('create');
    Route::post('/records', [RecordsAdminController::class, 'store'])->name('store');
    Route::get('/records/{record}/edit', [RecordsAdminController::class, 'edit'])->name('edit');
    Route::put('/records/{record}', [RecordsAdminController::class, 'update'])->name('update');
    Route::delete('/records/{record}', [RecordsAdminController::class, 'delete'])->name('delete');
    Route::post('/records/{record}/media', [RecordsAdminController::class, 'upload'])->name('upload');

    // Media bytes stay private: authenticated admins only, never a public URL.
    Route::get('/record-media/{media}', [RecordsAdminController::class, 'showMedia'])->name('media.show');
    Route::patch('/record-media/{media}', [RecordsAdminController::class, 'mediaStatus'])->name('media.status');
    Route::delete('/record-media/{media}', [RecordsAdminController::class, 'deleteMedia'])->name('media.delete');
});
