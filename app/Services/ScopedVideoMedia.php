<?php
namespace App\Services;

use App\Models\{CountyInstitution, MediaAsset, MediaDerivative, SectorEntity};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Log, Storage};
use Illuminate\Support\Str;

/** Mutates native media IDs; never deletes a shared R2 object or invents a hierarchy. */
class ScopedVideoMedia
{
    public const MAX_KB = 2097152; // 2 GiB. Same-origin POSTs are additionally capped by the edge proxy (~100 MB); above that the R2 presigned direct-upload path is required.

    private function upload(UploadedFile $file, CountyInstitution $institution): array
    {
        $path = 'institutions/'.$institution->slug.'/videos/'.Str::uuid().'.'.strtolower($file->getClientOriginalExtension());
        $stream = fopen($file->getRealPath(), 'rb');
        try { $ok = Storage::disk('r2')->writeStream($path, $stream); }
        finally { if (is_resource($stream)) fclose($stream); }
        abort_unless($ok && Storage::disk('r2')->exists($path), 503, 'R2 did not confirm the upload. No media record was published.');
        return ['path'=>$path,'original_name'=>$file->getClientOriginalName(),'mime'=>$file->getMimeType(),'size_bytes'=>$file->getSize(),'disk'=>'r2','kind'=>'video','status'=>'ready'];
    }

    public function store(UploadedFile $file, CountyInstitution $institution, int $sectorId, string $slot, string $title): MediaAsset
    {
        $fields = $this->upload($file, $institution);
        try {
            // Native observer dispatches HLS/sync only after the source record exists.
            $asset = DB::transaction(function () use ($fields, $institution, $sectorId, $slot, $title) {
                $asset = MediaAsset::create($fields + ['uuid'=>(string)Str::uuid(),'owner_type'=>CountyInstitution::class,'owner_id'=>$institution->id,'slot'=>$slot,'metadata'=>['sector_id'=>$sectorId,'title'=>$title,'source'=>'sequential-admin']]);
                if ($fields['mime']==='video/mp4') $asset->derivatives()->create(['kind'=>'video_mp4','variant'=>'source','path'=>$fields['path'],'mime'=>$fields['mime'],'size_bytes'=>$fields['size_bytes']]);
                return $asset;
            });
        } catch (\Throwable $e) { Storage::disk('r2')->delete($fields['path']); throw $e; }
        \Illuminate\Support\Facades\Cache::forget('reference.native.v1');
        return $asset;
    }

    public function replace(UploadedFile $file, CountyInstitution $institution, MediaAsset $asset): MediaAsset
    {
        $fields = $this->upload($file, $institution);
        $old = array_unique([$asset->path, ...$asset->derivatives->pluck('path')->all()]);
        try {
            DB::transaction(function () use ($fields, $asset, $institution, $old) {
                $locked = MediaAsset::lockForUpdate()->findOrFail($asset->id);
                abort_unless($locked->owner_type===$asset->owner_type && (int)$locked->owner_id===(int)$asset->owner_id && $locked->path===$asset->path && ($locked->metadata['sector_id']??null)===($asset->metadata['sector_id']??null),409,'Media changed owners or source while uploading. Reload before replacing.');
                $locked->derivatives()->delete();
                $locked->update($fields);
                if ($fields['mime']==='video/mp4') $locked->derivatives()->create(['kind'=>'video_mp4','variant'=>'source','path'=>$fields['path'],'mime'=>$fields['mime'],'size_bytes'=>$fields['size_bytes']]);
                $this->legacyReferences($institution, $old, $fields['path']);
            });
        } catch (\Throwable $e) { Storage::disk('r2')->delete($fields['path']); throw $e; }
        $fresh = $asset->fresh();
        \Illuminate\Support\Facades\Cache::forget('reference.native.v1');
        $this->retire($old);
        return $fresh;
    }

    public function destroy(CountyInstitution $institution, MediaAsset $asset): array
    {
        $old = array_unique([$asset->path, ...$asset->derivatives->pluck('path')->all()]);
        DB::transaction(function () use ($asset, $institution, $old) {
            $locked = MediaAsset::lockForUpdate()->findOrFail($asset->id);
            abort_unless($locked->owner_type===$asset->owner_type && (int)$locked->owner_id===(int)$asset->owner_id && $locked->path===$asset->path && ($locked->metadata['sector_id']??null)===($asset->metadata['sector_id']??null),409,'Media changed owners or source. Reload before deleting.');
            $this->legacyReferences($institution, $old, null);
            $locked->derivatives()->delete();
            $locked->delete();
        });
        return $this->retire($old);
    }

    private function legacyReferences(CountyInstitution $institution, array $paths, ?string $new): void
    {
        $locked = CountyInstitution::lockForUpdate()->findOrFail($institution->id);
        $videos = [];
        foreach ($locked->videos ?? [] as $video) {
            if (in_array($video['path']??null, $paths, true)) {
                if ($new===null) continue;
                $video['path']=$new;
            }
            $videos[]=$video;
        }
        if ($videos!==($locked->videos??[])) $locked->update(['videos'=>$videos]);
    }

    public function retire(array $paths): array
    {
        $report=[];
        foreach (array_unique($paths) as $path) {
            if (!$path) continue;
            $shared = MediaAsset::where('disk','r2')->where('path',$path)->exists() || MediaDerivative::where('path',$path)->exists();
            // The old admin's JSON video lists are still authoritative references.
            if (!$shared) $shared = CountyInstitution::whereNotNull('videos')->get(['videos'])->contains(fn($i)=>collect($i->videos??[])->contains(fn($v)=>($v['path']??null)===$path));
            if ($shared) { $report[$path]='retained_shared'; continue; }
            try {
                $disk=Storage::disk('r2');
                $report[$path]=(!$disk->exists($path) || ($disk->delete($path) && !$disk->exists($path)))?'removed':'cleanup_pending';
                if (in_array(strtolower(pathinfo($path,PATHINFO_EXTENSION)),['mp4','mov','webm'],true)) {
                    $prefix=dirname($path).'/hls/'.pathinfo($path,PATHINFO_FILENAME);
                    if (!MediaDerivative::where('path','like',$prefix.'/%')->exists() && !MediaAsset::where('path','like',$prefix.'/%')->exists()) {
                        foreach($disk->allFiles($prefix) as $segment) $disk->delete($segment);
                    }
                }
            } catch (\Throwable $e) { $report[$path]='cleanup_pending'; Log::warning('Video object cleanup pending',['path'=>$path,'error'=>$e->getMessage()]); }
        }
        return $report;
    }
}
