<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

class AdminPortalController extends Controller
{
    public function index()
    {
        return view('admin.portal');
    }
}
