<?php

namespace App\Http\Controllers\Web;

use App\Models\Room3d;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;

class Room3dController extends Controller
{
    public function index()
    {
        $rooms = Room3d::whereIn('status', ['ready', 'processed'])
            ->orderBy('created_at', 'desc')
            ->paginate(12);
        return view('room3d.index', compact('rooms'));
    }

    public function create()
    {
        return view('room3d.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'photos' => 'required|array|min:2|max:20',
            'photos.*' => 'image|mimes:jpeg,jpg,png,webp|max:10240',
        ]);

        $paths = [];
        foreach ($request->file('photos') as $photo) {
            $paths[] = $photo->store('room3d', 'public');
        }

        $room = Room3d::create([
            'user_id' => $request->user()?->id,
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']) . '-' . Str::random(6),
            'description' => $validated['description'] ?? null,
            'image_paths' => $paths,
            'cover_image' => $paths[0] ?? null,
            'status' => 'ready',
            'pipeline' => 'photo_sphere',
            'processed_at' => now(),
        ]);

        return redirect()->route('room3d.viewer', $room)
            ->with('success', 'Room created! Explore it in 3D below.');
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