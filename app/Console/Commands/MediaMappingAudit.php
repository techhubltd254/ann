<?php

namespace App\Console\Commands;

use App\Models\County;
use App\Models\CountyInstitution;
use App\Models\MediaAsset;
use App\Models\MediaDerivative;
use App\Support\MediaMapping;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MediaMappingAudit extends Command
{
    protected $signature = 'kicc:media-audit {--out=storage/app/media-audit : output directory}';

    protected $description = 'Audit every video reference against R2 + admin rows and log each as kept, mapped or removed';

    public function handle(): int
    {
        $dir = base_path($this->option('out'));
        @mkdir($dir, 0775, true);

        try {
            $objects = Storage::disk('r2')->allFiles();
        } catch (Throwable $e) {
            $this->error('R2 unreachable: ' . $e->getMessage());

            return self::FAILURE;
        }

        $assetPaths = MediaAsset::whereNotNull('path')->pluck('path')->all();
        $derivPaths = MediaDerivative::whereNotNull('path')->pluck('path')->all();
        $referenced = array_flip(array_merge($assetPaths, $derivPaths));

        // ── R2 objects ────────────────────────────────────────────────
        $objRows = [];
        foreach ($objects as $key) {
            $isHls = (bool) preg_match('#\.(m4s|m3u8)$#i', $key) || str_contains($key, '/hls/');
            $objRows[] = [
                'key' => $key,
                'category' => $isHls ? 'hls' : (str_starts_with($key, 'db-backups/') ? 'backup' : (str_starts_with($key, 'icons/') ? 'icon' : (str_starts_with($key, 'img/') ? 'loose-upload' : 'media'))),
                'referenced_by_row' => isset($referenced[$key]) ? 'yes' : 'no',
                'verdict' => isset($referenced[$key]) ? 'kept' : 'removed',
            ];
        }

        // ── admin rows ────────────────────────────────────────────────
        $rowRows = [];
        foreach (MediaAsset::with('derivatives')->get() as $a) {
            $inR2 = in_array($a->path, $objects, true);
            $rowRows[] = [
                'asset_id' => $a->id,
                'owner_type' => $a->owner_type,
                'owner_id' => $a->owner_id,
                'slot' => $a->slot,
                'path' => $a->path,
                'status' => $a->status,
                'in_r2' => $inR2 ? 'yes' : 'no',
                'verdict' => $inR2 ? 'mapped' : 'removed',
            ];
        }

        // ── per-entity verdicts ───────────────────────────────────────
        $entityRows = [];
        foreach (County::orderBy('name')->get(['id', 'name', 'slug', 'code']) as $c) {
            $hero = MediaMapping::countyHero($c);
            $entityRows[] = [
                'scope' => 'county',
                'entity_id' => $c->id,
                'entity' => $c->name,
                'slug' => $c->slug,
                'state' => $hero['state'],
                'renders' => $hero['video'] ? $hero['video'] : 'FALLBACK TILE (no own film)',
                'source_key' => $hero['path'] ?? '',
                'reason' => $hero['reason'],
            ];
        }
        foreach (CountyInstitution::orderBy('name')->get(['id', 'name', 'slug']) as $i) {
            $hero = MediaMapping::institutionHero($i);
            $entityRows[] = [
                'scope' => 'institution',
                'entity_id' => $i->id,
                'entity' => $i->name,
                'slug' => $i->slug,
                'state' => $hero['state'],
                'renders' => $hero['video'] ? $hero['video'] : 'FALLBACK TILE (no own film)',
                'source_key' => $hero['path'] ?? '',
                'reason' => $hero['reason'],
            ];
        }

        $this->write($dir, 'r2-objects', $objRows);
        $this->write($dir, 'admin-rows', $rowRows);
        $this->write($dir, 'entity-mapping', $entityRows);

        $states = array_count_values(array_column($entityRows, 'state'));
        $this->info('R2 objects: ' . count($objRows) . ' (kept ' . count(array_filter($objRows, fn ($r) => $r['verdict'] === 'kept')) . ', removed ' . count(array_filter($objRows, fn ($r) => $r['verdict'] === 'removed')) . ')');
        $this->info('Admin rows: ' . count($rowRows) . ' (mapped ' . count(array_filter($rowRows, fn ($r) => $r['verdict'] === 'mapped')) . ', removed ' . count(array_filter($rowRows, fn ($r) => $r['verdict'] === 'removed')) . ')');
        $this->info('Entities: ' . count($entityRows) . ' — ' . json_encode($states));
        $this->info('Written to ' . $dir);

        return self::SUCCESS;
    }

    private function write(string $dir, string $name, array $rows): void
    {
        file_put_contents("{$dir}/{$name}.json", json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        if (! $rows) {
            return;
        }
        $fh = fopen("{$dir}/{$name}.csv", 'w');
        fputcsv($fh, array_keys($rows[0]));
        foreach ($rows as $r) {
            fputcsv($fh, $r);
        }
        fclose($fh);
    }
}
