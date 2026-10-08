<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\Request;
class LiveChatController extends Controller {
    public function widget(Request $r, $vendorId) {
        $conversation = Conversation::firstOrCreate(
            ['user_id' => auth()->id(), 'vendor_id' => $vendorId],
            ['subject' => 'Live Chat', 'status' => 'active']
        );
        $conversation->touch('last_message_at');
        $messages = $conversation->messages()->with('user')->latest()->take(50)->get()->reverse();
        return view('experience.pages.ecommerce.live-chat', compact('conversation', 'messages', 'vendorId'));
    }
    public function send(Request $r, $vendorId) {
        $conversation = Conversation::firstOrCreate(
            ['user_id' => auth()->id(), 'vendor_id' => $vendorId],
            ['subject' => 'Live Chat', 'status' => 'active']
        );
        $conversation->messages()->create([
            'user_id' => auth()->id(), 'body' => $r->input('message'),
        ]);
        $conversation->touch('last_message_at');
        return response()->json(['ok'=>true]);
    }
    public function poll(Request $r, $vendorId) {
        $conversation = Conversation::where(['user_id'=>auth()->id(), 'vendor_id'=>$vendorId])->first();
        if (!$conversation) return response()->json([]);
        $since = $r->input('since');
        $messages = $conversation->messages()->with('user')
            ->when($since, fn($q) => $q->where('created_at', '>', $since))
            ->oldest()->get();
        return response()->json($messages);
    }
}