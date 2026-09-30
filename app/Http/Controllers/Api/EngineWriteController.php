<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\CountyInstitution;
use App\Models\CountyTourismAttraction;
use App\Models\CountyHotel;
use App\Models\CountyFarm;
use App\Models\CountyTransport;
use App\Models\CountyHealthFacility;
use App\Models\CountyCultureSite;
use App\Models\Venue;
use App\Models\Exhibition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Engine Write Contract — the SINGLE entry point for the Kotlin admin engine
 * to write data into the platform. This replaces direct TiDB writes by the engine.
 *
 * Contract guarantee: every write goes through this controller. The engine must
 * NOT write TiDB tables directly — it calls this API instead. HMAC-signed.
 *
 * Currently a STUB — the engine still writes directly. This endpoint must be
 * adopted by the engine side before direct DB access is removed.
 */
class EngineWriteController extends Controller
{
    /**
     * Write a county data entity (attraction, hotel, farm, transport, health, culture).
     * The engine sends camelCase payloads; this endpoint maps to snake_case tables.
     */
    public function writeCountyEntity(Request $request)
    {
        $data = $request->validate([
            'type'       => 'required|in:attraction,hotel,farm,transport,health_facility,culture_site',
            'county_id'  => 'required|integer|exists:counties,id',
            'name'       => 'required|string|max:255',
            'is_published' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $modelMap = [
            'attraction'     => CountyTourismAttraction::class,
            'hotel'          => CountyHotel::class,
            'farm'           => CountyFarm::class,
            'transport'      => CountyTransport::class,
            'health_facility' => CountyHealthFacility::class,
            'culture_site'   => CountyCultureSite::class,
        ];

        $class = $modelMap[$data['type']];
        $entity = new $class;
        $entity->fill($data);
        $entity->save();

        return response()->json(['id' => $entity->id, 'type' => $data['type']], 201);
    }

    /**
     * Write an institution (snake_case input from engine, already mirrored).
     */
    public function writeInstitution(Request $request)
    {
        $data = $request->validate([
            'county_id'    => 'required|integer|exists:counties,id',
            'name'         => 'required|string|max:255',
            'is_published' => 'boolean',
        ]);

        $institution = CountyInstitution::create($data);
        return response()->json(['id' => $institution->id], 201);
    }

    /**
     * Upsert a venue.
     */
    public function writeVenue(Request $request)
    {
        $data = $request->validate([
            'id'          => 'integer',
            'name'        => 'required|string|max:255',
            'is_active'   => 'boolean',
            'county_id'   => 'nullable|integer|exists:counties,id',
            'capacity'    => 'nullable|integer',
        ]);

        $venue = isset($data['id']) ? Venue::findOrFail($data['id']) : new Venue;
        $venue->fill($data);
        $venue->save();

        return response()->json(['id' => $venue->id], 200);
    }
}