<?php
namespace App\Http\Controllers\Web;
use App\Http\Controllers\Controller;
class ExhibitorOnboardingController extends Controller
{
    public function index() { return redirect('/exhibitor-admin'); }
}
