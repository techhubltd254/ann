<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\CountyTourismAttraction;
use App\Models\CountyHotel;
use App\Models\CountyProduct;
use App\Models\CountyInstitution;
use App\Models\CountyFarm;
use App\Models\CountyTransport;
use App\Models\CountyHealthFacility;
use App\Models\CountyCultureSite;
use App\Models\EntityMedia;
use App\Models\SectorEntity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class CountySectorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function uploadMedia(Request $request, County $county, string $sector, string $entityId = null)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:51200',
            'type' => 'required|in:image,video,document,3d_model',
            'alt_text' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $file = $request->file('file');
        $path = $file->store("counties/{$county->slug}/{$sector}", 'r2');
        $url = Storage::disk('r2')->url($path);

        $media = EntityMedia::create([
            'mediable_type' => $entityId ? "App\\Models\\SectorEntity" : "App\\Models\\County",
            'mediable_id' => $entityId ?? $county->id,
            'type' => $request->type,
            'url' => $url,
            'alt_text' => $request->alt_text,
        ]);

        return response()->json($media, 201);
    }

    public function getCountyData(County $county)
    {
        $sectors = [
            'tourism' => CountyTourismAttraction::where('county_id', $county->id)->get(),
            'hotels' => CountyHotel::where('county_id', $county->id)->get(),
            'products' => CountyProduct::where('county_id', $county->id)->get(),
            'institutions' => CountyInstitution::where('county_id', $county->id)->get(),
            'farms' => CountyFarm::where('county_id', $county->id)->get(),
            'transport' => CountyTransport::where('county_id', $county->id)->get(),
            'health' => CountyHealthFacility::where('county_id', $county->id)->get(),
            'culture' => CountyCultureSite::where('county_id', $county->id)->get(),
        ];

        return response()->json($sectors);
    }

    public function exportCountyJson(County $county)
    {
        $data = [
            'county' => $county->load('sectors'),
            'sectors' => $this->getCountyData($county)->original,
            'entities' => SectorEntity::where('county_id', $county->id)
                ->with('entity', 'media')
                ->get(),
        ];

        return response()->json($data);
    }
}
