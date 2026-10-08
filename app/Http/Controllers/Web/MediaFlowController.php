<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\{CountyInstitution, MediaAsset, SectorEntity};
use App\Services\{AdminHierarchyScope, ScopedVideoMedia};
use App\Support\MediaMapping;
use Illuminate\Http\Request;

/** Existing county -> institution -> linked sector -> ID-bound media. */
class MediaFlowController extends Controller
{
    private function actor(Request $r): \App\Models\User
    {
        $u=$r->user();
        abort_unless($u && ($u->status??'active')==='active' && app(AdminHierarchyScope::class)->level($u),403,'An active assigned administration role is required.');
        return $u;
    }
    private function owner(Request $r, CountyInstitution $institution): void
    {
        abort_unless(app(AdminHierarchyScope::class)->canInstitution($this->actor($r),$institution),403,'This institution is outside your administration scope.');
    }
    private function sector(CountyInstitution $institution, int $id): void
    {
        abort_unless($id>0 && $institution->sectorEntities()->where('sector_id',$id)->exists(),422,'Choose a sector actually linked to this institution.');
        abort_unless($institution->county->sectors()->where('sectors.id',$id)->exists(),422,'Sector is not linked to the responsible county.');
    }
    private function asset(CountyInstitution $institution, MediaAsset $asset, int $sectorId): void
    {
        $ids=$institution->sectorEntities()->where('sector_id',$sectorId)->pluck('id');
        $direct=$asset->owner_type===CountyInstitution::class && (int)$asset->owner_id===(int)$institution->id;
        $linked=$asset->owner_type===SectorEntity::class && $ids->contains((int)$asset->owner_id);
        abort_unless($direct||$linked,403,'Media does not belong to the selected institution and sector.');
        $bound=(int)($asset->metadata['sector_id']??0);
        abort_unless(!$bound||$bound===$sectorId,403,'Media is assigned to a different sector.');
        abort_unless($asset->disk==='r2' && $asset->kind==='video',422,'Select an R2 video.');
    }
    public function index(Request $r)
    {
        $u=$this->actor($r);$scope=app(AdminHierarchyScope::class);
        $counties=$scope->counties($u)->orderBy('name')->get(['id','name','slug']);
        $institutions=$scope->institutions($u)->with('county')->orderBy('name')->get();
        return response()->view('experience.admin.media-flow',compact('institutions','counties'))->withHeaders(['Cache-Control'=>'private,no-store','CDN-Cache-Control'=>'no-store']);
    }
    public function sectors(Request $r, CountyInstitution $institution)
    {
        $this->owner($r,$institution);
        $linked=$institution->sectorEntities()->with('sector')->get()->pluck('sector')->filter()->unique('id')->values();
        return response()->json(['institution'=>['id'=>$institution->id,'name'=>$institution->name,'slug'=>$institution->slug],'sectors'=>$linked->map(fn($s)=>['id'=>$s->id,'name'=>$s->name,'slug'=>$s->slug]),'source'=>'sector_entities + county_sector'])->header('Cache-Control','private,no-store');
    }
    public function media(Request $r, CountyInstitution $institution)
    {
        $this->owner($r,$institution);$id=$r->integer('sector_id');$this->sector($institution,$id);
        $entityIds=$institution->sectorEntities()->where('sector_id',$id)->pluck('id');
        $assets=MediaAsset::where(fn($q)=>$q->where(fn($q)=>$q->where('owner_type',CountyInstitution::class)->where('owner_id',$institution->id))->orWhere(fn($q)=>$q->where('owner_type',SectorEntity::class)->whereIn('owner_id',$entityIds)))->with('derivatives')->latest('id')->get();
        $items=[];
        foreach($assets as $a){$bound=(int)($a->metadata['sector_id']??0);if($bound && $bound!==$id)continue;
            $v=MediaMapping::classify($a,(string)$institution->slug,(int)$institution->id);
            $items[]=['id'=>$a->id,'slot'=>$a->slot,'kind'=>$a->kind,'status'=>$a->status,'path'=>$a->path,'sector_id'=>$bound?:null,'legacy_shared_scope'=>!$bound && $a->owner_type===CountyInstitution::class,'in_r2'=>MediaMapping::inR2($a->path),'algorithm_state'=>$v['state'],'algorithm_reason'=>$v['reason'],'serves'=>$v['state']===MediaMapping::DISTINCT?'/media/video/'.ltrim($a->path,'/'):null];}
        $hero=MediaMapping::institutionHero($institution);
        return response()->json(['institution'=>['id'=>$institution->id,'slug'=>$institution->slug],'sector_id'=>$id,'display'=>['state'=>$hero['state'],'reason'=>$hero['reason'],'video'=>$hero['video']?'/media/video/'.ltrim($hero['path'],'/'):null],'media'=>$items])->header('Cache-Control','private,no-store');
    }
    public function store(Request $r, CountyInstitution $institution, ScopedVideoMedia $library)
    {
        $this->owner($r,$institution);
        $d=$r->validate(['sector_id'=>'required|integer','slot'=>'required|in:hero_video,institution_video,4d_video','title'=>'required|string|max:255','video'=>'required|file|mimes:mp4,webm,mov|max:'.ScopedVideoMedia::MAX_KB]);
        $this->sector($institution,(int)$d['sector_id']);
        $asset=$library->store($r->file('video'),$institution,(int)$d['sector_id'],$d['slot'],$d['title']);
        return response()->json(['id'=>$asset->id,'owner_id'=>$asset->owner_id,'sector_id'=>(int)$d['sector_id'],'path'=>$asset->path,'status'=>$asset->status],201)->header('Cache-Control','private,no-store');
    }
    public function replace(Request $r, CountyInstitution $institution, MediaAsset $asset, ScopedVideoMedia $library)
    {
        $this->owner($r,$institution);$d=$r->validate(['sector_id'=>'required|integer','video'=>'required|file|mimes:mp4,webm,mov|max:'.ScopedVideoMedia::MAX_KB]);$id=(int)$d['sector_id'];$this->sector($institution,$id);$this->asset($institution,$asset,$id);
        $updated=$library->replace($r->file('video'),$institution,$asset);
        return response()->json(['id'=>$updated->id,'path'=>$updated->path,'owner_id'=>$updated->owner_id,'sector_id'=>$id])->header('Cache-Control','private,no-store');
    }
    public function destroy(Request $r, CountyInstitution $institution, MediaAsset $asset, ScopedVideoMedia $library)
    {
        $this->owner($r,$institution);$id=$r->integer('sector_id');$this->sector($institution,$id);$this->asset($institution,$asset,$id);
        $report=$library->destroy($institution,$asset);
        return response()->json(['deleted_id'=>$asset->id,'objects'=>$report])->header('Cache-Control','private,no-store');
    }
}
