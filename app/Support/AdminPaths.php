<?php
namespace App\Support;
final class AdminPaths
{
    public static function canonical(string $path): string
    {
        $path='/'.ltrim($path,'/');
        foreach(['/kicc-admin/national'=>'/admin/national','/kicc-admin'=>'/admin/kicc','/county-admin'=>'/admin/counties','/institution-admin'=>'/admin/institutions','/national-admin'=>'/admin/national','/records-admin'=>'/admin/records','/portal'=>'/admin'] as $from=>$to){
            if($path===$from || str_starts_with($path,$from.'/'))return $to.substr($path,strlen($from));
        }
        return $path;
    }
    public static function legacy(string $path): string
    {
        $path=ltrim($path,'/');
        foreach(['admin/kicc'=>'kicc-admin','admin/counties'=>'county-admin','admin/institutions'=>'institution-admin','admin/national'=>'national-admin','admin/records'=>'records-admin'] as $from=>$to)
            if($path===$from||str_starts_with($path,$from.'/'))return $to.substr($path,strlen($from));
        if($path==='admin'||str_starts_with($path,'admin/'))return 'portal'.substr($path,5);
        return $path;
    }
}
