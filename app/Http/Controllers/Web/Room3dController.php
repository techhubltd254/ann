<?php

namespace App\Http\Controllers\Web;

use App\Models\Room3d;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class Room3dController extends Controller
{
    public function index()
    {
        $rooms = Room3d::whereIn('status', ['ready', 'processed'])
            ->orderBy('created_at', 'desc')
            ->paginate(12);
        return view('room3d.index', compact('rooms'));
    }

    public function show(Room3d $room3d)
    {
        if (!$room3d->isReady()) {
            abort(404);
        }
        return view('room3d.show', compact('room3d'));
    }

    public function viewer(Room3d $room3d)
    {
        if (!$room3d->isReady()) {
            abort(404);
        }
        return view('room3d.viewer', compact('room3d'));
    }

    public function api(Room3d $room3d)
    {
        if (!$room3d->isReady()) {
            return response()->json(['error' => 'not ready'], 404);
        }
        return response()->json([
            'id' => $room3d->id,
            'title' => $room3d->title,
            'description' => $room3d->description,
            'images' => array_map(fn($p) => url('storage/' . $p), $room3d->images()),
            'cover' => $room3d->coverUrl(),
            'pipeline' => $room3d->pipeline,
            'job_result' => $room3d->job_result,
            'created_at' => $room3d->created_at,
        ]);
    }
}