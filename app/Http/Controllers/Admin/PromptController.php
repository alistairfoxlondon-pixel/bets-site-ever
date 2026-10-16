<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromptController extends Controller
{
    public const KEYS = [
        'prompt.audit_system' => 'Audit system prompt',
        'prompt.audit_user' => 'Audit user prompt',
        'prompt.writer_system' => 'Writer system prompt',
        'prompt.writer_user' => 'Writer user prompt',
    ];

    public function index(): View
    {
        $prompts = [];

        foreach (self::KEYS as $key => $label) {
            $prompts[$key] = [
                'label' => $label,
                'value' => Setting::get($key) ?: '',
            ];
        }

        return view('admin.prompts.index', [
            'prompts' => $prompts,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'prompts' => ['nullable', 'array'],
        ]);

        foreach ((array) ($data['prompts'] ?? []) as $key => $value) {
            if (! array_key_exists($key, self::KEYS)) {
                continue;
            }

            Setting::put($key, (string) $value, [
                'group' => 'prompts',
                'label' => self::KEYS[$key],
                'type' => 'textarea',
            ]);
        }

        return back()->with('status', 'Prompts updated.');
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string'],
        ]);

        if (! array_key_exists($data['key'], self::KEYS)) {
            return back()->with('error', 'Unknown prompt.');
        }

        Setting::put($data['key'], null, [
            'group' => 'prompts',
            'label' => self::KEYS[$data['key']],
            'type' => 'textarea',
        ]);

        return back()->with('status', 'Prompt reset to the built in version.');
    }
}
