<?php
namespace App\Http\Controllers\Web;
use App\Http\Controllers\Controller;
class ProviderPortalController extends Controller
{
    public function index() { return view('dashboards.provider'); }
    public function updatePrice() { return redirect()->back(); }
    public function add() { return redirect()->back(); }
}
