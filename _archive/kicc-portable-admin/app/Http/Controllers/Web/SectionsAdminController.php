<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\Exhibition;
use App\Models\Venue;
use App\Models\Booth;
use App\Models\Screen;
use App\Models\SubscriptionPlan;
use App\Models\CountyTourismAttraction;
use App\Models\CountyHotel;
use App\Models\CountyFarm;
use App\Models\CountyHealthFacility;
use App\Models\CountyInstitution;
use App\Models\CountyTransport;
use App\Models\CountyCultureSite;
use App\Models\CountyProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SectionsAdminController extends Controller
{
    protected function authorize($role = 'kicc_admin')
    {
        if (!Auth::user()?->hasRole($role)) abort(403);
    }

    public function index()
    {
        $this->authorize();
        return view('admin.sections', [
            'exhibitions' => Exhibition::orderBy('start_date', 'desc')->get(),
            'venues' => Venue::orderBy('name')->get(),
            'booths' => Booth::orderBy('name')->get(),
            'screens' => Screen::orderBy('name')->get(),
            'subscriptionPlans' => SubscriptionPlan::all(),
            'countyPlans' => DB::table('county_subscription_plans')->get(),
            'subscribers' => DB::table('county_subscribers')->orderBy('created_at', 'desc')->take(50)->get(),
            'counties' => County::orderBy('name')->get(),
            'users' => \App\Models\User::with('roles')->orderBy('name')->get(),
            'roles' => DB::table('roles')->orderBy('name')->get(),
        ]);
    }

    // ─── Exhibitions ───
    public function storeExhibition(Request $r)
    {
        $this->authorize();
        Exhibition::create($r->validate([
            'name'=>'required|string|max:255','slug'=>'required|string|unique:exhibitions',
            'start_date'=>'required|date','end_date'=>'required|date|after:start_date',
            'description'=>'nullable|string','venue_id'=>'nullable|exists:venues,id',
            'organizer'=>'nullable|string','status'=>'nullable|string',
        ]));
        return redirect()->route('admin.sections')->with('success', 'Exhibition created.');
    }

    public function deleteExhibition($id)
    {
        $this->authorize();
        Exhibition::findOrFail($id)->delete();
        return redirect()->route('admin.sections')->with('success', 'Exhibition deleted.');
    }

    // ─── Venues ───
    public function storeVenue(Request $r)
    {
        $this->authorize();
        Venue::create($r->validate([
            'name'=>'required|string|max:255','slug'=>'required|string|unique:venues',
            'capacity'=>'nullable|integer','type'=>'nullable|string',
            'location'=>'nullable|string','description'=>'nullable|string',
            'amenities'=>'nullable|string','price_per_hour'=>'nullable|numeric',
        ]));
        return redirect()->route('admin.sections')->with('success', 'Venue created.');
    }

    public function deleteVenue($id)
    {
        $this->authorize();
        Venue::findOrFail($id)->delete();
        return redirect()->route('admin.sections')->with('success', 'Venue deleted.');
    }

    // ─── Booths ───
    public function storeBooth(Request $r)
    {
        $this->authorize();
        Booth::create($r->validate([
            'name'=>'required|string|max:255','venue_id'=>'nullable|exists:venues,id',
            'size'=>'nullable|string','price'=>'nullable|numeric',
            'max_quantity'=>'nullable|integer','description'=>'nullable|string',
        ]));
        return redirect()->route('admin.sections')->with('success', 'Booth created.');
    }

    public function deleteBooth($id)
    {
        $this->authorize();
        Booth::findOrFail($id)->delete();
        return redirect()->route('admin.sections')->with('success', 'Booth deleted.');
    }

    // ─── Screens ───
    public function storeScreen(Request $r)
    {
        $this->authorize();
        Screen::create($r->validate([
            'name'=>'required|string|max:255','county_id'=>'nullable|exists:counties,id',
            'location'=>'nullable|string','type'=>'nullable|string',
            'resolution'=>'nullable|string','orientation'=>'nullable|string',
            'status'=>'nullable|string',
        ]));
        return redirect()->route('admin.sections')->with('success', 'Screen created.');
    }

    public function deleteScreen($id)
    {
        $this->authorize();
        Screen::findOrFail($id)->delete();
        return redirect()->route('admin.sections')->with('success', 'Screen deleted.');
    }

    // ─── Subscription Plans ───
    public function storeSubscriptionPlan(Request $r)
    {
        $this->authorize();
        SubscriptionPlan::create($r->validate([
            'name'=>'required|string|max:255','slug'=>'required|string|unique:subscription_plans',
            'description'=>'nullable|string','price'=>'required|numeric',
            'billing_interval'=>'required|string|in:monthly,yearly,quarterly',
            'features'=>'nullable|string','is_active'=>'nullable|boolean',
        ]));
        return redirect()->route('admin.sections')->with('success', 'Plan created.');
    }

    public function deleteSubscriptionPlan($id)
    {
        $this->authorize();
        SubscriptionPlan::findOrFail($id)->delete();
        return redirect()->route('admin.sections')->with('success', 'Plan deleted.');
    }

    // ─── Users ───
    public function storeUser(Request $r)
    {
        $this->authorize();
        $data = $r->validate([
            'name'=>'required|string|max:255',
            'email'=>'required|email|unique:users,email',
            'password'=>'required|string|min:8',
            'role'=>'required|string|exists:roles,name',
        ]);
        $user = \App\Models\User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);
        $user->assignRole($data['role']);
        return redirect()->route('admin.sections')->with('success', 'User created.');
    }

    public function deleteUser($id)
    {
        $this->authorize();
        if ($id == Auth::id()) return back()->with('error', 'Cannot delete yourself.');
        \App\Models\User::findOrFail($id)->delete();
        return redirect()->route('admin.sections')->with('success', 'User deleted.');
    }

    // ─── Sync ───
    public function syncPull()
    {
        $this->authorize();
        $exitCode = \Illuminate\Support\Facades\Artisan::call('sync:from-tidb');
        return redirect()->route('admin.sections')->with('sync_output', \Illuminate\Support\Facades\Artisan::output());
    }

    public function syncPush()
    {
        $this->authorize();
        $exitCode = \Illuminate\Support\Facades\Artisan::call('sync:to-tidb');
        return redirect()->route('admin.sections')->with('sync_output', \Illuminate\Support\Facades\Artisan::output());
    }

    // ─── Import ───
    public function importData(Request $r)
    {
        $this->authorize();
        $r->validate(['import_file' => 'required|file|mimes:json|max:10240']);

        $json = json_decode(file_get_contents($r->file('import_file')->path()), true);
        if (!$json || !isset($json['county'])) {
            return back()->with('error', 'Invalid import file format.');
        }

        $slug = $json['county']['slug'] ?? null;
        if (!$slug) return back()->with('error', 'County slug not found in file.');

        $county = County::where('slug', $slug)->first();
        if (!$county) return back()->with('error', "County '{$slug}' not found.");

        $imported = 0;
        $models = [
            'attractions' => CountyTourismAttraction::class,
            'hotels' => CountyHotel::class,
            'farms' => CountyFarm::class,
            'health' => CountyHealthFacility::class,
            'institutions' => CountyInstitution::class,
            'transport' => CountyTransport::class,
            'culture' => CountyCultureSite::class,
            'products' => CountyProduct::class,
        ];

        foreach ($models as $key => $model) {
            if (!isset($json[$key])) continue;
            foreach ($json[$key] as $item) {
                $item['county_id'] = $county->id;
                $item['is_published'] = $item['is_published'] ?? true;
                // Remove ID so it auto-increments
                unset($item['id']);
                try {
                    $model::create($item);
                    $imported++;
                } catch (\Exception $e) {
                    // Skip invalid rows
                }
            }
        }

        return redirect()->route('admin.sections')->with('success', "Imported {$imported} records for {$county->name}.");
    }
}
