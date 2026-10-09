<?php

namespace App\Http\Controllers\Web;

use App\Models\MediaAsset;
use App\Models\CountyInstitution;
use App\Support\MediaMapping;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Chunked upload path for very large video.
 *
 * The edge (Cloudflare) rejects request bodies over ~100 MB, so a 2 GB file can
 * never arrive in a single POST. The browser slices the file, each slice is a
 * small request, and the server appends it to a part file on local disk. Only
 * the assembled file is streamed to R2, so peak memory stays flat regardless
 * of file size.
 */
class ChunkedUploadController extends Controller
{
    public const MAX_BYTES = 2147483648;   // 2 GiB
    public const CHUNK_BYTES = 4194304;    // 4 MiB slices
    private const PART_DIR = 'app/chunked';

    private function guard(Request $request): void
    {
        $u = $request->user();
        abort_unless($u && $u->hasAnyRole(['kicc_admin', 'national_admin', 'county_admin', 'institution_admin', 'exhibitor']), 403, 'An administration role is required to upload.');
    }

    public function init(Request $request)
    {
        $this->guard($request);
        $d = $request->validate([
            'filename' => 'required|string|max:255',
            'size' => 'required|integer|min:1|max:'.self::MAX_BYTES,
            'mime' => 'nullable|string|max:100',
            'owner_type' => 'required|string|max:200',
            'owner_id' => 'required|integer',
            'slot' => 'required|string|max:100',
            'title' => 'nullable|string|max:255',
        ]);

        $id = (string) Str::uuid();
        $dir = storage_path(self::PART_DIR.'/'.$id);
        @mkdir($dir, 0775, true);
        file_put_contents($dir.'/meta.json', json_encode($d));

        return response()->json([
            'upload_id' => $id,
            'chunk_bytes' => self::CHUNK_BYTES,
            'max_bytes' => self::MAX_BYTES,
        ])->header('Cache-Control', 'no-store');
    }

    public function chunk(Request $request, string $uploadId)
    {
        $this->guard($request);
        abort_unless(preg_match('/^[a-f0-9\-]{36}$/', $uploadId), 404, 'Unknown upload session.');
        $dir = storage_path(self::PART_DIR.'/'.$uploadId);
        abort_unless(is_dir($dir), 404, 'Unknown upload session.');

        $request->validate(['index' => 'required|integer|min:0|max:65535', 'chunk' => 'required|file']);
        $index = (int) $request->input('index');
        $request->file('chunk')->move($dir, sprintf('%06d.part', $index));

        $received = count(glob($dir.'/*.part') ?: []);
        $bytes = 0;
        foreach (glob($dir.'/*.part') ?: [] as $p) { $bytes += filesize($p); }
        abort_if($bytes > self::MAX_BYTES, 422, 'Upload exceeds the 2 GiB ceiling.');

        return response()->json(['received' => $received, 'index' => $index, 'bytes' => $bytes])
            ->header('Cache-Control', 'no-store');
    }

    public function complete(Request $request, string $uploadId)
    {
        $this->guard($request);
        abort_unless(preg_match('/^[a-f0-9\-]{36}$/', $uploadId), 404, 'Unknown upload session.');
        $dir = storage_path(self::PART_DIR.'/'.$uploadId);
        abort_unless(is_dir($dir) && is_file($dir.'/meta.json'), 404, 'Unknown upload session.');

        $meta = json_decode(file_get_contents($dir.'/meta.json'), true) ?: [];
        $parts = glob($dir.'/*.part') ?: [];
        sort($parts);
        abort_if($parts === [], 422, 'No chunks were received for this session.');

        $assembled = $dir.'/assembled.bin';
        $out = fopen($assembled, 'wb');
        $total = 0;
        foreach ($parts as $p) {
            $in = fopen($p, 'rb');
            $total += stream_copy_to_stream($in, $out);
            fclose($in);
        }
        fclose($out);
        abort_if($total < 1, 422, 'Assembled file is empty.');
        abort_if($total > self::MAX_BYTES, 422, 'Assembled file exceeds 2 GiB.');

        $ext = strtolower(pathinfo((string) ($meta['filename'] ?? ''), PATHINFO_EXTENSION)) ?: 'mp4';
        abort_unless(in_array($ext, ['mp4', 'mov', 'webm', 'm4v', 'avi', 'mkv'], true), 422, 'Unsupported video container: '.$ext);

        $slug = Str::slug(pathinfo((string) ($meta['filename'] ?? 'video'), PATHINFO_FILENAME));
        $key = 'uploads/'.$uploadId.'/'.$slug.'.'.$ext;

        $stream = fopen($assembled, 'rb');
        Storage::disk('r2')->putStream($key, $stream);
        if (is_resource($stream)) { fclose($stream); }

        $mime = $meta['mime'] ?? 'video/'.($ext === 'mov' ? 'quicktime' : $ext);
        $asset = MediaAsset::create([
            'uuid' => (string) Str::uuid(),
            'owner_type' => $meta['owner_type'],
            'owner_id' => (int) $meta['owner_id'],
            'slot' => $meta['slot'],
            'disk' => 'r2',
            'path' => $key,
            'original_name' => $meta['filename'] ?? basename($key),
            'mime' => $mime,
            'kind' => 'video',
            'size_bytes' => $total,
            'status' => 'ready',
            'alt_text' => $meta['title'] ?? null,
            'metadata' => ['title' => $meta['title'] ?? null, 'uploaded_via' => 'chunked', 'bytes' => $total],
        ]);

        // One asset per (owner, slot): retire the previous row for this slot.
        MediaAsset::forSlot($meta['owner_type'], (int) $meta['owner_id'], $meta['slot'])
            ->where('id', '!=', $asset->id)
            ->delete();

        // The public site resolves tiles through these caches; drop them so the
        // change is visible on the next request, not in five minutes.
        foreach (['reference.native.v1', 'kicc_home', 'kicc_counties_index'] as $k) { Cache::forget($k); }
        Cache::forget('county_hero_id_'.(int) $meta['owner_id']);
        Cache::increment('kicc_cache_version');

        foreach (glob($dir.'/*') ?: [] as $f) { @unlink($f); }
        @rmdir($dir);

        $serves = null;
        if ($meta['owner_type'] === CountyInstitution::class) {
            $inst = CountyInstitution::find((int) $meta['owner_id']);
            if ($inst) { $serves = MediaMapping::institutionHero($inst)['state'] ?? null; }
        }

        return response()->json([
            'id' => $asset->id,
            'path' => $asset->path,
            'bytes' => $total,
            'status' => $asset->status,
            'public_url' => '/media/video/'.ltrim($asset->path, '/'),
            'algorithm_state' => $serves,
        ])->header('Cache-Control', 'no-store');
    }
}
