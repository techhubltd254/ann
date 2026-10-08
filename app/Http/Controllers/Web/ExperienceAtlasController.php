<?php
namespace App\Http\Controllers\Web;
use App\Http\Controllers\Controller;
class ExperienceAtlasController extends Controller {
 public function index() {
  $pages=json_decode(file_get_contents(base_path('verification/experience-prototype-atlas.json')),true);
  return view('experience.atlas',compact('pages'));
 }
}
