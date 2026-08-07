<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Screen;
use Illuminate\Http\Request;

class ScreenController extends Controller
{
    public function index()
    {
        $screens = Screen::where('active', true)->orderBy('id')->get()->map(function ($screen) {
            $videoPath = storage_path("app/public/screens/auto_{$screen->id}.mp4");
            $screen->video_exists = file_exists($videoPath);
            $screen->video_size_mb = $screen->video_exists
                ? round(filesize($videoPath) / 1024 / 1024, 1)
                : null;
            return $screen;
        });

        return view('screens.index', compact('screens'));
    }

    public function show(string $id)
    {
        $screen = Screen::findOrFail($id);
        $videoPath = storage_path("app/public/screens/auto_{$screen->id}.mp4");
        $screen->video_exists = file_exists($videoPath);
        $screen->video_size_mb = $screen->video_exists
            ? round(filesize($videoPath) / 1024 / 1024, 1)
            : null;

        return view('screens.show', compact('screen'));
    }

    public function directory()
    {
        $screens = Screen::where('active', true)->orderBy('id')->get()->map(function ($screen) {
            $videoPath = storage_path("app/public/screens/auto_{$screen->id}.mp4");
            $screen->video_exists = file_exists($videoPath);
            $screen->video_size_mb = $screen->video_exists
                ? round(filesize($videoPath) / 1024 / 1024, 1)
                : null;

            // Get image count from registry
            $screen->image_count = \App\Models\ScreenImage::where(function ($q) use ($screen) {
                if ($screen->county_id) {
                    $q->where('county_id', $screen->county_id);
                }
                if ($screen->sector_id) {
                    $q->where('sector_ids', 'like', "%{$screen->sector_id}%");
                }
                if (!$screen->county_id && !$screen->sector_id) {
                    $q->whereNotNull('id');
                }
            })->count();

            return $screen;
        });

        return view('screens.directory', compact('screens'));
    }
}
