<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Ministry;

class NationalGovernmentController extends Controller
{
    public function index()
    {
        $ministries = Ministry::with('agencies')->where('is_active', true)->orderBy('name')->get();
        $agencies = Agency::with('ministry')->where('is_active', true)->orderBy('name')->get();
        $stats = [
            'ministries' => $ministries->count(),
            'agencies' => $agencies->count(),
        ];
        return view('national-government.index', compact('ministries', 'agencies', 'stats'));
    }
}