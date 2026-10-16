<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ContactController extends Controller
{
    public const CATEGORIES = [
        'general' => 'General question',
        'billing' => 'Billing or refund',
        'technical' => 'Technical problem',
        'sales' => 'Partnership or sales',
        'abuse' => 'Report abuse',
        'other' => 'Something else',
    ];

    public function create(Request $request): View
    {
        return view('contact', [
            'categories' => self::CATEGORIES,
            'preset' => $request->string('reason')->toString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:191'],
            'category' => ['required', 'string', 'in:'.implode(',', array_keys(self::CATEGORIES))],
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'min:10', 'max:4000'],
        ]);

        ContactMessage::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'category' => $data['category'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
            'status' => 'new',
        ]);

        Log::info('Contact message received', ['category' => $data['category']]);

        return redirect()->route('contact.create')->with('status', 'Message received. We reply to every message within one business day.');
    }
}
