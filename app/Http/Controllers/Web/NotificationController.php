<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct() { $this->middleware('auth'); }

    public function index()
    {
        $notifications = UserNotification::where('user_id', Auth::id())->latest()->paginate(25);
        return view('experience.pages.notifications.index', compact('notifications'));
    }

    public function markRead(UserNotification $notification)
    {
        if ($notification->user_id !== Auth::id()) abort(403);
        $notification->update(['is_read' => true, 'read_at' => now()]);
        return back();
    }

    public function markAllRead()
    {
        UserNotification::where('user_id', Auth::id())->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
        return back()->with('success', 'All marked as read.');
    }

    public function unreadCount()
    {
        return response()->json([
            'count' => UserNotification::where('user_id', Auth::id())->where('is_read', false)->count()
        ]);
    }
}