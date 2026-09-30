<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Concerns\CountyAdminHelpers;
use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\CountyHotel;
use App\Models\CountyProduct;
use App\Models\CountyTourismAttraction;
use App\Models\MediaAsset;
use App\Models\SectorEntity;
use App\Events\GenericDomainEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CountyMediaController extends Controller
{
    use CountyAdminHelpers;

    protected function sectorImages(string $slug): array
    {
        $sectors = \App\Models\Sector::where('is_active', true)->orderBy('name')->pluck('slug')->prepend('hero')->toArray();
        $images = [];
        $county = \App\Models\County::where('slug', $slug)->first();
        foreach ($sectors as $s) {
            $path = "counties/{$slug}/{$s}.jpeg";
            $fullPath = storage_path("app/public/{$path}");
            $images[$s] = [
                'path' => $path, 'exists' => file_exists($fullPath), 'url' => $path,
                'video' => null, 'video_name' => null,
            ];
            if ($county && $s !== 'hero') {
                $asset = MediaAsset::resolveSlot(\App\Models\County::class, $county->id, 'sector_video_' . $s);
                $images[$s]['video'] = $asset?->mp4Url();
                $images[$s]['video_name'] = $asset?->original_name;
            }
        }
        if ($county) {
            $heroAsset = MediaAsset::resolveSlot(\App\Models\County::class, $county->id, 'hero_video');
            $images['hero']['video'] = $heroAsset?->mp4Url();
            $images['hero']['video_name'] = $heroAsset?->original_name;
        }
        return $images;
    }

    public function uploadImage(Request $request, string $slug)
    {
        $user = Auth::user();
        $county = County::where('slug', $slug)->firstOrFail();
        abort_if(!$user->isAdmin() && $user->county_id !== $county->id, 403);
        $county = $this->authorizeCounty($slug);
        $data = $request->validate([
            'sector' => 'required|in:hero,tourism,products,education,culture,hotels,farms,transport,health',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);
        $file = $request->file('image');
        $filename = "{$data['sector']}.{$file->extension()}";
        $path = $file->storeAs("counties/{$slug}", $filename, 'public');
        if ($file->extension() !== 'jpeg') {
            $jpegPath = storage_path("app/public/counties/{$slug}/{$data['sector']}.jpeg");
            @copy(storage_path("app/public/{$path}"), $jpegPath);
        }
        event(new GenericDomainEvent('county_image_updated', [
            'county' => $slug, 'sector' => $data['sector'],
        ], n8nEventName: 'county_image_updated'));
        $this->syncCounty($county);
        return back()->with('success', "{$data['sector']} image updated.");
    }

    public function deleteImage(string $slug, string $sector)
    {
        $user = Auth::user();
        $county = County::where('slug', $slug)->firstOrFail();
        abort_if(!$user->isAdmin() && $user->county_id !== $county->id, 403);
        $county = $this->authorizeCounty($slug);
        $path = storage_path("app/public/counties/{$slug}/{$sector}.jpeg");
        if (file_exists($path)) @unlink($path);
        $altPath = storage_path("app/public/counties/{$slug}/{$sector}.jpg");
        if (file_exists($altPath)) @unlink($altPath);
        $this->syncCounty($county);
        return back()->with('success', "{$sector} image removed.");
    }

    public function uploadSectorVideo(Request $request, string $slug)
    {
        $user = Auth::user();
        $county = County::where('slug', $slug)->firstOrFail();
        abort_if(!$user->isAdmin() && $user->county_id !== $county->id, 403);
        $county = $this->authorizeCounty($slug);
        $data = $request->validate([
            'sector' => 'required|in:tourism,products,education,culture,hotels,farms,transport,health',
            'video' => 'required|file|mimes:mp4,webm,mov|max:512000',
        ]);
        $file = $request->file('video');
        $slot = 'sector_video_' . $data['sector'];
        $disk = Storage::disk('r2');
        $r2Path = "counties/{$slug}/sector-videos/{$data['sector']}.{$file->getClientOriginalExtension()}";
        $disk->writeStream($r2Path, fopen($file->getRealPath(), 'r'), ['visibility' => 'public']);
        MediaAsset::forSlot(County::class, $county->id, $slot)->delete();
        $asset = MediaAsset::create([
            'uuid' => (string) Str::uuid(), 'owner_id' => $county->id, 'owner_type' => County::class,
            'slot' => $slot, 'disk' => 'r2', 'path' => $r2Path, 'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(), 'kind' => 'video', 'size_bytes' => $file->getSize(), 'status' => 'ready',
        ]);
        $asset->derivatives()->create([
            'kind' => 'video_mp4', 'path' => $r2Path, 'mime' => 'video/mp4', 'size_bytes' => $file->getSize(), 'variant' => 'source',
        ]);
        $this->syncCounty($county);
        return back()->with('success', "Sector video for {$data['sector']} uploaded.");
    }

    public function deleteSectorVideo(string $slug, string $sector)
    {
        $user = Auth::user();
        $county = County::where('slug', $slug)->firstOrFail();
        abort_if(!$user->isAdmin() && $user->county_id !== $county->id, 403);
        $this->authorizeCounty($slug);
        $slot = 'sector_video_' . $sector;
        $assets = MediaAsset::forSlot(County::class, $county->id, $slot)->get();
        foreach ($assets as $a) {
            if ($a->disk === 'r2') Storage::disk('r2')->delete($a->path);
            $a->derivatives()->delete();
            $a->delete();
        }
        $this->syncCounty($county);
        return back()->with('success', "Sector video for {$sector} removed.");
    }

    public function uploadHeroVideo(Request $request, string $slug)
    {
        $user = Auth::user();
        $county = County::where('slug', $slug)->firstOrFail();
        abort_if(!$user->isAdmin() && $user->county_id !== $county->id, 403);
        $county = $this->authorizeCounty($slug);
        $file = $request->validate(['video' => 'required|file|mimes:mp4,webm,mov|max:512000'])['video'];
        $disk = Storage::disk('r2');
        $r2Path = "counties/{$county->slug}/video/hero/hero.mp4";
        $disk->writeStream($r2Path, fopen($file->getRealPath(), 'r'), ['visibility' => 'public']);
        MediaAsset::forSlot(County::class, $county->id, 'hero_video')->delete();
        $asset = MediaAsset::create([
            'uuid' => (string) Str::uuid(), 'owner_id' => $county->id, 'owner_type' => County::class,
            'slot' => 'hero_video', 'disk' => 'r2', 'path' => $r2Path, 'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(), 'kind' => 'video', 'size_bytes' => $file->getSize(), 'status' => 'ready',
        ]);
        $asset->derivatives()->create([
            'kind' => 'video_mp4', 'path' => $r2Path, 'mime' => 'video/mp4', 'size_bytes' => $file->getSize(), 'variant' => '1080p',
        ]);
        $this->syncCounty($county);
        return back()->with('success', 'Hero video uploaded.');
    }

    public function deleteHeroVideo(string $slug)
    {
        $user = Auth::user();
        $county = County::where('slug', $slug)->firstOrFail();
        abort_if(!$user->isAdmin() && $user->county_id !== $county->id, 403);
        $this->authorizeCounty($slug);
        $assets = MediaAsset::forSlot(County::class, $county->id, 'hero_video')->get();
        foreach ($assets as $a) {
            if ($a->disk === 'r2') { Storage::disk('r2')->delete($a->path); foreach ($a->derivatives as $d) Storage::disk('r2')->delete($d->path); }
            $a->derivatives()->delete(); $a->delete();
        }
        $this->syncCounty($county);
        return back()->with('success', 'Hero video removed.');
    }

    public function uploadFlagVideo(Request $request, string $slug)
    {
        $county = $this->authorizeCounty($slug);
        $file = $request->validate(['video' => 'required|file|mimes:mp4,webm,mov|max:512000'])['video'];
        $filename = 'flag.' . $file->getClientOriginalExtension();
        $disk = Storage::disk('r2');
        $r2Path = "counties/{$slug}/flag-video/{$filename}";
        $disk->writeStream($r2Path, fopen($file->getRealPath(), 'r'), ['visibility' => 'public']);
        MediaAsset::forSlot(County::class, $county->id, 'county_flag_video')->delete();
        $asset = MediaAsset::create([
            'uuid' => (string) Str::uuid(), 'owner_id' => $county->id, 'owner_type' => County::class,
            'slot' => 'county_flag_video', 'disk' => 'r2', 'path' => $r2Path, 'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(), 'kind' => 'video', 'size_bytes' => $file->getSize(), 'status' => 'ready',
        ]);
        $asset->derivatives()->create([
            'kind' => 'video_mp4', 'path' => $r2Path, 'mime' => 'video/mp4', 'size_bytes' => $file->getSize(), 'variant' => 'source',
        ]);
        $this->syncCounty($county);
        return back()->with('success', 'County animated flag uploaded.');
    }

    public function deleteFlagVideo(string $slug)
    {
        $this->authorizeCounty($slug);
        $countyId = County::where('slug', $slug)->value('id');
        $assets = MediaAsset::forSlot(County::class, $countyId, 'county_flag_video')->get();
        foreach ($assets as $a) { if ($a->disk === 'r2') Storage::disk('r2')->delete($a->path); $a->derivatives()->delete(); $a->delete(); }
        $this->syncCounty($county);
        return back()->with('success', 'County animated flag removed.');
    }

    public function upload4dVideo(Request $request, string $slug)
    {
        $user = Auth::user();
        $county = County::where('slug', $slug)->firstOrFail();
        abort_if(!$user->isAdmin() && $user->county_id !== $county->id, 403);
        $county = $this->authorizeCounty($slug);
        $data = $request->validate([
            'entity_type' => 'required|in:attraction,hotel,product,sector_entity', 'entity_id' => 'required|integer',
            'video' => 'required|file|mimes:mp4,webm,mov|max:512000',
        ]);
        $model = match ($data['entity_type']) {
            'attraction' => CountyTourismAttraction::class, 'hotel' => CountyHotel::class,
            'product' => CountyProduct::class, 'sector_entity' => SectorEntity::class,
        };
        $entity = $model::where('county_id', $county->id)->findOrFail($data['entity_id']);
        $file = $request->file('video');
        $disk = Storage::disk('r2');
        $r2Path = "counties/{$slug}/4d/{$data['entity_type']}-{$entity->id}." . $file->getClientOriginalExtension();
        $disk->writeStream($r2Path, fopen($file->getRealPath(), 'r'), ['visibility' => 'public']);
        MediaAsset::forSlot($model, $entity->id, '4d_video')->delete();
        $asset = MediaAsset::create([
            'uuid' => (string) Str::uuid(), 'owner_id' => $entity->id, 'owner_type' => $model,
            'slot' => '4d_video', 'disk' => 'r2', 'path' => $r2Path, 'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(), 'kind' => 'video', 'size_bytes' => $file->getSize(), 'status' => 'ready',
        ]);
        $asset->derivatives()->create([
            'kind' => 'video_mp4', 'path' => $r2Path, 'mime' => 'video/mp4', 'size_bytes' => $file->getSize(), 'variant' => 'source',
        ]);
        try {
            event(new GenericDomainEvent('county_4d_uploaded', [
                'county' => $slug, 'entity_type' => $data['entity_type'], 'entity_id' => $entity->id,
            ], n8nEventName: 'county_4d_uploaded'));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Event dispatch failed for 4D upload: ' . $e->getMessage());
        }
        $this->syncCounty($county);
        return back()->with('success', "4D video attached to {$entity->name}.");
    }

    public function delete4dVideo(string $slug, string $entityType, int $entityId)
    {
        $user = Auth::user();
        $county = County::where('slug', $slug)->firstOrFail();
        abort_if(!$user->isAdmin() && $user->county_id !== $county->id, 403);
        $county = $this->authorizeCounty($slug);
        $model = match ($entityType) {
            'attraction' => CountyTourismAttraction::class, 'hotel' => CountyHotel::class,
            'product' => CountyProduct::class, 'sector_entity' => SectorEntity::class,
            default => abort(422, 'Unknown entity type'),
        };
        MediaAsset::forSlot($model, $entityId, '4d_video')->get()->each->delete();
        $this->syncCounty($county);
        return back()->with('success', '4D video removed.');
    }
}