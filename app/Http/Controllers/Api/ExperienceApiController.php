<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\Marketplace\Product;
use App\Models\Venue;

class ExperienceApiController extends Controller
{
    public function data()
    {
        $counties = County::orderBy('name')->get(['id','name','slug','former_province','population_2024','primary_sectors']);
        $products = Product::where('status','active')->with(['county','variants'])->limit(48)->get();
        $venues = Venue::where('is_active',1)->get(['name','slug','capacity','venue_type']);

        $countiesOut = [];
        foreach ($counties as $c) {
            $sectors = $c->primary_sectors;
            if (is_string($sectors)) $sectors = json_decode($sectors, true);
            if (!is_array($sectors) || empty($sectors)) $sectors = ['Trade','Agriculture'];
            $countiesOut[] = [
                $c->name, $c->former_province ?? '',
                intval($c->population_2024 ?? 0),
                array_slice($sectors, 0, 3),
                str_pad((string)$c->id, 3, '0', STR_PAD_LEFT),
            ];
        }

        $productsOut = [];
        foreach ($products as $p) {
            $price = $p->variants->first()?->price ?? 0;
            $productsOut[] = [
                'n' => $p->name, 'p' => $price,
                'cat' => $p->category?->name ?? 'General',
                'county' => $p->county?->name ?? '', 'slug' => $p->slug,
            ];
        }

        $venuesOut = [];
        foreach ($venues as $v) {
            $venuesOut[] = [
                'name' => $v->name, 'type' => $v->venue_type ?? 'Hall',
                'cap' => intval($v->capacity ?? 100), 'status' => 'Available', 'desc' => '',
            ];
        }

        return response()->json([
            'counties' => $countiesOut,
            'products' => $productsOut,
            'venues' => $venuesOut,
        ]);
    }
}