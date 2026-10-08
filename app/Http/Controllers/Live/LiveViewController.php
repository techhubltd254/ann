<?php
namespace App\Http\Controllers\Live;

use App\Http\Controllers\Controller;
use App\Models\Booth;
use App\Models\MeetingBooking;
use App\Models\FavouriteBooth;
use Illuminate\Http\Request;

class LiveViewController extends Controller
{
    public function show(Booth $booth)
    {
        $booth->load('authorization', 'liveStreams', 'meetingBookings');
        $isFavourite = auth()->check() ? FavouriteBooth::where('user_id', auth()->id())
            ->where('booth_id', $booth->id)->exists() : false;

        return view('experience.pages.live.viewer.show', compact('booth', 'isFavourite'));
    }

    public function chat(Booth $booth)
    {
        // TODO: load chat messages
        return view('experience.pages.live.viewer.chat', compact('booth'));
    }
}