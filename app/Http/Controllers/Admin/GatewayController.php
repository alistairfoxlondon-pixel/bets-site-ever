<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gateway;
use App\Services\Billing\GatewayManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GatewayController extends Controller
{
    public function index(Request $request, GatewayManager $manager): View
    {
        $gateways = Gateway::query()->orderBy('sort_order')->get();

        $rows = $gateways->map(function (Gateway $gateway) use ($manager) {
            $driver = $manager->driver($gateway);

            return [
                'gateway' => $gateway,
                'configured' => $driver?->isConfigured() ?? false,
                'supported' => $driver !== null,
            ];
        });

        return view('admin.gateways.index', [
            'rows' => $rows,
        ]);
    }

    public function update(Request $request, Gateway $gateway): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:240'],
            'logo' => ['nullable', 'string', 'max:120'],
            'currencies' => ['nullable', 'string', 'max:120'],
            'countries' => ['nullable', 'string', 'max:240'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'is_test_mode' => ['nullable', 'boolean'],
            'credentials' => ['sometimes', 'array'],
            'credentials.*' => ['nullable', 'string', 'max:2048'],
            'clear_credentials' => ['sometimes', 'array'],
            'clear_credentials.*' => ['nullable', 'boolean'],
        ]);

        $credentials = (array) ($data['credentials'] ?? []);
        $clearCredentials = (array) ($data['clear_credentials'] ?? []);

        $gateway->fill([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'logo' => $data['logo'] ?? null,
            'currencies' => array_values(array_filter(array_map('trim', explode(',', (string) ($data['currencies'] ?? ''))))),
            'countries' => array_values(array_filter(array_map('trim', explode(',', (string) ($data['countries'] ?? ''))))),
            'sort_order' => (int) $data['sort_order'],
            'is_test_mode' => $request->boolean('is_test_mode'),
        ]);

        $existing = $gateway->credentials ?? [];
        $allowedKeys = array_keys($gateway->credentialFields());

        foreach ($credentials as $key => $value) {
            $key = (string) $key;

            if (! in_array($key, $allowedKeys, true) || $value === null || trim($value) === '') {
                continue;
            }

            $existing[$key] = trim($value);
        }

        foreach ($clearCredentials as $key => $clear) {
            $key = (string) $key;

            if (in_array($key, $allowedKeys, true) && $request->boolean('clear_credentials.'.$key)) {
                unset($existing[$key]);
            }
        }

        $gateway->credentials = $existing;

        $gateway->save();

        return back()->with('status', 'Gateway '.$gateway->name.' updated.');
    }

    public function toggle(Gateway $gateway): RedirectResponse
    {
        if (in_array($gateway->slug, ['aamarpay', 'paymob'], true) && ! $gateway->is_enabled) {
            return back()->with('error', $gateway->name.' is unavailable until its server-side payment verification is completed.');
        }

        $gateway->update(['is_enabled' => ! $gateway->is_enabled]);

        return back()->with('status', $gateway->name.' is now '.($gateway->is_enabled ? 'enabled' : 'paused').'.');
    }
}
