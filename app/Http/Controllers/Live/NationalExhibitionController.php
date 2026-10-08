<?php
namespace App\Http\Controllers\Live;

use App\Http\Controllers\Controller;
use App\Models\Booth;
use App\Models\BoothAuthorization;
use App\Services\HeartbeatService;

class NationalExhibitionController extends Controller
{
    public function index()
    {
        $pillars = Booth::with('authorization', 'liveStreams')
            ->whereIn('slug', ['affordable-housing', 'trade-commerce', 'healthcare', 'security-community', 'infrastructure-roads'])
            ->get()
            ->map(function ($booth) {
                $health = app(HeartbeatService::class)->getHeartbeatHealth($booth->id);
                $booth->heartbeat_color = match ($health) {
                    'healthy' => 'green',
                    'warning' => 'amber',
                    'dead' => 'red',
                    default => 'gray',
                };
                return $booth;
            });

        $stats = [
            'total' => $pillars->count(),
            'live' => $pillars->filter(fn($b) => $b->stream_status === 'live')->count(),
            'authorized' => $pillars->filter(fn($b) => $b->authorization?->status === 'AUTHORIZED')->count(),
        ];

        return view('experience.pages.live.national.index', compact('pillars', 'stats'));
    }

    public function show($slug)
    {
        $booth = Booth::with('authorization', 'liveStreams', 'meetingBookings', 'favourites')
            ->where('slug', $slug)
            ->firstOrFail();

        $health = app(HeartbeatService::class)->getHeartbeatHealth($booth->id);
        $isFavourite = auth()->check()
            ? \App\Models\FavouriteBooth::where('user_id', auth()->id())
                ->where('booth_id', $booth->id)->exists()
            : false;

        return view('experience.pages.live.national.show', compact('booth', 'health', 'isFavourite'));
    }
}