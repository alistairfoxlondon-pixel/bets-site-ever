<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function index(Request $request): View
    {
        $conversation = $this->currentConversation($request);

        return view('support.index', [
            'conversation' => $conversation,
            'messages' => $conversation?->messages()->latest()->get()->reverse() ?? collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['nullable', 'string', 'max:180'],
            'body' => ['required', 'string', 'min:2', 'max:4000'],
        ]);

        $user = $request->user();

        $conversation = $this->currentConversation($request);

        if (! $conversation) {
            $conversation = ChatConversation::create([
                'user_id' => $user->id,
                'subject' => $data['subject'] ?? 'Support request',
                'status' => 'open',
                'last_message_at' => now(),
            ]);
        }

        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'sender_type' => 'user',
            'body' => $data['body'],
        ]);

        $conversation->update(['last_message_at' => now()]);

        foreach (\App\Models\User::where('role', 'admin')->get() as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => 'chat',
                'title' => 'New support message from '.$user->name,
                'body' => Str::limit($data['body'], 140),
                'action_url' => route('admin.chat.show', $conversation),
            ]);
        }

        return back()->with('status', 'Message sent. We reply inside the app.');
    }

    public function messages(Request $request): View
    {
        $conversation = $this->currentConversation($request);

        $conversations = ChatConversation::query()
            ->where('user_id', $request->user()->id)
            ->latest('last_message_at')
            ->latest('id')
            ->get();

        return view('support.messages', [
            'conversation' => $conversation,
            'conversations' => $conversations,
            'messages' => $conversation?->messages()->latest()->get()->reverse() ?? collect(),
        ]);
    }

    private function currentConversation(Request $request): ?ChatConversation
    {
        $conversationId = $request->integer('conversation');

        if ($conversationId > 0) {
            $conversation = ChatConversation::query()
                ->where('user_id', $request->user()->id)
                ->find($conversationId);

            if ($conversation) {
                $conversation->messages()
                    ->where('sender_type', 'admin')
                    ->whereNull('read_at')
                    ->update(['read_at' => now()]);

                return $conversation;
            }
        }

        $conversation = ChatConversation::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->latest()
            ->first();

        if ($conversation) {
            $conversation->messages()
                ->where('sender_type', 'admin')
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        return $conversation;
    }
}
