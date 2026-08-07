<?php
namespace App\Http\Controllers\Web;
use App\Http\Controllers\Controller;
class ExhibitorPortalController extends Controller
{
    public function index() { return view('dashboards.exhibitor'); }
    public function products() { return view('dashboards.exhibitor'); }
}
