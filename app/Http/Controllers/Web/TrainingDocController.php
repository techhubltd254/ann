<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

class TrainingDocController extends Controller
{
    public function index()
    {
        return view('training.index');
    }

    public function admin()
    {
        return view('training.admin-manual');
    }

    public function api()
    {
        return view('training.api-docs');
    }
}
