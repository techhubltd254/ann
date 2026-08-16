<?php namespace App\Http\Controllers\Web;
use App\Http\Controllers\Controller;
use App\Models\County;

class AdminLoginController extends Controller {
    public function showLoginForm() {
        $counties = County::orderBy('name')->get(['id', 'name']);
        return view('auth.login', compact('counties'));
    }
}