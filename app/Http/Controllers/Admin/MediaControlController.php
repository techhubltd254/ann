<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\CountyInstitution;
use App\Models\MediaAsset;
use App\Models\MediaDerivative;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

/**
 * Per-entity video control for the publishing admin.
 *
 * Every entity — a county, an institution, KICC itself or the national
 * government portal — owns its own media rows. Nothing is inferred from
 * ordering and nothing is shared between entities: a county only ever renders
 * a video whose R2 path is actually about that county.
 *
 * Bytes always land in the R2 bucket on the `r2` disk. Rows are written with
 * the morph class as owner_type so they are found by MediaAsset::resolveSlot().
 */
class MediaControlController extends Controller
{
    /** Owner type => human label, used by the target picker. */
    private const SCOPES = [
        'county' => County::class,
        'institution' => CountyInstitution::class,
        'kicc' => 'kicc',
        'national' => 'national',
    ];

    private function actor()
    {
        $user = Auth::user();
        abort_unless($user && ($user->status ?? 'active') === 'active' && $user->hasRole('kicc_admin'), 403, 'KICC administrator required.');

        return $user;
    }

    private function r2()
    {
        return Storage::disk('r2');
    }

    /** Every selectable media target, with the number of videos it already holds. */
    private function targets(): array
    {
        $counts = MediaAsset::query()
            ->select('owner_type', 'owner_id', DB::raw('COUNT(*) as n'))
            ->groupBy('owner_type', 'owner_id')
            ->get()
            ->keyBy(fn ($r) => $r->owner_type . ':' . $r->owner_id);

        $out = [];
        foreach (County::orderBy('name')->get(['id', 'name', 'slug', 'code']) as $c) {
            $key = County::class . ':' . $c->id;
            $out[] = [
                'scope' => 'county',
                'owner_type' => County::class,
                'owner_id' => $c->id,
                'label' => $c->name . ' County',
                'hint' => $c->code,
                'slug' => $c->slug,
                'count' => (int) ($counts[$key]->n ?? 0),
            ];
        }
        foreach (CountyInstitution::orderBy('name')->get(['id', 'name', 'slug', 'county_id']) as $i) {
            $key = CountyInstitution::class . ':' . $i->id;
            $out[] = [
                'scope' => 'institution',
                'owner_type' => CountyInstitution::class,
                'owner_id' => $i->id,
                'label' => $i->name,
                'hint' => 'institution #' . $i->id,
                'slug' => $i->slug,
                'count' => (int) ($counts[$key]->n ?? 0),
            ];
        }
        foreach ([['kicc', 'KICC — the venue itself'], ['national', 'National Government portal']] as [$scope, $label]) {
            $key = $scope . ':1';
            $out[] = [
                'scope' => $scope,
                'owner_type' => $scope,
                'owner_id' => 1,
                'label' => $label,
                'hint' => 'site-wide',
                'slug' => $scope,
                'count' => (int) ($counts[$key]->n ?? 0),
            ];
        }

        return $out;
    }

    /* ------------------------------------------------------------------ */
    /* Listing + manager screen                                            */
    /* ------------------------------------------------------------------ */

    public function index(Request $request): View
    {
        $this->actor();

        $scope = $request->string('scope')->toString() ?: 'all';
        $q = trim($request->string('q')->toString());

        $query = MediaAsset::query()->with('derivatives')->latest('id');

        if (isset(self::SCOPES[$scope])) {
            $query->where('owner_type', self::SCOPES[$scope]);
        }
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('path', 'like', "%{$q}%")
                    ->orWhere('original_name', 'like', "%{$q}%")
                    ->orWhere('slot', 'like', "%{$q}%");
            });
        }

        $assets = $query->paginate(30)->withQueryString();

        // Live R2 existence check for the rows on this page — never trust the DB alone.
        $keys = $assets->getCollection()->pluck('path')->filter()->all();
        $present = $this->presentKeys($keys);

        $assets->getCollection()->transform(function (MediaAsset $a) use ($present) {
            $a->setAttribute('in_r2', in_array($a->path, $present, true));
            $a->setAttribute('play_url', $a->in_r2 ? $this->playUrl($a) : null);

            return $a;
        });

        return view('experience.pages.admin.media.manager', [
            'assets' => $assets,
            'targets' => $this->targets(),
            'scope' => $scope,
            'q' => $q,
            'counts' => [
                'total' => MediaAsset::count(),
                'county' => MediaAsset::where('owner_type', County::class)->count(),
                'institution' => MediaAsset::where('owner_type', CountyInstitution::class)->count(),
                'kicc' => MediaAsset::whereIn('owner_type', ['kicc', 'landing_page'])->count(),
                'national' => MediaAsset::where('owner_type', 'national')->count(),
            ],
            'slots' => ['hero_video', 'institution_video', 'product_video', '4d_video', 'hero_image', 'poster', 'sector_video'],
        ]);
    }

    /** Which of these keys really exist in R2 right now. */
    private function presentKeys(array $keys): array
    {
        $present = [];
        foreach (array_chunk(array_values(array_unique(array_filter($keys))), 100) as $chunk) {
            try {
                foreach ($chunk as $key) {
                    if ($this->r2()->exists($key)) {
                        $present[] = $key;
                    }
                }
            } catch (Throwable $e) {
                Log::warning('media-control: r2 exists() failed', ['msg' => $e->getMessage()]);
            }
        }

        return $present;
    }

    private function playUrl(MediaAsset $a): string
    {
        try {
            return $a->mp4Url() ?: $a->url();
        } catch (Throwable) {
            return url('/media/video/' . ltrim((string) $a->path, '/'));
        }
    }

    /* ------------------------------------------------------------------ */
    /* Add                                                                 */
    /* ------------------------------------------------------------------ */

    public function store(Request $request): RedirectResponse
    {
        $this->actor();

        $data = $request->validate([
            'scope' => ['required', 'in:county,institution,kicc,national'],
            'owner_id' => ['required', 'integer', 'min:1'],
            'slot' => ['required', 'string', 'max:64'],
            'file' => ['required', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime,image/jpeg,image/png,image/webp', 'max:204800'],
            'alt_text' => ['nullable', 'string', 'max:240'],
        ]);

        $ownerType = self::SCOPES[$data['scope']];
        $slug = $this->ownerSlug($data['scope'], (int) $data['owner_id']);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'mp4');
        $isVideo = str_starts_with((string) $file->getMimeType(), 'video/');
        $name = $data['slot'] . '-' . substr(md5(uniqid('', true)), 0, 6) . '.' . $ext;

        $prefix = match ($data['scope']) {
            'county' => "counties/{$slug}",
            'institution' => "institutions/{$slug}",
            default => $data['scope'] === 'kicc' ? 'kicc/media' : 'national/media',
        };
        $path = "{$prefix}/{$data['slot']}/{$name}";

        try {
            $this->r2()->put($path, fopen($file->getRealPath(), 'r'), ['visibility' => 'public']);
        } catch (Throwable $e) {
            return back()->withErrors(['file' => 'R2 upload failed: ' . $e->getMessage()]);
        }

        // Only claim a row once the object is really there.
        if (! $this->r2()->exists($path)) {
            return back()->withErrors(['file' => 'Upload reported success but the object is not in R2 — nothing was recorded.']);
        }

        $asset = MediaAsset::create([
            'owner_id' => (int) $data['owner_id'],
            'owner_type' => $ownerType,
            'slot' => $data['slot'],
            'display_mode' => 'native',
            'disk' => 'r2',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => (string) $file->getMimeType(),
            'kind' => $isVideo ? 'video' : 'image',
            'size_bytes' => (int) $file->getSize(),
            'status' => 'ready',
            'alt_text' => $data['alt_text'] ?? null,
            'metadata' => ['uploaded_from' => 'records-admin/media', 'uploaded_at' => now()->toIso8601String()],
        ]);

        return redirect()
            ->route('admin.media.index', ['scope' => $data['scope']])
            ->with('success', "Added {$path} to {$slug} (asset #{$asset->id}). It is in R2 and live.");
    }

    /* ------------------------------------------------------------------ */
    /* Replace + delete                                                    */
    /* ------------------------------------------------------------------ */

    public function replace(Request $request, MediaAsset $asset): RedirectResponse
    {
        $this->actor();

        $data = $request->validate([
            'file' => ['required', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime,image/jpeg,image/png,image/webp', 'max:204800'],
        ]);

        $file = $request->file('file');
        $dir = dirname((string) $asset->path);
        $ext = strtolower($file->getClientOriginalExtension() ?: 'mp4');
        $path = "{$dir}/" . basename((string) $asset->path, '.' . pathinfo((string) $asset->path, PATHINFO_EXTENSION)) . '-' . substr(md5(uniqid('', true)), 0, 6) . ".{$ext}";

        try {
            $this->r2()->put($path, fopen($file->getRealPath(), 'r'), ['visibility' => 'public']);
        } catch (Throwable $e) {
            return back()->withErrors(['file' => 'R2 upload failed: ' . $e->getMessage()]);
        }
        if (! $this->r2()->exists($path)) {
            return back()->withErrors(['file' => 'Replacement is not in R2 — the row was left untouched.']);
        }

        $old = $asset->path;
        $asset->update([
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => (string) $file->getMimeType(),
            'size_bytes' => (int) $file->getSize(),
            'status' => 'ready',
        ]);
        MediaDerivative::where('media_asset_id', $asset->id)->delete();

        try {
            if ($old && $old !== $path) {
                $this->r2()->delete($old);
            }
        } catch (Throwable) {
            // Old object may already be gone; the row is correct either way.
        }

        return back()->with('success', "Replaced asset #{$asset->id} with {$path}.");
    }

    public function destroy(MediaAsset $asset): RedirectResponse
    {
        $this->actor();

        $path = (string) $asset->path;
        $derivatives = MediaDerivative::where('media_asset_id', $asset->id)->pluck('path')->all();
        $id = $asset->id;

        MediaDerivative::where('media_asset_id', $asset->id)->delete();
        $asset->delete();

        $removed = [];
        foreach (array_merge([$path], $derivatives) as $key) {
            if (! $key) {
                continue;
            }
            try {
                if ($this->r2()->exists($key)) {
                    $this->r2()->delete($key);
                    $removed[] = $key;
                }
            } catch (Throwable $e) {
                Log::warning('media-control: r2 delete failed', ['key' => $key, 'msg' => $e->getMessage()]);
            }
        }

        return back()->with('success', "Deleted asset #{$id} and " . count($removed) . ' R2 object(s).');
    }

    /** Private byte stream for the admin preview (never a public URL). */
    public function file(MediaAsset $asset)
    {
        $this->actor();

        $key = (string) $asset->path;
        if (! $this->r2()->exists($key)) {
            abort(404, 'Object is not in R2.');
        }

        return response()->streamDownload(function () use ($key) {
            $stream = $this->r2()->readStream($key);
            fpassthru($stream);
            fclose($stream);
        }, basename($key), ['Content-Type' => $asset->mime ?: 'application/octet-stream']);
    }

    /* ------------------------------------------------------------------ */
    /* R2 objects that no admin row points at                              */
    /* ------------------------------------------------------------------ */

    public function orphans(Request $request): View
    {
        $this->actor();

        $report = $this->orphanReport();

        return view('experience.pages.admin.media.orphans', [
            'report' => $report,
            'assets' => null,
        ]);
    }

    public function orphanReport(): array
    {
        $objects = [];
        try {
            $objects = $this->r2()->allFiles();
        } catch (Throwable $e) {
            Log::warning('media-control: r2 list failed', ['msg' => $e->getMessage()]);
        }

        $assetPaths = MediaAsset::whereNotNull('path')->pluck('path')->all();
        $derivPaths = MediaDerivative::whereNotNull('path')->pluck('path')->all();
        $referenced = array_flip(array_merge($assetPaths, $derivPaths));

        $orphans = array_values(array_filter($objects, fn ($f) => ! isset($referenced[$f])));
        sort($orphans);

        $dbMissing = MediaAsset::whereNotNull('path')
            ->get(['id', 'owner_type', 'owner_id', 'slot', 'path', 'status'])
            ->filter(fn ($a) => ! in_array($a->path, $objects, true))
            ->values();

        $grouped = [];
        foreach ($orphans as $o) {
            $grouped[$this->classify($o)] = ($grouped[$this->classify($o)] ?? 0) + 1;
        }

        return [
            'objects' => count($objects),
            'referenced' => count($objects) - count($orphans),
            'orphans' => $orphans,
            'orphan_groups' => $grouped,
            'db_missing' => $dbMissing,
            'db_missing_count' => $dbMissing->count(),
        ];
    }

    /** Best-effort category so a human can triage a 190-row list. */
    private function classify(string $key): string
    {
        if (preg_match('#/(hls)/#i', $key) || preg_match('#\.(m3u8|m4s)$#i', $key)) {
            return 'HLS segments / playlists';
        }
        if (str_starts_with($key, 'db-backups/')) {
            return 'database backups';
        }
        if (str_starts_with($key, 'icons/')) {
            return 'icon JSON';
        }
        if (str_starts_with($key, 'img/')) {
            return 'loose uploads (img/)';
        }
        if (preg_match('#^kicc/#', $key)) {
            return 'KICC brand';
        }
        if (preg_match('#\.(mp4|webm|mov)$#i', $key)) {
            return 'video (unclaimed)';
        }
        if (preg_match('#\.(jpe?g|png|webp)$#i', $key)) {
            return 'image (unclaimed)';
        }

        return 'other';
    }

    /** Delete R2 objects that no row points at. Always previewed first. */
    public function purgeOrphans(Request $request): RedirectResponse
    {
        $this->actor();

        $data = $request->validate([
            'confirm' => ['required', 'in:DELETE'],
            'groups' => ['required', 'array', 'min:1'],
            'groups.*' => ['string', 'max:64'],
        ]);

        $report = $this->orphanReport();
        $allowed = array_intersect($data['groups'], array_keys($report['orphan_groups']));

        // SAFETY (2026-10-08): "unreferenced by media_assets" is NOT the same as
        // "safe to delete". An audit of the live bucket showed the orphan list
        // contains objects the site still needs:
        //   * HLS renditions (v360/v480/v720/v1080 init.mp4, playlist.m3u8,
        //     seg_*.m4s) whose master.m3u8 IS referenced and is fetched by the
        //     live county pages -> deleting them breaks playback;
        //   * db-backups/ hourly database archives;
        //   * icons/ + kicc/ logos referenced from views;
        //   * img/ real photographs (Eliper Hotel, Mombasa landmark) awaiting
        //     mapping to their entity.
        // Those are skipped and reported instead of deleted. Only media objects
        // that are genuinely unreferenced can be purged.
        $protected = function (string $key): bool {
            if (str_contains($key, '/hls/') || preg_match('#\.(m4s|m3u8)$#i', $key)) {
                return true;
            }

            foreach (['db-backups/', 'icons/', 'kicc/', 'img/'] as $prefix) {
                if (str_starts_with($key, $prefix)) {
                    return true;
                }
            }

            return false;
        };

        $candidates = array_values(array_filter(
            $report['orphans'],
            fn ($o) => in_array($this->classify($o), $allowed, true)
        ));

        $victims = array_values(array_filter($candidates, fn ($o) => ! $protected($o)));
        $skipped = count($candidates) - count($victims);

        $deleted = 0;
        $failed = 0;
        foreach ($victims as $key) {
            try {
                $this->r2()->delete($key);
                $deleted++;
            } catch (Throwable $e) {
                $failed++;
                Log::warning('media-control: orphan delete failed', ['key' => $key, 'msg' => $e->getMessage()]);
            }
        }

        return back()->with('success', "Removed {$deleted} unreferenced R2 object(s)" . ($failed ? " ({$failed} failed — see logs)" : '') . ($skipped ? " — {$skipped} protected object(s) kept (HLS renditions, backups, logos, unmapped photos)." : '') . '.');
    }

    /** Drop admin rows whose R2 object is gone. Never touches R2. */
    public function pruneMissing(): RedirectResponse
    {
        $this->actor();

        $objects = [];
        try {
            $objects = $this->r2()->allFiles();
        } catch (Throwable) {
        }

        $rows = MediaAsset::whereNotNull('path')->get(['id', 'path']);
        $gone = $rows->filter(fn ($a) => ! in_array($a->path, $objects, true))->pluck('id')->all();

        MediaDerivative::whereIn('media_asset_id', $gone)->delete();
        MediaAsset::whereIn('id', $gone)->delete();

        return back()->with('success', 'Pruned ' . count($gone) . ' admin row(s) whose object is not in R2. No R2 object was touched.');
    }

    /* ------------------------------------------------------------------ */
    /* Inventory JSON (used by the QA harness)                             */
    /* ------------------------------------------------------------------ */

    public function inventory(): JsonResponse
    {
        $this->actor();
        $report = $this->orphanReport();

        return response()->json([
            'r2_objects' => $report['objects'],
            'referenced' => $report['referenced'],
            'orphans' => count($report['orphans']),
            'orphan_groups' => $report['orphan_groups'],
            'admin_rows_missing_from_r2' => $report['db_missing_count'],
            'by_scope' => [
                'county' => MediaAsset::where('owner_type', County::class)->count(),
                'institution' => MediaAsset::where('owner_type', CountyInstitution::class)->count(),
                'kicc' => MediaAsset::whereIn('owner_type', ['kicc', 'landing_page'])->count(),
                'national' => MediaAsset::where('owner_type', 'national')->count(),
            ],
        ])->withHeaders(['Cache-Control' => 'private,no-store']);
    }

    private function ownerSlug(string $scope, int $id): string
    {
        return match ($scope) {
            'county' => (string) (County::whereKey($id)->value('slug') ?? "county-{$id}"),
            'institution' => (string) (CountyInstitution::whereKey($id)->value('slug') ?? "institution-{$id}"),
            'kicc' => 'kicc',
            default => 'national',
        };
    }
}
