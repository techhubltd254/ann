<?php
namespace App\Http\Controllers\Web;
use App\Http\Controllers\Controller;use App\Models\CountyInstitution;use Illuminate\Http\Request;
class InstitutionResearchAdminController extends Controller {public function update(Request $r,CountyInstitution $institution){$u=$r->user();abort_unless($u&&($u->status??'active')==='active'&&app(\App\Services\AdminHierarchyScope::class)->canInstitution($u,$institution),403);$data=$r->validate(['expected_video_description'=>'required|string|max:3000']);$institution->updateQuietly($data);return redirect('/admin/institutions/'.$institution->slug.'/products')->with('success','Expected-video filming brief saved. This does not publish an unverified offering.');}}
