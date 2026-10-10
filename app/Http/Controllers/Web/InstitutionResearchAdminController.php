<?php
namespace App\Http\Controllers\Web;
use App\Http\Controllers\Controller;use App\Models\CountyInstitution;use Illuminate\Http\Request;
class InstitutionResearchAdminController extends Controller {
 public function update(Request $r,CountyInstitution $institution){
  $u=$r->user();abort_unless($u&&($u->status??'active')==='active'&&app(\App\Services\AdminHierarchyScope::class)->canInstitution($u,$institution),403);
  $data=$r->validate(['expected_video_description'=>'required|string|max:3000','refined_story'=>'nullable|string|max:8000']);
  $draft=$institution->research_dossier??[];if(array_key_exists('refined_story',$data))$draft['refined_story']=$data['refined_story'];$draft['automatic_publication']=false;
  $institution->updateQuietly(['expected_video_description'=>$data['expected_video_description'],'research_dossier'=>$draft]);
  return redirect('/admin/institutions/'.$institution->slug.'/products')->with('success','Private refined story and expected-video filming brief saved. No public offering was published.');
 }
}
