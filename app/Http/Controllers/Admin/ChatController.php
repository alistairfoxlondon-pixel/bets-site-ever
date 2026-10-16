<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function index(Request $request): View
    {
        $conversations = ChatConversation::query()
            ->with('user')
            ->withCount(['messages'])
            ->orderByDesc('last_message_at')
            ->paginate(20);

        return view('admin.chat.index', [
            'conversations' => $conversations,
        ]);
    }

    public function show(ChatConversation $conversation): View
    {
        $conversation->load('user');

        $conversation->messages()
            ->where('sender_type', 'user')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('admin.chat.show', [
            'conversation' => $conversation,
            'messages' => $conversation->messages()->latest()->get()->reverse(),
        ]);
    }

    public function reply(Request $request, ChatConversation $conversation): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
        ]);

        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $request->user()->id,
            'sender_type' => 'admin',
            'body' => $data['body'],
        ]);

        $conversation->update(['last_message_at' => now()]);

        Notification::create([
            'user_id' => $conversation->user_id,
            'type' => 'chat',
            'title' => 'Support replied to your message',
            'body' => Str::limit($data['body'], 140),
            'action_url' => route('support.index', ['conversation' => $conversation->id]),
        ]);

        return back()->with('status', 'Reply sent.');
    }
}
