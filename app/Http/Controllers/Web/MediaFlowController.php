<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\{CountyInstitution, MediaAsset, Sector};
use App\Services\AdminHierarchyScope;
use App\Support\MediaMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Sequential media flow: Institution -> Sector -> Media.
 * Every media item is keyed to the institution+sector that owns it; upload,
 * replace and delete are only reachable after drilling into that owner.
 */
class MediaFlowController extends Controller
{
    private function actor(Request $r): \App\Models\User
    {
        $u = $r->user();
        abort_unless($u && app(AdminHierarchyScope::class)->level($u), 403, 'An assigned administration role is required.');
        return $u;
    }

    public function index(Request $r)
    {
        $u = $this->actor($r);
        $institutions = app(AdminHierarchyScope::class)->institutions($u)->with('county')->orderBy('name')->get();
        return view('experience.admin.media-flow', compact('institutions'));
    }

    /** Sectors the institution is actually linked to (sector_entities + sector_mappings). */
    public function sectors(Request $r, CountyInstitution $institution)
    {
        $u = $this->actor($r);
        abort_unless(app(AdminHierarchyScope::class)->canInstitution($u, $institution), 403);
        $linked = $institution->sectorEntities()->with('sector')->get()->pluck('sector')->filter()->unique('id')->values();
        return response()->json([
            'institution' => ['id' => $institution->id, 'name' => $institution->name, 'slug' => $institution->slug],
            'sectors' => $linked->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'slug' => $s->slug])->values(),
            'source' => 'sector_entities',
        ])->header('Cache-Control', 'private,no-store');
    }

    /** Media bound to this institution, with the display algorithm's current pick per slot. */
    public function media(Request $r, CountyInstitution $institution)
    {
        $u = $this->actor($r);
        abort_unless(app(AdminHierarchyScope::class)->canInstitution($u, $institution), 403);
        $sectorId = $r->integer('sector_id') ?: null;

        $assets = MediaAsset::where('owner_type', CountyInstitution::class)->where('owner_id', $institution->id)
            ->where('status', 'ready')->with('derivatives')->latest('id')->get();

        $items = [];
        foreach ($assets as $a) {
            $verdict = MediaMapping::classify($a, (string)$institution->slug, (int)$institution->id);
            $items[] = [
                'id' => $a->id, 'slot' => $a->slot, 'kind' => $a->kind, 'path' => $a->path,
                'in_r2' => MediaMapping::inR2($a->path),
                'algorithm_state' => $verdict['state'],
                'algorithm_reason' => $verdict['reason'],
                'serves' => $verdict['state'] === MediaMapping::DISTINCT ? url('/media/video/' . $a->path) : null,
                'thumb' => $a->kind === 'image' && MediaMapping::inR2($a->path) ? url('/media/video/' . $a->path) : null,
            ];
        }

        // What the site actually shows for this institution right now.
        $hero = MediaMapping::institutionHero($institution);
        $display = [
            'state' => $hero['state'],
            'reason' => $hero['reason'],
            'video' => $hero['video'],
            'image' => null,
        ];
        foreach ($assets as $a) {
            if ($a->kind === 'image' && MediaMapping::inR2($a->path)) { $display['image'] = url('/media/video/' . $a->path); break; }
        }

        return response()->json([
            'institution' => ['id' => $institution->id, 'slug' => $institution->slug],
            'sector_id' => $sectorId,
            'display' => $display,
            'media' => $items,
        ])->header('Cache-Control', 'private,no-store');
    }
}
