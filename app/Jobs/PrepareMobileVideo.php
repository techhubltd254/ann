<?php
namespace App\Jobs;
use App\Models\MediaAsset;
use Illuminate\Bus\Queueable;use Illuminate\Contracts\Queue\ShouldQueue;use Illuminate\Foundation\Bus\Dispatchable;use Illuminate\Queue\InteractsWithQueue;use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\{Storage,Cache};use Symfony\Component\Process\Process;
class PrepareMobileVideo implements ShouldQueue
{
 use Dispatchable,InteractsWithQueue,Queueable,SerializesModels;public $timeout=2400;public $tries=2;public $backoff=60;
 public function __construct(public int $assetId,public string $expectedPath){$this->onQueue('video');}
 public function handle():void{
  $a=MediaAsset::with('derivatives')->find($this->assetId);if(!$a||$a->path!==$this->expectedPath||$a->kind!=='video')return;if($a->derivatives->contains('kind','video_mobile'))return;
  $root=storage_path('app/video-processing');$dir=$root.'/mobile-'.$a->id.'-'.bin2hex(random_bytes(4));if(!is_dir($dir)&&!mkdir($dir,0700,true))throw new \RuntimeException('Mobile video workspace unavailable.');$disk=Storage::disk($a->disk);$src=$dir.'/source.mp4';$dst=$dir.'/mobile.mp4';
  try{if(disk_free_space($dir)<$a->size_bytes*2+536870912)throw new \RuntimeException('Mobile preparation requires more free storage.');$key=$a->derivatives->firstWhere('variant','stream-safe')?->path?:$a->path;$in=$disk->readStream($key);if(!is_resource($in))throw new \RuntimeException('Cannot read mobile preparation source.');$out=fopen($src,'wb');try{stream_copy_to_stream($in,$out);}finally{fclose($in);fclose($out);}
   $p=new Process(['ffmpeg','-nostdin','-y','-i',$src,'-map','0:v:0','-map','0:a:0?','-vf','scale=min(854\\,iw):-2','-r','24','-c:v','libx264','-preset','veryfast','-crf','27','-maxrate','900k','-bufsize','1800k','-pix_fmt','yuv420p','-threads','2','-c:a','aac','-b:a','64k','-movflags','+faststart',$dst]);$p->setTimeout(2200);$p->mustRun();
   $probe=new Process(['ffprobe','-v','error','-show_entries','format=duration','-of','json',$dst]);$probe->setTimeout(30);$probe->mustRun();if((float)(json_decode($probe->getOutput(),true)['format']['duration']??0)<=0)throw new \RuntimeException('Mobile derivative invalid.');
   if(MediaAsset::where('id',$a->id)->where('path',$this->expectedPath)->doesntExist())return;$target=dirname($a->path).'/mobile/'.pathinfo($a->path,PATHINFO_FILENAME).'-480p.mp4';$f=fopen($dst,'rb');try{$ok=$disk->put($target,$f,['ContentType'=>'video/mp4']);}finally{fclose($f);}if(!$ok||$disk->size($target)!==filesize($dst))throw new \RuntimeException('Mobile derivative storage did not verify.');$a->derivatives()->updateOrCreate(['kind'=>'video_mobile','variant'=>'480p'],['path'=>$target,'mime'=>'video/mp4','size_bytes'=>filesize($dst)]);Cache::forget('reference.native.v1');Cache::increment('kicc_cache_version');
  }finally{foreach(glob($dir.'/*')?:[] as $f)unlink($f);if(is_dir($dir))rmdir($dir);}
 }
}
