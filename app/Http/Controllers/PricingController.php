<?php

namespace App\Http\Controllers;

use App\Models\Gateway;
use App\Models\Plan;
use App\Support\Currency;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PricingController extends Controller
{
    public function index(Request $request): View
    {
        $currency = $this->currency($request);

        $plans = Plan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $gateways = Gateway::query()
            ->orderBy('sort_order')
            ->get();

        return view('pricing', [
            'plans' => $plans,
            'gateways' => $gateways,
            'currency' => $currency,
            'rates' => ['bdt_rate' => Currency::rate()],
        ]);
    }

    public function features(Request $request): View
    {
        return view('features');
    }

    private function currency(Request $request): string
    {
        $fromRequest = strtoupper((string) $request->attributes->get('display_currency', ''));

        if (in_array($fromRequest, ['USD', 'BDT'], true)) {
            return $fromRequest;
        }

        $requested = strtoupper((string) $request->query('currency', ''));

        if (in_array($requested, ['USD', 'BDT'], true)) {
            return $requested;
        }

        return 'USD';
    }
}
