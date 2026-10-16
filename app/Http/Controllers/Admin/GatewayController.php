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
        ]);

        $credentials = $request->input('credentials', []);

        $gateway->fill([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'logo' => $data['logo'] ?? null,
            'currencies' => array_values(array_filter(array_map('trim', explode(',', (string) ($data['currencies'] ?? ''))))),
            'countries' => array_values(array_filter(array_map('trim', explode(',', (string) ($data['countries'] ?? ''))))),
            'sort_order' => (int) $data['sort_order'],
            'is_test_mode' => $request->boolean('is_test_mode'),
        ]);

        if (is_array($credentials)) {
            $existing = $gateway->credentials ?? [];

            foreach ($credentials as $key => $value) {
                $key = (string) $key;

                if ($value === null || $value === '') {
                    unset($existing[$key]);

                    continue;
                }

                $existing[$key] = (string) $value;
            }

            $gateway->credentials = $existing;
        }

        $gateway->save();

        return back()->with('status', 'Gateway '.$gateway->name.' updated.');
    }

    public function toggle(Gateway $gateway): RedirectResponse
    {
        $gateway->update(['is_enabled' => ! $gateway->is_enabled]);

        return back()->with('status', $gateway->name.' is now '.($gateway->is_enabled ? 'enabled' : 'paused').'.');
    }
}
