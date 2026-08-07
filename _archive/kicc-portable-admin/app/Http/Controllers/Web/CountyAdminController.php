<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\Sector;
use App\Models\SectorEntity;
use App\Models\CountyProduct;
use App\Models\CountyTourismAttraction;
use App\Models\CountyHotel;
use App\Models\CountyFarm;
use App\Models\CountyHealthFacility;
use App\Models\CountyInstitution;
use App\Models\CountyTransport;
use App\Models\CountyCultureSite;
use App\Models\Exhibition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class CountyAdminController extends Controller
{
    protected function getCounty()
    {
        $slug = session('admin_county_slug');
        if ($slug) {
            $county = County::where('slug', $slug)->first();
            if ($county) return $county;
        }
        $slug = request()->route('slug');
        if ($slug && Auth::user()?->hasRole('kicc_admin')) {
            $county = County::where('slug', $slug)->first();
            if ($county) return $county;
        }
        abort(403, 'No county selected.');
    }

    public function proDashboard($slug)
    {
        $county = County::where('slug', $slug)->firstOrFail();
        $user = Auth::user();
        $sessionSlug = session('admin_county_slug');
        $hasAccess = $user->hasRole('kicc_admin')
            || ($user->hasRole('county_admin') && ($sessionSlug === $slug || $user->county_id == $county->id))
            || $user->hasRole('national_admin');
        if (!$hasAccess) abort(403);

        $cacheKey = "county_dash_{$slug}";
        $data = Cache::remember($cacheKey, 3600, function () use ($county) {
            return [
                'sectors' => $county->sectors()->orderBy('name')->get(),
                'entities' => SectorEntity::where('county_id', $county->id)->orderBy('name')->get(),
                'products' => CountyProduct::where('county_id', $county->id)->get(),
                'attractions' => CountyTourismAttraction::where('county_id', $county->id)->get(),
                'hotels' => CountyHotel::where('county_id', $county->id)->get(),
                'farms' => CountyFarm::where('county_id', $county->id)->get(),
                'health' => CountyHealthFacility::where('county_id', $county->id)->get(),
                'institutions' => CountyInstitution::where('county_id', $county->id)->get(),
                'transport' => CountyTransport::where('county_id', $county->id)->get(),
                'culture' => CountyCultureSite::where('county_id', $county->id)->get(),
                'exhibitions' => Exhibition::where('county_id', $county->id)->orderBy('start_date', 'desc')->get(),
                'allSectors' => Sector::orderBy('name')->get(),
            ];
        });

        return view('county-admin.pro', array_merge(
            ['county' => $county],
            $data
        ));
    }

    public function updateContent(Request $request, $slug)
    {
        $county = County::where('slug', $slug)->firstOrFail();
        $data = $request->validate([
            'tagline' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'capital' => 'nullable|string|max:255',
            'population_2024' => 'nullable|numeric',
            'area_km2' => 'nullable|numeric',
        ]);
        $county->update($data);
        return redirect()->back()->with('success', 'County content updated!');
    }

    public function uploadImage(Request $request, $slug)
    {
        $county = County::where('slug', $slug)->firstOrFail();
        $request->validate(['image' => 'required|image|max:10240']);
        $path = $request->file('image')->store("counties/{$slug}", 'public');
        $county->update(['profile_image' => $path]);
        return redirect()->back()->with('success', 'Image uploaded!');
    }

    public function addEntity(Request $request, $slug)
    {
        $county = County::where('slug', $slug)->firstOrFail();
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sector_id' => 'required|exists:sectors,id',
            'type' => 'nullable|string|max:100',
            'description' => 'nullable|string',
        ]);
        $data['county_id'] = $county->id;
        $data['is_published'] = true;
        SectorEntity::create($data);
        return redirect()->back()->with('success', 'Entity added!');
    }

    public function deleteEntity($slug, $id)
    {
        $county = County::where('slug', $slug)->firstOrFail();
        SectorEntity::where('id', $id)->where('county_id', $county->id)->firstOrFail()->delete();
        return redirect()->back()->with('success', 'Entity removed.');
    }

    public function linkSector(Request $request, $slug)
    {
        $county = County::where('slug', $slug)->firstOrFail();
        $sector = Sector::findOrFail($request->sector_id);
        if (!$county->sectors()->where('sector_id', $sector->id)->exists()) {
            $county->sectors()->attach($sector->id);
        }
        return redirect()->back()->with('success', "Sector '{$sector->name}' linked.");
    }

    public function unlinkSector($slug, $sectorId)
    {
        $county = County::where('slug', $slug)->firstOrFail();
        $county->sectors()->detach($sectorId);
        return redirect()->back()->with('success', 'Sector unlinked.');
    }

    // ─── Tourism ─────────────────────────────────────────────────────
    public function addAttraction(Request $request, $slug)
    {
        $county = County::where('slug', $slug)->firstOrFail();
        $data = $request->validate(['name'=>'required|string|max:255','entry_fee'=>'nullable|numeric','description'=>'nullable|string']);
        $data['county_id'] = $county->id; $data['is_published'] = true;
        $data['category'] = $data['category'] ?? 'General';
        CountyTourismAttraction::create($data);
        return redirect()->back()->with('success', 'Attraction added!');
    }
    public function deleteAttraction($slug, $id)
    {
        $county = County::where('slug', $slug)->firstOrFail();
        CountyTourismAttraction::where('id',$id)->where('county_id',$county->id)->firstOrFail()->delete();
        return redirect()->back()->with('success', 'Attraction removed.');
    }

    // ─── Hotels ──────────────────────────────────────────────────────
    public function addHotel(Request $request, $slug)
    {
        $county = County::where('slug', $slug)->firstOrFail();
        $data = $request->validate(['name'=>'required|string|max:255','star_rating'=>'nullable|integer|min:1|max:5','price_range'=>'nullable|string']);
        $data['county_id'] = $county->id; $data['is_published'] = true;
        $data['category'] = $data['category'] ?? 'Hotel';
        CountyHotel::create($data);
        return redirect()->back()->with('success', 'Hotel added!');
    }
    public function deleteHotel($slug, $id)
    {
        $county = County::where('slug', $slug)->firstOrFail();
        CountyHotel::where('id',$id)->where('county_id',$county->id)->firstOrFail()->delete();
        return redirect()->back()->with('success', 'Hotel removed.');
    }

    // ─── Farms ───────────────────────────────────────────────────────
    public function addFarm(Request $request, $slug)
    {
        $county = County::where('slug', $slug)->firstOrFail();
        $data = $request->validate(['name'=>'required|string|max:255','size_acres'=>'nullable|numeric','main_crops'=>'nullable|string']);
        $data['county_id'] = $county->id; $data['is_published'] = true;
        $data['type'] = $data['type'] ?? 'General';
        CountyFarm::create($data);
        return redirect()->back()->with('success', 'Farm added!');
    }
    public function deleteFarm($slug, $id)
    {
        $county = County::where('slug', $slug)->firstOrFail();
        CountyFarm::where('id',$id)->where('county_id',$county->id)->firstOrFail()->delete();
        return redirect()->back()->with('success', 'Farm removed.');
    }

    // ─── Health Facilities ───────────────────────────────────────────
    public function addHealth(Request $request, $slug)
    {
        $county = County::where('slug', $slug)->firstOrFail();
        $data = $request->validate(['name'=>'required|string|max:255','type'=>'nullable|string','services'=>'nullable|string']);
        $data['county_id'] = $county->id; $data['is_published'] = true;
        $data['type'] = $data['type'] ?? 'Clinic';
        $data['level'] = $data['level'] ?? 'Primary';
        CountyHealthFacility::create($data);
        return redirect()->back()->with('success', 'Health facility added!');
    }
    public function deleteHealth($slug, $id)
    {
        $county = County::where('slug', $slug)->firstOrFail();
        CountyHealthFacility::where('id',$id)->where('county_id',$county->id)->firstOrFail()->delete();
        return redirect()->back()->with('success', 'Health facility removed.');
    }

    // ─── Institutions ─────────────────────────────────────────────────
    public function addInstitution(Request $request, $slug)
    {
        $county = County::where('slug', $slug)->firstOrFail();
        $data = $request->validate(['name'=>'required|string|max:255','type'=>'nullable|string','student_count'=>'nullable|integer']);
        $data['county_id'] = $county->id; $data['is_published'] = true;
        $data['type'] = $data['type'] ?? 'Institution';
        CountyInstitution::create($data);
        return redirect()->back()->with('success', 'Institution added!');
    }
    public function deleteInstitution($slug, $id)
    {
        $county = County::where('slug', $slug)->firstOrFail();
        CountyInstitution::where('id',$id)->where('county_id',$county->id)->firstOrFail()->delete();
        return redirect()->back()->with('success', 'Institution removed.');
    }

    // ─── Transport ────────────────────────────────────────────────────
    public function addTransport(Request $request, $slug)
    {
        $county = County::where('slug', $slug)->firstOrFail();
        $data = $request->validate(['name'=>'required|string|max:255','type'=>'nullable|string','description'=>'nullable|string']);
        $data['county_id'] = $county->id; $data['is_published'] = true;
        $data['type'] = $data['type'] ?? 'Transport Hub';
        CountyTransport::create($data);
        return redirect()->back()->with('success', 'Transport entry added!');
    }
    public function deleteTransport($slug, $id)
    {
        $county = County::where('slug', $slug)->firstOrFail();
        CountyTransport::where('id',$id)->where('county_id',$county->id)->firstOrFail()->delete();
        return redirect()->back()->with('success', 'Transport entry removed.');
    }

    // ─── Culture Sites ────────────────────────────────────────────────
    public function addCulture(Request $request, $slug)
    {
        $county = County::where('slug', $slug)->firstOrFail();
        $data = $request->validate(['name'=>'required|string|max:255','type'=>'nullable|string','community'=>'nullable|string','description'=>'nullable|string']);
        $data['county_id'] = $county->id; $data['is_published'] = true;
        $data['type'] = $data['type'] ?? 'Cultural Site';
        CountyCultureSite::create($data);
        return redirect()->back()->with('success', 'Culture site added!');
    }
    public function deleteCulture($slug, $id)
    {
        $county = County::where('slug', $slug)->firstOrFail();
        CountyCultureSite::where('id',$id)->where('county_id',$county->id)->firstOrFail()->delete();
        return redirect()->back()->with('success', 'Culture site removed.');
    }

    // ─── Products ─────────────────────────────────────────────────────
    public function addProduct(Request $request, $slug)
    {
        $county = County::where('slug', $slug)->firstOrFail();
        $data = $request->validate([
            'name'=>'required|string|max:255',
            'category'=>'nullable|string',
            'price'=>'nullable|numeric',
            'description'=>'nullable|string',
            'unit'=>'nullable|string',
            'status'=>'nullable|string',
        ]);
        $data['county_id'] = $county->id;
        $data['user_id'] = Auth::id();
        $data['is_published'] = true;
        $data['category'] = $data['category'] ?? 'General';
        $data['status'] = $data['status'] ?? 'available';
        CountyProduct::create($data);
        return redirect()->back()->with('success', 'Product added!');
    }
    public function deleteProduct($slug, $id)
    {
        $county = County::where('slug', $slug)->firstOrFail();
        CountyProduct::where('id',$id)->where('county_id',$county->id)->firstOrFail()->delete();
        return redirect()->back()->with('success', 'Product removed.');
    }

    // ─── County Profile ───────────────────────────────────────────────
    public function updateProfile(Request $request, $slug)
    {
        $county = County::where('slug', $slug)->firstOrFail();
        $data = $request->validate([
            'tagline'=>'nullable|string|max:255',
            'description'=>'nullable|string',
            'capital'=>'nullable|string|max:255',
            'population_2024'=>'nullable|integer',
            'area_km2'=>'nullable|numeric',
            'region'=>'nullable|string|max:100',
        ]);
        $county->update($data);
        return redirect()->back()->with('success', 'County profile updated!');
    }

    // ─── Image Delete ─────────────────────────────────────────────────
    public function deleteImage($slug, $id)
    {
        $county = County::where('slug', $slug)->firstOrFail();
        $image = \App\Models\ScreenImage::where('id',$id)->first();
        if ($image) $image->delete();
        return redirect()->back()->with('success', 'Image removed.');
    }

    // ─── Weather / Seasonal ───────────────────────────────────────────
    public function addWeather(Request $request, $slug)
    {
        $county = County::where('slug', $slug)->firstOrFail();
        $data = $request->validate([
            'month'=>'required|integer|min:1|max:12',
            'avg_temp_c'=>'nullable|numeric',
            'rainfall_mm'=>'nullable|numeric',
            'tourism_season'=>'nullable|string',
            'agri_season'=>'nullable|string',
            'weather_tag'=>'nullable|string',
        ]);
        $data['county_id'] = $county->id;
        \Illuminate\Support\Facades\DB::table('seasonal_calendars')->updateOrInsert(
            ['county_id' => $county->id, 'month' => $data['month']],
            $data
        );
        return redirect()->back()->with('success', 'Weather data saved!');
    }
}