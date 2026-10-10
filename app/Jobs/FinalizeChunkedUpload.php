<?php
namespace App\Jobs;
use Illuminate\Bus\Queueable;use Illuminate\Contracts\Queue\ShouldQueue;use Illuminate\Foundation\Bus\Dispatchable;use Illuminate\Queue\InteractsWithQueue;use Illuminate\Queue\SerializesModels;
class FinalizeChunkedUpload implements ShouldQueue {
 use Dispatchable,InteractsWithQueue,Queueable,SerializesModels;public $timeout=1200;public $tries=2;public $backoff=30;
 public function __construct(public string $uploadId,public int $actorId){$this->onQueue('uploads');}
 public function handle():void {
  $dir=storage_path('app/chunked/'.$this->uploadId);try{$user=\App\Models\User::findOrFail($this->actorId);$r=\Illuminate\Http\Request::create('/admin/uploads/'.$this->uploadId.'/complete','POST');$r->setUserResolver(fn()=>$user);$r->attributes->set('finalize_job',true);app(\App\Http\Controllers\Web\ChunkedUploadController::class)->complete($r,$this->uploadId);}
  catch(\Throwable $e){if(is_dir($dir))file_put_contents($dir.'/finalization-error.json',json_encode(['message'=>'Final verification failed. Retry completion or cancel the pending upload.','http'=>$e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface?$e->getStatusCode():503]));\Illuminate\Support\Facades\Log::error('Chunked upload finalization failed',['upload'=>$this->uploadId,'error'=>$e->getMessage()]);throw $e;}
 }
}
