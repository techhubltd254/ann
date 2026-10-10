<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\{MediaAsset,County,CountyInstitution,Venue,Ministry};
use App\Models\Marketplace\Product;
use App\Services\AdminHierarchyScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Cache,DB,Storage};
use Illuminate\Support\Str;

/** Actor-bound, size-checked 4 MiB uploads; uses the existing entity hierarchy. */
class ChunkedUploadController extends Controller
{
    public const MAX_BYTES=2147483648;
    public const CHUNK_BYTES=4194304;
    private function actor(Request $r): \App\Models\User
    {
        $u=$r->user();
        abort_unless($u && ($u->status??'active')==='active' && app(AdminHierarchyScope::class)->level($u),403,'An active administration role is required.');
        return $u;
    }
    private function owner(Request $r,array $d)
    {
        $u=$this->actor($r);$scope=app(AdminHierarchyScope::class);
        $type=$d['owner_type'];$id=(int)$d['owner_id'];
        if($type==='national_page'){
            app(\App\Services\NationalMediaService::class)->authorize($u);
            abort_unless($id===1&&in_array($d['slot']??'',['national_hero_video','national_flag_video'],true),422,'Invalid national media target.');
            return (object)['id'=>1,'slug'=>'national-government'];
        }
        if($type===Ministry::class){app(\App\Services\NationalMediaService::class)->authorize($u);$ministry=Ministry::findOrFail($id);abort_unless(in_array($d['slot']??'',['ministry_video_'.$ministry->slug,'ministry_flag_video'],true),422,'Invalid ministry media slot.');return $ministry;}

        abort_unless(in_array($type,[County::class,CountyInstitution::class,Venue::class,Product::class],true),422,'Unsupported owner type.');
        $o=$type::findOrFail($id);
        $inst=$type===Product::class?CountyInstitution::findOrFail((int)$o->institution_id):null;
        $ok=match($type){County::class=>$scope->canCounty($u,$o),CountyInstitution::class=>$scope->canInstitution($u,$o),Product::class=>$scope->canInstitution($u,$inst)&&(int)$o->county_id===(int)$inst->county_id,default=>$u->hasRole('kicc_admin')};
        abort_unless($ok,403,'This owner is outside your administration scope.');
        if($type===Product::class)abort_unless(($d['slot']??'')==='product_video',422,'A product upload requires the product video slot.');
        if($type===County::class)abort_unless(in_array($d['slot']??'',['hero_video','flag_video','4d_video'],true),422,'Invalid county slot.');
        if($type===Venue::class)abort_unless(($d['slot']??'')==='hero_video',422,'Invalid venue slot.');
        if($type===CountyInstitution::class)abort_unless(in_array($d['slot']??'',['institution_video','hero_video','4d_video','flag_video'],true),422,'Invalid institution slot.');
        if($type===CountyInstitution::class||$type===Product::class){
            $context=$inst??$o;
            $sector=(int)($d['sector_id']??0);
            abort_unless($sector>0 && $context->sectorEntities()->where('sector_id',$sector)->exists() && $context->county->sectors()->where('sectors.id',$sector)->exists(),422,'Choose a sector linked to the institution and county.');
        }
        return $o;
    }
    private function session(Request $r,string $id): array
    {
        $u=$this->actor($r);
        abort_unless((bool)preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/D',$id),404);
        $dir=storage_path('app/chunked/'.$id);
        abort_unless(is_file($dir.'/meta.json'),404,'Unknown upload.');
        $meta=json_decode(file_get_contents($dir.'/meta.json'),true);
        abort_unless(($meta['actor_id']??0)===$u->id,403,'Upload session belongs to another account.');
        abort_unless(($meta['expires']??0)>time(),410,'Upload expired.');
        $this->owner($r,$meta);
        return [$dir,$meta];
    }
    public function index(Request $r)
    {
        $u=$this->actor($r);$scope=app(AdminHierarchyScope::class);
        return response()->view('experience.admin.uploads',[
            'counties'=>$scope->counties($u)->orderBy('name')->get(['id','name','slug']),
            'institutions'=>$scope->institutions($u)->orderBy('name')->get(['id','county_id','name','slug']),
            'venues'=>$u->hasRole('kicc_admin')?Venue::orderBy('name')->get(['id','name','slug']):collect(),
        ])->header('Cache-Control','private,no-store');
    }
    public function init(Request $r)
    {
        $u=$this->actor($r);
        $d=$r->validate(['filename'=>'required|string|max:255','size'=>'required|integer|min:1|max:'.self::MAX_BYTES,'mime'=>'required|in:video/mp4,video/webm,video/quicktime,video/x-matroska,video/x-msvideo','owner_type'=>'required|string|max:200','owner_id'=>'required|integer|min:1','sector_id'=>'nullable|integer|min:1','slot'=>'required|string|max:150','title'=>'required|string|max:255','replace_id'=>'nullable|integer|min:1','publish_on_ready'=>'nullable|boolean']);
        $d['publish_on_ready']=$r->boolean('publish_on_ready',true);
        $o=$this->owner($r,$d);
        if($d['owner_type']===Product::class){$inst=CountyInstitution::findOrFail((int)$o->institution_id);$d['institution_id']=$inst->id;$d['institution_slug']=$inst->slug;}
        $ext=strtolower(pathinfo($d['filename'],PATHINFO_EXTENSION));
        abort_unless(in_array($ext,['mp4','webm','mov','m4v','mkv','avi'],true),422,'Unsupported container.');
        if(in_array($d['owner_type'],['national_page',Ministry::class],true))abort_unless(empty($d['replace_id']),422,'National uploads are staged as new drafts; publishing safely replaces the live version.');
        if(!empty($d['replace_id'])){
            $a=MediaAsset::findOrFail($d['replace_id']);
            abort_unless($a->owner_type===$d['owner_type'] && (int)$a->owner_id===(int)$o->id && $a->slot===$d['slot'],403,'Replacement does not belong to this owner and slot.');
            $d['replace_path']=$a->path;
        }
        abort_unless(disk_free_space(storage_path())>($d['size']*2+1073741824),507,'Insufficient temporary disk space.');
        $id=(string)Str::uuid();$dir=storage_path('app/chunked/'.$id);
        $root=dirname($dir);if(!is_dir($root))mkdir($root,02770,true);chmod($root,02770);abort_unless(mkdir($dir,02770,true),503,'Cannot create upload session.');chmod($dir,02770);
        $d+=['actor_id'=>$u->id,'expires'=>time()+86400,'slug'=>$o->slug?:('entity-'.$o->id),'extension'=>$ext];
        file_put_contents($dir.'/meta.json',json_encode($d,JSON_THROW_ON_ERROR),LOCK_EX);chmod($dir.'/meta.json',0660);touch($dir.'/lock');chmod($dir.'/lock',0660);
        return response()->json(['upload_id'=>$id,'chunk_bytes'=>self::CHUNK_BYTES,'max_bytes'=>self::MAX_BYTES],201)->header('Cache-Control','no-store');
    }
    public function status(Request $r,string $uploadId){[$dir,$m]=$this->session($r,$uploadId);$n=(int)ceil($m['size']/self::CHUNK_BYTES);$next=0;while($next<$n&&is_file($dir.'/'.sprintf('%06d.part',$next)))$next++;return response()->json(['upload_id'=>$uploadId,'next'=>$next,'chunk_bytes'=>self::CHUNK_BYTES,'size'=>$m['size'],'expires'=>$m['expires'],'error'=>is_file($dir.'/finalization-error.json')?json_decode(file_get_contents($dir.'/finalization-error.json'),true):null,'result'=>is_file($dir.'/result.json')?json_decode(file_get_contents($dir.'/result.json'),true):null])->header('Cache-Control','no-store');}
    public function cancel(Request $r,string $uploadId){[$dir,$m]=$this->session($r,$uploadId);$lock=fopen($dir.'/lock','c');abort_unless(flock($lock,LOCK_EX|LOCK_NB),409,'Upload is completing.');try{abort_if(is_file($dir.'/result.json'),409,'Completed media belongs to the media library.');foreach(glob($dir.'/*')?:[] as $f)if(is_file($f)&&basename($f)!=='lock')unlink($f);}finally{flock($lock,LOCK_UN);fclose($lock);}unlink($dir.'/lock');rmdir($dir);return response()->json(['cancelled'=>true]);}
    public function chunk(Request $r,string $uploadId)
    {
        [$dir,$m]=$this->session($r,$uploadId);
        $d=$r->validate(['index'=>'required|integer|min:0|max:511','chunk'=>'required|file|max:4096']);
        $i=(int)$d['index'];$off=$i*self::CHUNK_BYTES;
        abort_unless($off<(int)$m['size'],422,'Chunk index out of range.');
        $expected=min(self::CHUNK_BYTES,(int)$m['size']-$off);
        abort_unless($r->file('chunk')->getSize()===$expected,422,'Chunk size mismatch.');
        $lock=fopen($dir.'/lock','c');abort_unless(flock($lock,LOCK_EX),503);
        try{$r->file('chunk')->move($dir,sprintf('%06d.part',$i));chmod($dir.'/'.sprintf('%06d.part',$i),0660);}finally{flock($lock,LOCK_UN);fclose($lock);}
        return response()->json(['index'=>$i,'received'=>count(glob($dir.'/*.part')?:[]),'bytes'=>$expected])->header('Cache-Control','no-store');
    }
    public function complete(Request $r,string $uploadId)
    {
        [$dir,$m]=$this->session($r,$uploadId);
        if($r->boolean('async')&&!$r->attributes->get('finalize_job')){
            if(is_file($dir.'/result.json'))return response()->json(json_decode(file_get_contents($dir.'/result.json'),true));
            $enqueue=fopen($dir.'/enqueue.lock','c');abort_unless(flock($enqueue,LOCK_EX|LOCK_NB),409,'Finalization is being scheduled.');
            try{if(!is_file($dir.'/enqueued')||is_file($dir.'/finalization-error.json')){\App\Jobs\FinalizeChunkedUpload::dispatch($uploadId,(int)$m['actor_id']);file_put_contents($dir.'/enqueued',(string)time());if(is_file($dir.'/finalization-error.json'))unlink($dir.'/finalization-error.json');}}finally{flock($enqueue,LOCK_UN);fclose($enqueue);}
            return response()->json(['upload_id'=>$uploadId,'status'=>'finalizing'],202)->header('Cache-Control','no-store');
        }
        $lock=fopen($dir.'/lock','c');abort_unless(flock($lock,LOCK_EX|LOCK_NB),409,'Upload is being completed.');
        try{
            if(is_file($dir.'/result.json'))return response()->json(json_decode(file_get_contents($dir.'/result.json'),true));
            $n=(int)ceil($m['size']/self::CHUNK_BYTES);
            for($i=0;$i<$n;$i++){
                $p=$dir.'/'.sprintf('%06d.part',$i);$expected=min(self::CHUNK_BYTES,$m['size']-$i*self::CHUNK_BYTES);
                abort_unless(is_file($p) && filesize($p)===$expected,422,'Missing or malformed chunk '.$i);
            }
            $assembled=$dir.'/assembled.bin';$out=fopen($assembled,'wb');$total=0;
            try{for($i=0;$i<$n;$i++){$in=fopen($dir.'/'.sprintf('%06d.part',$i),'rb');$total+=stream_copy_to_stream($in,$out);fclose($in);}}finally{fclose($out);}
            abort_unless($total===(int)$m['size'],422,'Assembled size mismatch.');
            $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($assembled);
            abort_unless(in_array($mime,['video/mp4','video/webm','video/quicktime','video/x-matroska','video/x-msvideo'],true),422,'File content is not a supported video.');
            $playbackReady=true;$sourceCodec=null;
            if(true){
                $probe=new \Symfony\Component\Process\Process(['ffprobe','-v','error','-show_streams','-show_format','-of','json',$assembled]);$probe->setTimeout(120);$probe->run();
                abort_unless($probe->isSuccessful(),422,'The uploaded video is corrupt or cannot be decoded. Nothing was published.');
                $info=json_decode($probe->getOutput(),true);$video=null;$audio=null;foreach($info['streams']??[] as $stream){if(($stream['codec_type']??'')==='video'&&!$video)$video=$stream;if(($stream['codec_type']??'')==='audio'&&!$audio)$audio=$stream;}
                abort_unless($video&&(float)($info['format']['duration']??0)>0,422,'The file has no playable video stream. Nothing was published.');
                $sourceCodec=$video['codec_name'];$playbackReady=$mime==='video/mp4'&&$sourceCodec==='h264'&&($video['pix_fmt']??'')==='yuv420p'&&(!$audio||($audio['codec_name']??'')==='aac');
            }
            $prefix=match($m['owner_type']){County::class=>'counties',CountyInstitution::class=>'institutions',default=>'venues'};
            $national=in_array($m['owner_type'],['national_page',Ministry::class],true);
            $key=$m['owner_type']===Product::class?'institutions/'.$m['institution_slug'].'/products/'.$m['owner_id'].'/videos/'.$uploadId.'.'.$m['extension']:$prefix.'/'.$m['slug'].'/videos/'.$uploadId.'.'.$m['extension'];$key=$national?('national/'.($m['owner_type']==='national_page'?'government':'ministries/'.$m['slug']).'/'.$m['slot'].'/'.$uploadId.'.'.$m['extension']):$key;$disk=Storage::disk('r2');
            $in=fopen($assembled,'rb');
            try{$ok=$disk->put($key,$in,['ContentType'=>$mime]);}finally{if(is_resource($in))fclose($in);}
            abort_unless($ok && $disk->exists($key) && $disk->size($key)===$total,503,'R2 did not verify the complete file. Nothing was published.');
            try{
                $a=DB::transaction(function()use($m,$key,$mime,$total,$playbackReady,$sourceCodec,$national){
                    $fields=['disk'=>'r2','path'=>$key,'original_name'=>$m['filename'],'mime'=>$mime,'kind'=>'video','size_bytes'=>$total,'status'=>$playbackReady?'ready':'processing','alt_text'=>$m['title'],'metadata'=>['sector_id'=>$m['sector_id']??null,'title'=>$m['title'],'uploaded_via'=>'chunked','actor_id'=>$m['actor_id'],'publish_on_ready'=>$m['publish_on_ready']??true,'playback'=>['state'=>$playbackReady?'source-ready':'queued','source_codec'=>$sourceCodec]]];
                    if($national){$fields['status']='processing';$fields['metadata']=array_merge($fields['metadata'],['namespace'=>'national','publication'=>'draft','publish_on_ready'=>$m['publish_on_ready']??true,'target_slot'=>$m['slot']]);}
                    if(!empty($m['replace_id'])){
                        $a=MediaAsset::lockForUpdate()->findOrFail($m['replace_id']);
                        abort_unless($a->path===$m['replace_path'] && $a->owner_type===$m['owner_type'] && (int)$a->owner_id===(int)$m['owner_id'],409,'Media changed during upload.');
                        $a->derivatives()->delete();$a->update($fields);
                    }else{$a=MediaAsset::create($fields+['uuid'=>(string)Str::uuid(),'owner_type'=>$m['owner_type']==='national_page'?County::class:$m['owner_type'],'owner_id'=>$m['owner_type']==='national_page'?0:$m['owner_id'],'slot'=>$national?'draft__'.$m['slot']:$m['slot']]);}
                    if($mime==='video/mp4')$a->derivatives()->create(['kind'=>'video_mp4','variant'=>'source','path'=>$key,'mime'=>$mime,'size_bytes'=>$total]);
                    if($m['owner_type']===Product::class){
                        $p=Product::lockForUpdate()->findOrFail($m['owner_id']);abort_unless((int)$p->institution_id===(int)$m['institution_id'],409,'Product ownership changed during upload.');
                        $newUrl=url('/media/original/'.$key);$oldPath=$m['replace_path']??null;$urls=array_values(array_filter($p->videos??[],fn($v)=>!$oldPath||!str_contains($v,$oldPath)));array_unshift($urls,$newUrl);$p->update(['video_url'=>$newUrl,'videos'=>array_values(array_unique($urls))]+(($m['publish_on_ready']??true)?['status'=>'active']:[]));
                        $i=CountyInstitution::lockForUpdate()->findOrFail($m['institution_id']);$entries=$i->products??[];
                        foreach($entries as &$entry)if((int)($entry['marketplace_product_id']??0)===$p->id||($entry['name']??'')===$p->name){$entry['marketplace_product_id']=$p->id;$entry['videos']=$p->videos;$entry['video_url']=$newUrl;if($m['publish_on_ready']??true)$entry['publication_status']='active';}unset($entry);$i->update(['products'=>$entries]);
                    }
                    return $a;
                });
            }catch(\Throwable $e){$disk->delete($key);throw $e;}
            foreach(['reference.native.v1','kicc_home','kicc_counties_index','resolve:county_hero_id_'.$m['owner_id']] as $k)Cache::forget($k);
            Cache::forget('kicc:r2:keys');Cache::forget('kicc:r2:keyset');
            Cache::increment('kicc_cache_version');
            if(true){\App\Jobs\PrepareProductVideo::dispatch($a->id,$key);}
            $ownerUrl=match($m['owner_type']){Product::class=>route('marketplace.show',Product::findOrFail($m['owner_id'])->slug),CountyInstitution::class=>url('/institutions/'.CountyInstitution::findOrFail($m['owner_id'])->slug),County::class=>url('/counties/'.County::findOrFail($m['owner_id'])->slug),default=>url('/venues')};if($national)$ownerUrl=url('/national-government');
            $result=['owner_url'=>$ownerUrl,'id'=>$a->id,'path'=>$key,'bytes'=>$total,'status'=>$a->status,'public_url'=>'/media/original/'.$key,'sha256'=>hash_file('sha256',$assembled)];
            file_put_contents($dir.'/result.json',json_encode($result));
            foreach(glob($dir.'/*.part')?:[] as $p)unlink($p);unlink($assembled);
            return response()->json($result)->header('Cache-Control','no-store');
        }finally{flock($lock,LOCK_UN);fclose($lock);}
    }
}
