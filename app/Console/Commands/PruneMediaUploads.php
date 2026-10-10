<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
class PruneMediaUploads extends Command {
 protected $signature='uploads:prune';protected $description='Remove expired resumable upload chunks, without deleting published media';
 public function handle():int {$removed=0;foreach(glob(storage_path('app/chunked/*/meta.json'))?:[] as $metaFile){$dir=dirname($metaFile);if(!preg_match('/^[a-f0-9-]{36}$/',basename($dir)))continue;$meta=json_decode(file_get_contents($metaFile),true);if(!$meta||($meta['expires']??PHP_INT_MAX)>=time())continue;$lock=fopen($dir.'/lock','c');if(!flock($lock,LOCK_EX|LOCK_NB)){fclose($lock);continue;}try{foreach(glob($dir.'/*')?:[] as $p)if(is_file($p)&&basename($p)!=='lock')unlink($p);}finally{flock($lock,LOCK_UN);fclose($lock);}unlink($dir.'/lock');rmdir($dir);$removed++;}$this->info('Expired upload sessions removed: '.$removed);return self::SUCCESS;}
}
