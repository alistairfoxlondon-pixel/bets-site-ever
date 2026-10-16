<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ContactMessageController extends Controller
{
    public function index(Request $request): View
    {
        $query = ContactMessage::query();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $messages = $query->orderByDesc('id')->paginate(20)->withQueryString();

        return view('admin.messages.index', [
            'messages' => $messages,
        ]);
    }

    public function show(ContactMessage $message): View
    {
        $message->update(['status' => 'open']);

        return view('admin.messages.show', [
            'message' => $message,
        ]);
    }

    public function reply(Request $request, ContactMessage $message): RedirectResponse
    {
        $data = $request->validate([
            'reply' => ['required', 'string', 'max:4000'],
        ]);

        $message->update([
            'reply' => $data['reply'],
            'status' => 'replied',
            'replied_at' => now(),
        ]);

        if ($message->user_id) {
            Notification::create([
                'user_id' => $message->user_id,
                'type' => 'contact',
                'title' => 'We replied to: '.$message->subject,
                'body' => Str::limit(strip_tags($data['reply']), 140),
                'action_url' => route('support.index'),
            ]);
        }

        return back()->with('status', 'Reply saved.');
    }

    public function destroy(ContactMessage $message): RedirectResponse
    {
        $message->delete();

        return back()->with('status', 'Message deleted.');
    }
}
