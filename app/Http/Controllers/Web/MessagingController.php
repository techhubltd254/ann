<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessagingController extends Controller
{
    public function __construct() { $this->middleware('auth'); }

    public function inbox()
    {
        $conversations = Conversation::with('user', 'vendor', 'messages')
            ->where('user_id', Auth::id())
            ->orWhere('vendor_id', Auth::id())
            ->latest('last_message_at')
            ->paginate(25);
        return view('messaging.inbox', compact('conversations'));
    }

    public function show(Conversation $conversation)
    {
        abort_if($conversation->user_id !== auth()->id() && $conversation->vendor_id !== auth()->id(), 403);

        $messages = $conversation->messages()->with('user')->latest()->paginate(50);
        Message::where('conversation_id', $conversation->id)
            ->where('user_id', '!=', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);
        return view('messaging.show', compact('conversation', 'messages'));
    }

    public function start(Request $request)
    {
        $data = $request->validate([
            'vendor_id' => 'required|exists:users,id',
            'order_id' => 'nullable|exists:orders,id',
            'subject' => 'nullable|string|max:255',
            'body' => 'nullable|string|max:2000',
        ]);

        $conversation = Conversation::firstOrCreate(
            ['user_id' => Auth::id(), 'vendor_id' => $data['vendor_id'], 'order_id' => $data['order_id'] ?? null],
            ['subject' => $data['subject'] ?? 'Inquiry', 'last_message_at' => now()]
        );

        if ($data['body']) {
            $conversation->messages()->create([
                'user_id' => Auth::id(),
                'body' => $data['body'],
            ]);
            $conversation->update(['last_message_at' => now()]);
        }

        return redirect()->route('messaging.show', $conversation->id);
    }

    public function send(Request $request, Conversation $conversation)
    {
        abort_if($conversation->user_id !== auth()->id() && $conversation->vendor_id !== auth()->id(), 403);

        $data = $request->validate(['body' => 'required|string|max:2000']);
        $message = $conversation->messages()->create([
            'user_id' => Auth::id(),
            'body' => $data['body'],
        ]);
        $conversation->update(['last_message_at' => now()]);

        \App\Services\N8nService::fire('message_sent', [
            'conversation_id' => $conversation->id,
            'sender_id' => Auth::id(),
            'preview' => substr($data['body'], 0, 100),
        ]);

        return back();
    }

    public function unreadCount()
    {
        $count = Message::whereHas('conversation', function ($q) {
            $q->where('vendor_id', Auth::id())->orWhere('user_id', Auth::id());
        })->where('user_id', '!=', Auth::id())->where('is_read', false)->count();
        return response()->json(['unread' => $count]);
    }

    public function apiMessages(Conversation $conversation)
    {
        return response()->json($conversation->messages()->with('user')->latest()->take(50)->get());
    }
}