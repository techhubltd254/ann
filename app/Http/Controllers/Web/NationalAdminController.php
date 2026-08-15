<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Ministry;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class NationalAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $ministries = Ministry::with('agencies')->orderBy('name')->get();
        $agencies = Agency::with('ministry')->orderBy('name')->get();
        $nationalPages = Page::whereIn('slug', ['about','mission','vision','history','org-structure','pricing'])->orderBy('sort_order')->get();
        $stats = [
            'ministries' => $ministries->count(),
            'agencies' => $agencies->count(),
        ];
        return view('national.admin', compact('ministries', 'agencies', 'nationalPages', 'stats'));
    }

    public function storeMinistry(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:20',
            'color' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:2000',
            'website' => 'nullable|url|max:500',
            'contact_email' => 'nullable|email|max:255',
        ]);
        $data['slug'] = Str::slug($data['name']);
        Ministry::create($data);
        return redirect()->route('national.admin.v2')->with('success', "Ministry created.");
    }

    public function updateMinistry(Request $request, Ministry $ministry)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:2000',
            'is_active' => 'nullable|boolean',
        ]);
        $ministry->update($data);
        return redirect()->route('national.admin.v2')->with('success', "Ministry updated.");
    }

    public function deleteMinistry(Ministry $ministry)
    {
        $ministry->delete();
        return redirect()->route('national.admin.v2')->with('success', "Ministry removed.");
    }

    public function storeAgency(Request $request)
    {
        $data = $request->validate([
            'ministry_id' => 'required|exists:ministries,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:2000',
        ]);
        $data['slug'] = Str::slug($data['name']);
        Agency::create($data);
        return redirect()->route('national.admin.v2')->with('success', "Agency created.");
    }

    public function deleteAgency(Agency $agency)
    {
        $agency->delete();
        return redirect()->route('national.admin.v2')->with('success', "Agency removed.");
    }
}