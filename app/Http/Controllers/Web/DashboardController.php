<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Exhibition;
use App\Models\Booking;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = auth()->user();

        $stats = [
            'total_bookings' => Booking::where('user_id', $user->id)->count(),
            'upcoming_bookings' => Booking::where('user_id', $user->id)
                ->whereHas('exhibition', fn($q) => $q->where('start_date', '>=', now()))
                ->count(),
            'exhibitions' => Exhibition::where('organizer_info->email', $user->email)->count(),
        ];

        return view('dashboard.index', compact('stats'));
    }

    public function exhibitions()
    {
        $user = auth()->user();
        $exhibitions = Exhibition::where('organizer_info->email', $user->email)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('dashboard.exhibitions', compact('exhibitions'));
    }

    public function bookings()
    {
        $bookings = Booking::with(['exhibition', 'booths'])
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('dashboard.bookings', compact('bookings'));
    }

    public function profile()
    {
        return view('dashboard.profile');
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        $user->update($request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]));

        return redirect()->route('dashboard.profile')->with('success', 'Profile updated.');
    }
}
