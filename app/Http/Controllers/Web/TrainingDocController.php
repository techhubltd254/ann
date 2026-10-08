<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

class TrainingDocController extends Controller
{
    public function index()
    {
        return view('experience.pages.training.index');
    }

    public function admin()
    {
        return view('experience.pages.training.admin-manual');
    }

    public function api()
    {
        return view('experience.pages.training.api-docs');
    }
}
