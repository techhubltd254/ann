<?php
namespace App\Jobs;
use App\Models\MediaAsset;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\{Storage,DB,Cache,Log};
use Symfony\Component\Process\Process;
class PrepareProductVideo implements ShouldQueue {
 use Dispatchable,InteractsWithQueue,Queueable,SerializesModels;
 public $timeout=3300;public $tries=2;public $backoff=60;
 public function __construct(public int $assetId,public string $expectedPath){$this->onQueue('video');}
 public function handle():void {
  $a=MediaAsset::find($this->assetId);if(!$a||$a->path!==$this->expectedPath)return;
  $dir=storage_path('app/video-processing/'.$a->id.'-'.bin2hex(random_bytes(4)));mkdir($dir,0700,true);
  $source=$dir.'/source.bin';$dest=$dir.'/stream.mp4';$poster=$dir.'/poster.webp';$disk=Storage::disk('r2');
  try {
   if(disk_free_space($dir)<$a->size_bytes*3+1073741824)throw new \RuntimeException('Not enough video processing disk space');
   $in=$disk->readStream($a->path);if(!is_resource($in))throw new \RuntimeException('Cannot read source');$out=fopen($source,'wb');try{stream_copy_to_stream($in,$out);}finally{fclose($in);fclose($out);}
   $probe=new Process(['ffprobe','-v','error','-show_streams','-show_format','-of','json',$source]);$probe->setTimeout(120);$probe->mustRun();$info=json_decode($probe->getOutput(),true);$video=null;$audio=null;
   foreach($info['streams']??[] as $stream){if(($stream['codec_type']??'')==='video'&&!$video)$video=$stream;if(($stream['codec_type']??'')==='audio'&&!$audio)$audio=$stream;}
   if(!$video)throw new \RuntimeException('No decodable video stream');
   $compatible=($video['codec_name']??'')==='h264'&&($video['pix_fmt']??'')==='yuv420p'&&(!$audio||($audio['codec_name']??'')==='aac');
   // Compatible H.264/AAC masters are remuxed, not needlessly re-encoded.
   $args=['ffmpeg','-nostdin','-y','-i',$source,'-map','0:v:0','-map','0:a:0?'];
   $args=array_merge($args,$compatible?['-c','copy']:['-c:v','libx264','-preset','veryfast','-crf','23','-maxrate','4000k','-bufsize','8000k','-pix_fmt','yuv420p','-threads','2','-vf','scale=min(1920\\,iw):-2','-c:a','aac','-b:a','128k']);
   // A 60-fps master is unnecessary work and bandwidth for a web hero.
   if(!$compatible)$args=array_merge($args,['-r','30']);
   $args=array_merge($args,['-movflags','+faststart',$dest]);$p=new Process($args);$p->setTimeout(3000);$p->mustRun();
   $verify=new Process(['ffprobe','-v','error','-show_entries','stream=codec_name,codec_type:format=duration','-of','json',$dest]);$verify->setTimeout(90);$verify->mustRun();$output=json_decode($verify->getOutput(),true);
   if((float)($output['format']['duration']??0)<=0)throw new \RuntimeException('Prepared video verification failed');
   $streamKey=dirname($a->path).'/stream/'.pathinfo($a->path,PATHINFO_FILENAME).'-faststart.mp4';
   $fh=fopen($dest,'rb');try{$ok=$disk->put($streamKey,$fh,['ContentType'=>'video/mp4']);}finally{fclose($fh);}
   if(!$ok||$disk->size($streamKey)!==filesize($dest))throw new \RuntimeException('Prepared R2 video verification failed');
   $posterKey=dirname($a->path).'/poster/'.pathinfo($a->path,PATHINFO_FILENAME).'.webp';
   $pp=new Process(['ffmpeg','-nostdin','-y','-ss',((float)($output['format']['duration']??0)>2?'1':'0'),'-i',$dest,'-frames:v','1','-vf','scale=720:-2','-c:v','libwebp','-q:v','75',$poster]);$pp->setTimeout(60);$pp->run();
   if(MediaAsset::where('id',$a->id)->where('path',$this->expectedPath)->doesntExist()){$disk->delete($streamKey);return;}
   DB::transaction(function()use($a,$disk,$streamKey,$poster,$posterKey,$dest,$output,$compatible){$a=MediaAsset::lockForUpdate()->findOrFail($a->id);if($a->path!==$this->expectedPath)return;
    $a->derivatives()->where('kind','video_mp4')->delete();
    $a->derivatives()->create(['kind'=>'video_mp4','variant'=>'stream-safe','path'=>$streamKey,'mime'=>'video/mp4','size_bytes'=>filesize($dest)]);
    if(is_file($poster)&&filesize($poster)>0){$fh=fopen($poster,'rb');try{$ok=$disk->put($posterKey,$fh,['ContentType'=>'image/webp']);}finally{fclose($fh);}if($ok)$a->derivatives()->updateOrCreate(['kind'=>'poster','variant'=>'720p'],['path'=>$posterKey,'mime'=>'image/webp','size_bytes'=>filesize($poster)]);}
    $meta=$a->metadata??[];$meta['playback']=['state'=>'ready','format'=>'faststart-mp4','method'=>$compatible?'remux':'transcode','duration'=>$output['format']['duration'],'prepared_at'=>now()->toIso8601String()];$a->update(['metadata'=>$meta,'status'=>'ready']);
   });
   Cache::forget('reference.native.v1');Cache::increment('kicc_cache_version');Cache::forget('kicc:r2:keys');Cache::forget('kicc:r2:keyset');if(($a->metadata['namespace']??'')==='national')app(\App\Services\NationalMediaService::class)->bust();
   app(\App\Services\AutomaticMediaPublication::class)->publishIfReady($a);
  }catch(\Throwable $e){$current=MediaAsset::find($this->assetId);if($current&&$current->path===$this->expectedPath){$m=$current->metadata??[];$m['playback']=['state'=>'failed','error'=>'Video processing failed; retry processing or upload an H.264 MP4.'];$current->update(['metadata'=>$m]);}Log::error('Product video preparation failed',['asset'=>$this->assetId,'error'=>$e->getMessage()]);throw $e;
  }finally{foreach(glob($dir.'/*')?:[] as $f)unlink($f);rmdir($dir);}
 }
}
