<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Ministry;
use App\Models\MediaAsset;
use App\Models\LiveStream;

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

        // Hero video: national hero_video MediaAsset or first live booth stream
        $heroAsset = MediaAsset::resolveSlot(\App\Models\County::class, 0, 'national_hero_video');
        $heroVid = $heroAsset?->mp4Url() ?? null;

        $liveBooths = LiveStream::where('isLive', true)->with('booth')->get()->map(function ($s) {
            return $s->booth?->name ? media_url() . '/streams/' . $s->id . '.m3u8' : null;
        })->filter()->values();

        $heroPoster = media('kicc/national-hero.jpeg');

        return view('national-government.index', compact('ministries', 'agencies', 'stats', 'heroVid', 'heroPoster', 'liveBooths'));
    }
}