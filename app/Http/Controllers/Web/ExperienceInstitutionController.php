<?php
namespace App\Http\Controllers\Web;
use App\Http\Controllers\Controller;
use App\Models\CountyInstitution;
class ExperienceInstitutionController extends Controller {
 public function index(){
  $institutions=CountyInstitution::where('is_published',true)->with('county')->orderBy('name')->paginate(24);
  return view('institutions.experience-index',compact('institutions'));
 }
}
