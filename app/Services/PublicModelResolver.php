<?php
namespace App\Services;
use App\Models\MediaAsset;use App\Support\MediaAssetIndex;
class PublicModelResolver {
 public function forOwner(string $type,int $id,?MediaAssetIndex $index=null):array {
  $rows=$index?$index->forOwner($type,$id):MediaAsset::where('owner_type',$type)->where('owner_id',$id)->where('status','ready')->with('derivatives')->get();
  return $rows->filter(fn($a)=>$a->kind==='model')->map(fn($a)=>['id'=>$a->id,'name'=>$a->alt_text?:$a->original_name,'format'=>strtolower(pathinfo($a->path,PATHINFO_EXTENSION)),'url'=>url('/media/original/'.$a->path),'viewerUrl'=>route('public.model',$a->id)])->values()->all();
 }
}
