<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class SettingController extends Controller
{
    /**
     * The settings that are safe to show in a plain text field.
     */
    public const EDITABLE = [
        'ai.api_key' => ['label' => 'OpenRouter API key', 'type' => 'secret', 'group' => 'ai'],
        'ai.base_url' => ['label' => 'OpenRouter base URL', 'type' => 'text', 'group' => 'ai'],
        'ai.site_url' => ['label' => 'Site URL sent to OpenRouter', 'type' => 'text', 'group' => 'ai'],
        'ai.site_name' => ['label' => 'Application name sent to OpenRouter', 'type' => 'text', 'group' => 'ai'],
        'ai.price_per_million' => ['label' => 'Assumed price per million tokens', 'type' => 'number', 'group' => 'ai'],
        'ai.model_normal' => ['label' => 'Model for the Normal audit', 'type' => 'text', 'group' => 'ai'],
        'ai.model_standard' => ['label' => 'Model for the Standard audit', 'type' => 'text', 'group' => 'ai'],
        'ai.model_premium' => ['label' => 'Model for the Premium audit', 'type' => 'text', 'group' => 'ai'],
        'ai.model_writer_standard' => ['label' => 'Model for the Standard writer', 'type' => 'text', 'group' => 'ai'],
        'ai.model_writer_premium' => ['label' => 'Model for the Premium writer', 'type' => 'text', 'group' => 'ai'],
        'indexing.google_service_account' => ['label' => 'Google Indexing API service account JSON', 'type' => 'secret_textarea', 'group' => 'indexing'],
        'indexing.indexnow_key' => ['label' => 'IndexNow API key', 'type' => 'secret', 'group' => 'indexing'],
        'indexing.indexnow_key_location' => ['label' => 'IndexNow key file URL', 'type' => 'text', 'group' => 'indexing'],
        'currency.primary' => ['label' => 'Primary currency', 'type' => 'text', 'group' => 'currency'],
        'currency.bdt_rate' => ['label' => 'Taka exchange rate', 'type' => 'number', 'group' => 'currency'],
        'billing.free_signup_credits' => ['label' => 'Free credits for new accounts', 'type' => 'number', 'group' => 'billing'],
        'billing.credits_price' => ['label' => 'Price for one credit', 'type' => 'number', 'group' => 'billing'],
        'auth.google_client_id' => ['label' => 'Google OAuth client id', 'type' => 'text', 'group' => 'auth'],
        'auth.google_client_secret' => ['label' => 'Google OAuth client secret', 'type' => 'secret', 'group' => 'auth'],
        'support_email' => ['label' => 'Support email address', 'type' => 'text', 'group' => 'general'],
        'contact_address' => ['label' => 'Company address', 'type' => 'text', 'group' => 'general'],
        'crawl_max_pages' => ['label' => 'Hard cap on crawled pages', 'type' => 'number', 'group' => 'general'],
    ];

    public function index(): View
    {
        $settings = [];

        foreach (self::EDITABLE as $key => $meta) {
            $settings[$key] = [
                'meta' => $meta,
                'value' => Setting::get($key),
            ];
        }

        return view('admin.settings.index', [
            'settings' => $settings,
            'groups' => [
                'ai' => 'Artificial intelligence',
                'indexing' => 'Indexing',
                'auth' => 'Sign in',
                'currency' => 'Currency',
                'billing' => 'Billing',
                'general' => 'General',
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'settings' => ['nullable', 'array'],
        ]);

        foreach ((array) ($data['settings'] ?? []) as $key => $value) {
            if (! array_key_exists($key, self::EDITABLE)) {
                continue;
            }

            if (in_array(self::EDITABLE[$key]['type'], ['secret', 'secret_textarea'], true) && $value === '') {
                continue;
            }

            Setting::put($key, $value, [
                'group' => self::EDITABLE[$key]['group'],
                'label' => self::EDITABLE[$key]['label'],
                'type' => self::EDITABLE[$key]['type'],
            ]);
        }

        return back()->with('status', 'Settings saved.');
    }

    public function updateSecret(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:120', 'regex:/^[A-Za-z0-9._-]+$/'],
            'value' => ['nullable', 'string', 'max:20000'],
        ]);

        if (! Setting::isSensitiveKey($data['key'])) {
            return back()->with('error', 'Raw values can only be stored under a key that identifies a secret, token, password or private key.');
        }

        if (filled($data['value'] ?? null)) {
            Setting::put($data['key'], $data['value']);
        }

        return back()->with('status', filled($data['value'] ?? null) ? 'Secret stored securely.' : 'No change was made.');
    }
}
