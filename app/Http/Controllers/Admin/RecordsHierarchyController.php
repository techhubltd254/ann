<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\CountyInstitution;
use App\Models\Record;
use App\Models\Sector;
use App\Models\SectorEntity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * County -> sectors -> institutions, implemented with the legacy system's own
 * model: a generic `records` table linked by `parent_id`, with the native row id
 * carried in `payload->source_id` (Record::findByOwner).
 *
 * No new hierarchy was invented: this mirrors the native County / Sector /
 * CountyInstitution tables into records and lets an operator re-parent a
 * sector record under a county record and an institution record under a
 * sector record. Native tables remain the source of truth for public pages.
 */
class RecordsHierarchyController extends Controller
{
    /** Record type => native model, for the mirror pass. */
    private const MIRROR = [
        'counties' => County::class,
        'sectors' => Sector::class,
        'institutions' => CountyInstitution::class,
    ];

    public function index(Request $r)
    {
        $counties = Record::where('type', 'counties')->orderBy('name')->get();

        $selectedCounty = $r->filled('county')
            ? $counties->firstWhere('id', $r->string('county'))
            : $counties->first();

        $sectors = $selectedCounty
            ? Record::where('type', 'sectors')->where('parent_id', $selectedCounty->id)->orderBy('name')->get()
            : collect();

        $selectedSector = $r->filled('sector')
            ? $sectors->firstWhere('id', $r->string('sector'))
            : $sectors->first();

        $institutions = $selectedSector
            ? Record::where('type', 'institutions')->where('parent_id', $selectedSector->id)->orderBy('name')->get()
            : collect();

        return view('admin.hierarchy', [
            'counties' => $counties,
            'selectedCounty' => $selectedCounty,
            'sectors' => $sectors,
            'selectedSector' => $selectedSector,
            'institutions' => $institutions,
            // Unplaced nodes, so nothing is silently orphaned.
            'unplacedSectors' => Record::where('type', 'sectors')
                ->where(fn ($q) => $q->whereNull('parent_id')->orWhereNotIn('parent_id', $counties->pluck('id')))
                ->orderBy('name')->get(),
            'unplacedInstitutions' => Record::where('type', 'institutions')
                ->where(fn ($q) => $q->whereNull('parent_id')->orWhereNotIn('parent_id', Record::where('type', 'sectors')->pluck('id')))
                ->orderBy('name')->limit(300)->get(),
            'nativeCounts' => [
                'counties' => County::count(),
                'sectors' => Sector::count(),
                'institutions' => CountyInstitution::count(),
                'sector_entities' => SectorEntity::count(),
            ],
            'mirrored' => [
                'counties' => Record::where('type', 'counties')->count(),
                'sectors' => Record::where('type', 'sectors')->count(),
                'institutions' => Record::where('type', 'institutions')->count(),
            ],
        ]);
    }

    /**
     * Mirror the native county / sector / institution rows into publishing
     * records and link them parent->child. Idempotent: re-running never
     * duplicates, because records are matched on payload->source_id.
     */
    public function mirror()
    {
        $summary = DB::transaction(function () {
            $out = ['counties' => 0, 'sectors' => 0, 'institutions' => 0, 'linked_sectors' => 0, 'linked_institutions' => 0];

            // 1. Counties
            foreach (County::orderBy('name')->get() as $county) {
                $rec = $this->upsert('counties', $county->id, $county->name, [
                    'source_id' => $county->id,
                    'slug' => $county->slug,
                    'capital' => $county->capital,
                    'code' => $county->code,
                    'region' => $county->region,
                ]);
                $out['counties'] += $rec['created'];
            }

            // 2. Sectors (global catalogue) -> parented to the county that lists them
            $sectorRecords = [];
            foreach (Sector::orderBy('name')->get() as $sector) {
                $rec = $this->upsert('sectors', $sector->id, $sector->name, [
                    'source_id' => $sector->id,
                    'slug' => $sector->slug,
                    'emoji' => $sector->emoji,
                    'code' => $sector->code,
                ]);
                $sectorRecords[$sector->id] = $rec['record'];
                $out['sectors'] += $rec['created'];
            }

            // 3. Institutions -> parented to their county record, then re-parented to a sector
            $instRecords = [];
            foreach (CountyInstitution::orderBy('name')->get() as $inst) {
                $countyRec = Record::where('type', 'counties')->where('payload->source_id', $inst->county_id)->first();
                $rec = $this->upsert('institutions', $inst->id, $inst->name, [
                    'source_id' => $inst->id,
                    'slug' => $inst->slug,
                    'type' => $inst->type,
                    'county_id' => $inst->county_id,
                    'website' => $inst->website,
                    'email' => $inst->email,
                    'phone' => $inst->phone,
                ], $countyRec?->id);
                $instRecords[$inst->id] = $rec['record'];
                $out['institutions'] += $rec['created'];
            }

            // 4. Link each sector record under every county that has it attached
            DB::table('county_sector')->orderBy('county_id')->get()->each(function ($pivot) use ($sectorRecords, &$out) {
                $countyRec = Record::where('type', 'counties')->where('payload->source_id', $pivot->county_id)->first();
                $sectorRec = $sectorRecords[$pivot->sector_id] ?? null;
                if ($countyRec && $sectorRec && $sectorRec->parent_id !== $countyRec->id) {
                    $sectorRec->forceFill(['parent_id' => $countyRec->id])->save();
                    $out['linked_sectors']++;
                }
            });

            // 5. Place each institution under a sector, using its native sector mapping
            foreach (CountyInstitution::orderBy('id')->get() as $inst) {
                $rec = $instRecords[$inst->id] ?? null;
                if (!$rec) {
                    continue;
                }
                $mapping = collect($inst->sector_mappings ?? [])->first();
                $slug = $mapping['sector_slug'] ?? null;
                if (!$slug) {
                    continue;
                }
                $sectorRec = Record::where('type', 'sectors')->where('payload->slug', $slug)->first()
                    ?? Record::where('type', 'sectors')->where('slug', Str::slug($slug))->first();
                if ($sectorRec && $rec->parent_id !== $sectorRec->id) {
                    $rec->forceFill(['parent_id' => $sectorRec->id])->save();
                    $out['linked_institutions']++;
                }
            }

            return $out;
        });

        return back()->with('success', sprintf(
            'Hierarchy mirrored — counties %d, sectors %d, institutions %d created; %d sector links, %d institution links placed.',
            $summary['counties'], $summary['sectors'], $summary['institutions'],
            $summary['linked_sectors'], $summary['linked_institutions']
        ));
    }

    /** Re-parent one sector record under a county record. */
    public function link(Request $r)
    {
        $v = $r->validate([
            'child_id' => 'required|uuid|exists:records,id',
            'parent_id' => 'required|uuid|exists:records,id',
        ]);

        $child = Record::findOrFail($v['child_id']);
        $parent = Record::findOrFail($v['parent_id']);

        // A sector hangs off a county; an institution hangs off a sector.
        $allowed = ['sectors' => 'counties', 'institutions' => 'sectors'];
        abort_unless(($allowed[$child->type] ?? null) === $parent->type, 422, 'Only county → sector → institution links are allowed.');
        abort_if($child->id === $parent->id, 422, 'A record cannot be its own parent.');

        $child->forceFill(['parent_id' => $parent->id])->save();
        DB::table('audit_events')->insert([
            'user_id' => auth()->id(), 'action' => 'hierarchy.link', 'subject_id' => $child->id,
            'details' => json_encode(['parent_id' => $parent->id]), 'created_at' => now(),
        ]);

        return back()->with('success', "Linked {$child->name} → {$parent->name}.");
    }

    /** Detach a child node (keeps the record, clears the link). */
    public function unlink(Request $r)
    {
        $v = $r->validate(['child_id' => 'required|uuid|exists:records,id']);
        $child = Record::findOrFail($v['child_id']);
        $child->forceFill(['parent_id' => null])->save();

        DB::table('audit_events')->insert([
            'user_id' => auth()->id(), 'action' => 'hierarchy.unlink', 'subject_id' => $child->id,
            'details' => json_encode([]), 'created_at' => now(),
        ]);

        return back()->with('success', "Detached {$child->name}.");
    }

    /** Create a record directly in the tree at the chosen level. */
    public function createNode(Request $r)
    {
        $v = $r->validate([
            'type' => 'required|in:sectors,institutions',
            'name' => 'required|string|max:240',
            'parent_id' => 'nullable|uuid|exists:records,id',
        ]);

        $slug = Str::slug($v['name']);
        $record = Record::create([
            'type' => $v['type'],
            'name' => $v['name'],
            'slug' => $slug,
            'status' => 'draft',
            'parent_id' => $v['parent_id'] ?? null,
            'payload' => ['name' => $v['name'], 'slug' => $slug],
            'updated_by' => auth()->id(),
        ]);

        DB::table('audit_events')->insert([
            'user_id' => auth()->id(), 'action' => 'hierarchy.create', 'subject_id' => $record->id,
            'details' => json_encode(['type' => $v['type']]), 'created_at' => now(),
        ]);

        return back()->with('success', "Created {$v['type']} “{$v['name']}”.");
    }

    /** Create-or-update one publishing record keyed on payload->source_id. */
    private function upsert(string $type, int $sourceId, string $name, array $payload, ?string $parentId = null): array
    {
        $record = Record::where('type', $type)->where('payload->source_id', $sourceId)->first();
        $created = 0;

        if (!$record) {
            $record = new Record([
                'type' => $type,
                'name' => $name,
                'slug' => $this->uniqueSlug($type, $payload['slug'] ?? $name),
                'status' => 'draft',
            ]);
            $created = 1;
        }

        $record->forceFill([
            'name' => $name,
            'payload' => $payload,
            'parent_id' => $record->parent_id ?: $parentId,
            'updated_by' => auth()->id(),
        ])->save();

        return ['record' => $record, 'created' => $created];
    }

    private function uniqueSlug(string $type, string $value): string
    {
        $base = Str::slug($value) ?: Str::random(8);
        $slug = $base;
        $n = 2;
        while (Record::where('type', $type)->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $n++;
        }

        return $slug;
    }
}
