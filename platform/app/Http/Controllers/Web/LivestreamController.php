<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class LivestreamController extends Controller
{
    public function index()
    {
        $channels = DB::table('livestream_channels')->orderBy('name')->get();
        return view('livestreams.index', compact('channels'));
    }

    public function show(string $slug)
    {
        $channel = DB::table('livestream_channels')->where('slug', $slug)->first();
        abort_unless($channel, 404);
        return view('livestreams.show', compact('channel'));
    }
}
