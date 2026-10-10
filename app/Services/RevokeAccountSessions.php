<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class RevokeAccountSessions {
 public function revoke(int $userId):void {
  if(config('session.driver')==='database'){DB::table(config('session.table','sessions'))->where('user_id',$userId)->delete();return;}
  if(config('session.driver')!=='file')return;
  $dir=config('session.files');if(!is_dir($dir))return;
  foreach(new \FilesystemIterator($dir,\FilesystemIterator::SKIP_DOTS) as $file){if(!$file->isFile()||!preg_match('/^[A-Za-z0-9]{40}$/',$file->getFilename()))continue;$payload=@file_get_contents($file->getPathname());if($payload===false)continue;$values=@unserialize($payload,['allowed_classes'=>false]);if(!is_array($values))continue;foreach($values as $key=>$value){if(str_starts_with((string)$key,'login_web_')&&(int)$value===$userId){app('session')->driver()->getHandler()->destroy($file->getFilename());break;}}}
 }
}
